<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = ['company_id', 'client_id', 'project_id', 'invoice_id', 'created_by', 'amount', 'method', 'status', 'paid_at', 'notes'];

    protected function casts(): array
    {
        return ['paid_at' => 'date'];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
