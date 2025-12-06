<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EventAttendanceRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_attendance_session_id',
        'event_id',
        'registration_id',
        'user_id',
        'scanned_by',
        'scanned_at',
    ];

    protected $casts = [
        'scanned_at' => 'datetime',
    ];

    /*
     * The session (Day X / Morning or Afternoon) this record belongs to.
     */
    public function session()
    {
        return $this->belongsTo(EventAttendanceSession::class, 'event_attendance_session_id');
    }

    /*
     * Parent event (redundant but convenient).
     */
    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    /*
     * Registration record (null for some edge cases).
     */
    public function registration()
    {
        return $this->belongsTo(EventRegistration::class, 'registration_id');
    }

    /*
     * The user who attended (if they have an account).
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /*
     * Staff, co-organizer, or owner who scanned this record.
     */
    public function scanner()
    {
        return $this->belongsTo(User::class, 'scanned_by');
    }
}
