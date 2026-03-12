<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DoctorController extends Controller
{
    public function index()
    {
        $doctors = Doctor::with([
                'user',
                'feedbacks.user',
                'posts.comments',
                'services' // 🔥 servicios
            ])->latest()->get();
        return response()->json($doctors);
    }

    public function store(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'career' => 'nullable|string|max:255',
            'specialty' => 'nullable|string|max:255',
            'graduate_code' => 'nullable|string|max:50',
            'services' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'university' => 'nullable|string|max:255',
            'image' => 'nullable|string',
            'schedule' => 'nullable|string',
        ]);

        $doctor = Doctor::create(array_merge($request->all(), ['user_id' => Auth::id()]));

        return response()->json(['message' => 'Doctor created.', 'data' => $doctor], 201);
    }

    public function show($id)
    {
        $doctor = Doctor::with(['user', 'feedbacks', 'posts','services'])->findOrFail($id);
        return response()->json($doctor);
    }

    public function update(Request $request, $id)
    {
        $doctor = Doctor::findOrFail($id);

        if ($doctor->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $doctor->update($request->all());
        return response()->json(['message' => 'Doctor updated.', 'data' => $doctor]);
    }

            // GET /api/doctors/me
        public function me()
        {
            $doctor = Doctor::with(['user', 'feedbacks', 'posts'])
                ->where('user_id', Auth::id())
                ->firstOrFail();

            return response()->json($doctor);
        }

        // GET /api/doctors/latest
public function latest()
{
    $doctors = Doctor::with([
            'user',
            'feedbacks.user',
            'posts',
            'serviceItems'
        ])
        ->latest()
        ->limit(3)
        ->get();

    return response()->json($doctors);
}



    public function destroy($id)
    {
        $doctor = Doctor::findOrFail($id);

        if ($doctor->user_id !== Auth::id() && !Auth::user()->hasRole('admin')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $doctor->delete();
        return response()->json(['message' => 'Doctor deleted.']);
    }
}
