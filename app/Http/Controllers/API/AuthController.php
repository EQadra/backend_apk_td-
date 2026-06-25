<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\Models\Association;
use App\Models\Lawyer;
use App\Models\Doctor;
use App\Models\Shop;

class AuthController extends Controller
{
    /* =======================
     | LOGIN
     ======================= */

    public function login(Request $request)
    {
        Log::info('🟢 LOGIN HIT', [
            'ip' => $request->ip(),
            'email' => $request->email,
        ]);

        $credentials = $request->only('email', 'password');

        // Validar credenciales
        if (!$token = auth()->attempt($credentials)) {
            Log::warning('🔴 LOGIN FAILED', [
                'email' => $credentials['email'],
            ]);

            return response()->json([
                'message' => 'Credenciales incorrectas'
            ], 401);
        }

        $user = auth()->user();

        Log::info('✅ LOGIN OK', [
            'user_id' => $user->id
        ]);

        // SOLO 1 DISPOSITIVO
        $user->update([
            'current_token' => $token
        ]);

        // Cargar perfiles del usuario
        $user->load(['doctor', 'lawyer', 'association', 'shop']);

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => auth()->factory()->getTTL() * 60,
            'user' => $user,
        ]);
    }

    /* =======================
     | REGISTER
     ======================= */
    
    public function register(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|confirmed|min:8',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // 🔍 DETECCIÓN POR CAMPO
        if ($request->dni) {
            // USUARIO NORMAL
            $user->update(['dni' => $request->dni]);
        } 
        elseif ($request->licencia) {
            // ABOGADO: se requiere first_name y last_name
            Lawyer::create([
                'user_id'     => $user->id,
                'first_name'  => $request->first_name ?? $request->name,
                'last_name'   => $request->last_name ?? '',
                'license_code'=> $request->licencia,
            ]);
        } 
        elseif ($request->codigoDoctor) {
            // DOCTOR: el campo en BD es graduation_code
            Doctor::create([
                'user_id'         => $user->id,
                'first_name'      => $request->first_name,
                'last_name'       => $request->last_name,
                'degree'          => $request->degree ?? 'Médico',
                'specialty'       => $request->specialty ?? 'General',
                'graduation_code' => $request->codigoDoctor,
            ]);
        } 
        elseif ($request->ruc) {
            if ($request->type === 'asociacion') {
                Association::create([
                    'user_id' => $user->id,
                    'name'    => $request->name,
                    'ruc'     => $request->ruc,
                ]);
            }
            if ($request->type === 'tienda') {
                Shop::create([
                    'user_id' => $user->id,
                    'name'    => $request->name,
                    'ruc'     => $request->ruc,
                ]);
            }
        }

        $token = Auth::guard('api')->login($user);

        // Cargar perfiles del usuario
        $user->load(['doctor', 'lawyer', 'association', 'shop']);

        return response()->json([
            'message' => 'Registro exitoso',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => Auth::guard('api')->factory()->getTTL() * 60,
            'user' => $user
        ], 201);
    }

    /* =======================
     | ME
     ======================= */
    public function me()
    {
        $user = Auth::guard('api')->user();

        if (!$user) {
            return response()->json([
                'message' => 'Usuario no autenticado'
            ], 401);
        }

        // Cargar perfiles del usuario con with
        $user = User::with(['doctor', 'lawyer', 'association', 'shop'])->find($user->id);

        return response()->json($user);
    }
    
    /* =======================
     | LOGOUT
     ======================= */
    public function logout()
    {
        Auth::guard('api')->logout();

        return response()->json(['message' => 'Sesión cerrada correctamente']);
    }

    /* =======================
     | REFRESH TOKEN
     ======================= */
    public function refresh()
    {
        $user = Auth::guard('api')->user();
        $user->load(['doctor', 'lawyer', 'association', 'shop']);

        return response()->json([
            'access_token' => Auth::guard('api')->refresh(),
            'token_type' => 'Bearer',
            'expires_in' => Auth::guard('api')->factory()->getTTL() * 60,
            'user' => $user,
        ]);
    }

    /* =======================
     | FORGOT PASSWORD
     ======================= */
    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => ['required', 'email']]);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        return $status === Password::RESET_LINK_SENT
            ? response()->json(['message' => __($status)])
            : response()->json(['message' => __($status)], 422);
    }

    /* =======================
     | RESET PASSWORD
     ======================= */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token'    => ['required'],
            'email'    => ['required', 'email'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        return $status === Password::PASSWORD_RESET
            ? response()->json(['message' => 'Contraseña actualizada'])
            : response()->json(['message' => __($status)], 422);
    }

    /* =======================
     | CHANGE PASSWORD
     ======================= */
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required'],
            'password'         => ['required', 'confirmed', 'min:8'],
        ]);

        $user = Auth::guard('api')->user();

        if (!Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['La contraseña actual no es correcta'],
            ]);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return response()->json(['message' => 'Contraseña cambiada correctamente']);
    }

    /* =======================
     | ✅ ACTUALIZAR AVATAR (NUEVO)
     ======================= */
    
    public function updateAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpg,jpeg,png,webp|max:4096',
        ]);

        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'message' => 'Usuario no autenticado'
            ], 401);
        }

        try {
            // Eliminar avatar anterior si existe
            if ($user->avatar) {
                $oldPath = str_replace('https://apiapk.tudealer.app/', '', $user->avatar);
                $fullPath = '/home1/icjmeomy/apiapk.tudealer.app/public/' . $oldPath;
                if (file_exists($fullPath)) {
                    unlink($fullPath);
                }
            }

            $file = $request->file('avatar');
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $destinationPath = '/home1/icjmeomy/apiapk.tudealer.app/public/imagenes_app/avatars';
            
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }
            
            $file->move($destinationPath, $filename);
            $avatarUrl = 'https://apiapk.tudealer.app/imagenes_app/avatars/' . $filename;
            
            $user->update(['avatar' => $avatarUrl]);

            // Recargar usuario con perfiles
            $user->load(['doctor', 'lawyer', 'association', 'shop']);

            return response()->json([
                'success' => true,
                'message' => 'Avatar actualizado correctamente',
                'data' => [
                    'avatar' => $avatarUrl,
                    'user' => $user
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error al subir avatar: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al subir la imagen',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /* =======================
     | ✅ ELIMINAR AVATAR (NUEVO)
     ======================= */
    
    public function deleteAvatar(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'message' => 'Usuario no autenticado'
            ], 401);
        }

        try {
            // Eliminar avatar anterior si existe
            if ($user->avatar) {
                $oldPath = str_replace('https://apiapk.tudealer.app/', '', $user->avatar);
                $fullPath = '/home1/icjmeomy/apiapk.tudealer.app/public/' . $oldPath;
                if (file_exists($fullPath)) {
                    unlink($fullPath);
                }
            }

            $user->update(['avatar' => null]);

            // Recargar usuario con perfiles
            $user->load(['doctor', 'lawyer', 'association', 'shop']);

            return response()->json([
                'success' => true,
                'message' => 'Avatar eliminado correctamente',
                'data' => $user
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error al eliminar avatar: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la imagen',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /* =======================
     | RESPUESTA TOKEN
     ======================= */
    protected function respondWithToken($token)
    {
        $user = Auth::guard('api')->user();
        $user->load(['doctor', 'lawyer', 'association', 'shop']);

        return response()->json([
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'expires_in'   => Auth::guard('api')->factory()->getTTL() * 60,
            'user'         => $user,
        ]);
    }
}