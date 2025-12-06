<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserProvider extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'provider', 'provider_uid',
        'email', 'display_name', 'avatar_url', 'linked_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
