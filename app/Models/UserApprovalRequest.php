<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Role;
use App\Enums\UserStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $admin_user_id
 * @property Role $requested_role
 * @property UserStatus $status
 * @property string|null $note
 * @property int|null $reviewed_by
 * @property Carbon|null $reviewed_at
 * @property string|null $review_note
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read AdminUser $user
 * @property-read AdminUser|null $reviewer
 */
class UserApprovalRequest extends Model
{
    protected $table = 'user_approval_requests';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'admin_user_id',
        'requested_role',
        'status',
        'note',
        'reviewed_by',
        'reviewed_at',
        'review_note',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'requested_role' => Role::class,
            'status' => UserStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<AdminUser, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'admin_user_id');
    }

    /**
     * @return BelongsTo<AdminUser, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'reviewed_by');
    }
}
