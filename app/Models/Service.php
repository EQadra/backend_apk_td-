<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\UploadImage;

class Service extends Model
{
    use HasFactory, UploadImage;

    protected $fillable = [
        'serviceable_id',
        'serviceable_type',
        'name',
        'description',
        'price',
        'duration',
        'image',
    ];

    protected $appends = [
        'image_url',
        'formatted_price',
    ];

    // ============================================================
    // RELACIONES
    // ============================================================

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

    // ============================================================
    // ACCESSORS
    // ============================================================

    public function getImageUrlAttribute()
    {
        return $this->getFullImageUrl($this->image);
    }

    public function getFormattedPriceAttribute()
    {
        return 'S/ ' . number_format($this->price, 2);
    }

    // ============================================================
    // MÉTODOS HELPER
    // ============================================================

    public function hasImage(): bool
    {
        return !is_null($this->image);
    }

    /**
     * Subir imagen para este servicio
     */
    public function uploadImage(Request $request)
    {
        return $this->uploadImageToProduction($request, $this, 'services', 'image');
    }

    /**
     * Eliminar imagen de este servicio
     */
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