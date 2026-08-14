<?php

namespace App\Controller\Invoice;

use App\Entity\Client\ClientAddress;
use App\Entity\Core\Setting;
use App\Entity\Invoice\ClientInvoiceEmailLog;
use App\Entity\Invoice\Invoice;
use App\Entity\Invoice\InvoiceItem;
use App\Entity\Invoice\InvoiceLog;
use App\Enum\Invoice\InvoicedStatus;
use App\Enum\Invoice\InvoiceEmailStatus;
use App\Enum\Invoice\InvoiceLogStatus;
use App\Enum\Invoice\InvoiceType;
use App\Form\Invoice\InvoiceForm;
use App\Service\Core\CoreUtils;
use App\Enum\Invoice\InvoiceMeasure;
use App\Form\Invoice\InvoiceSearchForm;
use App\Service\Invoice\InvoiceService;
use App\Enum\Invoice\InvoicePromotionType;
use App\EventListener\Invoice\Exception\InvoiceDiffTotalPriceException;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\Invoice\InvoiceRepository;
use App\Service\Invoice\PriceTransformationService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Form\Invoice\ClientInvoiceEmailLogForm;
use App\Service\Core\DomainTranslationService;
use App\Service\Core\MailService;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

#[Route(path: '/invoices', name: 'invoice_')]
class InvoiceController extends AbstractController
{


    public function __construct(private TranslatorInterface $translator, private InvoiceService $invoiceService) {}

    #[Route(path: '/', name: 'list')]
    #[IsGranted('ROLE_INVOICE_VIEW')]
    public function list(Request $request, EntityManagerInterface $em, PaginatorInterface $paginator)
    {
        $page = $request->get('page', 1);

        $coreRedirect = CoreUtils::setDefaultOrderInList($request, ['issueDate' => 'DESC']);
        if ($coreRedirect !== null) {
            return $coreRedirect;
        }

        $searchFormData = [];

        $form = $this->createForm(InvoiceSearchForm::class, null, ['csrf_protection' => false]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $searchFormData = $form->getData();
        }

        /** @var InvoiceRepository $mainRepository */
        $mainRepository = $em->getRepository(Invoice::class);

        $allGetParams = $request->query->all();
        $sortQuery = [];
        if (!empty($allGetParams[CoreUtils::$SORT_LIST_QUERY_NAME])) {
            $sortQuery = $allGetParams[CoreUtils::$SORT_LIST_QUERY_NAME];
        }
        if ($sortQuery) {
            $searchFormData[CoreUtils::$SORT_LIST_QUERY_NAME] = $sortQuery;
        }

        if ($request->get('export') !== null) {
            $searchFormData['loadInvoiceItems'] = true;
            /* @var Invoice[] $rows */
            $rows = $mainRepository->getPaginatedQuery($searchFormData)->getResult();
            $fileRows = [];
            if ($rows) {
                foreach ($rows as $row) {
                    if ($row->getInvoiceItems()->isEmpty() === false) {
                        foreach ($row->getInvoiceItems() as $invoiceItem) {

                            $client = $row->getClient();
                            $tmpRow = [];
                            $tmpRow[] = $row->getNumber();
                            $tmpRow[] = $row->getIssueDate()?->format('d.m.Y') ?? '';
                            $tmpRow[] = $row->getType()->value;
                            $tmpRow[] = $client?->getName() ?? '';
                            $tmpRow[] = $client?->getEek() ?? '';
                            $tmpRow[] = $client?->getVat() ?? '';
                            $tmpRow[] = $client?->getClientNumber() ?? '';
                            $tmpRow[] = 'услуга';
                            $tmpRow[] = $row->getTaxBasePrice();
                            $tmpRow[] = $row->getTotalPrice();
                            $tmpRow[] = $row->getDdsPrice();
                            $tmpRow[] = $row->getDdsPercentage();
                            $tmpRow[] = $row->getPaymentMethod()?->value ?? '';

                            // invoice item
                            $tmpRow[] = $invoiceItem->getQuantity();
                            $tmpRow[] = $invoiceItem->getMeasure()?->value ?? '';
                            $tmpRow[] = $invoiceItem->getUnitPrice();
                            $tmpRow[] = $invoiceItem->getTotal();
                            $tmpRow[] = $row->getIsPaid()?->value ?? '';
                            $tmpRow[] = $row->getIsPosted()?->value ?? '';

                            foreach ($tmpRow as $k => $v) {
                                $tmpRow[$k] = iconv('UTF-8', 'Windows-1251//TRANSLIT', (string) $v) ?: (string) $v;
                            }

                            $fileRows[] = implode("\t", $tmpRow);
                        }
                    }
                }
            }

            $fileName = 'ExportInvoices-' . date('Y-m-d_His') . '.txt';

            return new Response(
                implode("\r\n", $fileRows),
                Response::HTTP_OK,
                [
                    'Content-Type' => 'text/csv; charset=Windows-1251',
                    'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
                    'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
                    'Content-Description' => 'File Transfer',
                ]
            );
        }

        $rows = $paginator->paginate(
            $mainRepository->getPaginatedQuery($searchFormData),
            $page,
            $request->query->get('ipp') ?: $this->getParameter('datatable_ipp_default')
        );

        return $this->render('invoice/index.html.twig', [
            'rows' => $rows,
            'form' => $form->createView(),
            'creditTypeId' => InvoiceType::CreditInvoice->value,
            'debitTypeId' => InvoiceType::DebitInvoice->value,
        ]);
    }

