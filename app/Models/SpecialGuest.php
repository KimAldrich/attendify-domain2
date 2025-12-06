<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SpecialGuest extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'name',
        'title',
        'description',
        'photo_path',
        'order_index',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function getPhotoUrlAttribute(): ?string
    {
        if (! $this->photo_path) {
            return null;
        }

        $diskConfig   = config('filesystems.disks.r2', []);
        $baseUrl      = rtrim($diskConfig['url'] ?? env('R2_URL', ''), '/');
        $relativePath = ltrim($this->photo_path, '/');

        if ($baseUrl !== '') {
            return $baseUrl.'/'.$relativePath;
        }

        $endpoint = rtrim($diskConfig['endpoint'] ?? env('R2_ENDPOINT', ''), '/');
        $bucket   = $diskConfig['bucket'] ?? env('R2_BUCKET', '');

        if ($endpoint !== '' && $bucket !== '') {
            return $endpoint.'/'.$bucket.'/'.$relativePath;
        }

        return null;
    }
}
