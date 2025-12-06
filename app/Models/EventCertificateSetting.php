<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EventCertificateSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'background_path',
        'main_title',
        'body_text',
        'date_label',
        'sponsor_logos_json',
        'is_active',
    ];

    protected $casts = [
        'sponsor_logos_json' => 'array',
        'is_active'          => 'boolean',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function certificates()
    {
        return $this->hasMany(EventCertificate::class, 'certificate_settings_id');
    }
}
