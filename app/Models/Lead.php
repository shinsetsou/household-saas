<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'last_followup_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public const FOLLOWUP_DAY_0 = 'day0_sent';

    public const FOLLOWUP_DAY_3 = 'day3_sent';

    public const FOLLOWUP_DAY_7 = 'day7_sent';

    /**
     * Stages in chronological order. Past the last stage the service is a no-op.
     */
    public const FOLLOWUP_CADENCE = [
        self::FOLLOWUP_DAY_0,
        self::FOLLOWUP_DAY_3,
        self::FOLLOWUP_DAY_7,
    ];
}
