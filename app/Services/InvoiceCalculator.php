<?php

namespace App\Services;

use App\Models\Invoice;
use Illuminate\Support\Collection;

class InvoiceCalculator
{
    public const PAID_PAYMENT_STATUSES = ['paid', 'received', 'verified'];

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array{subtotal: float, tax: float, total: float, items: array<int, array<string, mixed>>}
     */
    public function totalsFromItems(array $items, float $taxAmount = 0.0): array
    {
        $normalized = collect($items)
            ->map(function (array $item): array {
                $quantity = round((float) ($item['quantity'] ?? 1), 2);
                $unitPrice = round((float) ($item['unit_price'] ?? 0), 2);

                return [
                    'description' => trim((string) ($item['description'] ?? '')),
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'line_total' => round($quantity * $unitPrice, 2),
                ];
            })
            ->filter(fn (array $item): bool => $item['description'] !== '')
            ->values();

        $subtotal = round((float) $normalized->sum('line_total'), 2);
        $tax = round(max(0, $taxAmount), 2);

        return [
            'subtotal' => $subtotal,
            'tax' => $tax,
            'total' => round($subtotal + $tax, 2),
            'items' => $normalized->all(),
        ];
    }

    public function syncPaymentState(Invoice $invoice): Invoice
    {
        $paidAmount = round((float) $invoice->payments()
            ->where('payment_type', 'client_project')
            ->whereIn('status', self::PAID_PAYMENT_STATUSES)
            ->sum('amount'), 2);

        $total = round((float) $invoice->total, 2);
        $balance = round(max(0, $total - $paidAmount), 2);

        $invoice->forceFill([
            'paid_amount' => min($paidAmount, $total),
            'balance_amount' => $balance,
            'status' => $this->statusFor($invoice, $paidAmount, $balance),
        ])->save();

        return $invoice->refresh();
    }

    private function statusFor(Invoice $invoice, float $paidAmount, float $balance): string
    {
        if ($invoice->status === 'cancelled' || $invoice->status === 'draft') {
            return $invoice->status;
        }

        if ($paidAmount > 0 && $balance <= 0.0) {
            return 'paid';
        }

        if ($paidAmount > 0) {
            return 'partially_paid';
        }

        if ($invoice->due_date && $invoice->due_date->isPast()) {
            return 'overdue';
        }

        return $invoice->status === 'overdue' ? 'sent' : $invoice->status;
    }
}
