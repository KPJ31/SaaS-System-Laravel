<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class StoreCompanyRegistrationRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'company_name' => trim((string) $this->input('company_name')),
            'company_email' => Str::lower(trim((string) $this->input('company_email'))),
            'company_phone' => trim((string) $this->input('company_phone')),
            'company_address' => trim((string) $this->input('company_address')),
            'website' => filled($this->input('website')) ? trim((string) $this->input('website')) : null,
            'admin_name' => trim((string) $this->input('admin_name')),
            'admin_email' => Str::lower(trim((string) $this->input('admin_email'))),
            'username' => Str::lower(trim((string) $this->input('username'))),
        ]);
    }

    public function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'max:255'],
            'company_email' => ['required', 'email', 'max:255', 'unique:companies,email', 'unique:company_registration_requests,company_email'],
            'company_phone' => ['required', 'string', 'max:30'],
            'company_address' => ['required', 'string', 'max:2000'],
            'website' => ['nullable', 'url', 'max:255'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'email', 'max:255', 'unique:users,email', 'unique:company_registration_requests,admin_email'],
            'username' => ['required', 'alpha_dash', 'max:60', 'unique:users,username', 'unique:company_registration_requests,username'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'terms' => ['accepted'],
        ];
    }
}
