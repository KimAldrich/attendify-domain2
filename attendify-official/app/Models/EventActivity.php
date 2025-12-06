<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EventActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'day_id',
        'track_id',
        'title',
        'description',
        'start_time',
        'end_time',
        'type',
        'needs_analytics',
        'order_index',
    ];

    protected $casts = [
        'start_time'     => 'datetime:H:i',
        'end_time'       => 'datetime:H:i',
        'needs_analytics'=> 'boolean',
    ];

    /*
     * Parent event.
     */
    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    /*
     * Day this activity belongs to.
     */
    public function day()
    {
        return $this->belongsTo(EventDay::class, 'day_id');
    }

    /*
     * Track/venue for this activity (optional).
     */
    public function track()
    {
        return $this->belongsTo(EventTrack::class, 'track_id');
    }

    /*
     * Evaluation forms that specifically target this activity.
     */
    public function evaluationForms()
    {
        return $this->hasMany(EvaluationForm::class, 'activity_id');
    }

    /*
     * Evaluation responses submitted for this activity.
     */
    public function evaluationResponses()
    {
        return $this->hasMany(EvaluationResponse::class, 'activity_id');
    }

    /*
     * Cached numeric stats for this activity (averages, counts).
     */
    public function summaryStats()
    {
        return $this->hasMany(EvaluationSummaryStat::class, 'activity_id');
    }

    /*
     * AI summaries specific to this activity (optional).
     */
    public function aiSummaries()
    {
        return $this->hasMany(EventAiSummary::class, 'activity_id');
    }

    /*
     * Simple schedule label "08:00 – 09:30".
     */
    public function getScheduleLabelAttribute(): ?string
    {
        if (! $this->start_time && ! $this->end_time) {
            return null;
        }

        $start = $this->start_time ? $this->start_time->format('H:i') : '??';
        $end   = $this->end_time ? $this->end_time->format('H:i') : '??';

        return "{$start} – {$end}";
    }
}
