<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EventUserRole extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'user_id',
        'role', // owner, co_organizer, staff
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getIsOwnerAttribute(): bool
    {
        return $this->role === 'owner';
    }

    public function getIsCoOrganizerAttribute(): bool
    {
        return $this->role === 'co_organizer';
    }

    public function getIsStaffAttribute(): bool
    {
        return $this->role === 'staff';
    }
}
