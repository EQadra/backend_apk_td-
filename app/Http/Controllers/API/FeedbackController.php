<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FeedbackController extends Controller
{
    /**
     * Display all feedbacks (admin or owner view).
     */
    public function index()
    {
        $feedbacks = Feedback::with(['user', 'feedbackable'])->latest()->get();
        return response()->json($feedbacks, 200);
    }

    /**
     * Store new feedback for a polymorphic model.
     * Example: feedbackable_type = 'App\\Models\\Doctor', feedbackable_id = 3
     */
    public function store(Request $request)
    {
        $request->validate([
            'feedbackable_type' => 'required|string',
            'feedbackable_id' => 'required|integer',
            'comment' => 'nullable|string|max:1000',
            'rating' => 'required|numeric|min:1|max:5',
        ]);

        $feedback = Feedback::create([
            'user_id' => Auth::id(),
            'feedbackable_type' => $request->feedbackable_type,
            'feedbackable_id' => $request->feedbackable_id,
            'comment' => $request->comment,
            'rating' => $request->rating,
        ]);

        return response()->json([
            'message' => 'Feedback successfully submitted.',
            'data' => $feedback
        ], 201);
    }

    /**
     * Show a single feedback.
     */
    public function show($id)
    {
        $feedback = Feedback::with(['user', 'feedbackable'])->findOrFail($id);
        return response()->json($feedback, 200);
    }

    /**
     * Delete feedback (owner or admin only).
     */
    public function destroy($id)
    {
        $feedback = Feedback::findOrFail($id);

        if ($feedback->user_id !== Auth::id() && !Auth::user()->hasRole('admin')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $feedback->delete();

        return response()->json(['message' => 'Feedback deleted successfully.'], 200);
    }
}
