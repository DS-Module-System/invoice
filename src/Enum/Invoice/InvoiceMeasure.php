<?php

namespace App\Enum\Invoice;

enum InvoiceMeasure: int
{
    case Unit = 1;
//    case SquareMeter = 2;
//    case Kg = 3;
    case Hour = 4;

    public function label(): string
    {
        return match($this)
        {
            self::Unit => 'invoice.unitLabel',
//            self::SquareMeter => 'кв.м.',
//            self::Kg => 'кг.',
            self::Hour => 'invoice.hourLabel',
        };
    }
}
