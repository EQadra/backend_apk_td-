<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class News extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',          // ✅ Nuevo: para usuarios normales
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

    protected $appends = ['image_url'];

    public function getImageUrlAttribute()
    {
        if (!$this->image) {
            return null;
        }

        if (filter_var($this->image, FILTER_VALIDATE_URL)) {
            return $this->image;
        }

        return asset('storage/' . $this->image);
    }

    // ✅ Relación con el usuario que creó la noticia
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ✅ Relación polimórfica con el perfil (opcional)
    public function newable()
    {
        return $this->morphTo();
    }

    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function likes()
    {
        return $this->morphMany(Like::class, 'likeable');
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