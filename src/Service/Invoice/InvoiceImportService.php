<?php

namespace App\Service\Invoice;

use App\Dto\Invoice\ErpNsInvoiceRowDto;
use App\Dto\Invoice\InvoiceImportResult;
use App\Entity\Client\Client;
use App\Entity\Invoice\Invoice;
use App\Entity\Invoice\InvoiceItem;
use App\Entity\User\BaseUser;
use App\Enum\Client\ClientNPLEType;
use App\Enum\Invoice\InvoiceDdsOptions;
use App\Enum\Invoice\InvoiceMeasure;
use App\Enum\Invoice\InvoicePaymentMethod;
use App\Enum\Invoice\InvoicePromotionType;
use App\Enum\Invoice\InvoiceType;
use App\Enum\Invoice\InvoicedStatus;
use App\Repository\Client\ClientRepository;
use App\Repository\Invoice\InvoiceRepository;
use App\Repository\User\BaseUserRepository;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InvoiceImportService
{
    private const COLUMN_MAP = [
        'A' => 'invoiceNumber',
        'B' => 'invoiceType',
        'C' => 'article',
        'D' => 'quantity',
        'E' => 'measure',
        'H' => 'unitPrice',
        'J' => 'total',
        'M' => 'ddsPercent',
        'O' => 'clientNumber',
        'P' => 'clientName',
        'Q' => 'clientAddress',
        'R' => 'clientEek',
        'S' => 'clientVat',
        'T' => 'responsiblePerson',
        'U' => 'issueDate',
        'V' => 'taxDate',
        'W' => 'subTotalPrice',
        'X' => 'promotionPrice',
        'Y' => 'taxBasePrice',
        'Z' => 'ddsPrice',
        'AA' => 'totalPrice',
        'AC' => 'paymentMethod',
        'AD' => 'status',
        'AJ' => 'createdAt',
        'AK' => 'ddsOptionReason',
        'AL' => 'note',
        'AU' => 'recipient',
    ];

    public function __construct(
        private EntityManagerInterface $em,
        private ClientRepository $clientRepository,
        private InvoiceRepository $invoiceRepository,
        private BaseUserRepository $userRepository,
    ) {
    }

    public function import(string $filePath, bool $dryRun = false, bool $skipExisting = true, ?int $userId = null): InvoiceImportResult
    {
        $result = new InvoiceImportResult();

        if (!is_file($filePath)) {
            $result->errors[] = 'Файлът не съществува: ' . $filePath;

            return $result;
        }

        $user = $this->resolveUser($userId);
        if ($user === null) {
            $result->errors[] = 'Не е намерен потребител за createdBy/publisher. Посочете --user-id.';

            return $result;
        }

        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $this->parseRows($sheet);

        if ($rows === []) {
            $result->warnings[] = 'Няма данни за импорт.';

            return $result;
        }

        $groups = $this->groupRows($rows);
        /** @var array<string, Client> $dryRunClients */
        $dryRunClients = [];

        foreach ($groups as $groupRows) {
            $firstRow = $groupRows[0];
            $invoiceType = $this->mapInvoiceType($firstRow->invoiceType);
            if ($invoiceType === null) {
                $result->errors[] = sprintf(
                    'Ред с номер %s: непознат тип фактура "%s".',
                    $firstRow->invoiceNumber,
                    $firstRow->invoiceType
                );
                continue;
            }

            $invoiceNumber = $this->normalizeInvoiceNumber($firstRow->invoiceNumber);

            $existing = $this->invoiceRepository->findOneBy([
                'type' => $invoiceType,
                'number' => $invoiceNumber,
            ]);

            if ($existing !== null) {
                if ($skipExisting) {
                    $result->invoicesSkipped++;
                    $result->warnings[] = sprintf(
                        'Пропусната съществуваща фактура %s (%s).',
                        $invoiceNumber,
                        $firstRow->invoiceType
                    );
                    continue;
                }

                $result->errors[] = sprintf(
                    'Фактура %s (%s) вече съществува.',
                    $invoiceNumber,
                    $firstRow->invoiceType
                );
                continue;
            }

            if ($firstRow->clientEek === '') {
                $result->errors[] = sprintf(
                    'Фактура %s: липсва БУЛСТАТ/ЕГН на клиента.',
                    $invoiceNumber
                );
                continue;
            }

            try {
                $issueDate = $this->parseDate($firstRow->issueDate, 'Дата на издаване');
                $taxDate = $this->parseDate($firstRow->taxDate, 'Дата на данъчно събитие');
                $createdAt = $this->parseDate($firstRow->createdAt, 'Дата на създаване');
            } catch (\InvalidArgumentException $e) {
                $result->errors[] = sprintf('Фактура %s: %s', $invoiceNumber, $e->getMessage());
                continue;
            }

            $clientResult = $this->resolveClient($firstRow, $dryRun, $result, $dryRunClients);
            if ($clientResult === null) {
                continue;
            }

            $ddsPercentage = (int) $this->formatDecimal($firstRow->ddsPercent, 0);
            $ddsOption = $this->mapDdsOption($firstRow->ddsOptionReason, $ddsPercentage);

            if ($dryRun) {
                $result->invoicesCreated++;
                $result->itemsCreated += count($groupRows);
                continue;
            }

            $connection = $this->em->getConnection();
            $connection->beginTransaction();

            try {
                $client = $clientResult;
                $invoice = new Invoice();
                $invoice->setNumber($invoiceNumber);
                $invoice->setType($invoiceType);
                $invoice->setClient($client);
                $invoice->setIssueDate($issueDate);
                $invoice->setTaxDate($taxDate);
                $invoice->setCreatedAt($createdAt);
                $invoice->setSubTotalPrice($this->formatDecimal($firstRow->subTotalPrice));
                $invoice->setPromotionPrice($this->formatDecimal($firstRow->promotionPrice));
                $invoice->setTaxBasePrice($this->formatDecimal($firstRow->taxBasePrice));
                $invoice->setDdsPrice($this->formatDecimal($firstRow->ddsPrice));
                $invoice->setTotalPrice($this->formatDecimal($firstRow->totalPrice));
                $invoice->setDdsPercentage($ddsPercentage);
                $invoice->setDdsOption($ddsOption);
                $invoice->setPromotionType(InvoicePromotionType::Percentage);
                $invoice->setPromotionValue('0.00');
                $invoice->setPaymentMethod($this->mapPaymentMethod($firstRow->paymentMethod));
                $invoice->setIsPaid($this->mapIsPaid($firstRow->status));
                $invoice->setIsPosted(InvoicedStatus::No);
                $invoice->setNote($firstRow->note !== '' ? $firstRow->note : null);
                $invoice->setName($client->getName());
                $invoice->setVat($client->getVat());
                $invoice->setEek($client->getEek());
                $invoice->setAddress($client->getAddress());
                $invoice->setResponsiblePerson($client->getResponsiblePerson());
                $invoice->setCountryCode($client->getCountryCode());
                $invoice->setCreatedBy($user);
                $invoice->setPublisher($user);

                $ord = 1;
                foreach ($groupRows as $row) {
                    $item = new InvoiceItem();
                    $item->setOrd((string) $ord);
                    $item->setName($row->article !== '' ? $row->article : null);
                    $item->setQuantity($this->formatDecimal($row->quantity));
                    $item->setMeasure($this->mapMeasure($row->measure));
                    $item->setUnitPrice($this->formatDecimal($row->unitPrice));
                    $item->setTotal($this->formatDecimal($row->total));
                    $invoice->addInvoiceItem($item);
                    $ord++;
                }

                $this->em->persist($invoice);
                $this->em->flush();
                $connection->commit();

                $result->invoicesCreated++;
                $result->itemsCreated += count($groupRows);
            } catch (\Throwable $e) {
                $connection->rollBack();
                $result->errors[] = sprintf('Фактура %s: %s', $invoiceNumber, $e->getMessage());
            }
        }

        return $result;
    }

    /**
     * @return list<ErpNsInvoiceRowDto>
     */
    private function parseRows(Worksheet $sheet): array
    {
        $highestRow = $sheet->getHighestRow();
        $rows = [];

        for ($rowIndex = 2; $rowIndex <= $highestRow; $rowIndex++) {
            $data = [];
            foreach (self::COLUMN_MAP as $column => $field) {
                $value = $sheet->getCell($column . $rowIndex)->getCalculatedValue();
                $data[$field] = trim((string) ($value ?? ''));
            }

            if ($data['invoiceNumber'] === '' && $data['article'] === '') {
                continue;
            }

            $rows[] = new ErpNsInvoiceRowDto(
                invoiceNumber: $data['invoiceNumber'],
                invoiceType: $data['invoiceType'],
                article: $data['article'],
                quantity: $data['quantity'],
                measure: $data['measure'],
                unitPrice: $data['unitPrice'],
                total: $data['total'],
                ddsPercent: $data['ddsPercent'],
                clientName: $data['clientName'],
                clientAddress: $data['clientAddress'],
                clientEek: $data['clientEek'],
                clientVat: $data['clientVat'],
                responsiblePerson: $data['responsiblePerson'],
                recipient: $data['recipient'],
                clientNumber: $data['clientNumber'],
                issueDate: $data['issueDate'],
                taxDate: $data['taxDate'],
                createdAt: $data['createdAt'],
                subTotalPrice: $data['subTotalPrice'],
                promotionPrice: $data['promotionPrice'],
                taxBasePrice: $data['taxBasePrice'],
                ddsPrice: $data['ddsPrice'],
                totalPrice: $data['totalPrice'],
                paymentMethod: $data['paymentMethod'],
                status: $data['status'],
                ddsOptionReason: $data['ddsOptionReason'],
                note: $data['note'] !== '' ? $data['note'] : null,
            );
        }

        return $rows;
    }

    /**
     * @param list<ErpNsInvoiceRowDto> $rows
     *
     * @return array<string, list<ErpNsInvoiceRowDto>>
     */
    private function groupRows(array $rows): array
    {
        $groups = [];

        foreach ($rows as $row) {
            $key = mb_strtolower(trim($row->invoiceType)) . '|' . trim($row->invoiceNumber);
            $groups[$key][] = $row;
        }

        return $groups;
    }

    private function resolveUser(?int $userId): ?BaseUser
    {
        if ($userId !== null) {
            return $this->userRepository->find($userId);
        }

        return $this->userRepository->findOneBy([], ['id' => 'ASC']);
    }

    /**
     * @param array<string, Client> $dryRunClients
     */
    private function resolveClient(
        ErpNsInvoiceRowDto $row,
        bool $dryRun,
        InvoiceImportResult $result,
        array &$dryRunClients,
    ): ?Client {
        $eek = trim($row->clientEek);
        $existing = $this->clientRepository->findOneBy(['eek' => $eek]);

        if ($existing !== null) {
            $result->clientsExisting++;

            return $existing;
        }

        if ($dryRun) {
            if (!isset($dryRunClients[$eek])) {
                $client = new Client();
                $this->fillClient($client, $row);
                $dryRunClients[$eek] = $client;
                $result->clientsCreated++;
            }

            return $dryRunClients[$eek];
        }

        $client = new Client();
        $this->fillClient($client, $row);
        $this->em->persist($client);
        $this->em->flush();

        $result->clientsCreated++;

        return $client;
    }

    private function fillClient(Client $client, ErpNsInvoiceRowDto $row): void
    {
        $clientType = $this->mapClientType($row->clientName, $row->clientVat);
        $responsiblePerson = trim($row->responsiblePerson);
        if ($responsiblePerson === '' && $clientType === ClientNPLEType::NP) {
            $responsiblePerson = trim($row->recipient) ?: trim($row->clientName);
        }

        $client->setName($row->clientName !== '' ? $row->clientName : null);
        $client->setAddress($row->clientAddress !== '' ? $row->clientAddress : null);
        $client->setEek(trim($row->clientEek));
        $client->setVat($row->clientVat !== '' ? $row->clientVat : null);
        $client->setResponsiblePerson($responsiblePerson !== '' ? $responsiblePerson : null);
        $client->setClientNumber($row->clientNumber !== '' ? $row->clientNumber : null);
        $client->setCountryCode($this->resolveCountryCode($row->clientVat));
        $client->setClientType($clientType);
    }

    private function normalizeInvoiceNumber(string $number): string
    {
        $digits = preg_replace('/\D/', '', $number) ?? '';

        return str_pad($digits, 10, '0', STR_PAD_LEFT);
    }

    private function mapInvoiceType(string $type): ?InvoiceType
    {
        $normalized = mb_strtolower(trim($type));

        return match (true) {
            $normalized === 'фактура' => InvoiceType::Invoice,
            $normalized === 'проформа' => InvoiceType::ProformaInvoice,
            str_contains($normalized, 'кредитн') => InvoiceType::CreditInvoice,
            str_contains($normalized, 'дебитн') => InvoiceType::DebitInvoice,
            default => null,
        };
    }

    private function mapMeasure(string $measure): InvoiceMeasure
    {
        $normalized = mb_strtolower(trim($measure));

        return match (true) {
            str_starts_with($normalized, 'ч') => InvoiceMeasure::Hour,
            default => InvoiceMeasure::Unit,
        };
    }

    private function mapPaymentMethod(string $paymentMethod): InvoicePaymentMethod
    {
        return InvoicePaymentMethod::Bank;
    }

    private function mapIsPaid(string $status): InvoicedStatus
    {
        $normalized = mb_strtolower(trim($status));

        return match (true) {
            str_contains($normalized, 'неплатен') => InvoicedStatus::No,
            str_contains($normalized, 'частич') => InvoicedStatus::Part,
            default => InvoicedStatus::Yes,
        };
    }

    private function mapDdsOption(string $reason, int $ddsPercentage): InvoiceDdsOptions
    {
        $normalized = mb_strtolower(trim($reason));

        if ($normalized !== '') {
            return match (true) {
                str_contains($normalized, '113') => InvoiceDdsOptions::Ch113,
                str_contains($normalized, '86') => InvoiceDdsOptions::Ch86,
                str_contains($normalized, '82') => InvoiceDdsOptions::Ch82,
                str_contains($normalized, '21') && str_contains($normalized, 'ал') => InvoiceDdsOptions::Ch21Al2,
                str_contains($normalized, '21') => InvoiceDdsOptions::Ch21,
                str_contains($normalized, '173') => InvoiceDdsOptions::Ch173,
                default => $ddsPercentage > 0 ? InvoiceDdsOptions::WithDds : InvoiceDdsOptions::Ch113,
            };
        }

        return $ddsPercentage > 0 ? InvoiceDdsOptions::WithDds : InvoiceDdsOptions::Ch113;
    }

    private function mapClientType(string $name, string $vat): ClientNPLEType
    {
        if ($vat !== '') {
            return ClientNPLEType::LE;
        }

        $upperName = mb_strtoupper($name);

        foreach (['ООД', 'ЕООД', 'ЕТ', 'АД', 'КД', 'СД'] as $suffix) {
            if (str_contains($upperName, $suffix)) {
                return ClientNPLEType::LE;
            }
        }

        return ClientNPLEType::NP;
    }

    private function resolveCountryCode(string $vat): string
    {
        if (preg_match('/^([A-Z]{2})/', strtoupper(trim($vat)), $matches) === 1) {
            return $matches[1];
        }

        return 'BG';
    }

    private function parseDate(string $value, string $fieldLabel): \DateTimeImmutable
    {
        $value = trim($value);
        if ($value === '') {
            throw new \InvalidArgumentException($fieldLabel . ' е празна.');
        }

        $date = \DateTimeImmutable::createFromFormat('d.m.Y', $value);
        if ($date === false) {
            throw new \InvalidArgumentException($fieldLabel . ' е с невалиден формат: ' . $value);
        }

        return $date->setTime(0, 0);
    }

    private function formatDecimal(string $value, int $scale = 2): string
    {
        $normalized = str_replace(',', '.', trim($value));
        if ($normalized === '') {
            return number_format(0, $scale, '.', '');
        }

        if (!is_numeric($normalized)) {
            return number_format(0, $scale, '.', '');
        }

        return number_format((float) $normalized, $scale, '.', '');
    }
}
