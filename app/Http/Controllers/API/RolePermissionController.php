<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionController extends Controller
{
    /** ✅ Listar todos los roles con sus permisos */
    public function index()
    {
        $roles = Role::with('permissions')->get();

        return response()->json([
            'success' => true,
            'message' => 'Lista de roles con permisos',
            'data' => $roles,
        ]);
    }

    /** ✅ Mostrar permisos de un rol específico */
   /** ✅ Mostrar permisos de un rol específico (solo nombres) */
public function permissions($roleId)
{
    $role = Role::with('permissions')->find($roleId);

    if (!$role) {
        return response()->json([
            'success' => false,
            'message' => 'Rol no encontrado',
        ], 404);
    }

    // ⚙️ Devuelve solo los nombres de los permisos asignados
    $permissionNames = $role->permissions->pluck('name');

    return response()->json([
        'success' => true,
        'message' => 'Permisos del rol',
        'data' => $permissionNames,
    ], 200);
}


    /** ✅ Sincronizar permisos (reemplaza los existentes) */
    public function update(Request $request, $roleId)
    {
        $role = Role::find($roleId);

        if (!$role) {
            return response()->json([
                'success' => false,
                'message' => 'Rol no encontrado',
            ], 404);
        }

        $validated = $request->validate([
            'permissions' => 'required|array',
        ]);

        $role->syncPermissions($validated['permissions']);

        return response()->json([
            'success' => true,
            'message' => 'Permisos actualizados correctamente',
            'data' => $role->permissions,
        ]);
    }

    /** ✅ Asignar un permiso individual al rol */
    public function assignPermission(Request $request, $roleId)
    {
        $role = Role::find($roleId);
        if (!$role) {
            return response()->json(['success' => false, 'message' => 'Rol no encontrado'], 404);
        }

        $validated = $request->validate([
            'permission' => 'required|string|exists:permissions,name',
        ]);

        $permission = Permission::where('name', $validated['permission'])->first();

        if ($role->hasPermissionTo($permission)) {
            return response()->json([
                'success' => false,
                'message' => 'El rol ya tiene este permiso',
            ], 400);
        }

        $role->givePermissionTo($permission);

        return response()->json([
            'success' => true,
            'message' => 'Permiso asignado correctamente al rol',
            'data' => $role->permissions,
        ]);
    }

    /** 🚫 Revocar un permiso individual del rol */
    public function revokePermission(Request $request, $roleId)
    {
        $role = Role::find($roleId);
        if (!$role) {
            return response()->json(['success' => false, 'message' => 'Rol no encontrado'], 404);
        }

        $validated = $request->validate([
            'permission' => 'required|string|exists:permissions,name',
        ]);

        $permission = Permission::where('name', $validated['permission'])->first();

        if (!$role->hasPermissionTo($permission)) {
            return response()->json([
                'success' => false,
                'message' => 'El rol no tiene este permiso',
            ], 400);
        }

        $role->revokePermissionTo($permission);

        return response()->json([
            'success' => true,
            'message' => 'Permiso revocado correctamente del rol',
            'data' => $role->permissions,
        ]);
    }
}
