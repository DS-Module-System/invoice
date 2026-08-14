<?php

namespace App\Enum\Invoice;

use App\Trait\Core\EnumLabelTrait;

enum InvoiceType: int
{
    use EnumLabelTrait;

    case Invoice = 1;
    case CreditInvoice = 2;
    case DebitInvoice = 3;
    case ProformaInvoice = 4;

    public function getLabel(): string
    {
        return match ($this) {
            self::Invoice => 'invoice',
            self::CreditInvoice => 'creditNote',
            self::DebitInvoice => 'debitNote',
            self::ProformaInvoice => 'proformaInvoice',
        };
    }

    private function getDomain(): ?string
    {
        return 'invoice';
    }
}
