<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EvaluationQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'label',
        'scope_type',
        'scale_type',
        'category',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /*
     * Form-question pivot rows that include this question.
     */
    public function formQuestions()
    {
        return $this->hasMany(EvaluationFormQuestion::class, 'question_id');
    }

    /*
     * Answers given to this question.
     */
    public function answers()
    {
        return $this->hasMany(EvaluationAnswer::class, 'question_id');
    }

    /*
     * Summary stats (average, count) per event/activity for this question.
     */
    public function summaryStats()
    {
        return $this->hasMany(EvaluationSummaryStat::class, 'question_id');
    }

    /*
     * Helper: is this question Likert-type?
     */
    public function getIsLikertAttribute(): bool
    {
        return $this->scale_type === 'likert_1_5';
    }

    /*
     * Helper: is this question text/open-ended?
     */
    public function getIsTextAttribute(): bool
    {
        return $this->scale_type === 'text';
    }
}
