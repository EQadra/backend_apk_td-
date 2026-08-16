<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class News extends Model
{
    use HasFactory;

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

    /**
     * Usuario que creó la noticia
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Perfil asociado a la noticia.
     *
     * Puede ser:
     * Doctor
     * Lawyer
     * Shop
     * Association
     *
     * También puede ser NULL.
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

    /**
     * URL pública de la imagen
     */
    public function getImageUrlAttribute()
    {
        if (!$this->image) {
            return null;
        }

        if (filter_var($this->image, FILTER_VALIDATE_URL)) {
            return $this->image;
        }

        return asset('storage/' . ltrim($this->image, '/'));
    }
}