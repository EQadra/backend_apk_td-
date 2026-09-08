<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\UploadImage; // ✅ TRAIT PARA IMAGEN

class Product extends Model
{
    use HasFactory, UploadImage; // ✅ AGREGADO TRAIT

    protected $fillable = [
        'productable_id',
        'productable_type',
        'name',
        'description',
        'price',
        'image',
        'stock',
    ];

    protected $appends = [
        'image_url',
        'formatted_price',
    ];

    // ============================================================
    // RELACIONES
    // ============================================================

    public function productable()
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
    // MÉTODOS HELPER PARA IMÁGENES
    // ============================================================

    public function hasImage(): bool
    {
        return !is_null($this->image);
    }

    public function uploadImage($request)
    {
        return $this->uploadImageToProduction($request, $this, 'products', 'image');
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