<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EvaluationSummaryStat extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'activity_id',
        'question_id',
        'average_score',
        'response_count',
        'last_calculated_at',
    ];

    protected $casts = [
        'average_score'      => 'float',
        'response_count'     => 'integer',
        'last_calculated_at' => 'datetime',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function activity()
    {
        return $this->belongsTo(EventActivity::class, 'activity_id');
    }

    public function question()
    {
        return $this->belongsTo(EvaluationQuestion::class, 'question_id');
    }
}
