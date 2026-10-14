<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Weekly Grid
    |--------------------------------------------------------------------------
    |
    | The schedule grid is split into fixed-length slots (30 minutes by
    | default) between the start and end of the teaching day. Days run from
    | Monday (Pazartesi) to Friday (Cuma).
    |
    */

    'day_start' => env('SCHEDULE_DAY_START', '08:30'),

    'day_end' => env('SCHEDULE_DAY_END', '18:00'),

    'slot_minutes' => (int) env('SCHEDULE_SLOT_MINUTES', 30),

    /*
    |--------------------------------------------------------------------------
    | Lunch Break
    |--------------------------------------------------------------------------
    |
    | Slots that overlap the lunch break cannot be used for lessons.
    |
    */

    'lunch_break' => [
        'enabled' => (bool) env('SCHEDULE_LUNCH_ENABLED', true),
        'start' => env('SCHEDULE_LUNCH_START', '12:30'),
        'end' => env('SCHEDULE_LUNCH_END', '14:00'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Workload
    |--------------------------------------------------------------------------
    |
    | Default weekly teaching limit (in hours) for a faculty member. Each
    | faculty member may override it via "haftalik_ders_limiti".
    |
    */

    'max_weekly_hours' => (int) env('SCHEDULE_MAX_WEEKLY_HOURS', 20),

    /*
    |--------------------------------------------------------------------------
    | Super Admin Domains
    |--------------------------------------------------------------------------
    |
    | Accounts whose e-mail address belongs to one of these domains (exact
    | match, subdomains excluded) are granted the "super_admin" role when the
    | address has been verified by the Google OAuth provider.
    |
    */

    'super_admin_domains' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('SCHEDULE_SUPER_ADMIN_DOMAINS', 'ankara.edu.tr')),
    ))),

];
