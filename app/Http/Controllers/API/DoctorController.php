<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Traits\UploadProfileImage;

class DoctorController extends Controller
{
    use UploadProfileImage;
    
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
            'image'           => 'nullable|string',
            'schedule'        => 'nullable|string',
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
            'image'          => $request->image,
            'schedule'       => $request->schedule,
        ]);

        return response()->json($doctor, 201);
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

        if (
            $doctor->user_id !== Auth::id() &&
            !Auth::user()->hasRole('admin')
        ) {
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
            'image'           => 'nullable|string',
            'schedule'        => 'nullable|string',
        ]);

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
            'image',
            'schedule'
        ]));

        return response()->json([
            'message' => 'Doctor updated',
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

        if (
            $doctor->user_id !== Auth::id() &&
            !Auth::user()->hasRole('admin')
        ) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $doctor->delete();

        return response()->json([
            'message' => 'Doctor deleted'
        ]);
    }

    /**
     * ACTUALIZAR IMAGEN
     */
    public function updateImage(Request $request)
    {
        $doctor = Doctor::where('user_id', Auth::id())
            ->firstOrFail();

        return $this->uploadImage(
            $request,
            $doctor,
            'doctors'
        );
    }

    /**
     * 🔍 BUSCAR DOCTORES
     */
   /**
 * 🔍 BUSCAR DOCTORES
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
          // Búsqueda por nombre completo
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
}