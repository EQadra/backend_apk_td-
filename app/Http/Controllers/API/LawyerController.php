<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Lawyer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LawyerController extends Controller
{
    public function index()
    {
        $lawyers = Lawyer::with([
                'user',
                'feedbacks.user',
                'posts.comments',
                'services' // 🔥 servicios

        ])->latest()->get();
        return response()->json($lawyers);
    }

    public function store(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'specialty' => 'nullable|string|max:255',
            'license_code' => 'nullable|string|max:50',
            'services' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'university' => 'nullable|string|max:255',
            'image' => 'nullable|string',
            'schedule' => 'nullable|string',
        ]);

        $lawyer = Lawyer::create(array_merge($request->all(), ['user_id' => Auth::id()]));

        return response()->json(['message' => 'Lawyer created.', 'data' => $lawyer], 201);
    }

    public function show($id)
    {
        $lawyer = Lawyer::with(['user', 'feedbacks', 'posts','services'])->findOrFail($id);
        return response()->json($lawyer);
    }

    public function update(Request $request, $id)
    {
        $lawyer = Lawyer::findOrFail($id);

        if ($lawyer->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $lawyer->update($request->all());
        return response()->json(['message' => 'Lawyer updated.', 'data' => $lawyer]);
    }

    // GET /api/lawyers/me
public function me()
{
    $lawyer = Lawyer::with(['user', 'feedbacks', 'posts'])
        ->where('user_id', Auth::id())
        ->firstOrFail();

    return response()->json($lawyer);
}


    public function destroy($id)
    {
        $lawyer = Lawyer::findOrFail($id);

        if ($lawyer->user_id !== Auth::id() && !Auth::user()->hasRole('admin')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $lawyer->delete();
        return response()->json(['message' => 'Lawyer deleted.']);
    }

    // GET /api/lawyers/latest
public function latest()
{
    $lawyers = Lawyer::with([
        'user',
        'services'
    ])
    ->latest()
    ->limit(5)
    ->get();

    return response()->json($lawyers);
}

}
