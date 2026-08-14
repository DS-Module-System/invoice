<?php

namespace App\Enum\Invoice;

enum InvoicePromotionType: int
{
    case Percentage = 1;
    case Currency = 2;

    public function label(): string
    {
        return match($this)
        {
            self::Percentage => 'percentage',
            self::Currency => 'price',
        };
    }
    private function getDomain(): ?string
    {
        return 'invoice';
    }
}