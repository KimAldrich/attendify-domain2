<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExcuseLetter extends Model
{
    protected $fillable = [
        'course_section_id',
        'student_id',
        'meeting_date',
        'file_path',
        'original_name',
        'mime_type',
        'notes',
        'status',
    ];

    protected $casts = [
        'meeting_date' => 'date',
    ];

    public function section(): BelongsTo
    {
        return $this->belongsTo(CourseSection::class, 'course_section_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }
}
