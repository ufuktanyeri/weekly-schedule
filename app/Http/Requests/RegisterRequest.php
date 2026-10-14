<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\Role;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rules\Unique;

class RegisterRequest extends FormRequest
{
    /**
     * Roles a visitor may request; privileged roles are only granted by admins.
     */
    public const REQUESTABLE_ROLES = [Role::Instructor, Role::Teacher, Role::Editor, Role::Guest];

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge(['email' => Str::lower(trim($this->input('email')))]);
        }
    }

    /**
     * @return array<string, list<string|ValidationRule|Enum|Password|Unique>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email:filter', 'max:255', Rule::unique('admin_users', 'email')],
            'password' => ['required', 'string', 'confirmed', Password::min(8)],
            'requested_role' => ['nullable', Rule::enum(Role::class)->only(self::REQUESTABLE_ROLES)],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
