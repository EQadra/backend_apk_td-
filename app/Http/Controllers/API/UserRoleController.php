<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;

class UserRoleController extends Controller
{
    /**
     * Asignar un rol a un usuario
     */
    public function assignRole(Request $request, User $user)
    {
        $validated = $request->validate([
            'role' => 'required|exists:roles,name',
        ]);

        $user->assignRole($validated['role']);

        return response()->json([
            'success' => true,
            'message' => 'Rol asignado correctamente',
            'roles'   => $user->getRoleNames(),
        ], 200);
    }

    /**
     * Revocar un rol a un usuario
     */
    public function revokeRole(Request $request, User $user)
    {
        $validated = $request->validate([
            'role' => 'required|exists:roles,name',
        ]);

        $user->removeRole($validated['role']);

        return response()->json([
            'success' => true,
            'message' => 'Rol removido correctamente',
            'roles'   => $user->getRoleNames(),
        ], 200);
    }

    /**
     * Listar roles del usuario
     */
    public function roles(User $user)
    {
        return response()->json([
            'success' => true,
            'roles'   => $user->getRoleNames(),
        ], 200);
    }
}
