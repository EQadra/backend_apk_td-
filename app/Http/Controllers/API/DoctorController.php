<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\Traits\UploadImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DoctorController extends Controller
{
    use UploadImage;
    
    /**
     * LISTADO
     */
    public function index()
    {
        return Doctor::with([
            'user',
            'feedbacks.user',
            'posts.comments',
            'services'
        ])
        ->latest()
        ->get();
    }

    /**
     * CREAR
     */
    public function store(Request $request)
    {
        $request->validate([
            'first_name'      => 'required|string|max:100',
            'last_name'       => 'required|string|max:100',
            'description'     => 'nullable|string',
            'career'          => 'nullable|string|max:255',
            'specialty'       => 'nullable|string|max:255',
            'graduate_code'   => 'nullable|string|max:50',
            'services'        => 'nullable|string',
            'city'            => 'nullable|string|max:100',
            'university'      => 'nullable|string|max:255',
            'image'           => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'schedule'        => 'nullable|string',
            'phone'           => 'nullable|string|max:20',
            'emergency_phone' => 'nullable|string|max:20',
            'clinic_phone'    => 'nullable|string|max:20',
        ]);

        $doctor = Doctor::create([
            'user_id'        => Auth::id(),
            'first_name'     => $request->first_name,
            'last_name'      => $request->last_name,
            'description'    => $request->description,
            'career'         => $request->career,
            'specialty'      => $request->specialty,
            'graduate_code'  => $request->graduate_code,
            'services'       => $request->services,
            'city'           => $request->city,
            'university'     => $request->university,
            'schedule'       => $request->schedule,
            'phone'          => $request->phone,
            'emergency_phone' => $request->emergency_phone,
            'clinic_phone'   => $request->clinic_phone,
        ]);

        // Si hay imagen, subirla
        if ($request->hasFile('image')) {
            $this->uploadImageToProduction($request, $doctor, 'doctors');
        }

        // Cargar relaciones
        $doctor->load([
            'user',
            'feedbacks.user',
            'posts.comments',
            'services'
        ]);

        return response()->json([
            'message' => 'Doctor creado correctamente',
            'data' => $doctor
        ], 201);
    }

    /**
     * VER
     */
    public function show($id)
    {
        return Doctor::with([
            'user',
            'feedbacks',
            'posts',
            'services'
        ])->findOrFail($id);
    }

    /**
     * ACTUALIZAR
     */
    public function update(Request $request, $id)
    {
        $doctor = Doctor::findOrFail($id);

        if ($doctor->user_id !== Auth::id() && !Auth::user()->hasRole('admin')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'first_name'      => 'sometimes|string|max:100',
            'last_name'       => 'sometimes|string|max:100',
            'description'     => 'nullable|string',
            'career'          => 'nullable|string|max:255',
            'specialty'       => 'nullable|string|max:255',
            'graduate_code'   => 'nullable|string|max:50',
            'services'        => 'nullable|string',
            'city'            => 'nullable|string|max:100',
            'university'      => 'nullable|string|max:255',
            'image'           => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'schedule'        => 'nullable|string',
            'phone'           => 'nullable|string|max:20',
            'emergency_phone' => 'nullable|string|max:20',
            'clinic_phone'    => 'nullable|string|max:20',
        ]);

        // Actualizar datos
        $doctor->update($request->only([
            'first_name',
            'last_name',
            'description',
            'career',
            'specialty',
            'graduate_code',
            'services',
            'city',
            'university',
            'schedule',
            'phone',
            'emergency_phone',
            'clinic_phone',
        ]));

        // Si hay nueva imagen, subirla
        if ($request->hasFile('image')) {
            // Eliminar imagen anterior si existe
            if ($doctor->image) {
                $this->deleteImageFromProduction($doctor->image);
            }
            $this->uploadImageToProduction($request, $doctor, 'doctors');
        }

        // Cargar relaciones
        $doctor->load([
            'user',
            'feedbacks.user',
            'posts.comments',
            'services'
        ]);

        return response()->json([
            'message' => 'Doctor actualizado correctamente',
            'data' => $doctor
        ]);
    }

    /**
     * MI PERFIL
     */
    public function me()
    {
        return Doctor::with([
            'user',
            'feedbacks',
            'posts',
            'services'
        ])
        ->where('user_id', Auth::id())
        ->firstOrFail();
    }

    /**
     * ÚLTIMOS
     */
    public function latest()
    {
        return Doctor::with([
            'user',
            'feedbacks.user',
            'posts',
            'services'
        ])
        ->latest()
        ->take(3)
        ->get();
    }

    /**
     * ELIMINAR
     */
    public function destroy($id)
    {
        $doctor = Doctor::findOrFail($id);

        if ($doctor->user_id !== Auth::id() && !Auth::user()->hasRole('admin')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $this->deleteImageFromProduction($doctor->image);

        $doctor->delete();

        return response()->json([
            'message' => 'Doctor eliminado correctamente'
        ]);
    }

    /**
     * ACTUALIZAR IMAGEN - CORREGIDO
     * 
     * POST /api/doctors/update-image
     */
    public function updateImage(Request $request)
    {
        try {
            $doctor = Doctor::where('user_id', Auth::id())->firstOrFail();

            // Usar el trait para subir la imagen
            $response = $this->uploadImageToProduction(
                $request,
                $doctor,
                'doctors'
            );

            // Si la respuesta es un JsonResponse, extraer los datos
            if ($response instanceof \Illuminate\Http\JsonResponse) {
                $content = $response->getData();
                
                // Verificar si fue exitoso
                if (isset($content->success) && $content->success) {
                    // Obtener la URL de la imagen
                    $imageUrl = $content->data->image_url ?? $content->data->image ?? null;
                    
                    if ($imageUrl) {
                        // Recargar el doctor con relaciones
                        $doctor->refresh();
                        $doctor->load([
                            'user',
                            'feedbacks.user',
                            'posts.comments',
                            'services'
                        ]);
                        
                        return response()->json([
                            'success' => true,
                            'message' => 'Imagen actualizada correctamente',
                            'data' => $doctor,
                            'image' => $imageUrl
                        ], 200);
                    }
                }
                
                return $response;
            }

            return $response;

        } catch (\Exception $e) {
            Log::error('Error al actualizar imagen del doctor: ' . $e->getMessage(), [
                'user_id' => Auth::id(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error al actualizar la imagen',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * BUSCAR DOCTORES
     */
    public function search(Request $request)
    {
        $query = $request->get('q');
        
        if (empty($query)) {
            return response()->json([]);
        }

        $doctors = Doctor::with([
            'user',
            'services'
        ])
        ->where(function($q) use ($query) {
            $q->where('first_name', 'LIKE', "%{$query}%")
              ->orWhere('last_name', 'LIKE', "%{$query}%")
              ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$query}%"])
              ->orWhere('specialty', 'LIKE', "%{$query}%")
              ->orWhere('city', 'LIKE', "%{$query}%")
              ->orWhere('university', 'LIKE', "%{$query}%")
              ->orWhere('description', 'LIKE', "%{$query}%");
        })
        ->latest()
        ->get();

        return response()->json($doctors);
    }

    /**
     * OBTENER IMAGEN DEL DOCTOR (para forzar actualización)
     * 
     * GET /api/doctors/image
     */
    public function getImage(Request $request)
    {
        try {
            $doctor = Doctor::where('user_id', Auth::id())->firstOrFail();

            $imageUrl = $doctor->image;

            // Si la imagen existe, agregar timestamp para evitar caché
            if ($imageUrl) {
                // Verificar si la URL ya tiene parámetros
                $separator = strpos($imageUrl, '?') !== false ? '&' : '?';
                $imageUrl = $imageUrl . $separator . 't=' . time();
            }

            return response()->json([
                'success' => true,
                'image' => $imageUrl,
                'updated_at' => $doctor->updated_at ? $doctor->updated_at->toDateTimeString() : null
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Doctor no encontrado',
                'message' => $e->getMessage()
            ], 404);
        }
    }
}