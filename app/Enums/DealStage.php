<?php

namespace App\Enums;

use App\Enums\Concerns\HasEnumOptions;

enum DealStage: string
{
    use HasEnumOptions;

    case Qualification = 'Qualification';
    case Proposal = 'Proposal';
    case Negotiation = 'Negotiation';
    case Won = 'Won';
    case Lost = 'Lost';

    public function label(): string
    {
        return match ($this) {
            self::Qualification => 'Qualification',
            self::Proposal => 'Proposal',
            self::Negotiation => 'Negotiation',
            self::Won => 'Won',
            self::Lost => 'Lost',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Qualification => 'slate',
            self::Proposal => 'blue',
            self::Negotiation => 'amber',
            self::Won => 'green',
            self::Lost => 'red',
        };
    }
}
