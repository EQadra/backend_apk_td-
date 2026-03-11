<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /** Listar todos los usuarios con roles y permisos */
    public function index()
    {
        $usuarios = User::with(['roles', 'permissions'])->get();

        return response()->json([
            'success' => true,
            'data'    => $usuarios
        ], 200);
    }

    /** Crear usuario + asignar (roles|permisos) */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'         => 'required|string|max:255',
            'email'        => 'required|email|unique:users,email',
            'password'     => 'required|string|min:6',
            'roles'        => 'array',
            'roles.*'      => 'string|exists:roles,name',
            'permissions'  => 'array',
            'permissions.*'=> 'string|exists:permissions,name',
        ]);

        $user = User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        if (!empty($validated['roles'])) {
            $user->assignRole($validated['roles']);
        }
        if (!empty($validated['permissions'])) {
            $user->givePermissionTo($validated['permissions']);
        }

        return response()->json([
            'success' => true,
            'message' => 'Usuario creado correctamente',
            'data'    => $user->load(['roles', 'permissions'])
        ], 201);
    }

    /** Mostrar un usuario por ID */
    public function show(int $id)
    {
        $user = User::with(['roles', 'permissions'])->find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $user
        ], 200);
    }

    /** Actualizar usuario + sincronizar roles/permisos */
    public function update(Request $request, int $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado'
            ], 404);
        }

        $validated = $request->validate([
            'name'         => 'sometimes|required|string|max:255',
            'email'        => ['sometimes','required','email', Rule::unique('users')->ignore($user->id)],
            'password'     => 'nullable|string|min:6',
            'roles'        => 'array',
            'roles.*'      => 'string|exists:roles,name',
            'permissions'  => 'array',
            'permissions.*'=> 'string|exists:permissions,name',
        ]);

        if (isset($validated['name'])) {
            $user->name = $validated['name'];
        }
        if (isset($validated['email'])) {
            $user->email = $validated['email'];
        }
        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }
        $user->save();

        if (array_key_exists('roles', $validated)) {
            $user->syncRoles($validated['roles'] ?? []);
        }
        if (array_key_exists('permissions', $validated)) {
            $user->syncPermissions($validated['permissions'] ?? []);
        }

        return response()->json([
            'success' => true,
            'message' => 'Usuario actualizado correctamente',
            'data'    => $user->load(['roles','permissions'])
        ], 200);
    }

    /** Eliminar usuario por ID */
    public function destroy(int $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Usuario no encontrado'
            ], 404);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Usuario eliminado correctamente',
            'id'      => $id
        ], 200);
    }
}
