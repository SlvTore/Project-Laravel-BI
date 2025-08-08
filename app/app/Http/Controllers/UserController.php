<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function index()
    {
        return view('dashboard-users.index');
    }

    public function datatable(Request $request)
    {
        $user = Auth::user();
        $business = $user->primaryBusiness();

        if (!$business) {
            return response()->json([
                'draw' => $request->input('draw'),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => []
            ]);
        }

        // Get all users associated with this business
        $users = $business->users()
                         ->with(['userRole'])
                         ->select('users.*', 'business_user.joined_at', 'business_user.role_id');

        // Apply search if provided
        if ($request->has('search') && !empty($request->input('search.value'))) {
            $searchValue = $request->input('search.value');
            $users->where(function ($query) use ($searchValue) {
                $query->where('users.name', 'like', "%{$searchValue}%")
                      ->orWhere('users.email', 'like', "%{$searchValue}%");
            });
        }

        $recordsTotal = $users->count();
        $recordsFiltered = $recordsTotal;

        // Apply ordering
        if ($request->has('order')) {
            $orderColumn = $request->input('order.0.column');
            $orderDir = $request->input('order.0.dir');
            
            switch ($orderColumn) {
                case 0: // name
                    $users->orderBy('users.name', $orderDir);
                    break;
                case 1: // email
                    $users->orderBy('users.email', $orderDir);
                    break;
                case 3: // joined_date
                    $users->orderBy('business_user.joined_at', $orderDir);
                    break;
                default:
                    $users->orderBy('business_user.joined_at', 'desc');
            }
        } else {
            $users->orderBy('business_user.joined_at', 'desc');
        }

        // Apply pagination
        $start = $request->input('start', 0);
        $length = $request->input('length', 25);
        
        $users = $users->skip($start)->take($length)->get();

        $data = [];
        foreach ($users as $userRecord) {
            $currentUser = Auth::user();
            $actions = '';

            // Only Business Owner and Administrator can manage users
            if ($currentUser->canManageUsers()) {
                // Can promote staff to administrator
                if ($userRecord->isStaff() && $currentUser->canPromoteUsers()) {
                    $actions .= '<button class="btn btn-sm btn-outline-primary me-1" onclick="promoteUser(' . $userRecord->id . ', \'administrator\')" title="Promote to Administrator">
                                   <i class="bi bi-arrow-up-circle"></i>
                                 </button>';
                }

                // Can delete users (except Business Owner)
                if (!$userRecord->isBusinessOwner() && $currentUser->canDeleteUsers()) {
                    $actions .= '<button class="btn btn-sm btn-outline-danger" onclick="deleteUser(' . $userRecord->id . ')" title="Remove User">
                                   <i class="bi bi-trash"></i>
                                 </button>';
                }
            }

            if (empty($actions)) {
                $actions = '<span class="text-muted">No actions</span>';
            }

            $statusClass = $userRecord->is_active ? 'success' : 'danger';
            $statusText = $userRecord->is_active ? 'Active' : 'Inactive';

            $data[] = [
                'name' => $userRecord->name,
                'email' => $userRecord->email,
                'role_display' => $userRecord->userRole ? $userRecord->userRole->display_name : 'No Role',
                'joined_date' => $userRecord->joined_at ? $userRecord->joined_at->format('d M Y') : 'N/A',
                'status' => "<span class='badge bg-{$statusClass}'>{$statusText}</span>",
                'actions' => $actions
            ];
        }

        return response()->json([
            'draw' => $request->input('draw'),
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data' => $data
        ]);
    }

    public function promote(Request $request, User $user)
    {
        $request->validate([
            'role' => 'required|in:administrator,staff',
        ]);

        $currentUser = Auth::user();

        // Check permissions
        if (!$currentUser->canPromoteUsers()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Can't promote Business Owner
        if ($user->isBusinessOwner()) {
            return response()->json(['error' => 'Cannot modify Business Owner role'], 400);
        }

        $business = $currentUser->primaryBusiness();
        if (!$business) {
            return response()->json(['error' => 'No business found'], 404);
        }

        // Promote user
        if ($business->promoteUser($user, $request->role)) {
            return response()->json([
                'success' => true,
                'message' => "User successfully promoted to {$request->role}"
            ]);
        }

        return response()->json(['error' => 'Failed to promote user'], 500);
    }

    public function destroy(User $user)
    {
        $currentUser = Auth::user();

        // Check permissions
        if (!$currentUser->canDeleteUsers()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Can't delete Business Owner
        if ($user->isBusinessOwner()) {
            return response()->json(['error' => 'Cannot delete Business Owner'], 400);
        }

        $business = $currentUser->primaryBusiness();
        if (!$business) {
            return response()->json(['error' => 'No business found'], 404);
        }

        // Remove user from business
        if ($business->removeUser($user)) {
            return response()->json([
                'success' => true,
                'message' => 'User successfully removed from business'
            ]);
        }

        return response()->json(['error' => 'Failed to remove user'], 500);
    }

    public function getBusinessCodes()
    {
        $user = Auth::user();

        if (!$user->isBusinessOwner()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $business = $user->primaryBusiness();
        if (!$business) {
            return response()->json(['error' => 'No business found'], 404);
        }

        // Generate codes if not exist
        if (!$business->public_id) {
            $business->generatePublicId();
        }

        if (!$business->hasValidInvitationCode()) {
            $business->generateInvitationCode();
        }

        return response()->json([
            'public_id' => $business->public_id,
            'invitation_code' => $business->invitation_code,
            'invitation_code_generated_at' => $business->invitation_code_generated_at?->format('d M Y H:i'),
        ]);
    }

    public function refreshInvitationCode()
    {
        $user = Auth::user();

        if (!$user->isBusinessOwner()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $business = $user->primaryBusiness();
        if (!$business) {
            return response()->json(['error' => 'No business found'], 404);
        }

        $newCode = $business->refreshInvitationCode();

        return response()->json([
            'success' => true,
            'invitation_code' => $newCode,
            'invitation_code_generated_at' => $business->invitation_code_generated_at?->format('d M Y H:i'),
            'message' => 'Invitation code refreshed successfully'
        ]);
    }
}