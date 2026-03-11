<?php

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Role extends SpatieRole
{
    /**
     * Accesor personalizado: nombre en mayúsculas.
     */
    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => strtoupper($value),
        );
    }

    /**
     * Método auxiliar para chequear admin.
     */
    public function isAdmin()
    {
        // name ya devuelve mayúsculas
        return $this->name === 'ADMIN';
    }
}
