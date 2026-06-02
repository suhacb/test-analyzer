<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class UserStory extends Model
{
    protected $fillable = ['code', 'title', 'ai_report', 'ai_report_generated_at'];

    protected $casts = ['ai_report_generated_at' => 'datetime'];

    public function acceptanceCriteria(): HasMany
    {
        return $this->hasMany(AcceptanceCriteria::class);
    }

    public function testScenarios(): HasManyThrough
    {
        return $this->hasManyThrough(TestScenario::class, AcceptanceCriteria::class);
    }

    public function isAccepted(): bool
    {
        return $this->acceptanceCriteria()->get()->every(fn($ac) => $ac->isAccepted());
    }
}
