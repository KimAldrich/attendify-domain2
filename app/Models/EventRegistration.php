<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EventRegistration extends Model
{
    use HasFactory;

    public const ACTIVE_STATUSES = ['pending', 'approved'];

    protected $fillable = [
        'event_id',
        'user_id',
        'display_name',
        'email',
        'attendee_type',
        'status',
        'proof_of_payment_path',
    ];

    public function scopeActive($query)
    {
        return $query->whereIn('status', self::ACTIVE_STATUSES);
    }

    public function scopeWaitlisted($query)
    {
        return $query->where('status', 'waitlisted');
    }

    /*
     * Parent event.
     */
    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    /*
     * Linked user if they have an account (null for no-account registrations).
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /*
     * Attendance records for this registration (per session).
     */
    public function attendanceRecords()
    {
        return $this->hasMany(EventAttendanceRecord::class, 'registration_id');
    }

    /*
     * Evaluation responses submitted by this registration.
     */
    public function evaluationResponses()
    {
        return $this->hasMany(EvaluationResponse::class, 'registration_id');
    }

    /*
     * Certificates issued to this registration.
     */
    public function certificates()
    {
        return $this->hasMany(EventCertificate::class, 'registration_id');
    }

    /*
     * Helper: is this registration approved?
     */
    public function getIsApprovedAttribute(): bool
    {
        return $this->status === 'approved';
    }

    /*
     * Helper: did the user upload a payment proof?
     */
    public function getHasPaymentProofAttribute(): bool
    {
        return ! empty($this->proof_of_payment_path);
    }
}
