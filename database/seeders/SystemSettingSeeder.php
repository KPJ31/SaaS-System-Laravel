<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class SystemSettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            'platform_name' => ['Elevanix', 'string'],
            'platform_abbreviation' => ['EVX', 'string'],
            'support_email' => ['support@example.com', 'string'],
            'support_phone' => ['+1 555 1000', 'string'],
            'platform_address' => ['100 Platform Avenue, Tech City', 'string'],
            'default_plan_slug' => ['starter', 'string'],
            'trial_duration_days' => [14, 'integer'],
            'registration_enabled' => [true, 'boolean'],
            'company_approval_required' => [true, 'boolean'],
            'currency' => ['USD', 'string'],
            'date_format' => ['Y-m-d', 'string'],
            'timezone' => ['UTC', 'string'],
            'default_pagination_size' => [10, 'integer'],
            'subscription_reminder_days' => [7, 'integer'],
            'allow_trial_plan' => [true, 'boolean'],
            'allow_plan_upgrade' => [true, 'boolean'],
            'primary_color' => ['#6D28D9', 'string'],
            'email_sender_name' => ['Elevanix', 'string'],
            'email_sender_email' => ['noreply@example.com', 'string'],
        ];

        foreach ($settings as $key => [$value, $type]) {
            SystemSetting::put($key, $value, $type, 'platform');
        }
    }
}
