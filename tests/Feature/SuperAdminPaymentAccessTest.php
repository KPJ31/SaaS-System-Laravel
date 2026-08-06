<?php

use App\Models\Company;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

function paymentAccessSuperAdmin(array $attributes = []): User
{
    return User::factory()->create(array_merge([
        'password' => Hash::make('Password@123'),
        'role' => 'super_admin',
        'status' => 'active',
    ], $attributes));
}

function paymentAccessCompany(array $attributes = []): Company
{
    return Company::create(array_merge([
        'name' => fake()->unique()->company(),
        'slug' => fake()->unique()->slug(),
        'email' => fake()->unique()->safeEmail(),
        'phone' => '+1 555 0100',
        'address' => '100 Payment Street',
        'status' => 'active',
    ], $attributes));
}

test('super admin payment search does not leak client project payments', function () {
    $admin = paymentAccessSuperAdmin();
    $company = paymentAccessCompany(['name' => 'Client Project Co']);

    Payment::create([
        'company_id' => $company->id,
        'transaction_reference' => 'CLIENT-ONLY-001',
        'payment_type' => 'client_project',
        'amount' => 250,
        'method' => 'bank_transfer',
        'status' => 'received',
    ]);

    Payment::create([
        'company_id' => $company->id,
        'transaction_reference' => 'SUB-ONLY-001',
        'payment_type' => 'subscription',
        'amount' => 49,
        'method' => 'bank_transfer',
        'status' => 'submitted',
    ]);

    $this->actingAs($admin)
        ->get(route('super-admin.payments.index', ['search' => 'ONLY']))
        ->assertOk()
        ->assertSee('SUB-ONLY-001')
        ->assertDontSee('CLIENT-ONLY-001');
});

test('super admin cannot directly view or update client project payments', function () {
    $admin = paymentAccessSuperAdmin();
    $company = paymentAccessCompany();

    $payment = Payment::create([
        'company_id' => $company->id,
        'transaction_reference' => 'CLIENT-DIRECT-001',
        'payment_type' => 'client_project',
        'amount' => 250,
        'method' => 'bank_transfer',
        'status' => 'pending',
    ]);

    $this->actingAs($admin)
        ->get(route('super-admin.payments.show', $payment))
        ->assertNotFound();

    $this->actingAs($admin)
        ->post(route('super-admin.payments.status', [$payment, 'verified']), ['verification_note' => 'Wrong module.'])
        ->assertNotFound();

    $this->assertDatabaseHas('payments', [
        'id' => $payment->id,
        'status' => 'pending',
        'verified_by' => null,
    ]);
});
