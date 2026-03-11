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
}
