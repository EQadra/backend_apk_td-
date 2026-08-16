<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\UploadImage; // ✅ IMPORTAMOS EL TRAIT

class Service extends Model
{
    use HasFactory, UploadImage; // ✅ AGREGAMOS EL TRAIT

    protected $fillable = [
        'serviceable_id',
        'serviceable_type',
        'name',
        'description',
        'price',
        'duration',
        'image', // ✅ AGREGAMOS EL CAMPO IMAGE
    ];

    public function serviceable()
    {
        return $this->morphTo();
    }

    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function favorites()
    {
        return $this->morphMany(Favorite::class, 'favoritable');
    }

    public function histories()
    {
        return $this->morphMany(History::class, 'historyable');
    }

    // ✅ MÉTODOS HELPER PARA IMÁGENES
    public function getImageUrlAttribute()
    {
        return $this->image ?? null;
    }

    public function hasImage()
    {
        return !is_null($this->image);
    }
}