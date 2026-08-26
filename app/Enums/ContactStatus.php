<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumOptions;

enum ContactStatus: string
{
    use HasEnumOptions;

    case New = 'New';
    case Contacted = 'Contacted';
    case Qualified = 'Qualified';
    case Unresponsive = 'Unresponsive';

    public function label(): string
    {
        return match ($this) {
            self::New => 'New',
            self::Contacted => 'Contacted',
            self::Qualified => 'Qualified',
            self::Unresponsive => 'Unresponsive',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::New => 'blue',
            self::Contacted => 'amber',
            self::Qualified => 'green',
            self::Unresponsive => 'red',
        };
    }
}
