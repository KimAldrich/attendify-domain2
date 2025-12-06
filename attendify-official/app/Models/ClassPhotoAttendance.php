<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClassPhotoAttendance extends Model
{
    protected $fillable = [
        'course_section_id',
        'meeting_date',
        'uploaded_by',
        'photo_path',
    ];

    protected $casts = [
        'meeting_date' => 'date',
    ];

    public function section(): BelongsTo
    {
        return $this->belongsTo(CourseSection::class, 'course_section_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
