<?php

namespace App\Controller\Invoice;

use App\Controller\Core\CoreBaseController;
use App\Entity\Invoice\Invoice;
use App\Entity\Invoice\InvoicePayment;
use App\Enum\Invoice\InvoiceType;
use App\Form\Invoice\InvoicePaymentForm;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/invoice/payment/{invoiceId}', name: 'invoice_payment_')]
class InvoicePaymentController extends CoreBaseController
{

    protected string $entityClass = InvoicePayment::class;
    protected string $formClass = InvoicePaymentForm::class;
    protected string $searchFormClass = '';
    protected string $moduleTemplateName = 'invoice_payment';

    #[Route(path: '', name: 'list')]
    #[IsGranted('ROLE_INVOICE_VIEW')]
    public function list(Request $request, int $invoiceId): Response
    {
        $invoice = $this->em->getRepository(Invoice::class)->find($invoiceId);
        if (!$invoice || $invoice->getType() !== InvoiceType::Invoice) {
            throw $this->createNotFoundException('Invoice not found');
        }
        $this->additionalData['invoice'] = $invoice;
        $this->callbacks['changeSearchFormData'] = function ($searchFormData) use ($invoiceId) {
            $searchFormData['invoiceId'] = $invoiceId;
            return $searchFormData;
        };
        return $this->baseList($request, $request->query->getInt('page', 1));
    }

    #[Route(path: '/create', name: 'create')]
    #[IsGranted('ROLE_INVOICE_CREATE')]
    public function create(Request $request, $invoiceId): Response
    {
        $invoice = $this->em->getRepository(Invoice::class)->find($invoiceId);
        if (!$invoice || $invoice->getType() !== InvoiceType::Invoice) {
            throw $this->createNotFoundException('Invoice not found');
        }

        $this->additionalData['invoice'] = $invoice;
        // Callback за автоматично задаване на създателя
        $this->callbacks['setDefaultEntityData'] = function (InvoicePayment $entity) use ($invoice) {
            $entity->setDate(new \DateTimeImmutable());
            return $entity;
        };
        $this->callbacks['preCreatePersist'] = function (InvoicePayment $entity) use ($invoice) {
            $entity->setInvoice($invoice);
            $entity->setCreatedAt(new \DateTimeImmutable());
            return $entity;
        };

        $this->callbacks['redirectAfterCreate'] = function (InvoicePayment $entity) use ($invoice) {
            return $this->redirectToRoute('invoice_payment_list', ['invoiceId' => $invoice->getId()]);
        };

        return $this->baseCreate($request);
    }

    #[Route(path: '/{id}/edit', name: 'edit')]
    #[IsGranted('ROLE_INVOICE_EDIT')]
    public function edit($id, Request $request, int $invoiceId): Response
    {
        $invoice = $this->em->getRepository(Invoice::class)->find($invoiceId);
        if (!$invoice) {
            throw $this->createNotFoundException('Invoice not found');
        }
        $entity = $this->em->getRepository(InvoicePayment::class)->find($id);
        if (!$entity || $entity->getInvoice()->getId() !== $invoice->getId()) {
            throw $this->createNotFoundException('Invoice payment not found');
        }
        $this->additionalData['invoice'] = $invoice;

        $this->callbacks['redirectAfterEdit'] = function (InvoicePayment $entity) use ($invoice) {
            return $this->redirectToRoute('invoice_payment_list', ['invoiceId' => $invoice->getId()]);
        };
        return $this->baseEdit($request, $id);
    }

    #[Route(path: '/deletes', name: 'deletes')]
    #[IsGranted('ROLE_INVOICE_DELETE')]
    public function deletes(Request $request, int $invoiceId): Response
    {
        return $this->baseDeletes($request);
    }
}
