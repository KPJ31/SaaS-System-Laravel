@php
    $redact = function ($value) use (&$redact) {
        if (! is_array($value)) {
            return $value;
        }

        $safe = [];

        foreach ($value as $key => $item) {
            $normalized = strtolower((string) $key);
            $safe[$key] = str_contains($normalized, 'password')
                || str_contains($normalized, 'token')
                || str_contains($normalized, 'secret')
                || str_contains($normalized, 'smtp')
                ? '[redacted]'
                : $redact($item);
        }

        return $safe;
    };

    $safeValue = $redact($value ?? []);
@endphp

<pre class="bg-light p-3 rounded mb-0">{{ json_encode($safeValue, JSON_PRETTY_PRINT) ?: '{}' }}</pre>
