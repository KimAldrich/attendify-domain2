<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
class Event extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'slug',
        'title',
        'subtitle',
        'description',
        'hero_image_path',
        'banner_image_path',
        'event_type',
        'mode',
        'visibility',
        'target_audience_json',
        'capacity',
        'reg_open_at',
        'reg_close_at',
        'start_at',
        'end_at',
        'status',
        'owner_id',
        'registration_instructions',  
        'requires_payment_proof',  
        'auto_approve_registrations',
        'enable_waitlist', 
    ];

    protected $casts = [
        'target_audience_json' => 'array',
        'reg_open_at'          => 'datetime',
        'reg_close_at'         => 'datetime',
        'start_at'             => 'datetime',
        'end_at'               => 'datetime',
        'deleted_at'           => 'datetime',
        'requires_payment_proof' => 'boolean', 
        'auto_approve_registrations'  => 'boolean',
        'enable_waitlist'             => 'boolean',
    ];

    /*
     * Owner of the event (typically faculty/admin).
     */
    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /*
     * Days on which this event runs (Day 1, Day 2, etc.).
     */
    public function days()
    {
        return $this->hasMany(EventDay::class)->orderBy('order_index');
    }

    /*
     * Tracks/venues/streams defined for the event.
     */
    public function tracks()
    {
        return $this->hasMany(EventTrack::class)->orderBy('order_index');
    }

    /*
     * Program activities for this event (talks, panels, breaks...).
     */
    public function activities()
    {
        return $this->hasMany(EventActivity::class);
    }

    /*
     * Speakers attached to this event for the "Speakers" section.
     */
    public function speakers()
    {
        return $this->hasMany(EventSpeaker::class)->orderBy('order_index');
    }

    
    public function specialGuests()
    {
        return $this->hasMany(SpecialGuest::class)
            ->orderBy('order_index')
            ->orderBy('name');
    }

    public function coOrganizers()
    {
        return $this->belongsToMany(User::class, 'event_user_roles', 'event_id', 'user_id')
            ->wherePivot('role', 'co_organizer');
    }

    /*
     * Registrations (students/faculty/guests/no-account) for this event.
     */
    public function registrations()
    {
        return $this->hasMany(EventRegistration::class);
    }

    /*
     * Approved registrations (accepted attendees).
     */
    public function approvedRegistrations()
    {
        return $this->registrations()->where('status', 'approved');
    }

    /*
     * Pending + approved registrations (counts towards capacity).
     */
    public function activeRegistrations()
    {
        return $this->registrations()->active();
    }

    /*
     * Convenience count for capacity checks: pending + approved.
     */
    public function getActiveRegistrationCountAttribute(): int
    {
        return $this->activeRegistrations()->count();
    }

    public function waitlistedRegistrations()
    {
        return $this->registrations()->waitlisted();
    }

    /*
     * Attendance sessions (Day 1 AM, Day 1 PM, etc.).
     */
    public function attendanceSessions()
    {
        return $this->hasMany(EventAttendanceSession::class);
    }

    /*
     * Attendance records (who attended which session).
     */
    public function attendanceRecords()
    {
        return $this->hasMany(EventAttendanceRecord::class);
    }

    /*
     * Evaluation forms defined for this event (overall, per-activity, etc.).
     */
    public function evaluationForms()
    {
        return $this->hasMany(EvaluationForm::class);
    }

    /*
     * All evaluation responses submitted for this event.
     */
    public function evaluationResponses()
    {
        return $this->hasMany(EvaluationResponse::class);
    }

    /*
     * Precomputed numeric stats per question (for fast analytics).
     */
    public function evaluationSummaryStats()
    {
        return $this->hasMany(EvaluationSummaryStat::class);
    }

    /*
     * Stored AI-generated summaries for this event.
     */
    public function aiSummaries()
    {
        return $this->hasMany(EventAiSummary::class);
    }

    /*
     * Gallery photos for this event.
     */
    public function gallery()
    {
        return $this->hasMany(EventGallery::class);
    }

    /*
     * Event-level roles: owner, co_organizer, staff.
     */
    public function userRoles()
    {
        return $this->hasMany(EventUserRole::class);
    }

    /*
     * Certificate settings (layout/background/logos) for this event.
     */
    public function certificateSettings()
    {
        return $this->hasMany(EventCertificateSetting::class);
    }

    /*
     * Individual issued certificates for attendees.
     */
    public function certificates()
    {
        return $this->hasMany(EventCertificate::class);
    }

    /*
     * Helper: is this event published and visible?
     */
    public function getIsPublishedAttribute(): bool
    {
        return $this->status === 'published';
    }

    /*
     * Helper: is registration currently open based on dates and status.
     */
    public function getIsRegistrationOpenAttribute(): bool
    {
        if (! $this->is_published) {
            return false;
        }

        $now = now();

        if ($this->reg_open_at && $now->lt($this->reg_open_at)) {
            return false;
        }

        if ($this->reg_close_at && $now->gt($this->reg_close_at)) {
            return false;
        }

        return true;
    }

    /*
     * Short, plain-text description for listings.
     */
    public function getShortDescriptionAttribute(): ?string
    {
        if (! $this->description) {
            return null;
        }

        return str($this->description)->stripTags()->limit(160);
    }

    /*
     * Optional convenience: check if a user can register based on audience filters.
     * (You can flesh this out later according to target_audience_json rules.)
     */
    public function canUserRegister(?User $user): bool
    {
        // Basic rule: must be published and registration open.
        if (! $this->is_registration_open) {
            return false;
        }

        // No-account visitors allowed? Handle via target_audience_json later.
        // For now, default to true to let controllers implement final policy.
        return true;
    }

    public function getHeroImageUrlAttribute(): string
    {
        // no hero => default branding
        if (! $this->hero_image_path) {
            return asset('images/branding/attendify-brand.png');
        }

        // build from r2 disk (same style as User::photo_url)
        $diskConfig   = config('filesystems.disks.r2', []);
        $baseUrl      = rtrim($diskConfig['url'] ?? env('R2_URL', ''), '/');
        $relativePath = ltrim($this->hero_image_path, '/');

        if ($baseUrl !== '') {
            return $baseUrl.'/'.$relativePath;
        }

        $endpoint = rtrim($diskConfig['endpoint'] ?? env('R2_ENDPOINT', ''), '/');
        $bucket   = $diskConfig['bucket'] ?? env('R2_BUCKET', '');

        if ($endpoint !== '' && $bucket !== '') {
            return $endpoint.'/'.$bucket.'/'.$relativePath;
        }

        // extreme fallback
        return asset('images/branding/attendify-brand.png');
    }

    public function getBannerImageUrlAttribute(): string
    {
        if (! $this->banner_image_path) {
            return asset('images/branding/attendify-logo.png');
        }

        $diskConfig   = config('filesystems.disks.r2', []);
        $baseUrl      = rtrim($diskConfig['url'] ?? env('R2_URL', ''), '/');
        $relativePath = ltrim($this->banner_image_path, '/');

        if ($baseUrl !== '') {
            return $baseUrl.'/'.$relativePath;
        }

        $endpoint = rtrim($diskConfig['endpoint'] ?? env('R2_ENDPOINT', ''), '/');
        $bucket   = $diskConfig['bucket'] ?? env('R2_BUCKET', '');

        if ($endpoint !== '' && $bucket !== '') {
            return $endpoint.'/'.$bucket.'/'.$relativePath;
        }

        return asset('images/branding/attendify-logo.png');
    }


}
