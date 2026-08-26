<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumOptions;

enum OfferStatus: string
{
    use HasEnumOptions;

    case Draft = 'Draft';
    case Sent = 'Sent';
    case Accepted = 'Accepted';
    case Rejected = 'Rejected';
    case Expired = 'Expired';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Sent => 'Sent',
            self::Accepted => 'Accepted',
            self::Rejected => 'Rejected',
            self::Expired => 'Expired',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'slate',
            self::Sent => 'blue',
            self::Accepted => 'green',
            self::Rejected => 'red',
            self::Expired => 'gray',
        };
    }
}
