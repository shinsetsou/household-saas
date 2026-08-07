<?php

declare(strict_types=1);

namespace App\Services;

use App\AI\Jobs\SendSalesFollowupEmail;
use App\Models\Lead;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Idempotent lead capture + email follow-up chain.
 *
 * Cadence: day0 (thank-you) -> day3 (nudge) -> day7 (demo offer).
 * Stage progression is tracked on `leads.followup_stage`; the next queued
 * job updates the stage once delivery succeeds. Re-inserts with the same
 * email update metadata only — they never re-trigger outbound mail.
 */
class LeadCaptureService
{
    public function capture(
        string $email,
        ?string $name = null,
        ?string $role = null,
        ?string $organizationSize = null,
        ?string $source = 'sales_chat',
        array $metadata = [],
    ): Lead {
        return DB::transaction(function () use ($email, $name, $role, $organizationSize, $source, $metadata) {
            $lead = Lead::firstOrNew(['email' => $email]);

            $lead->fill([
                'name' => $name ?: $lead->name,
                'role' => $role ?: $lead->role,
                'organization_size' => $organizationSize ?: $lead->organization_size,
                'source' => $source ?: ($lead->source ?: 'sales_chat'),
                'metadata' => array_replace_recursive((array) $lead->metadata, $metadata),
            ]);

            $wasJustCreated = ! $lead->exists;

            $lead->save();

            if ($wasJustCreated && ! $lead->followup_stage) {
                SendSalesFollowupEmail::dispatch($lead->id, Lead::FOLLOWUP_DAY_0);
            }

            return $lead;
        });
    }

    /**
     * Mark a stage delivered; chain the next stage if not at the end of the
     * cadence. Called from SendSalesFollowupEmail once the queued mail is sent.
     */
    public function markFollowupStageSent(int $leadId, string $stage): ?Lead
    {
        return DB::transaction(function () use ($leadId, $stage) {
            $lead = Lead::find($leadId);

            if (! $lead) {
                return null;
            }

            $lead->followup_stage = $stage;
            $lead->last_followup_at = now();
            $lead->save();

            $nextStage = $this->nextStage($stage);

            if ($nextStage !== null) {
                SendSalesFollowupEmail::dispatch($lead->id, $nextStage);
            }

            return $lead;
        });
    }

    /**
     * Return the next stage in the cadence, or null if at the end.
     */
    public function nextStage(?string $currentStage): ?string
    {
        if ($currentStage === null) {
            return Lead::FOLLOWUP_DAY_0;
        }

        $idx = array_search($currentStage, Lead::FOLLOWUP_CADENCE, true);

        if ($idx === false || $idx === count(Lead::FOLLOWUP_CADENCE) - 1) {
            return null;
        }

        return Lead::FOLLOWUP_CADENCE[$idx + 1];
    }

    /**
     * Tag a lead for human follow-up (e.g. when the visitor asks to talk
     * to a person). Future Sales teams can filter by this flag.
     */
    public function requestHumanFollowup(int $leadId, ?string $note = null): ?Lead
    {
        try {
            $lead = Lead::find($leadId);

            if (! $lead) {
                return null;
            }

            $lead->metadata = array_replace_recursive((array) $lead->metadata, [
                'human_followup' => true,
                'human_followup_requested_at' => now()->toIso8601String(),
                'human_followup_note' => $note,
            ]);

            $lead->save();

            return $lead;
        } catch (\Throwable $e) {
            Log::warning('lead.human_followup flag failed', ['lead_id' => $leadId, 'error' => $e->getMessage()]);

            return null;
        }
    }
}
