<?php

// app/Models/SectionEnrollment.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SectionEnrollment extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_section_id',
        'student_id',
    ];

    protected $appends = [
        'present_count',
        'absent_count',
        'excused_count',
        'tardy_count', 
        'attendance_percentage',
        'face_recognition_status',
        'today_status',
    ];

    public function section()
    {
        return $this->belongsTo(CourseSection::class, 'course_section_id');
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function attendanceRecords()
    {
        return $this->hasMany(AttendanceRecord::class);
    }

    // --- Counts for present / absent / excused (per-section) ---

    public function getPresentCountAttribute(): int
    {
        return $this->attendanceRecords()->where('status', 'present')->count();
    }

    public function getAbsentCountAttribute(): int
    {
        return $this->attendanceRecords()->where('status', 'absent')->count();
    }

    public function getExcusedCountAttribute(): int
    {
        return $this->attendanceRecords()->where('status', 'excused')->count();
    }
    public function getTardyCountAttribute(): int
    {
        return $this->attendanceRecords()
            ->where('status', AttendanceRecord::STATUS_TARDY)
            ->count();
    }

    public function getAttendancePercentageAttribute(): float
    {
        $total = $this->attendanceRecords()->count();

        if ($total === 0) {
            return 0.0;
        }

        // present + tardy are counted as “attended”
        $presentLike = $this->attendanceRecords()
            ->whereIn('status', [
                AttendanceRecord::STATUS_PRESENT,
                AttendanceRecord::STATUS_TARDY,
            ])
            ->count();

        return round(($presentLike / $total) * 100, 2);
    }

    // --- Face recognition status (for listing table) ---

public function getFaceRecognitionStatusAttribute(): string
{
    return $this->student->has_face_recognition_photo ? 'active' : 'inactive';
}

    // --- Today's status for this student in this section (Student Page Attendance Today) ---

    public function getTodayStatusAttribute(): string
    {
        $record = $this->attendanceRecords()
            ->whereDate('meeting_date', today())
            ->latest('id')
            ->first();

        // "pending" if no record
        return $record?->status ?? 'pending';
    }
}
