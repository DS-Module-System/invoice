<?php

namespace App\Twig\Invoice;

use App\Entity\Invoice\Invoice;
use App\Service\Invoice\PriceTransformationService;
use Doctrine\ORM\EntityManagerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class InvoiceExtention extends AbstractExtension
{
    public function __construct(private PriceTransformationService $priceTransformationService, private EntityManagerInterface $em) {}

    public function number2Text($number, $currency, $lng): string
    {
        return $lng == 'bg' ? $this->priceTransformationService->number2currency($number, $currency) : $this->priceTransformationService->number2currencyNew($number, $currency);
    }

    public function getInvoiceById($id): Invoice|null
    {
        return $this->em->getRepository(Invoice::class)->find($id);
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('number2text', [$this, 'number2Text']),
            new TwigFunction('get_invoice_by_id', [$this, 'getInvoiceById']),
        ];
    }
}
