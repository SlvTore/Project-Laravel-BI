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

        // Only get the 3 initial roles as per requirements
        $roles = Role::whereIn('name', ['business-owner', 'staff', 'business-investigator'])
                    ->where('is_active', true)
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
                case 'access_validation':
                    return $this->handleAccessValidationStep($request);
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
        $role = Role::find($request->role_id);
        $user->update(['role_id' => $request->role_id]);

        // Determine next step based on role
        $nextStep = 'business';
        
        if (in_array($role->name, ['staff', 'business-investigator'])) {
            $nextStep = 'access_validation';
        }

        return response()->json([
            'success' => true,
            'next_step' => $nextStep,
            'role_name' => $role->name,
            'message' => 'Role saved successfully'
        ]);
    }

    private function handleAccessValidationStep(Request $request)
    {
        $user = Auth::user();
        $role = $user->userRole;

        if (!$role) {
            return response()->json([
                'success' => false,
                'message' => 'User role not found'
            ], 400);
        }

        if ($role->name === 'staff') {
            // Staff requires both dashboard ID and invitation code
            $validator = validator($request->all(), [
                'dashboard_id' => 'required|string',
                'invitation_code' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Validate dashboard ID and invitation code
            $business = Business::where('public_id', $request->dashboard_id)
                               ->where('invitation_code', $request->invitation_code)
                               ->first();

            if (!$business) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid dashboard ID or invitation code'
                ], 422);
            }

            // Add user to business as staff
            if (!$business->addUserWithRole($user, 'staff')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to join business'
                ], 500);
            }

        } elseif ($role->name === 'business-investigator') {
            // Business Investigator requires only dashboard ID
            $validator = validator($request->all(), [
                'dashboard_id' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Validate dashboard ID
            $business = Business::where('public_id', $request->dashboard_id)->first();

            if (!$business) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid dashboard ID'
                ], 422);
            }

            // Add user to business as investigator
            if (!$business->addUserWithRole($user, 'business-investigator')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to join business'
                ], 500);
            }
        }

        // Mark setup as completed for staff and investigators
        $user->markSetupCompleted();

        return response()->json([
            'success' => true,
            'next_step' => 'complete',
            'redirect' => route('dashboard'),
            'message' => 'Access validation successful'
        ]);
    }

    private function handleBusinessStep(Request $request)
    {
        $user = Auth::user();
        
        // Only Business Owner should reach this step
        if (!$user->hasRole('business-owner')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to business step'
            ], 403);
        }

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

        // Generate public ID and invitation code for Business Owner
        if (!$business->public_id) {
            $business->generatePublicId();
        }
        
        if (!$business->hasValidInvitationCode()) {
            $business->generateInvitationCode();
        }

        // Add business owner to the business_user pivot table
        $business->addUserWithRole($user, 'business-owner');

        return response()->json([
            'success' => true,
            'next_step' => 'goals',
            'message' => 'Business information saved successfully'
        ]);
    }

    private function handleGoalsStep(Request $request)
    {
        $user = Auth::user();
        
        // Only Business Owner should reach this step
        if (!$user->hasRole('business-owner')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access to goals step'
            ], 403);
        }

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
            'business_codes' => [
                'public_id' => $business->public_id,
                'invitation_code' => $business->invitation_code,
            ],
            'message' => 'Setup completed successfully'
        ]);
    }
}
