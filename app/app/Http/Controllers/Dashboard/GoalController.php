<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Goal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GoalController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $business = $user->isBusinessOwner() ? $user->primaryBusiness()->first() : $user->businesses()->first();
        $goals = $business ? Goal::where('business_id', $business->id)->orderBy('is_done')->orderByDesc('created_at')->get() : collect();
        return response()->json($goals);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'target_percent' => 'nullable|integer|min:1|max:100',
            'due_date' => 'nullable|date',
        ]);
        $user = Auth::user();
        $business = $user->isBusinessOwner() ? $user->primaryBusiness()->first() : $user->businesses()->first();
        $goal = Goal::create([
            'business_id' => $business->id,
            'title' => $validated['title'],
            'target_percent' => $validated['target_percent'] ?? 100,
            'current_percent' => 0,
            'is_done' => false,
            'due_date' => $validated['due_date'] ?? null,
            'created_by' => $user->id,
        ]);
        return response()->json($goal, 201);
    }

    public function update(Request $request, Goal $goal)
    {
        $this->authorizeGoal($goal);
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'target_percent' => 'sometimes|integer|min:1|max:100',
            'current_percent' => 'sometimes|integer|min:0|max:100',
            'is_done' => 'sometimes|boolean',
            'due_date' => 'nullable|date',
        ]);
        $goal->update($validated);
        return response()->json($goal);
    }

    public function toggle(Goal $goal)
    {
        $this->authorizeGoal($goal);
        $goal->update(['is_done' => !$goal->is_done]);
        return response()->json($goal);
    }

    public function destroy(Goal $goal)
    {
        $this->authorizeGoal($goal);
        $goal->delete();
        return response()->json(['deleted' => true]);
    }

    private function authorizeGoal(Goal $goal)
    {
        $user = Auth::user();
        $business = $user->isBusinessOwner() ? $user->primaryBusiness()->first() : $user->businesses()->first();
        abort_unless($goal->business_id === ($business->id ?? null), 403);
    }
}
