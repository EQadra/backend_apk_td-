<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\BelongsToUser;
use App\Models\Traits\HasServices;


class Lawyer extends Model
{
    use HasFactory, BelongsToUser, HasServices;

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'description',
        'specialty',
        'license_code',
        'city',
        'university',
        'image',
        'schedule',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function feedbacks()
    {
        return $this->morphMany(Feedback::class, 'feedbackable');
    }

    // ✅ Relación polimórfica corregida
    public function posts()
    {
        return $this->morphMany(Post::class, 'postable');
    }

    public function services()
    {
        return $this->morphMany(Service::class, 'serviceable');
    }

    public function news()
    {
        return $this->morphMany(News::class, 'newable');
    }

    protected $appends = ['image_url'];

public function getImageUrlAttribute()
{
    if (!$this->image) {
        return null;
    }

    return asset('storage/' . $this->image);
}
}
