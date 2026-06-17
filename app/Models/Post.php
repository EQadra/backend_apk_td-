<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\BelongsToUser;

class Post extends Model
{
    use HasFactory, BelongsToUser;

protected $fillable = [
    'user_id',
    'title',
    'content',
    'image',
    'category',
    'postable_type',
    'postable_id',
];

    /**
     * Atributos calculados que se envían al JSON
     */
    protected $appends = [
        'image_url',
        'short_content',
    ];

    /**
     * Casts (por si luego agregas flags)
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /* =======================
     | RELACIONES
     ======================= */

    // Polimórfico
    public function postable()
    {
        return $this->morphTo();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    /* =======================
     | ACCESSORS
     ======================= */

    /**
     * URL pública de la imagen
     */
    public function getImageUrlAttribute()
    {
        if (!$this->image) {
            return null;
        }

        // Soporta imágenes externas
        if (str_starts_with($this->image, 'http')) {
            return $this->image;
        }

        return asset('storage/' . $this->image);
    }

    /**
     * Contenido corto para Home
     */
    public function getShortContentAttribute()
    {
        return strlen($this->content) > 120
            ? substr($this->content, 0, 120) . '...'
            : $this->content;
    }

    /* =======================
     | SCOPES
     ======================= */

    /**
     * Últimos posts (ideal para Home)
     */
    public function scopeLatestForHome($query, $limit = 4)
    {
        return $query->latest()->take($limit);
    }

    /**
     * Filtrar por categoría
     */
    public function scopeCategory($query, $category)
    {
        return $query->where('category', $category);
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
