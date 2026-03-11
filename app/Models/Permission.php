<?php

namespace App\Models;

use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    /**
     * Agrupar permisos por prefijo (por ejemplo: 'producto.ver', 'producto.crear')
     */
    public function scopeByModule($query, $module)
    {
        return $query->where('name', 'like', "$module.%");
    }

    /**
     * Ver si un permiso pertenece a un módulo.
     */
    public function belongsToModule($module)
    {
        return str_starts_with($this->name, "$module.");
    }
}
