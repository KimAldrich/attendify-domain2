<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EvaluationResponse extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'registration_id',
        'activity_id',
        'form_id',
        'submitted_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function registration()
    {
        return $this->belongsTo(EventRegistration::class, 'registration_id');
    }

    public function activity()
    {
        return $this->belongsTo(EventActivity::class, 'activity_id');
    }

    public function form()
    {
        return $this->belongsTo(EvaluationForm::class, 'form_id');
    }

    public function answers()
    {
        return $this->hasMany(EvaluationAnswer::class, 'response_id');
    }
}
