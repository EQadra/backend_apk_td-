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
        'avatar', // ✅ Agregado
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $appends = ['avatar_url']; // ✅ Para obtener la URL completa

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

    /* =========================
       🔥 ACCESOR PARA AVATAR URL
       ========================= */

    public function getAvatarUrlAttribute()
    {
        if (!$this->avatar) {
            return null;
        }

        // Si ya es una URL completa, devolverla
        if (filter_var($this->avatar, FILTER_VALIDATE_URL)) {
            return $this->avatar;
        }

        // Si es una ruta relativa, construir la URL
        return asset('storage/' . $this->avatar);
    }
}