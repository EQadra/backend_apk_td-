<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Association;
use App\Models\Lawyer;
use App\Models\Doctor;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

class AuthController extends Controller
{
    /* =======================
     | LOGIN CON REMEMBER ME
     ======================= */

    public function login(Request $request)
    {
        Log::info('🟢 LOGIN HIT', [
            'ip' => $request->ip(),
            'email' => $request->email,
        ]);

        $credentials = $request->only('email', 'password');

        // ✅ CONFIGURAR TTL SEGÚN REMEMBER ME
        $ttl = config('jwt.ttl', 1440);
        
        // ✅ SI EL USUARIO MARCA "REMEMBER ME", AUMENTAR EL TTL
        $rememberMe = $request->boolean('remember_me', true);
        if ($rememberMe) {
            $ttl = 43200; // 30 días
        }

        // ✅ CAMBIAR EL TTL ANTES DE AUTENTICAR
        auth()->factory()->setTTL($ttl);

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
            'user_id' => $user->id,
            'remember_me' => $rememberMe,
            'ttl' => $ttl,
        ]);

        $user->update([
            'current_token' => $token
        ]);

        $user->load(['doctor', 'lawyer', 'association', 'shop']);

        $formattedUser = $this->formatUserResponse($user);

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => auth()->factory()->getTTL() * 60,
            'user' => $formattedUser,
        ]);
    }

    /* =======================
     | REGISTER CON CAMPO SEXO (LGBT+ INCLUIDO)
     ======================= */
    
    public function register(Request $request)
    {
        Log::info('📝 REGISTRO INICIADO', [
            'email' => $request->email,
            'role' => $request->type ?? 'usuario',
            'sexo' => $request->sexo ?? 'no_especificado',
        ]);

        // ✅ VALIDACIÓN CON SEXO (INCLUYE LGBT+)
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|confirmed|min:8',
            'phone'    => 'nullable|string|max:20',
            'dni'      => 'nullable|string|max:20',
            'avatar'   => 'nullable|string|max:500',
            'sexo'     => 'nullable|string|in:masculino,femenino,lgbt,otro,no_especificado',
        ]);

        // ✅ 1. CREAR USUARIO CON SEXO Y AVATAR
        $userData = [
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'dni' => $request->dni,
            'avatar' => $request->avatar,
            'sexo' => $request->sexo ?? 'no_especificado',
        ];

        $user = User::create($userData);

        Log::info('✅ USUARIO CREADO', [
            'user_id' => $user->id,
            'avatar' => $request->avatar,
            'sexo' => $user->sexo,
        ]);

        // ✅ 2. CREAR PERFIL SEGÚN ROL CON SEXO
        if ($request->licencia) {
            // ✅ ABOGADO
            $lawyerData = [
                'user_id'     => $user->id,
                'first_name'  => $request->first_name ?? $request->name,
                'last_name'   => $request->last_name ?? '',
                'license_code'=> $request->licencia,
                'phone'       => $request->phone ?? null,
                'office_phone'=> $request->office_phone ?? null,
                'image'       => $request->avatar,
                'sexo'        => $request->sexo ?? 'no_especificado',
            ];
            
            if ($request->specialty) $lawyerData['specialty'] = $request->specialty;
            if ($request->city) $lawyerData['city'] = $request->city;
            if ($request->university) $lawyerData['university'] = $request->university;
            if ($request->description) $lawyerData['description'] = $request->description;
            if ($request->schedule) $lawyerData['schedule'] = $request->schedule;
            
            Lawyer::create($lawyerData);
            $user->assignRole('lawyer');
            Log::info('✅ PERFIL ABOGADO CREADO', ['user_id' => $user->id]);
        } 
        elseif ($request->codigoDoctor) {
            // ✅ DOCTOR
            $doctorData = [
                'user_id'         => $user->id,
                'first_name'      => $request->first_name ?? $request->name,
                'last_name'       => $request->last_name ?? '',
                'degree'          => $request->degree ?? 'Médico',
                'specialty'       => $request->specialty ?? 'General',
                'graduation_code' => $request->codigoDoctor,
                'phone'           => $request->phone ?? null,
                'emergency_phone' => $request->emergency_phone ?? null,
                'clinic_phone'    => $request->clinic_phone ?? null,
                'image'           => $request->avatar,
                'sexo'            => $request->sexo ?? 'no_especificado',
            ];
            
            if ($request->city) $doctorData['city'] = $request->city;
            if ($request->university) $doctorData['university'] = $request->university;
            if ($request->description) $doctorData['description'] = $request->description;
            if ($request->schedule) $doctorData['schedule'] = $request->schedule;
            
            Doctor::create($doctorData);
            $user->assignRole('doctor');
            Log::info('✅ PERFIL DOCTOR CREADO', ['user_id' => $user->id]);
        } 
        elseif ($request->ruc) {
            // ✅ ASOCIACIÓN o TIENDA
            if ($request->type === 'asociacion') {
                Association::create([
                    'user_id' => $user->id,
                    'name'    => $request->name,
                    'ruc'     => $request->ruc,
                    'phone'   => $request->phone ?? null,
                    'image'   => $request->avatar,
                    'city'    => $request->city ?? null,
                    'address' => $request->address ?? null,
                    'description' => $request->description ?? null,
                    'website' => $request->website ?? null,
                    'sexo'    => $request->sexo ?? 'no_especificado',
                ]);
                $user->assignRole('association');
                Log::info('✅ PERFIL ASOCIACIÓN CREADO', ['user_id' => $user->id]);
            }
            elseif ($request->type === 'tienda') {
                Shop::create([
                    'user_id' => $user->id,
                    'name'    => $request->name,
                    'ruc'     => $request->ruc,
                    'phone'   => $request->phone ?? null,
                    'image'   => $request->avatar,
                    'city'    => $request->city ?? null,
                    'address' => $request->address ?? null,
                    'description' => $request->description ?? null,
                    'category' => $request->category ?? null,
                    'schedule' => $request->schedule ?? null,
                    'sexo'     => $request->sexo ?? 'no_especificado',
                ]);
                $user->assignRole('shop');
                Log::info('✅ PERFIL TIENDA CREADO', ['user_id' => $user->id]);
            }
        } else {
            // ✅ USUARIO NORMAL
            $user->assignRole('user');
            Log::info('✅ USUARIO NORMAL CREADO', ['user_id' => $user->id]);
        }

        // ✅ 3. GENERAR TOKEN
        $token = Auth::guard('api')->login($user);

        // ✅ 4. CARGAR RELACIONES
        $user->load(['doctor', 'lawyer', 'association', 'shop']);

        // ✅ 5. FORMATEAR RESPUESTA
        $formattedUser = $this->formatUserResponse($user);

        Log::info('🎉 REGISTRO COMPLETADO', [
            'user_id' => $user->id,
            'role' => $user->profile_type,
            'sexo' => $user->sexo,
        ]);

        return response()->json([
            'message' => 'Registro exitoso',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => Auth::guard('api')->factory()->getTTL() * 60,
            'user' => $formattedUser
        ], 201);
    }

    /* =======================
     | ME - OBTENER USUARIO
     ======================= */

    public function me()
    {
        try {
            $user = Auth::guard('api')->user();

            if (!$user) {
                return response()->json([
                    'message' => 'Usuario no autenticado'
                ], 401);
            }

            $userData = $user->toArray();
            
            $userData['phone'] = $user->phone ?? '';
            $userData['dni'] = $user->dni ?? '';
            $userData['address'] = $user->address ?? '';
            $userData['city'] = $user->city ?? '';
            $userData['sexo'] = $user->sexo ?? 'no_especificado';
            $userData['avatar_url'] = $user->avatar_url;

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

        } catch (\Exception $e) {
            Log::error('❌ Error en me(): ' . $e->getMessage());
            return response()->json([
                'message' => 'Error al obtener el usuario'
            ], 500);
        }
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
     | REFRESH TOKEN - CORREGIDO
     ======================= */
    public function refresh(Request $request)
    {
        try {
            // ✅ VERIFICAR QUE EL TOKEN EXISTA
            $token = $request->bearerToken();
            
            if (!$token) {
                Log::warning('⚠️ Token no proporcionado para refresh');
                return response()->json([
                    'message' => 'Token no proporcionado'
                ], 401);
            }

            // ✅ ESTABLECER EL TOKEN
            auth()->setToken($token);
            
            // ✅ VERIFICAR QUE EL TOKEN SEA VÁLIDO
            if (!auth()->check()) {
                Log::warning('⚠️ Token inválido para refresh');
                return response()->json([
                    'message' => 'Token inválido'
                ], 401);
            }

            // ✅ OBTENER USUARIO
            $user = auth()->user();
            
            if (!$user) {
                return response()->json([
                    'message' => 'Usuario no encontrado'
                ], 404);
            }

            // ✅ REFRESCAR TOKEN
            $newToken = auth()->refresh();

            // ✅ CARGAR RELACIONES
            $user->load(['doctor', 'lawyer', 'association', 'shop']);

            // ✅ FORMATEAR RESPUESTA
            $formattedUser = $this->formatUserResponse($user);

            Log::info('✅ Token refrescado exitosamente', [
                'user_id' => $user->id,
            ]);

            return response()->json([
                'access_token' => $newToken,
                'token_type' => 'Bearer',
                'expires_in' => auth()->factory()->getTTL() * 60,
                'user' => $formattedUser,
            ]);

        } catch (TokenExpiredException $e) {
            Log::error('❌ Token expirado al refrescar', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Token expirado. Inicia sesión nuevamente.'
            ], 401);
            
        } catch (TokenInvalidException $e) {
            Log::error('❌ Token inválido al refrescar', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Token inválido. Inicia sesión nuevamente.'
            ], 401);
            
        } catch (\Exception $e) {
            Log::error('❌ Error al refrescar token', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'message' => 'Error al refrescar el token: ' . $e->getMessage()
            ], 401);
        }
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
     | ACTUALIZAR PERFIL
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
            'sexo'    => 'nullable|string|in:masculino,femenino,lgbt,otro,no_especificado',
        ]);

        $user->update($request->only([
            'name',
            'email',
            'phone',
            'dni',
            'address',
            'city',
            'sexo',
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
     | ACTUALIZAR AVATAR
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
            
            $isDevelopment = env('APP_ENV') === 'local' || env('APP_ENV') === 'development';
            
            if ($isDevelopment) {
                $avatarUrl = 'http://192.168.203.82:8000/imagenes_app/avatars/' . $filename;
            } else {
                $avatarUrl = 'https://apiapk.tudealer.app/imagenes_app/avatars/' . $filename;
            }
            
            $user->update(['avatar' => $avatarUrl]);

            // ✅ También actualizar la imagen del perfil si existe
            $this->updateProfileImage($user, $avatarUrl);

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

    /**
     * Actualizar la imagen del perfil cuando se actualiza el avatar
     */
    private function updateProfileImage($user, $imageUrl)
    {
        if ($user->doctor) {
            $user->doctor->update(['image' => $imageUrl]);
        } elseif ($user->lawyer) {
            $user->lawyer->update(['image' => $imageUrl]);
        } elseif ($user->association) {
            $user->association->update(['image' => $imageUrl]);
        } elseif ($user->shop) {
            $user->shop->update(['image' => $imageUrl]);
        }
    }

    /* =======================
     | ELIMINAR AVATAR
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

            // ✅ También eliminar la imagen del perfil
            if ($user->doctor) {
                $user->doctor->update(['image' => null]);
            } elseif ($user->lawyer) {
                $user->lawyer->update(['image' => null]);
            } elseif ($user->association) {
                $user->association->update(['image' => null]);
            } elseif ($user->shop) {
                $user->shop->update(['image' => null]);
            }

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
            if (isset($userData['profile']['formatted_phone'])) {
                $userData['profile']['formatted_phone'] = $user->doctor->formatted_phone;
            }
        } elseif ($user->lawyer) {
            $userData['profile_type'] = 'lawyer';
            $userData['profile'] = $user->lawyer->toArray();
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