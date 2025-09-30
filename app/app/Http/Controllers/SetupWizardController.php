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
    /**
     * Ensure a business has default metrics. If MetricType is empty (e.g., after migrate without seed),
     * seed the default types on the fly, then create BusinessMetric records idempotently.
     */
    private function provisionDefaultMetricsForBusiness(Business $business): void
    {
        // Load available metric types
        $metricTypes = MetricType::active()->ordered()->get();

        // Fallback: seed defaults if none exist (handles migrate without --seed)
        if ($metricTypes->count() === 0) {
            $defaults = [
                [
                    'name' => 'total_penjualan',
                    'display_name' => 'Total Penjualan',
                    'description' => 'Total nilai penjualan yang dihasilkan dalam periode tertentu',
                    'category' => 'Penjualan',
                    'icon' => 'bi-currency-dollar',
                    'unit' => 'Rp',
                    'format' => 'currency',
                    'settings' => [
                        'target_type' => 'monthly',
                        'calculation' => 'sum'
                    ],
                    'is_active' => true,
                    'sort_order' => 1
                ],
                [
                    'name' => 'biaya_pokok_penjualan',
                    'display_name' => 'Biaya Pokok Penjualan (COGS)',
                    'description' => 'Total biaya langsung yang dikeluarkan untuk menghasilkan produk yang dijual',
                    'category' => 'Keuangan',
                    'icon' => 'bi-receipt',
                    'unit' => 'Rp',
                    'format' => 'currency',
                    'settings' => [
                        'target_type' => 'monthly',
                        'calculation' => 'sum'
                    ],
                    'is_active' => true,
                    'sort_order' => 2
                ],
                [
                    'name' => 'margin_keuntungan',
                    'display_name' => 'Margin Keuntungan (Profit Margin)',
                    'description' => 'Persentase keuntungan dari penjualan setelah dikurangi biaya pokok penjualan',
                    'category' => 'Keuangan',
                    'icon' => 'bi-percent',
                    'unit' => '%',
                    'format' => 'percentage',
                    'settings' => [
                        'target_type' => 'monthly',
                        'calculation' => 'percentage'
                    ],
                    'is_active' => true,
                    'sort_order' => 3
                ],
                [
                    'name' => 'penjualan_produk_terlaris',
                    'display_name' => 'Penjualan Produk Terlaris',
                    'description' => 'Jumlah unit produk terlaris yang terjual dalam periode tertentu',
                    'category' => 'Produk',
                    'icon' => 'bi-star-fill',
                    'unit' => 'unit',
                    'format' => 'number',
                    'settings' => [
                        'target_type' => 'monthly',
                        'calculation' => 'count'
                    ],
                    'is_active' => true,
                    'sort_order' => 4
                ],
                [
                    'name' => 'jumlah_pelanggan_baru',
                    'display_name' => 'Jumlah Pelanggan Baru',
                    'description' => 'Jumlah pelanggan baru yang diperoleh dalam periode tertentu',
                    'category' => 'Pelanggan',
                    'icon' => 'bi-person-plus',
                    'unit' => 'orang',
                    'format' => 'number',
                    'settings' => [
                        'target_type' => 'monthly',
                        'calculation' => 'count'
                    ],
                    'is_active' => true,
                    'sort_order' => 5
                ],
                [
                    'name' => 'jumlah_pelanggan_setia',
                    'display_name' => 'Jumlah Pelanggan Setia',
                    'description' => 'Jumlah pelanggan yang melakukan pembelian berulang dalam periode tertentu',
                    'category' => 'Pelanggan',
                    'icon' => 'bi-heart-fill',
                    'unit' => 'orang',
                    'format' => 'number',
                    'settings' => [
                        'target_type' => 'monthly',
                        'calculation' => 'count'
                    ],
                    'is_active' => true,
                    'sort_order' => 6
                ],
            ];

            foreach ($defaults as $d) {
                MetricType::updateOrCreate(['name' => $d['name']], $d);
            }

            $metricTypes = MetricType::active()->ordered()->get();
        }

        // Create business metrics idempotently
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
    }

    public function index()
    {
        // Redirect jika sudah setup
        if (Auth::user()->setup_completed) {
            return redirect()->route('dashboard');
        }

        // Check if user came from invitation
        $hasInvitation = session()->has('invitation_token');
        $businessName = session('business_name');
        $inviterName = session('inviter_name');

        // Only show specific roles for initial selection
        $roles = Role::where('is_active', true)
                    ->whereIn('name', ['business-owner', 'staff', 'business-investigator'])
                    ->get();

        // If user has invitation, exclude business-owner role
        if ($hasInvitation) {
            $roles = $roles->where('name', '!=', 'business-owner');
        }

        return view('wizard', compact('roles', 'hasInvitation', 'businessName', 'inviterName'));
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
                case 'accept_invitation':
                    return $this->handleAcceptInvitationStep($request);
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

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $role = Role::find($request->role_id);
        $user->update(['role_id' => $request->role_id]);

        // Check if user has invitation
        $hasInvitation = session()->has('invitation_token');

        if ($hasInvitation) {
            // User with invitation should directly accept and join business
            return response()->json([
                'success' => true,
                'next_step' => 'accept_invitation',
                'role_name' => $role->name,
                'message' => 'Role saved successfully'
            ]);
        }

        // For staff and investigators without invitation, show invitation modal
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

    /** @var \App\Models\User $user */
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

    // Auto-assign default metrics for the newly created business (always provision)
    $this->provisionDefaultMetricsForBusiness($business);

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

    /** @var \App\Models\User $user */
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
    /** @var \App\Models\User $user */
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

        // Ensure the business has metrics (in case it was created before seeding or by import)
        if ($business->metrics()->count() === 0) {
            $this->provisionDefaultMetricsForBusiness($business);
        }

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

    private function handleAcceptInvitationStep(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $token = session('invitation_token');

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'No active invitation found'
            ], 400);
        }

        try {
            // Find and validate invitation
            $invitation = \App\Models\BusinessInvitation::where('token', $token)
                ->whereNull('revoked_at')
                ->whereNull('accepted_at')
                ->where(function($query) {
                    $query->whereNull('expires_at')
                          ->orWhere('expires_at', '>', now());
                })
                ->where(function($query) {
                    $query->whereNull('max_uses')
                          ->orWhereRaw('uses < max_uses');
                })
                ->first();

            if (!$invitation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invitation is no longer valid'
                ], 400);
            }

            // Mark invitation as accepted
            $invitation->update([
                'accepted_at' => now(),
                'accepted_user_id' => $user->id,
                'uses' => $invitation->uses + 1,
            ]);

            // Add user to business
            $business = $invitation->business;
            $business->users()->attach($user->id);

            // Mark user setup as completed
            $user->update(['setup_completed' => true]);

            // Log the invitation acceptance activity
            \App\Models\ActivityLog::create([
                'business_id' => $business->id,
                'user_id' => $user->id,
                'type' => 'user_joined',
                'title' => 'User Joined via Invitation',
                'description' => "{$user->name} joined {$business->business_name} by accepting an invitation",
                'icon' => 'bi-person-check',
                'color' => 'success',
                'metadata' => json_encode([
                    'invitation_id' => $invitation->id,
                    'inviter_name' => $invitation->inviter->name ?? 'Unknown',
                    'joined_at' => now(),
                    'user_role' => $user->userRole->name ?? 'Unknown'
                ])
            ]);

            // Clear invitation from session
            session()->forget(['invitation_token', 'business_id', 'invited_by', 'business_name', 'inviter_name']);

            Log::info('User accepted invitation and completed setup', [
                'user_id' => $user->id,
                'invitation_id' => $invitation->id,
                'business_id' => $business->id
            ]);

            return response()->json([
                'success' => true,
                'next_step' => 'complete',
                'redirect' => route('dashboard'),
                'message' => 'Welcome to ' . $business->business_name . '!'
            ]);

        } catch (\Exception $e) {
            Log::error('Error accepting invitation in setup', [
                'user_id' => $user->id,
                'token' => $token,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to accept invitation: ' . $e->getMessage()
            ], 500);
        }
    }
}
