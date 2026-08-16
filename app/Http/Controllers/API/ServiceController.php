<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ServiceController extends Controller
{
    // Listar todos los servicios
    public function index()
    {
        $services = Service::with(['serviceable', 'comments'])->latest()->get();
        return response()->json($services);
    }

    // Crear un nuevo servicio
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'duration' => 'nullable|numeric',
            'serviceable_type' => 'required|string',
            'serviceable_id' => 'required|integer',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048', // ✅ Validación de imagen
        ]);

        $service = Service::create($request->except('image'));

        // ✅ Si hay imagen, subirla
        if ($request->hasFile('image')) {
            $this->uploadImageToProduction($request, $service, 'services', 'image');
        }

        return response()->json([
            'message' => 'Service created.',
            'data' => $service
        ], 201);
    }

    // Mostrar un servicio específico
    public function show($id)
    {
        $service = Service::with(['serviceable', 'comments'])->findOrFail($id);
        return response()->json($service);
    }

    // Actualizar un servicio
    public function update(Request $request, $id)
    {
        $service = Service::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|required|numeric|min:0',
            'duration' => 'nullable|numeric',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048', // ✅ Validación de imagen
        ]);

        // ✅ Si hay imagen nueva, actualizarla
        if ($request->hasFile('image')) {
            // Eliminar imagen anterior
            if ($service->image) {
                $this->deleteImageFromProduction($service->image);
            }
            
            // Subir nueva imagen
            $this->uploadImageToProduction($request, $service, 'services', 'image');
        }

        // Actualizar el resto de campos
        $service->update($request->except('image'));

        return response()->json([
            'message' => 'Service updated.',
            'data' => $service
        ]);
    }

    // Eliminar un servicio
    public function destroy($id)
    {
        $service = Service::findOrFail($id);
        
        // ✅ Eliminar imagen asociada
        if ($service->image) {
            $this->deleteImageFromProduction($service->image);
        }
        
        $service->delete();

        return response()->json([
            'message' => 'Service deleted.'
        ]);
    }

    // 🔥 Home: últimos 5 servicios
    public function latest()
    {
        $services = Service::with([
            'serviceable',
            'comments'
        ])
        ->latest()
        ->limit(5)
        ->get();

        return response()->json($services);
    }

    public function myLatestServices()
    {
        $user = Auth::user();

        $services = Service::with([
            'serviceable',
            'comments'
        ])
        ->where('serviceable_type', $user->role_to_model())
        ->where('serviceable_id', $user->model()->id)
        ->latest()
        ->take(4)
        ->get();

        return response()->json($services);
    }

    // ✅ NUEVO MÉTODO: Actualizar solo la imagen
    public function updateImage(Request $request, $id)
    {
        $service = Service::findOrFail($id);
        
        // Verificar que el usuario sea el dueño del servicio
        $user = Auth::user();
        if ($service->serviceable_id !== $user->model()->id) {
            return response()->json([
                'error' => 'No tienes permiso para actualizar esta imagen'
            ], 403);
        }

        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048'
        ]);

        // Eliminar imagen anterior
        if ($service->image) {
            $this->deleteImageFromProduction($service->image);
        }

        // Subir nueva imagen
        return $this->uploadImageToProduction($request, $service, 'services', 'image');
    }

    // ✅ NUEVO MÉTODO: Eliminar solo la imagen
    public function deleteImage($id)
    {
        $service = Service::findOrFail($id);
        
        // Verificar que el usuario sea el dueño del servicio
        $user = Auth::user();
        if ($service->serviceable_id !== $user->model()->id) {
            return response()->json([
                'error' => 'No tienes permiso para eliminar esta imagen'
            ], 403);
        }

        if ($service->image) {
            $this->deleteImageFromProduction($service->image);
            $service->update(['image' => null]);
            
            return response()->json([
                'success' => true,
                'message' => 'Imagen eliminada correctamente'
            ]);
        }

        return response()->json([
            'error' => 'El servicio no tiene imagen'
        ], 404);
    }
}