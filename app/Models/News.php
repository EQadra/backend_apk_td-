<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\UploadImage; // ✅ TRAIT PARA IMAGEN

class News extends Model
{
    use HasFactory, UploadImage; // ✅ AGREGADO TRAIT

    protected $fillable = [
        'user_id',
        'titulo',
        'descripcion',
        'url',
        'image',
        'fecha_publicacion',
        'newable_type',
        'newable_id',
    ];

    protected $casts = [
        'fecha_publicacion' => 'datetime',
    ];

    protected $appends = [
        'image_url',
    ];

    // ============================================================
    // RELACIONES
    // ============================================================

    /**
     * Usuario que creó la noticia
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Perfil asociado a la noticia.
     * Puede ser: Doctor, Lawyer, Shop, Association o NULL
     */
    public function newable()
    {
        return $this->morphTo();
    }

    /**
     * Comentarios de la noticia
     */
    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    /**
     * Likes de la noticia
     */
    public function likes()
    {
        return $this->morphMany(Like::class, 'likeable');
    }

    /**
     * Favoritos
     */
    public function favorites()
    {
        return $this->morphMany(Favorite::class, 'favoritable');
    }

    /**
     * Historial
     */
    public function histories()
    {
        return $this->morphMany(History::class, 'historyable');
    }

    // ============================================================
    // ACCESSORS
    // ============================================================

    /**
     * URL pública de la imagen
     */
    public function getImageUrlAttribute()
    {
        return $this->getFullImageUrl($this->image);
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
        return $this->uploadImageToProduction($request, $this, 'news', 'image');
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