<?php

namespace App\Http\Controllers;

use App\Models\BusinessInvitation;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class InvitationController extends Controller
{
    /**
     * Handle invitation link when user clicks it
     */
    public function handleInvite($token, Request $request)
    {
        try {
            // Cari invitation berdasarkan token
            $invitation = BusinessInvitation::where('token', $token)
                ->whereNull('revoked_at')
                ->where(function($query) {
                    $query->whereNull('expires_at')
                          ->orWhere('expires_at', '>', now());
                })
                ->where(function($query) {
                    $query->whereNull('max_uses')
                          ->orWhereRaw('uses < max_uses');
                })
                ->with(['business', 'inviter'])
                ->first();

            if (!$invitation) {
                Log::warning('Invalid invitation token accessed', [
                    'token' => $token,
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent()
                ]);

                return redirect('/register')
                    ->with('error', 'Link undangan tidak valid atau sudah kadaluarsa.');
            }

            // Store invitation data dalam session untuk digunakan saat registrasi
            session([
                'invitation_token' => $token,
                'business_id' => $invitation->business_id,
                'invited_by' => $invitation->inviter_id,
                'business_name' => $invitation->business->business_name ?? 'Tim',
                'inviter_name' => $invitation->inviter->name ?? 'Seseorang',
            ]);

            Log::info('Valid invitation accessed', [
                'token' => $token,
                'business_id' => $invitation->business_id,
                'invitation_id' => $invitation->id
            ]);

            // Redirect ke halaman registrasi dengan konteks invitation
            return redirect('/register')
                ->with('invitation_active', true)
                ->with('success', 'Anda diundang untuk bergabung dengan ' . $invitation->business->business_name);

        } catch (\Exception $e) {
            Log::error('Error handling invitation', [
                'token' => $token,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return redirect('/register')
                ->with('error', 'Terjadi kesalahan saat memproses undangan. Silakan coba lagi.');
        }
    }

    /**
     * Show invitation details (for preview or confirmation)
     */
    public function show($token)
    {
        $invitation = BusinessInvitation::where('token', $token)
            ->whereNull('revoked_at')
            ->where(function($query) {
                $query->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
            })
            ->where(function($query) {
                $query->whereNull('max_uses')
                      ->orWhereRaw('uses < max_uses');
            })
            ->with(['business', 'inviter'])
            ->first();

        if (!$invitation) {
            return view('invitation.invalid');
        }

        return view('invitation.show', compact('invitation'));
    }

    /**
     * Accept invitation after user registration/login
     */
    public function accept(Request $request)
    {
        $token = session('invitation_token');

        if (!$token) {
            return redirect('/dashboard')
                ->with('error', 'Tidak ada undangan yang aktif.');
        }

        try {
            $invitation = BusinessInvitation::where('token', $token)
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
                return redirect('/dashboard')
                    ->with('error', 'Undangan tidak valid atau sudah kadaluarsa.');
            }

            $user = Auth::user();

            // Mark invitation as accepted
            $invitation->update([
                'accepted_at' => now(),
                'accepted_user_id' => $user->id,
                'uses' => $invitation->uses + 1,
            ]);

            // Add user to business
            $business = $invitation->business;
            $business->users()->attach($user->id);

            // Log the invitation acceptance activity
            ActivityLog::create([
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

            Log::info('Invitation accepted', [
                'invitation_id' => $invitation->id,
                'user_id' => $user->id,
                'business_id' => $business->id
            ]);

            return redirect('/dashboard')
                ->with('success', 'Selamat datang di ' . $business->business_name . '!');

        } catch (\Exception $e) {
            Log::error('Error accepting invitation', [
                'token' => $token,
                'user_id' => Auth::id(),
                'error' => $e->getMessage()
            ]);

            return redirect('/dashboard')
                ->with('error', 'Terjadi kesalahan saat menerima undangan.');
        }
    }
}
