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

        // Cargar perfiles del usuario con todos los campos
        $user->load(['doctor', 'lawyer', 'association', 'shop']);

        // Formatear la respuesta del usuario con todos los datos
        $formattedUser = $this->formatUserResponse($user);

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => auth()->factory()->getTTL() * 60,
            'user' => $formattedUser,
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
            'phone'    => 'nullable|string|max:20',
            'dni'      => 'nullable|string|max:20',
        ]);

        $userData = [
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'dni' => $request->dni,
        ];

        $user = User::create($userData);

        // 🔍 DETECCIÓN POR CAMPO
        if ($request->licencia) {
            Lawyer::create([
                'user_id'     => $user->id,
                'first_name'  => $request->first_name ?? $request->name,
                'last_name'   => $request->last_name ?? '',
                'license_code'=> $request->licencia,
                'phone'       => $request->phone ?? null,
                'office_phone'=> $request->office_phone ?? null,
            ]);
            $user->assignRole('lawyer');
        } 
        elseif ($request->codigoDoctor) {
            Doctor::create([
                'user_id'         => $user->id,
                'first_name'      => $request->first_name,
                'last_name'       => $request->last_name,
                'degree'          => $request->degree ?? 'Médico',
                'specialty'       => $request->specialty ?? 'General',
                'graduation_code' => $request->codigoDoctor,
                'phone'           => $request->phone ?? null,
                'emergency_phone' => $request->emergency_phone ?? null,
                'clinic_phone'    => $request->clinic_phone ?? null,
            ]);
            $user->assignRole('doctor');
        } 
        elseif ($request->ruc) {
            if ($request->type === 'asociacion') {
                Association::create([
                    'user_id' => $user->id,
                    'name'    => $request->name,
                    'ruc'     => $request->ruc,
                    'phone'   => $request->phone ?? null,
                ]);
                $user->assignRole('association');
            }
            if ($request->type === 'tienda') {
                Shop::create([
                    'user_id' => $user->id,
                    'name'    => $request->name,
                    'ruc'     => $request->ruc,
                    'phone'   => $request->phone ?? null,
                ]);
                $user->assignRole('shop');
            }
        } else {
            $user->assignRole('user');
        }

        $token = Auth::guard('api')->login($user);

        // Cargar perfiles del usuario
        $user->load(['doctor', 'lawyer', 'association', 'shop']);

        // Formatear la respuesta del usuario con todos los datos
        $formattedUser = $this->formatUserResponse($user);

        return response()->json([
            'message' => 'Registro exitoso',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => Auth::guard('api')->factory()->getTTL() * 60,
            'user' => $formattedUser
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

        // Asegurar que todos los campos estén incluidos
        $userData = $user->toArray();
        
        $userData['phone'] = $user->phone ?? '';
        $userData['dni'] = $user->dni ?? '';
        $userData['address'] = $user->address ?? '';
        $userData['city'] = $user->city ?? '';
        $userData['avatar_url'] = $user->avatar_url;

        // Cargar perfiles del usuario
        $user->load(['doctor', 'lawyer', 'association', 'shop']);
        
        if ($user->doctor) {
            $userData['profile_type'] = 'doctor';
            $userData['profile'] = $user->doctor;
        } elseif ($user->lawyer) {
            $userData['profile_type'] = 'lawyer';
            $userData['profile'] = $user->lawyer;
        } elseif ($user->association) {
            $userData['profile_type'] = 'association';
            $userData['profile'] = $user->association;
        } elseif ($user->shop) {
            $userData['profile_type'] = 'shop';
            $userData['profile'] = $user->shop;
        } else {
            $userData['profile_type'] = 'user';
            $userData['profile'] = null;
        }

        return response()->json($userData);
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

        $formattedUser = $this->formatUserResponse($user);

        return response()->json([
            'access_token' => Auth::guard('api')->refresh(),
            'token_type' => 'Bearer',
            'expires_in' => Auth::guard('api')->factory()->getTTL() * 60,
            'user' => $formattedUser,
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
     | ✅ ACTUALIZAR PERFIL DE USUARIO
     ======================= */
    
    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'message' => 'Usuario no autenticado'
            ], 401);
        }

        $request->validate([
            'name'    => 'sometimes|string|max:255',
            'email'   => 'sometimes|email|unique:users,email,' . $user->id,
            'phone'   => 'nullable|string|max:20',
            'dni'     => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'city'    => 'nullable|string|max:100',
        ]);

        $user->update($request->only([
            'name',
            'email',
            'phone',
            'dni',
            'address',
            'city'
        ]));

        $user->load(['doctor', 'lawyer', 'association', 'shop']);

        $formattedUser = $this->formatUserResponse($user);

        return response()->json([
            'success' => true,
            'message' => 'Perfil actualizado correctamente',
            'data' => $formattedUser
        ], 200);
    }

    /* =======================
     | ✅ ACTUALIZAR AVATAR - CORREGIDO
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
                $oldPath = str_replace(['https://apiapk.tudealer.app/', 'http://192.168.203.82:8000/'], '', $user->avatar);
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
            
            // ✅ DETECTAR ENTORNO PARA LA URL CORRECTA
            $isDevelopment = env('APP_ENV') === 'local' || env('APP_ENV') === 'development';
            
            if ($isDevelopment) {
                $avatarUrl = 'http://192.168.203.82:8000/imagenes_app/avatars/' . $filename;
            } else {
                $avatarUrl = 'https://apiapk.tudealer.app/imagenes_app/avatars/' . $filename;
            }
            
            $user->update(['avatar' => $avatarUrl]);

            $user->load(['doctor', 'lawyer', 'association', 'shop']);

            $formattedUser = $this->formatUserResponse($user);

            return response()->json([
                'success' => true,
                'message' => 'Avatar actualizado correctamente',
                'data' => [
                    'avatar' => $avatarUrl,
                    'user' => $formattedUser
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
     | ✅ ELIMINAR AVATAR - CORREGIDO
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
            if ($user->avatar) {
                $oldPath = str_replace(['https://apiapk.tudealer.app/', 'http://192.168.203.82:8000/'], '', $user->avatar);
                $fullPath = '/home1/icjmeomy/apiapk.tudealer.app/public/' . $oldPath;
                if (file_exists($fullPath)) {
                    unlink($fullPath);
                }
            }

            $user->update(['avatar' => null]);

            $user->load(['doctor', 'lawyer', 'association', 'shop']);

            $formattedUser = $this->formatUserResponse($user);

            return response()->json([
                'success' => true,
                'message' => 'Avatar eliminado correctamente',
                'data' => $formattedUser
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
     | 🔥 FORMATO DE RESPUESTA DEL USUARIO
     ======================= */
    
    private function formatUserResponse($user)
    {
        $userData = $user->toArray();

        if ($user->doctor) {
            $userData['profile_type'] = 'doctor';
            $userData['profile'] = $user->doctor->toArray();
            $userData['profile']['formatted_phone'] = $user->doctor->formatted_phone;
            $userData['profile']['formatted_emergency_phone'] = $user->doctor->formatted_emergency_phone;
            $userData['profile']['formatted_clinic_phone'] = $user->doctor->formatted_clinic_phone;
        } elseif ($user->lawyer) {
            $userData['profile_type'] = 'lawyer';
            $userData['profile'] = $user->lawyer->toArray();
            $userData['profile']['formatted_phone'] = $user->lawyer->formatted_phone;
            $userData['profile']['formatted_office_phone'] = $user->lawyer->formatted_office_phone;
        } elseif ($user->association) {
            $userData['profile_type'] = 'association';
            $userData['profile'] = $user->association->toArray();
        } elseif ($user->shop) {
            $userData['profile_type'] = 'shop';
            $userData['profile'] = $user->shop->toArray();
        } else {
            $userData['profile_type'] = 'user';
            $userData['profile'] = null;
        }

        return $userData;
    }

    /* =======================
     | RESPUESTA TOKEN
     ======================= */
    protected function respondWithToken($token)
    {
        $user = Auth::guard('api')->user();
        $user->load(['doctor', 'lawyer', 'association', 'shop']);

        $formattedUser = $this->formatUserResponse($user);

        return response()->json([
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'expires_in'   => Auth::guard('api')->factory()->getTTL() * 60,
            'user'         => $formattedUser,
        ]);
    }
}