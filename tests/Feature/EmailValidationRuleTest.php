<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Defense in depth against CRLF injection in the default "email" rule (advisory fixed in
 * laravel/framework 12.60.0 / 13.10.0). Every e-mail rule must use the "filter" validator,
 * which rejects CR/LF characters.
 */
class EmailValidationRuleTest extends TestCase
{
    public function test_form_requests_only_use_filter_based_email_validation(): void
    {
        $checked = 0;

        foreach (File::allFiles(app_path('Http/Requests')) as $file) {
            $class = 'App\\Http\\Requests\\'.str_replace(['/', '.php'], ['\\', ''], $file->getRelativePathname());
            $this->assertTrue(is_subclass_of($class, FormRequest::class), $class);

            // Instantiated directly: resolving through the container would trigger validation.
            $request = new $class;
            if (! method_exists($request, 'rules')) {
                $this->fail("{$class} has no rules() method.");
            }

            foreach ($request->rules() as $field => $rules) {
                foreach (is_array($rules) ? $rules : explode('|', (string) $rules) as $rule) {
                    if (! is_string($rule) || ($rule !== 'email' && ! str_starts_with($rule, 'email:'))) {
                        continue;
                    }

                    $validators = explode(',', substr($rule, strlen('email:')));
                    $this->assertContains('filter', $validators, "{$class}::{$field} uses \"{$rule}\"; use \"email:filter\".");
                    $checked++;
                }
            }
        }

        $this->assertGreaterThan(0, $checked);
    }
}
