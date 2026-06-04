<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TestExecution extends Model
{
    use SoftDeletes;

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

    /** Executions imported from a template that was never filled in. */
    public function scopeBlank(Builder $query): Builder
    {
        return $query
            ->where(fn (Builder $q) => $q->whereNull('outcome_raw')->orWhere('outcome_raw', ''))
            ->whereNull('reviewed_at');
    }

    public function testScenario(): BelongsTo
    {
        return $this->belongsTo(TestScenario::class);
    }
}
