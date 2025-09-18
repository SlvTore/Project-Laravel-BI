<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\Business;
use App\Models\MetricType;
use App\Models\BusinessMetric;
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

        // Only show specific roles for initial selection
        $roles = Role::where('is_active', true)
                    ->whereIn('name', ['business-owner', 'staff', 'business-investigator'])
                    ->get();

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
                case 'invitation':
                    return $this->handleInvitationStep($request);
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
        $role = Role::find($request->role_id);
        $user->update(['role_id' => $request->role_id]);

        // For staff and investigators, show invitation modal instead of continuing to business step
        if ($role && in_array($role->name, ['staff', 'business-investigator'])) {
            return response()->json([
                'success' => true,
                'next_step' => 'invitation',
                'role_name' => $role->name,
                'message' => 'Role saved successfully'
            ]);
        }

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

        // Create business for business owner
        $business = $user->ownedBusinesses()->updateOrCreate(
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

        // Generate public_id and invitation_code for business owners
        if ($user->isBusinessOwner()) {
            if (!$business->public_id) {
                $business->generatePublicId();
            }
            if (!$business->invitation_code) {
                $business->generateInvitationCode();
            }
        }

        // Auto-assign default metrics for the newly created business
        // Idempotent: won't duplicate existing metrics
        $metricTypes = MetricType::active()->ordered()->get();
        foreach ($metricTypes as $type) {
            BusinessMetric::firstOrCreate(
                [
                    'business_id' => $business->id,
                    'metric_name' => $type->display_name,
                ],
                [
                    'category' => $type->category,
                    'icon' => $type->icon ?? 'bi-graph-up',
                    'description' => $type->description,
                    'current_value' => 0,
                    'previous_value' => 0,
                    'unit' => $type->unit,
                    'is_active' => true,
                ]
            );
        }

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
        $business = $user->ownedBusinesses()->first();

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

    private function handleInvitationStep(Request $request)
    {
        $user = Auth::user();
        $role = $user->userRole;

        if (!$role || !in_array($role->name, ['staff', 'business-investigator'])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid role for invitation step'
            ], 400);
        }

        $validationRules = [
            'public_id' => 'required|string|exists:businesses,public_id',
        ];

        // Staff members need both public_id and invitation_code
        if ($role->name === 'staff') {
            $validationRules['invitation_code'] = 'required|string';
        }

        $validator = validator($request->all(), $validationRules);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Find the business
        $business = Business::where('public_id', $request->public_id)->first();

        if (!$business) {
            return response()->json([
                'success' => false,
                'message' => 'Business not found'
            ], 404);
        }

        // For staff, validate invitation code
        if ($role->name === 'staff') {
            if ($business->invitation_code !== $request->invitation_code) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid invitation code'
                ], 400);
            }
        }

        // Add user to business
        $business->addUser($user);

        // Mark setup as completed
        $user->markSetupCompleted();

        Log::info('User joined business via invitation', [
            'user_id' => $user->id,
            'business_id' => $business->id,
            'role' => $role->name
        ]);

        return response()->json([
            'success' => true,
            'next_step' => 'complete',
            'redirect' => route('dashboard'),
            'message' => 'Successfully joined business'
        ]);
    }
}
