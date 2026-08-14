<?php

namespace App\Enum\Invoice;

use App\Trait\Core\EnumLabelTrait;

enum InvoicePaymentMethod: int
{
    use EnumLabelTrait;
    
//    case Cash = 1;
    case Bank = 2;

    
    public function getLabel(): string
    {
        
        return match($this) {
//            self::Cash => 'cash',
            self::Bank => 'paymentMethodBank',
        };
    }

    private function getDomain(): ?string
    {
        return 'invoice';
    }

}
