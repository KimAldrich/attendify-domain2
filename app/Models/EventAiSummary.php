<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EventAiSummary extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'activity_id',
        'summary_overall',
        'summary_strengths',
        'summary_weaknesses',
        'summary_recommendations',
        'metadata',
        'last_generated_at',
    ];

    protected $casts = [
        'metadata'          => 'array',
        'last_generated_at' => 'datetime',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function activity()
    {
        return $this->belongsTo(EventActivity::class, 'activity_id');
    }
}
