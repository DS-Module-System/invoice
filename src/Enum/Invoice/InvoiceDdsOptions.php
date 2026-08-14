<?php

namespace App\Enum\Invoice;

use App\Trait\Core\EnumLabelTrait;

enum InvoiceDdsOptions: int
{
    use EnumLabelTrait;

    case WithDds = 1;
    case Ch113 = 2;
    case Ch86 = 3;
    case Ch82 = 4;
    case Ch21 = 5;
    case Ch21Al2 = 6;
    case Ch173 = 7;

    public function getLabel($lng = 'bg'): string
    {
        return match ($this) {
            self::WithDds => 'withDds',
            self::Ch113 => 'ch113',
            self::Ch86 => 'ch86',
            self::Ch82 => 'ch82',
            self::Ch21 => 'ch21',
            self::Ch21Al2 => 'ch21Al2',
            self::Ch173 => 'ch173',
        };
    }


    private function getDomain(): ?string
    {
        return 'invoice';
    }
}
