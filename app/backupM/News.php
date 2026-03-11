<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class News extends Model
{
    use HasFactory;

    protected $fillable = [
        'titulo',
        'descripcion',
        'url',
        'fecha_publicacion',
        'newable_id',
        'newable_type',
    ];

    protected $casts = [
        'fecha_publicacion' => 'datetime',
    ];

    public function newable()
    {
        return $this->morphTo();
    }
}
