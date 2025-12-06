<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class RoleUpgradeRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'type',
        'creds_path',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getCredentialUrlAttribute(): ?string
    {
        return $this->creds_path
            ? Storage::disk('r2')->url($this->creds_path)
            : null;
    }
}