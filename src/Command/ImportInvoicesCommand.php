<?php

namespace App\Command;

use App\Service\Invoice\InvoiceImportService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\KernelInterface;

#[AsCommand(
    name: 'app:import-invoices',
    description: 'Импортира фактури от ERP NS Excel export в Client, Invoice и InvoiceItem',
)]
class ImportInvoicesCommand extends Command
{
    private const DEFAULT_FILE = 'export_invoices_20260630.xlsx';

    public function __construct(
        private InvoiceImportService $invoiceImportService,
        private KernelInterface $kernel,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('file', null, InputOption::VALUE_OPTIONAL, 'Път до Excel файла', self::DEFAULT_FILE)
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Само валидация и отчет, без запис в базата')
            ->addOption('no-skip-existing', null, InputOption::VALUE_NONE, 'Не пропуска съществуващи фактури (type + number)')
            ->addOption('user-id', null, InputOption::VALUE_OPTIONAL, 'ID на потребител за createdBy/publisher')
            ->setHelp('Импортира фактури от ERP NS export (.xlsx). По подразбиране пропуска дубликати.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        ini_set('memory_limit', '-1');

        $io = new SymfonyStyle($input, $output);
        $projectDir = $this->kernel->getProjectDir();
        $fileOption = (string) $input->getOption('file');
        $filePath = str_starts_with($fileOption, DIRECTORY_SEPARATOR) || preg_match('/^[A-Za-z]:/', $fileOption)
            ? $fileOption
            : $projectDir . DIRECTORY_SEPARATOR . $fileOption;

        $dryRun = (bool) $input->getOption('dry-run');
        $skipExisting = !$input->getOption('no-skip-existing');
        $userIdOption = $input->getOption('user-id');
        $userId = $userIdOption !== null ? (int) $userIdOption : null;

        $io->title('Импорт на фактури от ERP NS Excel');

        if ($dryRun) {
            $io->note('Режим dry-run — няма да се записват данни.');
        }

        $io->text(['Файл: ' . $filePath, '']);

        $result = $this->invoiceImportService->import($filePath, $dryRun, $skipExisting, $userId);

        if ($result->errors !== []) {
            $io->error('Грешки:');
            $io->listing($result->errors);
        }

        if ($result->warnings !== []) {
            $io->warning('Предупреждения:');
            $io->listing($result->warnings);
        }

        $io->section('Резултат');
        $io->table(
            ['Метрика', 'Брой'],
            [
                ['Клиенти (нови)', (string) $result->clientsCreated],
                ['Клиенти (съществуващи)', (string) $result->clientsExisting],
                ['Фактури (създадени)', (string) $result->invoicesCreated],
                ['Фактури (пропуснати)', (string) $result->invoicesSkipped],
                ['Артикули', (string) $result->itemsCreated],
            ]
        );

        if ($result->errors !== []) {
            return Command::FAILURE;
        }

        if ($dryRun) {
            $io->success('Dry-run завърши успешно.');
        } else {
            $io->success('Импортът завърши успешно.');
        }

        return Command::SUCCESS;
    }
}
