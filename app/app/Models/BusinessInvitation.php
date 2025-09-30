<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

class BusinessInvitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'inviter_id',
        'role_id',
        'email',
        'token',
        'max_uses',
        'uses',
        'expires_at',
        'accepted_at',
        'accepted_user_id',
        'revoked_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
            'revoked_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $invitation) {
            if (empty($invitation->token)) {
                $invitation->token = self::generateUniqueToken();
            }
        });
    }

    public static function generateUniqueToken(): string
    {
        do {
            $token = Str::random(40);
        } while (self::where('token', $token)->exists());

        return $token;
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function inviter()
    {
        return $this->belongsTo(User::class, 'inviter_id');
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function acceptedUser()
    {
        return $this->belongsTo(User::class, 'accepted_user_id');
    }

    public function scopeActive($query)
    {
        return $query->whereNull('revoked_at')
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', Carbon::now());
            })
            ->where(function ($q) {
                $q->whereNull('max_uses')->orWhereColumn('uses', '<', 'max_uses');
            });
    }

    public function decrementAvailableUses(): void
    {
        if (! is_null($this->max_uses) && $this->uses >= $this->max_uses) {
            return;
        }

        $this->increment('uses');
    }

    public function markAccepted(User $user): void
    {
        $this->update([
            'accepted_user_id' => $user->id,
            'accepted_at' => Carbon::now(),
            'uses' => $this->uses + 1,
        ]);
    }

    public function revoke(?User $byUser = null): void
    {
        $payload = [
            'revoked_at' => Carbon::now(),
        ];

        if ($byUser) {
            $metadata = $this->metadata ?? [];
            $metadata['revoked_by'] = $byUser->id;
            $payload['metadata'] = $metadata;
        }

        $this->update($payload);
    }

    public function isRevoked(): bool
    {
        return ! is_null($this->revoked_at);
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function hasRemainingUses(): bool
    {
        if (is_null($this->max_uses)) {
            return true;
        }

        return $this->uses < $this->max_uses;
    }

    public function isUsable(): bool
    {
        return ! $this->isRevoked() && ! $this->isExpired() && $this->hasRemainingUses();
    }
}
