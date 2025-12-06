<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EventSpeaker extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'name',
        'title',
        'bio',
        'photo_path',
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
     * Optional helper if you later store speakers in R2 or external storage.
     * For now, this simply returns the raw path.
     */
    public function getPhotoUrlAttribute(): ?string
    {
        if (! $this->photo_path) {
            return null;
        }

        return $this->photo_path;
    }
}
