<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class News extends Model
{
    use HasFactory;

    protected $fillable = [
        'titulo',
        'descripcion',
        'url',
        'fecha_publicacion',
        'newable_type',
        'newable_id',
    ];

    protected $casts = [
        'fecha_publicacion' => 'datetime',
    ];

    protected $appends = ['user'];

    public function newable()
    {
        return $this->morphTo();
    }

    // 🔥 comentarios
public function comments()
{
    return $this->morphMany(\App\Models\Comment::class, 'commentable');
}

    // 🔥 usuario directo (para frontend)
    public function getUserAttribute()
    {
        return $this->newable?->user;
    }
}