<?php

declare(strict_types=1);

namespace Tests\Unit\AI\Tools;

use App\AI\Events\CustomerSuccessEscalationTriggered;
use App\AI\Tools\CustomerSuccess\EscalateHighRiskTool;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class EscalateHighRiskToolTest extends TestCase
{
    use RefreshDatabase;

    public function test_emits_event_when_called(): void
    {
        Event::fake([CustomerSuccessEscalationTriggered::class]);

        $result = (new EscalateHighRiskTool)->execute([
            'organization_id' => 42,
            'score' => 88,
            'reason' => 'no households imported in 14 days',
            'signals' => ['no_households', 'no_other_members'],
        ])->toArray();

        $this->assertTrue($result['ok']);
        Event::assertDispatched(CustomerSuccessEscalationTriggered::class, function ($event) {
            return $event->organizationId === 42 && $event->score === 88;
        });
    }
}
