<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Profile extends Model
{
    use HasUuids;

    protected $table = 'profiles';

    protected $fillable = [
        'first_name',
        'last_name',
        'phone',
        'gender',
        'dob',
        'onbehalf',
        'religion',
        'caste',
        'marital_status',
        'education',
        'profession',
        'city',
        'country',
        'about',
        'mother_tongue',
        'height',
        'children',
        'salary',
        'photo',
        'is_active',
        'plan',
        'email',
        'plan_activated_at',
    ];

    protected function casts(): array
    {
        return [
            'dob' => 'date',
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'plan_activated_at' => 'datetime',
        ];
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProfileImage::class, 'user_id');
    }

    public function sentInterests(): HasMany
    {
        return $this->hasMany(Interest::class, 'sender_id');
    }

    public function receivedInterests(): HasMany
    {
        return $this->hasMany(Interest::class, 'receiver_id');
    }

    public function shortlists(): HasMany
    {
        return $this->hasMany(Shortlist::class, 'owner_id');
    }

    public function ignoredProfiles(): HasMany
    {
        return $this->hasMany(IgnoredProfile::class, 'owner_id');
    }

    public function viewsMade(): HasMany
    {
        return $this->hasMany(ProfileView::class, 'viewer_id');
    }

    public function viewsReceived(): HasMany
    {
        return $this->hasMany(ProfileView::class, 'viewed_id');
    }

    public function quota(): HasOne
    {
        return $this->hasOne(UserQuota::class, 'user_id');
    }

    public function createdConversations(): HasMany
    {
        return $this->hasMany(Conversation::class, 'created_by');
    }

    public function conversations(): BelongsToMany
    {
        return $this->belongsToMany(Conversation::class, 'conversation_participants', 'user_id', 'conversation_id')
            ->withPivot('joined_at');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    public function blocksMade(): HasMany
    {
        return $this->hasMany(UserBlock::class, 'blocker_id');
    }

    public function blockedBy(): HasMany
    {
        return $this->hasMany(UserBlock::class, 'blocked_id');
    }

    public function chatReportsMade(): HasMany
    {
        return $this->hasMany(ChatReport::class, 'reporter_id');
    }

    public function chatReportsAgainst(): HasMany
    {
        return $this->hasMany(ChatReport::class, 'reported_user_id');
    }

    public function supportTickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class, 'user_id');
    }
}
