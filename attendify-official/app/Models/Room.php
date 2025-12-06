<?php

// app/Models/Room.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Room extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_number',
        'camera_endpoint',
        'is_face_recognition_enabled',
        'notes',
    ];

    protected $casts = [
        'is_face_recognition_enabled' => 'boolean',
    ];

    public function schedules()
    {
        return $this->hasMany(ClassSchedule::class);
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->room_number;
    }
}
