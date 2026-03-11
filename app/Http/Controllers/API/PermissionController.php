<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    // ✅ Listar permisos
    public function index()
    {
        return response()->json([
            'message' => 'Lista de permisos',
            'data'    => Permission::all()
        ], 200);
    }

    // ✅ Crear permiso
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:permissions'
        ]);

        $permission = Permission::create(['name' => $request->name]);

        return response()->json([
            'message' => 'Permiso creado exitosamente',
            'data'    => $permission
        ], 201);
    }

    // ✅ Mostrar un permiso
    public function show(Permission $permission)
    {
        return response()->json([
            'message' => 'Permiso encontrado',
            'data'    => $permission
        ], 200);
    }

    // ✅ Actualizar permiso
    public function update(Request $request, Permission $permission)
    {
        $request->validate([
            'name' => 'required|unique:permissions,name,' . $permission->id
        ]);

        $permission->update(['name' => $request->name]);

        return response()->json([
            'message' => 'Permiso actualizado correctamente',
            'data'    => $permission
        ], 200);
    }

    // ✅ Eliminar permiso
    public function destroy(Permission $permission)
    {
        $permission->delete();

        return response()->json([
            'message' => 'Permiso eliminado correctamente',
            'data'    => null
        ], 200);
    }
}
