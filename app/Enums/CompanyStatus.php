<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumOptions;

enum CompanyStatus: string
{
    use HasEnumOptions;

    case Lead = 'Lead';
    case Prospect = 'Prospect';
    case Customer = 'Customer';
    case Inactive = 'Inactive';

    public function label(): string
    {
        return match ($this) {
            self::Lead => 'Lead',
            self::Prospect => 'Prospect',
            self::Customer => 'Customer',
            self::Inactive => 'Inactive',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Lead => 'slate',
            self::Prospect => 'blue',
            self::Customer => 'green',
            self::Inactive => 'zinc',
        };
    }
}
