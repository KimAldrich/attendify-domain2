<?php

// app/Models/AttendanceRecord.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AttendanceRecord extends Model
{
    use HasFactory;

    public const STATUS_PRESENT = 'present';
    public const STATUS_ABSENT  = 'absent';
    public const STATUS_EXCUSED = 'excused';
    public const STATUS_TARDY   = 'tardy';

    protected $fillable = [
        'course_section_id',
        'student_id',
        'section_enrollment_id',
        'meeting_date',
        'status',
        'time_in',
        'marked_by_id',
        'source',
    ];

    protected $casts = [
        'meeting_date' => 'date',
        'time_in'      => 'datetime',
    ];

    public function section()
    {
        return $this->belongsTo(CourseSection::class, 'course_section_id');
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function enrollment()
    {
        return $this->belongsTo(SectionEnrollment::class, 'section_enrollment_id');
    }

    public function markedBy()
    {
        return $this->belongsTo(User::class, 'marked_by_id');
    }

    public function scopeForDate($query, $date)
    {
        return $query->whereDate('meeting_date', $date);
    }
}
