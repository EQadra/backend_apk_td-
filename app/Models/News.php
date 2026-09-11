<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\UploadImage;

class News extends Model
{
    use HasFactory, UploadImage;

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
        'short_descripcion',
    ];

    // ============================================================
    // RELACIONES
    // ============================================================

    public function user()
    {
        return $this->belongsTo(User::class);
    }

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

    // ============================================================
    // ACCESSORS
    // ============================================================

    public function getImageUrlAttribute()
    {
        return $this->getFullImageUrl($this->image);
    }

    public function getShortDescripcionAttribute()
    {
        if (!$this->descripcion) return null;
        return strlen($this->descripcion) > 150
            ? substr($this->descripcion, 0, 150) . '...'
            : $this->descripcion;
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

    // ============================================================
    // SCOPES
    // ============================================================

    public function scopeLatestForHome($query, $limit = 6)
    {
        return $query->latest('created_at')->take($limit);
    }

    public function scopeByType($query, $type)
    {
        $typeMap = [
            'doctor' => 'App\\Models\\Doctor',
            'lawyer' => 'App\\Models\\Lawyer',
            'shop' => 'App\\Models\\Shop',
            'association' => 'App\\Models\\Association',
        ];

        if (isset($typeMap[$type])) {
            return $query->where('newable_type', $typeMap[$type]);
        }

        return $query;
    }
}