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
        // Nuevos campos de teléfono
        'phone',
        'office_phone',
    ];

    // Relaciones
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function feedbacks()
    {
        return $this->morphMany(Feedback::class, 'feedbackable');
    }

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

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function favorites()
    {
        return $this->morphMany(Favorite::class, 'favoritable');
    }

    public function histories()
    {
        return $this->morphMany(History::class, 'historyable');
    }

    // Accessors
    protected $appends = ['image_url', 'formatted_phone', 'formatted_office_phone'];

    public function getImageUrlAttribute()
    {
        if (!$this->image) {
            return null;
        }

        return asset('storage/' . $this->image);
    }

    public function getFormattedPhoneAttribute()
    {
        return $this->formatPhoneNumber($this->phone);
    }

    public function getFormattedOfficePhoneAttribute()
    {
        return $this->formatPhoneNumber($this->office_phone);
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
        
        return $phone;
    }
}