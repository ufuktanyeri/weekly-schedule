<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Enums\UserStatus;
use App\Exceptions\InvalidStatusTransitionException;
use App\Http\Requests\ApproveUserRequest;
use App\Http\Requests\RejectUserRequest;
use App\Models\AdminUser;
use App\Services\AuthService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UserApprovalController extends Controller
{
    public function __construct(private readonly AuthService $auth) {}

    public function index(): View
    {
        return view('approvals.index', [
            'bekleyenler' => AdminUser::query()
                ->with(['approvalRequests' => fn ($q) => $q->latest('id')])
                ->where('status', UserStatus::Pending)
                ->orderBy('created_at')
                ->get(),
            'sonIslemler' => AdminUser::query()
                ->where('status', '!=', UserStatus::Pending)
                ->latest('updated_at')
                ->limit(20)
                ->get(),
            'roller' => Role::cases(),
        ]);
    }

    public function approve(ApproveUserRequest $request, AdminUser $user): RedirectResponse
    {
        try {
            $this->auth->approve(
                $user,
                $this->reviewer($request),
                $request->enum('role', Role::class),
                $request->filled('note') ? $request->string('note')->toString() : null,
            );
        } catch (AuthorizationException|InvalidStatusTransitionException $e) {
            return back()->withErrors(['onay' => $e->getMessage()]);
        }

        return back()->with('status', "{$user->email} onaylandı.");
    }

    public function reject(RejectUserRequest $request, AdminUser $user): RedirectResponse
    {
        try {
            $this->auth->reject(
                $user,
                $this->reviewer($request),
                $request->filled('reason') ? $request->string('reason')->toString() : null,
            );
        } catch (AuthorizationException|InvalidStatusTransitionException $e) {
            return back()->withErrors(['onay' => $e->getMessage()]);
        }

        return back()->with('status', "{$user->email} reddedildi.");
    }

    private function reviewer(Request $request): AdminUser
    {
        $user = $request->user();
        abort_unless($user instanceof AdminUser, 403);

        return $user;
    }
}