    #[Route(path: '/create', name: 'create')]
    #[IsGranted('ROLE_INVOICE_CREATE')]
    public function create(Request $request, EntityManagerInterface $em)
    {
        // $lastInvoice = $em->getRepository(Invoice::class)->findOneBy([],['number'=>'DESC']);
        // $nextNumberInvoice = 1;
        // if($lastInvoice) {
        //     $nextNumberInvoice = $lastInvoice->getNumber() + 1;
        // }
        $last = $em->getRepository(Invoice::class)->findOneBy(
            ['type' => InvoiceType::Invoice],
            ['number' => 'DESC']
        );

        $nextNumber = $last ? $last->getNumber() + 1 : 1;
        $row = new Invoice();
        $row->setNumber($nextNumber);

        $invoiceType = InvoiceType::tryFrom((int)$request->query->get('type', InvoiceType::Invoice->value));
        $row->setType($invoiceType);
        $row->setIssueDate(new \DateTimeImmutable());
        $row->setTaxDate(new \DateTimeImmutable());
        $row->setPublisher($this->getUser());
        $row->setCreatedAt(new \DateTimeImmutable());
        $row->setCreatedBy($this->getUser());

        $requestInvoiceId = $request->query->get('invoiceId', null);
        if ($requestInvoiceId !== null) {
            $requestInvoice = $em->getRepository(Invoice::class)->find($requestInvoiceId);
            if ($requestInvoice) {
                $row->setParent($requestInvoice);
                if ($requestInvoice->getType() !== InvoiceType::Invoice) {
                    $this->addFlash('warning', 'Можете да създавате кредитно или дебитно известие само от фактура!');
                    return $this->redirect($request->server->get('HTTP_REFERER', $this->generateUrl('invoice_list')));
                }
            }
            $row->setClient($requestInvoice->getClient());
            $row->setSubTotalPrice($requestInvoice->getSubTotalPrice());
            $row->setTotalPrice($requestInvoice->getTotalPrice());
            $row->setTaxBasePrice($requestInvoice->getTaxBasePrice());
            $row->setPromotionValue($requestInvoice->getPromotionValue());
            $row->setPromotionPrice($requestInvoice->getPromotionPrice());
            $row->setPromotionType($requestInvoice->getPromotionType());
            $row->setDdsPercentage($requestInvoice->getDdsPercentage());
            $row->setDdsPrice($requestInvoice->getDdsPrice());
            $row->setPaymentMethod($requestInvoice->getPaymentMethod());

            foreach ($requestInvoice->getInvoiceItems() as $v) {
                $invoiceItem = new InvoiceItem();
                $invoiceItem->setOrd($v->getOrd());
                $invoiceItem->setMeasure($v->getMeasure());
                $invoiceItem->setUnitPrice($v->getUnitPrice());
                $invoiceItem->setQuantity($v->getQuantity());
                $invoiceItem->setTotal($v->getTotal());
                if ($request->getLocale() == 'en') {
                    $invoiceItem->setNameEng($v->getNameEng());
                } else {
                    $invoiceItem->setName($v->getName());
                }

                $row->addInvoiceItem($invoiceItem);
            }
        } else {
            $row->setSubTotalPrice('0.00');
            $row->setTotalPrice('0.00');
            $row->setTaxBasePrice('0.00');
            $row->setPromotionValue('0.00');
            $row->setPromotionPrice('0.00');
            $row->setPromotionType(InvoicePromotionType::Percentage);
            $row->setDdsPercentage(20);
            $row->setDdsPrice('0.00');

            $invoiceItem = new InvoiceItem();
            $invoiceItem->setOrd(1);
            $invoiceItem->setMeasure(InvoiceMeasure::Hour);
            $invoiceItem->setUnitPrice('0.00');
            $invoiceItem->setQuantity('0.00');
            $invoiceItem->setTotal('0.00');
            $row->addInvoiceItem($invoiceItem);
        }

        $form = $this->createForm(InvoiceForm::class, $row);

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->get('save')->isClicked() && $form->isValid()) {
                if ($this->invoiceService->checkForValidInvoiceNumberAndDate($row)) {

                    //lock the db while creating invoice
                    $em->getConnection()->beginTransaction();

                    $invoiceItems = $form->getData()->getInvoiceItems();

                    foreach ($invoiceItems as $item) {
                        $row->addInvoiceItem($item);
                    }

                    try {
                        $row->setName($row->getClient()->getName());
                        $row->setVat($row->getClient()->getVat());
                        $row->setEek($row->getClient()->getEek());
                        $row->setAddress($row->getClient()->getAddress());
                        $row->setResponsiblePerson($row->getClient()->getResponsiblePerson());
                        $row->setCountryCode($row->getClient()->getCountryCode());


                        $em->persist($row);

                        $em->flush();

                        // release the db after creating the invoice
                        $em->getConnection()->commit();

                        $this->addFlash('success', $this->translator->trans('core.layout.successCreateFlashMsg'));
                        return $this->redirectToRoute('invoice_list', ['id' => $row->getId()]);
                    } catch (\Exception $e) {
                        // release the db on error
                        $em->getConnection()->rollBack();

                        throw $e;
                    }
                } else {
                    $this->addFlash('error', 'Има конфликт с номета и датата на издаване на фактурата с останалите фактури!');
                }
            }
        }
        return $this->render('invoice/create.html.twig', [
            'form' => $form->createView(),
            'row' => $row,
        ]);
    }


    #[Route(path: '/proforma/create', name: 'proforma_create')]
    #[IsGranted('ROLE_INVOICE_CREATE')]
    public function proformaCreate(Request $request, EntityManagerInterface $em)
    {
        // $lastInvoice = $em->getRepository(Invoice::class)->findOneBy([],['number'=>'DESC']);
        // $nextNumberInvoice = 1;
        // if($lastInvoice) {
        //     $nextNumberInvoice = $lastInvoice->getNumber() + 1;
        // }
        $last = $em->getRepository(Invoice::class)->findOneBy(
            ['type' => InvoiceType::ProformaInvoice],
            ['number' => 'DESC']
        );

        $nextNumber = $last ? $last->getNumber() + 1 : 1;
        $row = new Invoice();
        $row->setNumber($nextNumber);

        $invoiceType = InvoiceType::tryFrom((int)$request->query->get('type', InvoiceType::ProformaInvoice->value));
        // dd($invoiceType);
        $row->setType($invoiceType);
        $row->setIssueDate(new \DateTimeImmutable());
        $row->setTaxDate(new \DateTimeImmutable());
        $row->setPublisher($this->getUser());
        $row->setCreatedAt(new \DateTimeImmutable());
        $row->setCreatedBy($this->getUser());

        $requestInvoiceId = $request->query->get('invoiceId', null);
        if ($requestInvoiceId !== null) {
            $requestInvoice = $em->getRepository(Invoice::class)->find($requestInvoiceId);
            if ($requestInvoice) {
                $row->setParent($requestInvoice);
                if ($requestInvoice->getType() !== InvoiceType::Invoice) {
                    $this->addFlash('warning', 'Можете да създавате кредитно или дебитно известие само от фактура!');
                    return $this->redirect($request->server->get('HTTP_REFERER', $this->generateUrl('invoice_list')));
                }
            }
            $row->setClient($requestInvoice->getClient());
            $row->setSubTotalPrice($requestInvoice->getSubTotalPrice());
            $row->setTotalPrice($requestInvoice->getTotalPrice());
            $row->setTaxBasePrice($requestInvoice->getTaxBasePrice());
            $row->setPromotionValue($requestInvoice->getPromotionValue());
            $row->setPromotionPrice($requestInvoice->getPromotionPrice());
            $row->setPromotionType($requestInvoice->getPromotionType());
            $row->setDdsPercentage($requestInvoice->getDdsPercentage());
            $row->setDdsPrice($requestInvoice->getDdsPrice());
            $row->setPaymentMethod($requestInvoice->getPaymentMethod());

            foreach ($requestInvoice->getInvoiceItems() as $v) {
                $invoiceItem = new InvoiceItem();
                $invoiceItem->setOrd($v->getOrd());
                $invoiceItem->setMeasure($v->getMeasure());
                $invoiceItem->setUnitPrice($v->getUnitPrice());
                $invoiceItem->setQuantity($v->getQuantity());
                $invoiceItem->setTotal($v->getTotal());
                if ($request->getLocale() == 'en') {
                    $invoiceItem->setNameEng($v->getNameEng());
                } else {
                    $invoiceItem->setName($v->getName());
                }

                $row->addInvoiceItem($invoiceItem);
            }
        } else {
            $row->setSubTotalPrice('0.00');
            $row->setTotalPrice('0.00');
            $row->setTaxBasePrice('0.00');
            $row->setPromotionValue('0.00');
            $row->setPromotionPrice('0.00');
            $row->setPromotionType(InvoicePromotionType::Percentage);
            $row->setDdsPercentage(20);
            $row->setDdsPrice('0.00');

            $invoiceItem = new InvoiceItem();
            $invoiceItem->setOrd(1);
            $invoiceItem->setMeasure(InvoiceMeasure::Hour);
            $invoiceItem->setUnitPrice('0.00');
            $invoiceItem->setQuantity('0.00');
            $invoiceItem->setTotal('0.00');
            $row->addInvoiceItem($invoiceItem);
        }

        $form = $this->createForm(InvoiceForm::class, $row);

        $form->handleRequest($request);

        // dump($form->getData());
        if ($form->isSubmitted()) {
            if ($form->get('save')->isClicked() && $form->isValid()) {
                if ($this->invoiceService->checkForValidInvoiceNumberAndDate($row)) {

                    //lock the db while creating invoice
                    $em->getConnection()->beginTransaction();

                    $invoiceItems = $form->getData()->getInvoiceItems();

                    foreach ($invoiceItems as $item) {
                        $row->addInvoiceItem($item);
                    }

                    try {
                        $row->setName($row->getClient()->getName());
                        $row->setVat($row->getClient()->getVat());
                        $row->setEek($row->getClient()->getEek());
                        $row->setAddress($row->getClient()->getAddress());
                        $row->setResponsiblePerson($row->getClient()->getResponsiblePerson());
                        $row->setCountryCode($row->getClient()->getCountryCode());

                        $row->setIsPaid(InvoicedStatus::No);
                        $row->setIsPosted(InvoicedStatus::No);

                        $em->persist($row);

                        $em->flush();

                        $em->getConnection()->commit();

                        $this->addFlash('success', $this->translator->trans('core.layout.successCreateFlashMsg'));
                        return $this->redirectToRoute('invoice_list', ['id' => $row->getId()]);
                    } catch (\Exception $e) {
                        $em->getConnection()->rollBack();
                        throw $e;
                    }
                } else {

                    $em->getConnection()->rollBack();
                    $this->addFlash('error', 'Има конфликт с номета и датата на издаване на фактурата с останалите фактури!');
                }
            }
        }
        return $this->render('invoice/create.html.twig', [
            'form' => $form->createView(),
            'row' => $row,
        ]);
    }

    #[Route(path: '/{id}/edit', name: 'edit')]
    #[IsGranted('ROLE_INVOICE_EDIT')]
    public function edit(int $id, Request $request, EntityManagerInterface $em, InvoiceService $invoiceService)
    {
        /** @var Invoice $row */
        $row = $em->getRepository(Invoice::class)->getInvoiceById($id);

        if ($row == null) {
            throw $this->createNotFoundException('The record does not exist');
        }

        // begin transaction when the invoice is found in the db
        $em->getConnection()->beginTransaction();

        $invoiceItemsDb = new ArrayCollection();

        // Create an ArrayCollection of the current Tag objects in the database
        foreach ($row->getInvoiceItems() as $item) {
            $invoiceItemsDb->add($item);
        }

        $form = $this->createForm(InvoiceForm::class, $row);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            if ($form->get('delete')->isClicked()) {
                $em->remove($row);
                try {
                    $em->getConnection()->commit();
                    $em->flush();
                    $this->addFlash('warning', $this->translator->trans('core.layout.deleteFlashMsg'));
                } catch (ForeignKeyConstraintViolationException $exception) {

                    $em->getConnection()->rollBack();
                    $this->addFlash('warning', $this->translator->trans('core.layout.errorDeleteFlashMsg'));
                }
            }
            if ($form->get('save')->isClicked()) {


                if ($this->invoiceService->checkForValidInvoiceNumberAndDate($row)) {

                    $invoiceItems = $form->getData()->getInvoiceItems();

                    foreach ($invoiceItems as $invoiceItem) {
                        $invoiceItem->setInvoice($row);
                    }

                    foreach ($invoiceItemsDb as $item) {
                        if (false === $row->getInvoiceItems()->contains($item)) {
                            $item->setInvoice(null);
                            $em->remove($item);
                        }
                    }

                    try {
                        $em->persist($row);

                        $em->flush();

                        $em->getConnection()->commit();
                        $this->addFlash('success', $this->translator->trans('core.layout.successEditFlashMsg'));
                        return $this->redirectToRoute('invoice_edit', ['id' => $id]);
                    } catch (InvoiceDiffTotalPriceException $e) {

                        $em->getConnection()->rollBack();
                        $this->addFlash('error', "Сумата на фактурата {$e->getTotalPrice()}лв. е по-малка от сбора на въведените разходи {$e->getSumTotalPrice()}лв.");
                    } catch (\Exception $e) {

                        $em->getConnection()->rollBack();
                        throw $e;
                    }
                } else {
                    $this->addFlash('error', 'Има конфликт с номета и датата на издаване на фактурата с останалите фактури!');
                }
            }
        }


        return $this->render('invoice/edit.html.twig', [
            'row' => $row,
            'form' => $form->createView(),
        ]);
    }

    #[Route(path: '/{id}/view', name: 'view')]
    #[IsGranted('ROLE_INVOICE_VIEW')]
    public function view(int $id, EntityManagerInterface $em)
    {

        /** @var Invoice $invoice */
        $invoice = $em->getRepository(Invoice::class)->find($id);
        if ($invoice == null) {
            throw $this->createNotFoundException('The record does not exist');
        }

        return $this->render('invoice/view.html.twig', [
            'invoice' => $invoice,
        ]);
    }

    #[Route('/invoice-preview/{id}', name: 'preview')]
    #[IsGranted('ROLE_INVOICE_VIEW')]
    public function invoicePreview($id, EntityManagerInterface $em, InvoiceService $invoiceService, PriceTransformationService $priceTransformationService, Request $request)
    {

        $invoice = $em->getRepository(Invoice::class)->find($id);

        if (!$invoice) {
            throw $this->createNotFoundException();
        }

        return $this->renderInvoiceTemplate($invoice, $em, $invoiceService, $priceTransformationService, $request);
    }

    private function renderInvoiceTemplate(Invoice $invoice, EntityManagerInterface $em, InvoiceService $invoiceService, PriceTransformationService $priceTransformationService, Request $request)
    {
        $company = $em->getRepository(Setting::class)->findOneBy(['property' => 'company_info']);

        if (!$company) {
            throw $this->createNotFoundException();
        }

        $data = $invoiceService->getData($invoice);

        return $this->render('invoice/template.html.twig', [
            'company' => $company,
            'client' => $data['client'],
            'invoice' => $invoice,
            'products' => $data['products'],
            'totalSum' => $invoice->getTotalPrice(),
            'ddsTotalSum' => $invoice->getDdsPrice(),
            'paymentMethod' => $invoice->getPaymentMethod(),
            'textPrice' => $request->getLocale() == 'bg' ? $priceTransformationService->number2currency($invoice->getTotalPrice(), 'BGN') : $priceTransformationService->number2currencyNew($invoice->getTotalPrice(), 'BGN'),
            'ddsPercent' => $invoice->getDdsPercentage(),
            'taxBasePrice' => $invoice->getTaxBasePrice(),
            'ddsOption' => $data['ddsOption'],
            'image' =>  $data['image'],
            'printLanguages' => [$request->getLocale()],
            'printTypes' => ['original'],
            'isIncludeBgnPrice' => false,
        ]);
    }

    #[Route('/invoice-preview-by-hash/{hash}', name: 'preview_by_hash')]
    public function invoicePreviewByHash($hash, EntityManagerInterface $em, InvoiceService $invoiceService, PriceTransformationService $priceTransformationService, Request $request)
    {

        $clientInvoiceEmailLog = $em->getRepository(ClientInvoiceEmailLog::class)->findOneBy(['hash' => $hash]);

        if ($clientInvoiceEmailLog === null) {
            throw $this->createNotFoundException('The record does not exist');
        }

        $invoice = $clientInvoiceEmailLog->getInvoice();

        if (!$invoice) {
            throw $this->createNotFoundException();
        }

        return $this->renderInvoiceTemplate($invoice, $em, $invoiceService, $priceTransformationService, $request);
    }

    #[Route('/download/{id}', name: 'download')]
    #[IsGranted('ROLE_INVOICE_VIEW')]
    public function download(int $id, EntityManagerInterface $em, Request $request)
    {

        $invoice = $em->getRepository(Invoice::class)->find($id);

        if ($request->query->get('download') && $request->query->get('download') == 1) {
            try {

                $isOriginal = $request->get('isOriginal', false);
                $isCopy = $request->get('isCopy', false);

                $numberInvoice = (int)$invoice->getNumber();
                $pdfOutput = $this->invoiceService->createInvoiceFile($invoice, $isOriginal, $isCopy);
                if ($invoice->getType() === InvoiceType::CreditInvoice) {
                    $invoiceName = "Credit_note_{$numberInvoice}_{$invoice->getIssueDate()->format('d-m-Y')}";
                } else {
                    $invoiceName = "Invoice_{$numberInvoice}_{$invoice->getIssueDate()->format('d-m-Y')}";
                }
                header("Content-type:application/pdf");
                header("Content-Disposition:attachment;filename=\"{$invoiceName}.pdf\"");
                echo $pdfOutput;
                exit();
            } catch (\Exception $e) {
                throw $e;
            }
        }

        die('2');
    }

    #[Route('/download-by-hash/{hash}', name: 'download_by_hash')]
    public function downloadByHash($hash, EntityManagerInterface $em, Request $request)
    {

        $clientInvoiceEmailLog = $em->getRepository(ClientInvoiceEmailLog::class)->findOneBy(['hash' => $hash]);

        if ($clientInvoiceEmailLog === null) {
            throw $this->createNotFoundException('The record does not exist');
        }

        $invoice = $clientInvoiceEmailLog->getInvoice();

        if (!$invoice) {
            throw $this->createNotFoundException();
        }

        if ($request->query->get('download') && $request->query->get('download') == 1) {
            try {

                // $isOriginal = $request->get('isOriginal', false);
                // $isCopy = $request->get('isCopy', false);

                $numberInvoice = (int)$invoice->getNumber();
                $pdfOutput = $this->invoiceService->createInvoiceFile($invoice, true, false);
                if ($invoice->getType() === InvoiceType::CreditInvoice) {
                    $invoiceName = "Credit_note_{$numberInvoice}_{$invoice->getIssueDate()->format('d-m-Y')}";
                } else {
                    $invoiceName = "Invoice_{$numberInvoice}_{$invoice->getIssueDate()->format('d-m-Y')}";
                }
                header("Content-type:application/pdf");
                header("Content-Disposition:attachment;filename=\"{$invoiceName}.pdf\"");
                echo $pdfOutput;
                exit();
            } catch (\Exception $e) {
                throw $e;
            }
        }

        die('2');
    }

    #[Route(path: '/{id}/send-email-to-client', name: 'send_email_to_client')]
    #[IsGranted('ROLE_INVOICE_SEND_EMAIL_TO_CLIENT')]
    public function sendEmailToClient(int $id, EntityManagerInterface $em, Request $request, MailService $mailService, DomainTranslationService $domainTranslationService)
    {
        /** @var Invoice $invoice */
        $invoice = $em->getRepository(Invoice::class)->find($id);
        if ($invoice == null) {
            throw $this->createNotFoundException('The record does not exist');
        }

        $companySettings = $em->getRepository(Setting::class)->findOneBy(['property' => 'company_info']);

        $receiversOptions = [];
        $receiversOptionsChecked = [];
        $clientAddresses = $em->getRepository(ClientAddress::class)->findBy(['client' => $invoice->getClient()]);
        if ($clientAddresses) {
            foreach ($clientAddresses as $v) {
                $receiversOptions[$v->getId()] = "{$v->getName()} <{$v->getEmail()}>";
                if ($v->isIsDefaultRecipient()) {
                    $receiversOptionsChecked[] = $v->getId();
                }
            }
        }

        $mol = $companySettings->getValue()['mol'] ? $companySettings->getValue()['mol'] : '';
        $companyName = $companySettings->getValue()['companyName'] ? $companySettings->getValue()['companyName'] : '';

        $formDataDefault = [
            'receivers' => $receiversOptionsChecked,
            'title' => "{$domainTranslationService->translate('invoice.sendEmailTitle')} {$invoice->getNumber()} {$companyName}",
            'content' => "
Здравейте,
Имате нова фактура от {$companySettings->getValue()['companyName']}, която може да получите на адрес:

{$domainTranslationService->translate('invoice.sendEmailContent')} {$invoice->getNumber()} {_INVOICE_CLIENT_LINK_}

------
{$mol}
{$companyName}
"
        ];

        if ($invoice->getType() === InvoiceType::CreditInvoice) {
            $formDataDefault['title'] = "Кредитно известие Nº {$invoice->getNumber()} {$companyName}";
            $formDataDefault['content'] = "
Здравейте,
Имате ново кредитно известие от {$companySettings->getValue()['companyName']}, което може да получите на адрес:

Кредитно известие Nº {$invoice->getNumber()} {_INVOICE_CLIENT_LINK_}

------
{$mol}
{$companyName}
";
        } else if ($invoice->getType() === InvoiceType::DebitInvoice) {
            $formDataDefault['title'] = "Дебитно известие Nº {$invoice->getNumber()} {$companyName}";
            $formDataDefault['content'] = "
Здравейте,
Имате ново дебитно известие от {$companySettings->getValue()['companyName']}, което може да получите на адрес:

Дебитно известие Nº {$invoice->getNumber()} {_INVOICE_CLIENT_LINK_}

------
{$mol}
{$companyName}
";
        }

        $form = $this->createForm(ClientInvoiceEmailLogForm::class, $formDataDefault, ['receivers' => $receiversOptions]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->get('save')->isClicked()) {
                $formData = $form->getData();
                $receiverIds = $formData['receivers'];
                if ($receiverIds) {
                    foreach ($receiverIds as $receiverId) {
                        $clientAddress = $em->getRepository(ClientAddress::class)->find($receiverId);

                        $createdAt = new \DateTimeImmutable();
                        $invoiceId = $invoice->getId();

                        $emailHash = hash(
                            'sha256',
                            $createdAt->format('Y-m-d H:i:s') . $clientAddress->getEmail() . $invoiceId . 'Ad8w4m'
                        );
                        $link = $request->getSchemeAndHttpHost() . $this->generateUrl(
                            'invoice_client_invoice',
                            ['hash' => $emailHash]
                        );

                        $link = "<a href='{$link}'>Линк за сваляне</a>";

                        $emailMessage = (nl2br($formData['content']));
                        $emailMessage = str_replace('{_INVOICE_CLIENT_LINK_}', $link, $emailMessage);

                        $email = (new Email())
                            ->from(new Address($_ENV['NO_REPLY_MAIL']))
                            ->to($clientAddress->getEmail())
                            ->subject($formData['title'])
                            ->html($emailMessage);

                        $invoiceEmailLog = new ClientInvoiceEmailLog();
                        $invoiceEmailLog->setCreatedAt($createdAt);
                        $invoiceEmailLog->setCreatedBy($this->getUser());
                        $invoiceEmailLog->setInvoice($invoice);
                        $invoiceEmailLog->setClientAddress($clientAddress);
                        $invoiceEmailLog->setEmailSubject($formData['title']);
                        $invoiceEmailLog->setEmailContent($emailMessage);
                        $invoiceEmailLog->setHash($emailHash);
                        $invoiceEmailLog->setStatus(InvoiceEmailStatus::Unsent);

                        try {
                            $mailService->sendSafeMail($email);

                            $invoiceEmailLog->setStatus(InvoiceEmailStatus::Sent);

                            $em->persist($invoiceEmailLog);

                            $this->addFlash(
                                'success',
                                $domainTranslationService->translate('invoice.emailSuccessSend') . ' ' . $clientAddress->getEmail() . '!'
                            );

                            $invoiceLog = new InvoiceLog();
                            $invoiceLog->setInvoice($invoice);
                            $invoiceLog->setCreatedBy($this->getUser());
                            $invoiceLog->setAction(InvoiceLogStatus::Sent);
                            $invoiceLog->setCreatedAt(new \DateTimeImmutable());
                            $invoiceLog->setClientInvoiceEmailLog($invoiceEmailLog);
                            $em->persist($invoiceLog);

                            $em->flush();
                        } catch (TransportExceptionInterface $e) {
                            $this->addFlash(
                                'error',
                                $domainTranslationService->translate('invoice.emailTransportException') . ' ' . $clientAddress->getEmail() . '!'
                            );
                        } catch (\Exception $e) {
                            throw $e;
                        }
                    }
                    return CoreUtils::redirect($request, $this->redirectToRoute('invoice_view', ['id' => $id]));
                } else {
                    $this->addFlash('error', $domainTranslationService->translate('invoice.emailMissedRecipients'));
                    return CoreUtils::redirect($request, $this->redirectToRoute('invoice_view', ['id' => $id]));
                }
            }
        }

        return $this->render('invoice/send_email.html.twig', [
            'invoice' => $invoice,
            'companySettings' => $companySettings,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/client/{hash}', name: 'client_invoice')]
    public function clientInvoice($hash, EntityManagerInterface $em, Request $request,  MailService $mailService, DomainTranslationService $domainTranslationService)
    {
        $clientInvoiceEmailLog = $em->getRepository(ClientInvoiceEmailLog::class)->findOneBy(['hash' => $hash]);

        if ($clientInvoiceEmailLog === null) {
            throw $this->createNotFoundException('The record does not exist');
        }

        $now = new \DateTimeImmutable();
        $linkThirtyDaysCreatedAt = $clientInvoiceEmailLog->getCreatedAt()->modify('+30 days');
        $lockedLink = false;
        if ($now >= $linkThirtyDaysCreatedAt) {
            $lockedLink = true;
        }
        $invoice = $clientInvoiceEmailLog->getInvoice();
        $company = $em->getRepository(Setting::class)->findOneBy(['property' => 'company_info']);

        $invoiceLog = new InvoiceLog();
        $invoiceLog->setInvoice($invoice);
        $invoiceLog->setCreatedBy($this->getUser());
        $invoiceLog->setAction(InvoiceLogStatus::Viewed);
        $invoiceLog->setCreatedAt(new \DateTimeImmutable());
        $invoiceLog->setEmail($clientInvoiceEmailLog->getClientAddress()->getEmail());
        $invoiceLog->setIp($request->getClientIp());
        $invoiceLog->setUserAgent($request->headers->get('User-Agent', null));
        $em->persist($invoiceLog);
        $em->flush();

        if ($request->isMethod("POST")) {
            $post = $request->request->all();
            if (isset($post['newLink']) && $post['newLink'] == 1) {
                $createdAt = new \DateTimeImmutable();
                $clientAddress = $clientInvoiceEmailLog->getClientAddress();

                $invoiceNumber = $invoice->getNumber();
                $companyName = $company->getValue()['companyName'];
                $companyMol = $company->getValue()['mol'];
                $subject = $domainTranslationService->translate('invoice.emailTitle') . $invoiceNumber . ' ' . $companyName;
                $emailHash = hash('sha256', $createdAt->format('Y-m-d H:i:s')
                    . $clientAddress->getEmail()
                    . $invoice->getId() . 'Ad8w4m');

                $link = $request->getSchemeAndHttpHost() . $this->generateUrl('invoice_client_invoice', ['hash' => $emailHash]);

                $content = "Здравейте,<br />
                Имате нова фактура от $companyName, която може да получите на адрес:<br />
                <br />
                Фактура Nº:$invoiceNumber <a href='{$link}'>Линк за сваляне</a><br />
                <br />
                ------<br />
                $companyMol<br />
                $companyName<br />";

                $email = (new Email())
                    ->from(new Address($_ENV['NO_REPLY_MAIL']))
                    ->to($clientAddress->getEmail())
                    ->subject($subject)
                    ->html($content);

                $invoiceEmailLog = new ClientInvoiceEmailLog();
                $invoiceEmailLog->setCreatedAt($createdAt);
                $invoiceEmailLog->setCreatedBy(null);
                $invoiceEmailLog->setInvoice($invoice);
                $invoiceEmailLog->setClientAddress($clientAddress);
                $invoiceEmailLog->setEmailSubject($subject);
                $invoiceEmailLog->setEmailContent($content);
                $invoiceEmailLog->setHash($emailHash);
                $invoiceEmailLog->setStatus(InvoiceEmailStatus::Unsent);

                try {

                    $mailService->sendSafeMail($email);

                    $invoiceEmailLog->setStatus(InvoiceEmailStatus::Sent);

                    $em->persist($invoice);

                    $em->persist($invoiceEmailLog);

                    $this->addFlash('success', $domainTranslationService->translate('invoice.emailSuccessSend') . ' ' . $clientAddress->getEmail() . '!');

                    $invoiceLog = new InvoiceLog();
                    $invoiceLog->setInvoice($invoice);
                    $invoiceLog->setCreatedBy($this->getUser());
                    $invoiceLog->setAction(InvoiceLogStatus::Sent);
                    $invoiceLog->setCreatedAt(new \DateTimeImmutable());
                    $invoiceLog->setClientInvoiceEmailLog($invoiceEmailLog);
                    $em->persist($invoiceLog);

                    $em->flush();
                } catch (TransportExceptionInterface $e) {
                    $this->addFlash('error', $domainTranslationService->translate('invoice.emailTransportException') . ' ' . $clientAddress->getEmail() . '!');
                } catch (\Exception $e) {
                    return $e;
                }
            }

            if (isset($post['approve']) && $post['approve'] == 1) {
                $invoiceLog = new InvoiceLog();
                $invoiceLog->setInvoice($invoice);
                $invoiceLog->setCreatedBy($this->getUser());
                $invoiceLog->setAction(InvoiceLogStatus::Accepted);
                $invoiceLog->setCreatedAt(new \DateTimeImmutable());
                $invoiceLog->setEmail($clientInvoiceEmailLog->getClientAddress()->getEmail());
                $invoiceLog->setIp($request->getClientIp());
                $invoiceLog->setUserAgent($request->headers->get('User-Agent', null));
                $em->persist($invoiceLog);

                $em->flush();
            }

            if (isset($post['disapprove'])) {
                $invoiceLog = new InvoiceLog();
                $invoiceLog->setInvoice($invoice);
                $invoiceLog->setCreatedBy($this->getUser());
                $invoiceLog->setAction(InvoiceLogStatus::Refused);
                $invoiceLog->setCreatedAt(new \DateTimeImmutable());
                $invoiceLog->setText($post['objection']);
                $invoiceLog->setEmail($clientInvoiceEmailLog->getClientAddress()->getEmail());
                $invoiceLog->setIp($request->getClientIp());
                $invoiceLog->setUserAgent($request->headers->get('User-Agent', null));
                $em->persist($invoiceLog);

                $em->flush();
            }
        }

        $invoiceLogs = $em->getRepository(InvoiceLog::class)->findBy(['invoice' => $invoice], ['createdAt' => 'DESC']);
        $invoiceActionStatus = '';
        foreach ($invoiceLogs as $invoiceLog) {
            if ($invoiceLog->getAction() === InvoiceLogStatus::Sent) {
                $invoiceActionStatus = 'sent';
                break;
            }
            if ($invoiceLog->getAction() === InvoiceLogStatus::Accepted) {
                $invoiceActionStatus = 'accepted';
                break;
            }
            if ($invoiceLog->getAction() === InvoiceLogStatus::Refused) {
                $invoiceActionStatus = 'refused';
                break;
            }
        }

        $invoiceLogs = [];



        return $this->render('invoice/invoice_client.html.twig', [
            'lockedLink' => $lockedLink,
            'clientInvoiceEmailLog' => $clientInvoiceEmailLog,
            'company' => $company,
            'invoiceLogs' => $invoiceLogs,
            'invoiceActionStatus' => $invoiceActionStatus,
        ]);
    }
}
