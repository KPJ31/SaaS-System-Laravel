<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreSubscriptionPlanRequest extends FormRequest
{
    public function rules(): array
    {
        $planId = $this->route('subscriptionPlan')?->id;

        return [
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'alpha_dash', 'max:140', Rule::unique('subscription_plans', 'slug')->ignore($planId)],
            'description' => ['nullable', 'string', 'max:2000'],
            'monthly_price' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'annual_price' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'employee_limit' => ['required', 'integer', 'min:1', 'max:100000'],
            'client_limit' => ['required', 'integer', 'min:1', 'max:100000'],
            'project_limit' => ['required', 'integer', 'min:1', 'max:100000'],
            'storage_limit_mb' => ['required', 'integer', 'min:100', 'max:1048576'],
            'trial_days' => ['required', 'integer', 'min:0', 'max:365'],
            'features' => ['nullable', 'string', 'max:4000'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'display_order' => ['required', 'integer', 'min:0', 'max:9999'],
        ];
    }

    public function validated($key = null, $default = null): mixed
    {
        $data = parent::validated($key, $default);

        if (is_array($data)) {
            $data['slug'] = Str::slug($data['slug'] ?: $data['name']);
            $data['features'] = collect(preg_split('/\r\n|\r|\n/', $data['features'] ?? ''))
                ->map(fn (string $feature): string => trim($feature))
                ->filter()
                ->values()
                ->all();
        }

        return $data;
    }
}
