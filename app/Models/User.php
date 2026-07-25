<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use Notifiable, HasRoles, HasFactory;

    protected $guard_name = 'api';

    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'phone',
        'dni',
        'address',
        'city',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'current_token',
    ];

    protected $appends = [
        'avatar_url',
        'formatted_phone',
        'profile_type',
        'profile_data',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /* =========================
       🔐 JWT REQUIRED METHODS
       ========================= */

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    /* =========================
       🔗 RELATIONSHIPS
       ========================= */

    public function doctor()
    {
        return $this->hasOne(Doctor::class);
    }

    public function lawyer()
    {
        return $this->hasOne(Lawyer::class);
    }

    public function shop()
    {
        return $this->hasOne(Shop::class);
    }

    public function association()
    {
        return $this->hasOne(Association::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function feedbacks()
    {
        return $this->hasMany(Feedback::class);
    }

    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    public function news()
    {
        return $this->morphMany(News::class, 'newable');
    }

    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }

    public function histories()
    {
        return $this->hasMany(History::class);
    }

    public function postsAsPostable()
    {
        return $this->morphMany(Post::class, 'postable');
    }

    public function likes()
    {
        return $this->hasMany(Like::class);
    }

    /* =========================
       🔥 ACCESSORS
       ========================= */

    public function getAvatarUrlAttribute()
    {
        if (!$this->avatar) {
            return null;
        }

        if (filter_var($this->avatar, FILTER_VALIDATE_URL)) {
            return $this->avatar;
        }

        return asset('storage/' . $this->avatar);
    }

    public function getFormattedPhoneAttribute()
    {
        return $this->formatPhoneNumber($this->phone);
    }

    public function getProfileTypeAttribute()
    {
        if ($this->doctor) {
            return 'doctor';
        }
        if ($this->lawyer) {
            return 'lawyer';
        }
        if ($this->shop) {
            return 'shop';
        }
        if ($this->association) {
            return 'association';
        }
        return 'user';
    }

    public function getProfileDataAttribute()
    {
        if ($this->doctor) {
            return $this->doctor;
        }
        if ($this->lawyer) {
            return $this->lawyer;
        }
        if ($this->shop) {
            return $this->shop;
        }
        if ($this->association) {
            return $this->association;
        }
        return null;
    }

    private function formatPhoneNumber($phone)
    {
        if (!$phone) return null;
        
        $cleaned = preg_replace('/[^0-9]/', '', $phone);
        
        if (strlen($cleaned) === 9) {
            return substr($cleaned, 0, 3) . ' ' . substr($cleaned, 3, 3) . ' ' . substr($cleaned, 6);
        }
        
        if (strlen($cleaned) === 8) {
            return substr($cleaned, 0, 2) . ' ' . substr($cleaned, 2, 3) . ' ' . substr($cleaned, 5);
        }
        
        if (strlen($cleaned) >= 10) {
            $countryCode = substr($cleaned, 0, strlen($cleaned) - 9);
            $number = substr($cleaned, -9);
            return '+' . $countryCode . ' ' . substr($number, 0, 3) . ' ' . substr($number, 3, 3) . ' ' . substr($number, 6);
        }
        
        return $phone;
    }

    /* =========================
       🔥 SCOPES
       ========================= */

    public function scopeWithPhone($query, $phone)
    {
        $cleaned = preg_replace('/[^0-9]/', '', $phone);
        return $query->where('phone', 'LIKE', "%{$cleaned}%");
    }

    public function scopeWithName($query, $name)
    {
        return $query->where('name', 'LIKE', "%{$name}%");
    }

    public function scopeWithEmail($query, $email)
    {
        return $query->where('email', 'LIKE', "%{$email}%");
    }

    /* =========================
       🔥 HELPER METHODS
       ========================= */

    public function hasProfile(): bool
    {
        return $this->profile_data !== null;
    }

    public function getProfileDisplayName(): string
    {
        $profile = $this->profile_data;
        
        if (!$profile) {
            return $this->name;
        }

        if ($this->doctor) {
            return $profile->first_name . ' ' . $profile->last_name;
        }

        if ($this->lawyer) {
            return $profile->first_name . ' ' . $profile->last_name;
        }

        if ($this->shop || $this->association) {
            return $profile->name;
        }

        return $this->name;
    }

    public function getProfileAvatar(): ?string
    {
        $profile = $this->profile_data;
        
        if (!$profile) {
            return $this->avatar_url;
        }

        if (isset($profile->image_url) && $profile->image_url) {
            return $profile->image_url;
        }

        if (isset($profile->image) && $profile->image) {
            return $profile->image;
        }

        return $this->avatar_url;
    }

    public function getProfilePhone(): ?string
    {
        $profile = $this->profile_data;
        
        if (!$profile) {
            return $this->formatted_phone;
        }

        if (isset($profile->phone) && $profile->phone) {
            return $this->formatPhoneNumber($profile->phone);
        }

        return $this->formatted_phone;
    }

    public function getProfileCity(): ?string
    {
        $profile = $this->profile_data;
        
        if (!$profile) {
            return $this->city;
        }

        if (isset($profile->city) && $profile->city) {
            return $profile->city;
        }

        return $this->city;
    }

    public function getProfileAddress(): ?string
    {
        $profile = $this->profile_data;
        
        if (!$profile) {
            return $this->address;
        }

        if (isset($profile->address) && $profile->address) {
            return $profile->address;
        }

        return $this->address;
    }
}