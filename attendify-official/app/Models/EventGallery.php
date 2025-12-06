<?php

// App\Models\EventGallery.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EventGallery extends Model
{
    use HasFactory;

    protected $table = 'event_gallery';

    protected $fillable = [
        'event_id',
        'album_name',
        'image_path',
        'caption',
        'order_index',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function getImageUrlAttribute(): ?string
    {
        if (! $this->image_path) {
            return null;
        }

        $diskConfig   = config('filesystems.disks.r2', []);
        $baseUrl      = rtrim($diskConfig['url'] ?? env('R2_URL', ''), '/');
        $relativePath = ltrim($this->image_path, '/');

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
