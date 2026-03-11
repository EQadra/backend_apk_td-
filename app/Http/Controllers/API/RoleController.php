<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{
    /** ✅ Listar roles con permisos */
    public function index()
    {
        return response()->json([
            'success' => true,
            'message' => 'Lista de roles',
            'data'    => Role::with('permissions')->get()
        ], 200);
    }

    /** ✅ Crear rol */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:roles,name',
        ]);

        $role = Role::create([
            'name' => $validated['name'],
            'guard_name' => 'web', // 👈 importante
        ]);

        $role->load('permissions');

        return response()->json([
            'success' => true,
            'message' => 'Rol creado exitosamente',
            'data'    => $role,
        ], 201);
    }

    /** ✅ Mostrar rol con permisos */
    public function show(Role $role)
    {
        $role->load('permissions');

        return response()->json([
            'success' => true,
            'message' => 'Rol encontrado',
            'data'    => $role,
        ], 200);
    }

    /** ✅ Actualizar rol */
    public function update(Request $request, Role $role)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:roles,name,' . $role->id,
        ]);

        $role->update(['name' => $validated['name']]);
        $role->load('permissions');

        return response()->json([
            'success' => true,
            'message' => 'Rol actualizado correctamente',
            'data'    => $role,
        ], 200);
    }

    /** ✅ Eliminar rol */
    public function destroy(Role $role)
    {
        $role->delete();

        return response()->json([
            'success' => true,
            'message' => 'Rol eliminado correctamente',
            'data'    => null,
        ], 200);
    }

    // -----------------------------
    // 🧩 NUEVAS FUNCIONES NECESARIAS
    // -----------------------------

    /** ✅ Obtener permisos de un rol */
    public function permisos($id)
    {
        try {
            $role = Role::findOrFail($id);
            $permissions = $role->permissions()->pluck('name');

            return response()->json([
                'success' => true,
                'message' => 'Permisos del rol cargados correctamente',
                'data'    => $permissions,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener permisos del rol',
                'error'   => $th->getMessage(),
            ], 500);
        }
    }

    /** ✅ Asignar permiso a un rol */
    public function assignPermission(Request $request, $id)
    {
        $validated = $request->validate([
            'permission' => 'required|string|exists:permissions,name',
        ]);

        try {
            $role = Role::findOrFail($id);
            $role->givePermissionTo($validated['permission']);

            return response()->json([
                'success' => true,
                'message' => "Permiso '{$validated['permission']}' asignado correctamente.",
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Error al asignar permiso',
                'error'   => $th->getMessage(),
            ], 500);
        }
    }

    /** ✅ Revocar permiso de un rol */
    public function revokePermission(Request $request, $id)
    {
        $validated = $request->validate([
            'permission' => 'required|string|exists:permissions,name',
        ]);

        try {
            $role = Role::findOrFail($id);
            $role->revokePermissionTo($validated['permission']);

            return response()->json([
                'success' => true,
                'message' => "Permiso '{$validated['permission']}' revocado correctamente.",
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Error al revocar permiso',
                'error'   => $th->getMessage(),
            ], 500);
        }
    }
}
