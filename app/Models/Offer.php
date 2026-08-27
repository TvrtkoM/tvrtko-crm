<?php

namespace App\Models;

use App\Enums\OfferStatus;
use Database\Factories\OfferFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property OfferStatus $status
 */
#[Fillable([
    'deal_id', 'title', 'status', 'issue_date', 'valid_until', 'tax_rate', 'notes',
])]
class Offer extends Model
{
    /** @use HasFactory<OfferFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $appends = ['subtotal', 'tax_amount', 'total'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => OfferStatus::class,
            'issue_date' => 'date',
            'valid_until' => 'date',
            'tax_rate' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Deal, $this>
     */
    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class);
    }

    /**
     * @return HasMany<OfferItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OfferItem::class);
    }

    /**
     * @return Attribute<float, never>
     */
    protected function subtotal(): Attribute
    {
        return Attribute::make(
            get: fn (): float => round($this->items->sum(fn (OfferItem $item): float => $item->line_total), 2),
        );
    }

    /**
     * @return Attribute<float, never>
     */
    protected function taxAmount(): Attribute
    {
        return Attribute::make(
            get: fn (): float => round($this->subtotal * (float) $this->tax_rate / 100, 2),
        );
    }

    /**
     * @return Attribute<float, never>
     */
    protected function total(): Attribute
    {
        return Attribute::make(
            get: fn (): float => round($this->subtotal + $this->taxAmount, 2),
        );
    }

    protected static function booted(): void
    {
        static::creating(function (Offer $offer): void {
            if (empty($offer->offer_number)) {
                $offer->offer_number = static::generateOfferNumber();
            }

            if (empty($offer->issue_date)) {
                $offer->issue_date = now()->toDateString();
            }
        });
    }

    protected static function generateOfferNumber(): string
    {
        $year = Carbon::now()->year;
        $prefix = "OFF-{$year}-";

        $lastNumber = static::query()
            ->where('offer_number', 'like', "{$prefix}%")
            ->orderByDesc('offer_number')
            ->value('offer_number');

        $sequence = 1;

        if ($lastNumber !== null) {
            $sequence = ((int) substr($lastNumber, strlen($prefix))) + 1;
        }

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}
