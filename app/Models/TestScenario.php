<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TestScenario extends Model
{
    protected $fillable = [
        'acceptance_criteria_id',
        'code',
        'title',
        'user_role',
        'preconditions',
        'test_steps',
        'expected_result',
    ];

    public function acceptanceCriteria(): BelongsTo
    {
        return $this->belongsTo(AcceptanceCriteria::class);
    }

    public function executions(): HasMany
    {
        return $this->hasMany(TestExecution::class);
    }
}
