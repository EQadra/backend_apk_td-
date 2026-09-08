<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\Traits\UploadImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ServiceController extends Controller
{
    use UploadImage; // ✅ USAR EL TRAIT UNIFICADO

    // 🔥 LISTAR SERVICIOS (PÚBLICO)
    public function index()
    {
        $services = Service::with(['serviceable', 'comments'])
            ->whereIn('serviceable_type', [
                'App\Models\Lawyer',
                'App\Models\Doctor',
            ])
            ->latest()
            ->limit(6)
            ->get();
            
        return response()->json($services);
    }

    // 🔥 ÚLTIMOS SERVICIOS (PÚBLICO)
    public function latest()
    {
        $services = Service::with([
            'serviceable',
            'comments'
        ])
        ->whereIn('serviceable_type', [
            'App\Models\Lawyer',
            'App\Models\Doctor',
        ])
        ->latest()
        ->limit(5)
        ->get();

        return response()->json($services);
    }

    // 🔥 CREAR SERVICIO
    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'price' => 'required|numeric|min:0',
                'duration' => 'nullable|numeric',
                'serviceable_type' => 'required|string',
                'serviceable_id' => 'required|integer',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            ]);

            // ✅ CREAR SERVICIO
            $service = Service::create($request->except('image'));

            // ✅ SUBIR IMAGEN SI EXISTE
            if ($request->hasFile('image')) {
                $response = $this->uploadImageToProduction($request, $service, 'services', 'image');
                // Si la respuesta es un JsonResponse, ya está manejado
                if ($response instanceof \Illuminate\Http\JsonResponse) {
                    $data = $response->getData();
                    if (!$data->success) {
                        return $response;
                    }
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Servicio creado correctamente',
                'data' => $service->fresh()
            ], 201);

        } catch (\Exception $e) {
            Log::error('❌ Error al crear servicio: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Error al crear el servicio',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // 🔥 MOSTRAR SERVICIO
    public function show($id)
    {
        $service = Service::with(['serviceable', 'comments'])->findOrFail($id);
        return response()->json($service);
    }

    // 🔥 ACTUALIZAR SERVICIO
    public function update(Request $request, $id)
    {
        try {
            $service = Service::findOrFail($id);

            $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'description' => 'nullable|string',
                'price' => 'sometimes|required|numeric|min:0',
                'duration' => 'nullable|numeric',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            ]);

            // ✅ SUBIR NUEVA IMAGEN SI EXISTE
            if ($request->hasFile('image')) {
                $response = $this->uploadImageToProduction($request, $service, 'services', 'image');
                if ($response instanceof \Illuminate\Http\JsonResponse) {
                    $data = $response->getData();
                    if (!$data->success) {
                        return $response;
                    }
                }
            }

            // ✅ ACTUALIZAR DATOS
            $service->update($request->except('image'));

            return response()->json([
                'success' => true,
                'message' => 'Servicio actualizado correctamente',
                'data' => $service->fresh()
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error al actualizar servicio: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Error al actualizar el servicio',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // 🔥 ELIMINAR SERVICIO
    public function destroy($id)
    {
        try {
            $service = Service::findOrFail($id);
            
            // ✅ ELIMINAR IMAGEN
            if ($service->image) {
                $this->deleteImageFromProduction($service->image);
            }
            
            $service->delete();

            return response()->json([
                'success' => true,
                'message' => 'Servicio eliminado correctamente'
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error al eliminar servicio: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Error al eliminar el servicio',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // 🔥 ACTUALIZAR SOLO IMAGEN
    public function updateImage(Request $request, $id)
    {
        try {
            $service = Service::findOrFail($id);
            
            // ✅ VERIFICAR PERMISOS
            $user = Auth::user();
            $isAdmin = $user->hasRole('admin');
            
            // Verificar que el usuario sea el dueño
            $isOwner = false;
            if ($service->serviceable && isset($service->serviceable->user_id)) {
                $isOwner = $service->serviceable->user_id === $user->id;
            }

            if (!$isOwner && !$isAdmin) {
                return response()->json([
                    'success' => false,
                    'error' => 'No tienes permiso para actualizar esta imagen'
                ], 403);
            }

            $request->validate([
                'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048'
            ]);

            // ✅ SUBIR NUEVA IMAGEN
            return $this->uploadImageToProduction($request, $service, 'services', 'image');

        } catch (\Exception $e) {
            Log::error('❌ Error al actualizar imagen del servicio: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Error al actualizar la imagen',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // 🔥 ELIMINAR SOLO IMAGEN
    public function deleteImage($id)
    {
        try {
            $service = Service::findOrFail($id);
            
            // ✅ VERIFICAR PERMISOS
            $user = Auth::user();
            $isAdmin = $user->hasRole('admin');
            
            $isOwner = false;
            if ($service->serviceable && isset($service->serviceable->user_id)) {
                $isOwner = $service->serviceable->user_id === $user->id;
            }

            if (!$isOwner && !$isAdmin) {
                return response()->json([
                    'success' => false,
                    'error' => 'No tienes permiso para eliminar esta imagen'
                ], 403);
            }

            if (!$service->image) {
                return response()->json([
                    'success' => false,
                    'error' => 'El servicio no tiene imagen'
                ], 404);
            }

            // ✅ ELIMINAR IMAGEN
            $this->deleteImageFromProduction($service->image);
            $service->update(['image' => null]);

            return response()->json([
                'success' => true,
                'message' => 'Imagen eliminada correctamente'
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error al eliminar imagen del servicio: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Error al eliminar la imagen',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // 🔥 MIS SERVICIOS
    public function myLatestServices()
    {
        try {
            $user = Auth::user();
            
            // ✅ OBTENER EL MODELO DEL USUARIO
            $model = null;
            if ($user->doctor) {
                $model = $user->doctor;
                $type = 'App\Models\Doctor';
            } elseif ($user->lawyer) {
                $model = $user->lawyer;
                $type = 'App\Models\Lawyer';
            } else {
                return response()->json([]);
            }

            $services = Service::with([
                'serviceable',
                'comments'
            ])
            ->where('serviceable_type', $type)
            ->where('serviceable_id', $model->id)
            ->latest()
            ->take(4)
            ->get();

            return response()->json($services);

        } catch (\Exception $e) {
            Log::error('❌ Error en myLatestServices: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Error al obtener servicios'
            ], 500);
        }
    }
}