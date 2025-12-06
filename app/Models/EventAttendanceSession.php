<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EventAttendanceSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'day_id',
        'period',
        'label',
        'scheduled_start_time',
    ];

    protected $casts = [
        'scheduled_start_time' => 'datetime',
    ];

    /*
     * Parent event.
     */
    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    /*
     * Day this session belongs to.
     */
    public function day()
    {
        return $this->belongsTo(EventDay::class, 'day_id');
    }

    /*
     * Attendance records for this session.
     */
    public function attendanceRecords()
    {
        return $this->hasMany(EventAttendanceRecord::class, 'event_attendance_session_id');
    }
}
