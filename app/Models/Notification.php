<?php

// app/Models/Notification.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'message',
        'link',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Helper scopes if you want them later
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeVisible($query)
    {
        return $query->whereIn('status', ['unread', 'read']);
    }
}
