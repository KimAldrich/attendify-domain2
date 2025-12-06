<?php

// app/Models/ClassSchedule.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\CarbonInterface;

class ClassSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'course_section_id',
        'room_id',
        'day_of_week',
        'start_time',
        'end_time',
    ];

    public function section()
    {
        return $this->belongsTo(CourseSection::class, 'course_section_id');
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    // Helper to check if this schedule applies to a given date
    public function appliesToDate(CarbonInterface $date): bool
    {
        return (int) $this->day_of_week === (int) $date->dayOfWeek;
    }
}
