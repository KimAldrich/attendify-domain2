<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EventDay extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'date',
        'order_index',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    /*
     * Parent event for this day.
     */
    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    /*
     * Activities scheduled on this day.
     */
    public function activities()
    {
        return $this->hasMany(EventActivity::class, 'day_id')->orderBy('start_time');
    }

    /*
     * Attendance sessions on this day (e.g., morning/afternoon).
     */
    public function attendanceSessions()
    {
        return $this->hasMany(EventAttendanceSession::class, 'day_id');
    }

    /*
     * Convenience label, e.g. "Day 1 – 2025-11-30".
     */
    public function getDisplayLabelAttribute(): string
    {
        $date = $this->date?->format('M d, Y') ?? 'Unknown date';

        return "Day {$this->order_index} – {$date}";
    }
}
