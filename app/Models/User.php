<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Auth\MustVerifyEmail as MustVerifyEmailTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Support\Str;
use App\Notifications\BrandedVerifyEmail;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable implements MustVerifyEmailContract
{
    use HasFactory, Notifiable, HasRoles, MustVerifyEmailTrait; 

// App\Models\User.php (snippets)
    protected $fillable = [
        'slug',
        'firebase_uid',
        'name',
        'first_name','middle_name','last_name',
        'cp_no',
        'email',
        'password',
        'photo_path',
        'address',          // kept for your joined string
        'address_house','address_brgy','address_city','address_province',
        'link_facebook','link_linkedin','bio',
        'info_status',
        'organization',
        'student_number','year_level',
        'student_department_id','student_campus_id','is_moderator',
        'is_teaching','faculty_department_id','faculty_office_id','faculty_campus_id',
    ];
    
    protected $hidden = ['password','remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'info_status'       => 'boolean',
            'is_moderator'      => 'boolean',
            'is_teaching'       => 'boolean',
        ];
    }

// App\Models\User.php
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    // ensure slug is set (create + optional backfill on update)
    protected static function booted(): void
    {
        static::creating(function ($user) {
            if (blank($user->slug) && filled($user->name)) {
                $base = Str::slug($user->name);
                // attempt a few times to avoid rare unique collisions
                for ($i = 0; $i < 5; $i++) {
                    $candidate = $base.'-'.Str::lower(Str::random(6));
                    if (! static::where('slug', $candidate)->exists()) {
                        $user->slug = $candidate;
                        break;
                    }
                }
                // last resort if all 5 collided (ultra-rare)
                if (blank($user->slug)) {
                    $user->slug = $base.'-'.Str::lower(Str::random(10));
                }
            }
        });
    }


    public function getDisplayNameAttribute(): string
    {
        $name = trim(($this->first_name ?? '').' '.($this->last_name ?? ''));
        return $name !== '' ? $name : ($this->name ?: $this->email);
    }

    public function getFullNameAttribute(): string
    {
        $first  = trim($this->first_name ?? '');
        $middle = trim($this->middle_name ?? '');
        $last   = trim($this->last_name ?? '');

        if ($last !== '' || $first !== '') {
            $core = trim($last . ', ' . $first); 
            if ($middle !== '') {
                $core .= ' ' . $middle;          
            }
            return $core;
        }

        return $this->name ?: $this->email ?: 'User';
    }

    public function sendEmailVerificationNotification()
    {
        $this->notify(new BrandedVerifyEmail());
    }

    public function providers()
    {
        return $this->hasMany(\App\Models\UserProvider::class);
    }
    public function getPhotoUrlAttribute(): string
    {
        // If no photo_path, fall back to your default avatar
        if (!$this->photo_path) {
            return asset('images/ui/userdefault.jpg');
        }

        // Prefer the "url" defined in your r2 disk config (usually the custom domain / public bucket URL)
        $diskConfig = config('filesystems.disks.r2', []);

        $baseUrl = rtrim($diskConfig['url'] ?? env('R2_URL', ''), '/');
        $relativePath = ltrim($this->photo_path, '/'); // e.g. "profile/slug/avatar.jpg"

        if ($baseUrl !== '') {
            // e.g. https://cdn.attendify.test/profile/slug/avatar.jpg
            return $baseUrl . '/' . $relativePath;
        }

        // Fallback: build from endpoint + bucket if url isn't set
        $endpoint = rtrim($diskConfig['endpoint'] ?? env('R2_ENDPOINT', ''), '/');
        $bucket   = $diskConfig['bucket'] ?? env('R2_BUCKET', '');

        if ($endpoint !== '' && $bucket !== '') {
            // e.g. https://<ACCOUNT_ID>.r2.cloudflarestorage.com/attendify-profile-photos/profile/slug/avatar.jpg
            return $endpoint . '/' . $bucket . '/' . $relativePath;
        }

        // Ultimate fallback: default avatar if something is misconfigured
        return asset('images/ui/userdefault.jpg');
    }
}
