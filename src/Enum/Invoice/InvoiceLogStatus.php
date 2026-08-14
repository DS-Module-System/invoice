<?php

namespace App\Enum\Invoice;

use App\Trait\Core\EnumLabelTrait;

enum InvoiceLogStatus: int
{
    use EnumLabelTrait;

    case Created = 1;
    case Edited = 2;
    case Refused = 3;
    case Accepted = 4;
    case Accounted = 5;
    case Sent = 6;
    case Viewed = 7;
    case Downloaded = 8;

    public function getLabel(): string
    {
        return match ($this) {
            self::Created => 'invoiceLogCreated',
            self::Edited => 'invoiceLogEdited',
            self::Refused => 'invoiceLogRefused',
            self::Accepted => 'invoiceLogAccepted',
            self::Accounted => 'invoiceLogAccounted',
            self::Sent => 'invoiceLogSent',
            self::Viewed => 'invoiceLogViewed',
            self::Downloaded => 'invoiceLogDownloaded',
        };
    }

    public function getActionLabel(): string
    {
        return match ($this) {
            self::Created => 'invoiceLogCreatedAction',
            self::Edited => 'invoiceLogEditedAction',
            self::Refused => 'invoiceLogRefusedAction',
            self::Accepted => 'invoiceLogAcceptedAction',
            self::Accounted => 'invoiceLogAccountedAction',
            self::Sent => 'invoiceLogSentAction',
            self::Viewed => 'invoiceLogViewedAction',
            self::Downloaded => 'invoiceLogDownloadedAction',
        };
    }

    private function getDomain(): ?string
    {
        return 'invoice';
    }
}
