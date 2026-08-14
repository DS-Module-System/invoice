<?php

namespace App\Dto\Invoice;

class InvoiceImportResult
{
    public int $clientsCreated = 0;
    public int $clientsExisting = 0;
    public int $invoicesCreated = 0;
    public int $invoicesSkipped = 0;
    public int $itemsCreated = 0;

    /** @var list<string> */
    public array $warnings = [];

    /** @var list<string> */
    public array $errors = [];
}
