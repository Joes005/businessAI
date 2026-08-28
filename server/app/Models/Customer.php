<?php

namespace App\Models;

use App\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes, BelongsToBusiness;

    protected $fillable = [
        'business_id',
        'name',
        'phone',
        'email',
        'address',
        'city',
        'state',
        'pincode',
        'gstin',
        'credit_limit',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'credit_limit' => 'float',
        'is_active'    => 'boolean',
    ];

    protected $appends = [
        'total_purchased',
        'total_paid',
        'outstanding_amount',
        'last_purchase_date',
    ];

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class)->latest('date');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->latest('payment_date');
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(CustomerReminder::class)->latest('reminder_date');
    }

    public function getTotalPurchasedAttribute(): float
    {
        return (float) ($this->invoices()->sum('grand_total') ?: 0.0);
    }

    public function getTotalPaidAttribute(): float
    {
        $invoicePaid = (float) ($this->invoices()->sum('amount_paid') ?: 0.0);
        $directPayments = (float) ($this->payments()->whereNull('invoice_id')->sum('amount') ?: 0.0);
        return round($invoicePaid + $directPayments, 2);
    }

    public function getOutstandingAmountAttribute(): float
    {
        return (float) ($this->invoices()->sum('balance_due') ?: 0.0);
    }

    public function getLastPurchaseDateAttribute(): ?string
    {
        $last = $this->invoices()->latest('date')->first();
        return $last ? $last->date->toDateString() : null;
    }
}
