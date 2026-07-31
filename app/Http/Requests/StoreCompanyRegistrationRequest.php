<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCompanyRegistrationRequest extends FormRequest
{
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
