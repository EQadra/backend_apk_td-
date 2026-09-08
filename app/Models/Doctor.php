<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\BelongsToUser;
use App\Models\Traits\HasServices;
use App\Models\Traits\UploadImage; // ✅ TRAIT PARA IMAGEN
use Illuminate\Support\Facades\Config;

class Doctor extends Model
{
    use HasFactory, BelongsToUser, HasServices, UploadImage; // ✅ AGREGADO TRAIT

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
        'services',
        'rating',
        'image',
        'schedule',
        'phone',
        'emergency_phone',
        'clinic_phone',
        'sexo',
    ];

    protected $casts = [
        'services' => 'array',
        'rating'   => 'float',
    ];

    protected $appends = [
        'image_url',
        'formatted_phone',
        'formatted_emergency_phone',
        'formatted_clinic_phone',
        'full_name',
    ];

    // ============================================================
    // RELACIONES
    // ============================================================

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

    // ============================================================
    // ACCESSORS
    // ============================================================

    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function getImageUrlAttribute()
    {
        return $this->getFullImageUrl($this->image);
    }

    public function getFormattedPhoneAttribute()
    {
        return $this->formatPhoneNumber($this->phone);
    }

    public function getFormattedEmergencyPhoneAttribute()
    {
        return $this->formatPhoneNumber($this->emergency_phone);
    }

    public function getFormattedClinicPhoneAttribute()
    {
        return $this->formatPhoneNumber($this->clinic_phone);
    }

    // ============================================================
    // MÉTODOS PRIVADOS
    // ============================================================

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

    // ============================================================
    // SCOPES
    // ============================================================

    public function scopeWithPhone($query, $phone)
    {
        $cleaned = preg_replace('/[^0-9]/', '', $phone);
        return $query->where('phone', 'LIKE', "%{$cleaned}%")
                     ->orWhere('emergency_phone', 'LIKE', "%{$cleaned}%")
                     ->orWhere('clinic_phone', 'LIKE', "%{$cleaned}%");
    }

    // ============================================================
    // MÉTODOS HELPER PARA IMÁGENES
    // ============================================================

    public function hasImage(): bool
    {
        return !is_null($this->image);
    }

    public function uploadImage($request)
    {
        return $this->uploadImageToProduction($request, $this, 'doctors', 'image');
    }

    public function deleteImage()
    {
        if ($this->image) {
            $this->deleteImageFromProduction($this->image);
            $this->update(['image' => null]);
            return true;
        }
        return false;
    }
}