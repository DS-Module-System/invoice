<?php

namespace App\Enum\Invoice;

enum InvoicedStatus: int
{
    case Yes = 1;
    case No = 0;
    case Part = 2;

    public function label(): string
    {
        return match($this)
        {
            self::Yes => 'yes',
            self::No => 'no',
            self::Part => 'partially',
        };
    }
    private function getDomain(): ?string
    {
        return 'invoice';
    }
}
