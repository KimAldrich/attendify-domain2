<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EventCertificate extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'registration_id',
        'certificate_settings_id',
        'verification_code',
        'file_path',
        'issued_at',
    ];

    protected $casts = [
        'issued_at' => 'datetime',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function registration()
    {
        return $this->belongsTo(EventRegistration::class, 'registration_id');
    }

    public function settings()
    {
        return $this->belongsTo(EventCertificateSetting::class, 'certificate_settings_id');
    }
}
