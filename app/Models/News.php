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


    public function newable()
    {
        return $this->morphTo();
    }

    // 🔥 comentarios
public function comments()
{
    return $this->morphMany(\App\Models\Comment::class, 'commentable');
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