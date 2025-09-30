<?php

namespace App\Http\Controllers;

use App\Models\BusinessInvitation;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;

class BusinessInvitationController extends Controller
{
    public function index(): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (! $user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        if (! $user->isBusinessOwner()) {
            return response()->json(['error' => 'Only business owners can view invitations'], 403);
        }

        $business = $user->ownedBusinesses()->first();

        if (! $business) {
            return response()->json(['error' => 'No business found'], 404);
        }

        try {
            $invitations = $business->invitations()->with(['inviter', 'role', 'acceptedUser'])->latest()->get();

            return response()->json([
                'data' => $invitations->map(fn (BusinessInvitation $invitation) => $this->transformInvitation($invitation)),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to load invitations: ' . $e->getMessage()], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (! $user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        if (! $user->isBusinessOwner()) {
            return response()->json(['error' => 'Only business owners can create invitations'], 403);
        }

        $business = $user->ownedBusinesses()->first();

        if (! $business) {
            return response()->json(['error' => 'No business found'], 404);
        }

        try {
            $validated = $request->validate([
                'email' => ['nullable', 'email'],
                'max_uses' => ['nullable', 'integer', 'min:1'],
                'expires_at' => ['nullable', 'date', 'after:now'],
                'note' => ['nullable', 'string', 'max:255'],
            ]);

            $payload = [
                'inviter_id' => $user->id,
                'role_id' => null, // Role will be chosen during registration
                'email' => $validated['email'] ?? null,
                'max_uses' => $validated['max_uses'] ?? null,
                'expires_at' => isset($validated['expires_at']) ? Carbon::parse($validated['expires_at']) : null,
                'uses' => 0,
                'metadata' => [],
            ];

            if (! empty($validated['note'])) {
                $payload['metadata']['note'] = $validated['note'];
            }

            $invitation = $business->issueInvitation($payload);

            return response()->json([
                'success' => true,
                'data' => $this->transformInvitation($invitation->fresh(['inviter', 'role', 'acceptedUser'])),
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to create invitation: ' . $e->getMessage()], 500);
        }
    }

    public function revoke(BusinessInvitation $invitation): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (! $user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        if (! $user->isBusinessOwner()) {
            return response()->json(['error' => 'Only business owners can revoke invitations'], 403);
        }

        $business = $user->ownedBusinesses()->first();

        if (! $business || $invitation->business_id !== $business->id) {
            return response()->json(['error' => 'Invitation not found'], 404);
        }

        try {
            if (! $invitation->isRevoked()) {
                $invitation->revoke($user);
            }

            return response()->json([
                'success' => true,
                'data' => $this->transformInvitation($invitation->fresh(['inviter', 'role', 'acceptedUser'])),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to revoke invitation: ' . $e->getMessage()], 500);
        }
    }

    protected function transformInvitation(BusinessInvitation $invitation): array
    {
        $status = $this->resolveStatus($invitation);

        return [
            'id' => $invitation->id,
            'token' => $invitation->token,
            'shareable_url' => $this->buildShareableUrl($invitation),
            'email' => $invitation->email,
            'max_uses' => $invitation->max_uses,
            'uses' => $invitation->uses,
            'expires_at' => optional($invitation->expires_at)->toIso8601String(),
            'created_at' => optional($invitation->created_at)->toIso8601String(),
            'accepted_at' => optional($invitation->accepted_at)->toIso8601String(),
            'role' => $invitation->role ? [
                'id' => $invitation->role->id,
                'name' => $invitation->role->name,
                'display_name' => $invitation->role->display_name ?? $invitation->role->name,
            ] : null,
            'accepted_user' => $invitation->acceptedUser ? [
                'id' => $invitation->acceptedUser->id,
                'name' => $invitation->acceptedUser->name,
            ] : null,
            'status' => $status['code'],
            'status_label' => $status['label'],
            'status_badge' => $status['badge'],
        ];
    }

    protected function resolveStatus(BusinessInvitation $invitation): array
    {
        if ($invitation->isRevoked()) {
            return ['code' => 'revoked', 'label' => 'Revoked', 'badge' => 'danger'];
        }

        if ($invitation->isExpired()) {
            return ['code' => 'expired', 'label' => 'Expired', 'badge' => 'secondary'];
        }

        if (! $invitation->hasRemainingUses()) {
            return ['code' => 'consumed', 'label' => 'Maxed Out', 'badge' => 'warning'];
        }

        if ($invitation->accepted_at) {
            return ['code' => 'accepted', 'label' => 'Accepted', 'badge' => 'success'];
        }

        return ['code' => 'active', 'label' => 'Active', 'badge' => 'success'];
    }

    protected function buildShareableUrl(BusinessInvitation $invitation): string
    {
        // Untuk development, gunakan URL yang bisa diakses
        if (config('app.env') === 'local') {
            $baseUrl = 'http://127.0.0.1:8000';
        } else {
            $baseUrl = config('app.frontend_url', config('app.url')) ?? URL::to('/');
        }

        return rtrim($baseUrl, '/') . '/invite/' . $invitation->token;
    }
}
