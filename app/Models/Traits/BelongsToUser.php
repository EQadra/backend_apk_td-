<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait BelongsToUser
{
    /**
     * Scope: filtra por usuario autenticado
     */
    public function scopeForUser(Builder $query)
    {
        if (Auth::check()) {
            $query->where($this->getTable() . '.user_id', Auth::id());
        }

        return $query;
    }

    /**
     * Asignar user_id automáticamente al crear
     */
    protected static function bootBelongsToUser()
    {
        static::creating(function ($model) {
            if (Auth::check() && empty($model->user_id)) {
                $model->user_id = Auth::id();
            }
        });
    }
}
