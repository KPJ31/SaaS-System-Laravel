<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AuditLogger
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function record(
        string $action,
        string $description,
        ?User $actor = null,
        ?Model $auditable = null,
        ?int $companyId = null,
        array $metadata = [],
        ?Request $request = null
    ): AuditLog {
        return AuditLog::create([
            'company_id' => $companyId ?? $actor?->company_id,
            'user_id' => $actor?->id,
            'action' => $action,
            'auditable_type' => $auditable ? $auditable::class : null,
            'auditable_id' => $auditable?->getKey(),
            'description' => $description,
            'metadata' => $this->sanitize($metadata + [
                'ip' => $request?->ip(),
                'user_agent' => $request?->userAgent(),
            ]),
        ]);
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>
     */
    private function sanitize(array $metadata): array
    {
        return collect($metadata)
            ->reject(fn (mixed $value, string $key): bool => str_contains(strtolower($key), 'password')
                || str_contains(strtolower($key), 'token')
                || str_contains(strtolower($key), 'secret')
                || str_contains(strtolower($key), 'smtp'))
            ->filter(fn (mixed $value): bool => $value !== null)
            ->all();
    }
}
