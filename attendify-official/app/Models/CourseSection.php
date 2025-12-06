<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CourseSection extends Model
{
    use HasFactory;

    protected $fillable = [
        'academic_period_id',
        'instructor_id',
        'section_label',
        'course_code',
        'course_name',
        'enrollment_limit',
        'is_archived',
    ];

    protected $casts = [
        'is_archived' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    // AY + Term
    public function academicPeriod()
    {
        return $this->belongsTo(AcademicPeriod::class, 'academic_period_id');
    }

    // Instructor teaching this section
    public function instructor()
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    // Per-day schedules (day_of_week, start/end, room)
    public function schedules()
    {
        return $this->hasMany(ClassSchedule::class, 'course_section_id');
    }

    // Enrollment pivot rows
    public function enrollments()
    {
        return $this->hasMany(SectionEnrollment::class, 'course_section_id');
    }

    // Direct shortcut to students via the pivot
    public function students()
    {
        return $this->belongsToMany(User::class, 'section_enrollments', 'course_section_id', 'student_id')
            ->withTimestamps();
    }

    // All attendance records for this section
    public function attendanceRecords()
    {
        return $this->hasMany(AttendanceRecord::class, 'course_section_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    // For "Sections Today" pages
    public function scopeForDayOfWeek($query, int $dayOfWeek)
    {
        return $query->whereHas('schedules', function ($q) use ($dayOfWeek) {
            $q->where('day_of_week', $dayOfWeek);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors / Helpers
    |--------------------------------------------------------------------------
    */

    // Convenience count (still prefer withCount in queries)
    public function getStudentsCountAttribute(): int
    {
        return $this->enrollments()->count();
    }

    public function getDisplayNameAttribute(): string
    {
        $code  = $this->course_code ?? '';
        $name  = $this->course_name ?? '';
        $label = $this->section_label ?? '';

        $coursePart = trim($code !== '' ? "{$code} • {$name}" : $name);

        if ($coursePart === '') {
            return $label !== '' ? $label : "Section #{$this->id}";
        }

        return $label !== ''
            ? "{$coursePart} ({$label})"
            : $coursePart;
    }
}
