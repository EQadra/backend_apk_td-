<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\BelongsToUser;
use App\Models\Traits\HasProducts;
use App\Models\Traits\UploadImage; // ✅ AGREGAR TRAIT

class Association extends Model
{
    use HasFactory, BelongsToUser, HasProducts, UploadImage; // ✅ AGREGAR TRAIT

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'city',
        'address',
        'phone',
        'image',
        'website',
    ];

    protected $appends = ['image_url'];

    // ✅ CORREGIDO: Manejar URLs completas
    public function getImageUrlAttribute()
    {
        if (!$this->image) {
            return null;
        }

        // Si ya es una URL completa (http o https), devolver tal cual
        if (filter_var($this->image, FILTER_VALIDATE_URL)) {
            return $this->image;
        }

        // Si es una ruta relativa, construir la URL completa
        return asset('storage/' . ltrim($this->image, '/'));
    }

    // ✅ AGREGAR MÉTODO PARA SUBIR IMAGEN USANDO EL TRAIT
    public function uploadImage(Request $request)
    {
        return $this->uploadImageToProduction($request, $this, 'associations');
    }

    // ✅ AGREGAR MÉTODO PARA ELIMINAR IMAGEN
    public function deleteImage()
    {
        if ($this->image) {
            $this->deleteImageFromProduction($this->image);
            $this->update(['image' => null]);
        }
    }

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

    public function news()
    {
        return $this->morphMany(News::class, 'newable');
    }

    // 🔥 Renombrada para evitar choque con atributo "services"
    public function services()
    {
        return $this->morphMany(Service::class, 'serviceable');
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
}