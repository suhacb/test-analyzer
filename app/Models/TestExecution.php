<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TestExecution extends Model
{
    protected $fillable = [
        'test_scenario_id',
        'side',
        'outcome',
        'outcome_raw',
        'outcome_comment',
        'failure_cause',
        'comments',
        'tester_name',
        'browser',
        'tested_at',
        'source_file',
        'source_file_hash',
        'review_notes',
        'reviewed_at',
        'flagged_by_ai',
        'ai_flag_reason',
        'ai_flag_dismissed_at',
    ];

    protected $casts = [
        'tested_at'   => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function testScenario(): BelongsTo
    {
        return $this->belongsTo(TestScenario::class);
    }
}
