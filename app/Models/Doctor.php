<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\BelongsToUser;
use App\Models\Traits\HasServices;

class Doctor extends Model
{
    use HasFactory, BelongsToUser, HasServices;

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'description',
        'degree',
        'specialty',
        'graduation_code',
        'city',
        'university',
        'services',   // ⭐ necesario
        'rating',     // ⭐ necesario
        'image',
        'schedule',
    ];

    protected $casts = [
        'services' => 'array', // ⭐ evita "Array to string conversion"
        'rating'   => 'float',
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

    // 🔥 Renombrada para evitar choque con atributo "services"
    public function services()
    {
        return $this->morphMany(Service::class, 'serviceable');
    }

    public function news()
    {
        return $this->morphMany(News::class, 'newable');
    }
}
