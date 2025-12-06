<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EventTrack extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'name',
        'location',
        'order_index',
    ];

    /*
     * Parent event.
     */
    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    /*
     * Activities assigned to this track.
     */
    public function activities()
    {
        return $this->hasMany(EventActivity::class, 'track_id');
    }

    /*
     * Useful for displaying "Main Hall (Building A)" style names.
     */
    public function getDisplayNameAttribute(): string
    {
        if ($this->location) {
            return "{$this->name} ({$this->location})";
        }

        return $this->name;
    }
}
