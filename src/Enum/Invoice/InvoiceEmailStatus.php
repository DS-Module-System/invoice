<?php

namespace App\Enum\Invoice;

use App\Trait\Core\EnumLabelTrait;

enum InvoiceEmailStatus: int
{
    use EnumLabelTrait;

    case Sent = 1;
    case Unsent = 2;

    public function getLabel(): string
    {
        return match ($this) {
            self::Sent => 'sent',
            self::Unsent => 'unsent',
        };
    }

    private function getDomain(): ?string
    {
        return 'invoice';
    }
}
