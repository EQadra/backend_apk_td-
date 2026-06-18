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
            'duration' => 'nullable|numeric', // ✅ Cambiado de 'string' a 'numeric'
            'serviceable_type' => 'required|string',
            'serviceable_id' => 'required|integer',
        ]);

        $service = Service::create($request->all());

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
            'duration' => 'nullable|numeric', // ✅ Cambiado de 'string' a 'numeric'
        ]);

        $service->update($request->all());

        return response()->json([
            'message' => 'Service updated.',
            'data' => $service
        ]);
    }

    // Eliminar un servicio
    public function destroy($id)
    {
        $service = Service::findOrFail($id);
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
}