<?php

namespace App\Models;

use Database\Factories\OfferItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'offer_id', 'description', 'quantity', 'unit_price', 'position',
])]
class OfferItem extends Model
{
    /** @use HasFactory<OfferItemFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $appends = ['line_total'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Offer, $this>
     */
    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }

    /**
     * @return Attribute<float, never>
     */
    protected function lineTotal(): Attribute
    {
        return Attribute::make(
            get: fn (): float => round((float) $this->quantity * (float) $this->unit_price, 2),
        );
    }
}
