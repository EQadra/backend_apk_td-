<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\BelongsToUser;
use App\Models\Traits\HasServices;
use Illuminate\Support\Facades\Config;

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
        'services',
        'rating',
        'image',
        'schedule',
        'phone',
        'emergency_phone',
        'clinic_phone',
    ];

    protected $casts = [
        'services' => 'array',
        'rating'   => 'float',
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
    protected $appends = ['image_url', 'formatted_phone', 'formatted_emergency_phone', 'formatted_clinic_phone'];

    public function getImageUrlAttribute()
    {
        if (!$this->image) {
            return null;
        }

        // ✅ LA IMAGEN YA ES UNA URL COMPLETA (guardada por el trait UploadImage)
        // Solo verificar si es una URL válida, si no, construirla
        if (filter_var($this->image, FILTER_VALIDATE_URL)) {
            return $this->image;
        }

        // Si por algún motivo no es URL completa, construirla
        $isDevelopment = Config::get('app.env') === 'local' || Config::get('app.env') === 'development';
        
        if ($isDevelopment) {
            return 'http://192.168.203.82:8000/imagenes_app/doctors/' . $this->image;
        }
        
        return 'https://apiapk.tudealer.app/imagenes_app/doctors/' . $this->image;
    }

    // Formatear teléfonos
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

    // Método privado para formatear números de teléfono
    private function formatPhoneNumber($phone)
    {
        if (!$phone) return null;
        
        // Limpiar el número: solo dígitos
        $cleaned = preg_replace('/[^0-9]/', '', $phone);
        
        // Formato para Perú
        if (strlen($cleaned) === 9) {
            return substr($cleaned, 0, 3) . ' ' . substr($cleaned, 3, 3) . ' ' . substr($cleaned, 6);
        }
        
        if (strlen($cleaned) === 8) {
            return substr($cleaned, 0, 2) . ' ' . substr($cleaned, 2, 3) . ' ' . substr($cleaned, 5);
        }
        
        return $phone;
    }

    // Scopes
    public function scopeWithPhone($query, $phone)
    {
        $cleaned = preg_replace('/[^0-9]/', '', $phone);
        return $query->where('phone', 'LIKE', "%{$cleaned}%")
                     ->orWhere('emergency_phone', 'LIKE', "%{$cleaned}%")
                     ->orWhere('clinic_phone', 'LIKE', "%{$cleaned}%");
    }
}