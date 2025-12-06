<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EvaluationForm extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'activity_id',
        'name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /*
     * Event this form belongs to.
     */
    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    /*
     * Activity this form targets (null for overall event forms).
     */
    public function activity()
    {
        return $this->belongsTo(EventActivity::class, 'activity_id');
    }

    /*
     * Pivot rows linking this form to its questions.
     */
    public function formQuestions()
    {
        return $this->hasMany(EvaluationFormQuestion::class, 'form_id')
                    ->orderBy('order_index');
    }

    /*
     * Questions for this form via pivot, already ordered.
     */
    public function questions()
    {
        return $this->belongsToMany(EvaluationQuestion::class, 'evaluation_form_questions', 'form_id', 'question_id')
                    ->withPivot(['order_index', 'is_required'])
                    ->orderBy('evaluation_form_questions.order_index');
    }

    /*
     * All responses submitted via this form.
     */
    public function responses()
    {
        return $this->hasMany(EvaluationResponse::class, 'form_id');
    }
}
