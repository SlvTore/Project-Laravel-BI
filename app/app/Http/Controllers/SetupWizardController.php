<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\Business;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SetupWizardController extends Controller
{
    public function index()
    {
        // Redirect jika sudah setup
        if (Auth::user()->setup_completed) {
            return redirect()->route('dashboard');
        }

        $roles = Role::where('is_active', true)->get();
        return view('wizard', compact('roles'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $step = $request->input('step', 'role');

        Log::info('Setup wizard step', [
            'step' => $step,
            'data' => $request->all(),
            'user_id' => $user->id
        ]);

        try {
            switch ($step) {
                case 'role':
                    return $this->handleRoleStep($request);
                case 'business':
                    return $this->handleBusinessStep($request);
                case 'goals':
                    return $this->handleGoalsStep($request);
                default:
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid step'
                    ], 400);
            }
        } catch (\Exception $e) {
            Log::error('Setup wizard error', [
                'step' => $step,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }

    private function handleRoleStep(Request $request)
    {
        $validator = validator($request->all(), [
            'role_id' => 'required|exists:roles,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Auth::user();
        $user->update(['role_id' => $request->role_id]);

        return response()->json([
            'success' => true,
            'next_step' => 'business',
            'message' => 'Role saved successfully'
        ]);
    }

    private function handleBusinessStep(Request $request)
    {
        $validator = validator($request->all(), [
            'business_name' => 'required|string|max:255',
            'industry' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'founded_date' => 'nullable|date',
            'website' => 'nullable|url',
            'initial_revenue' => 'nullable|numeric|min:0',
            'initial_customers' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Auth::user();

        // Create or update business
        $business = $user->businesses()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'business_name' => $request->business_name,
                'industry' => $request->industry,
                'description' => $request->description,
                'founded_date' => $request->founded_date,
                'website' => $request->website,
                'initial_revenue' => $request->initial_revenue,
                'initial_customers' => $request->initial_customers,
            ]
        );

        return response()->json([
            'success' => true,
            'next_step' => 'goals',
            'message' => 'Business information saved successfully'
        ]);
    }

    private function handleGoalsStep(Request $request)
    {
        $validator = validator($request->all(), [
            'revenue_target' => 'required|numeric|min:0',
            'customer_target' => 'required|integer|min:0',
            'growth_rate_target' => 'required|numeric|min:0|max:100',
            'key_metrics' => 'array',
            'key_metrics.*' => 'string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Auth::user();
        $business = $user->businesses()->first();

        if ($business) {
            $goals = [
                'revenue_target' => $request->revenue_target,
                'customer_target' => $request->customer_target,
                'growth_rate_target' => $request->growth_rate_target,
                'key_metrics' => $request->key_metrics ?? [],
                'target_date' => now()->addYear(),
            ];

            $business->update(['goals' => $goals]);
        }

        // Mark setup as completed
        $user->markSetupCompleted();

        return response()->json([
            'success' => true,
            'next_step' => 'complete',
            'redirect' => route('dashboard'),
            'message' => 'Setup completed successfully'
        ]);
    }
}
