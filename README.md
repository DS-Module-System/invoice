# Invoice

Фактури и проформи: редове, ДДС, плащания, PDF и имейл към клиента. Връзва се към клиент и банкова сметка.

## Функционалност

- Създаване, редакция, преглед и изтриване на фактури и проформи
- Редове с мерки, отстъпки и ДДС опции
- Плащания към фактура
- PDF (wkhtmltopdf) и преглед по hash за клиента
- Имейл към клиента с лог
- Фирмени настройки за фактури
- Импорт команда
- Суми словом (`kwn/number-to-words`)

## Интеграция в системата

Copy-in модул: файловете се копират в хоста под `App\`.

- Пътища: `src/Controller|Entity|Enum|Form|Repository|Service|Command/Invoice/`, `templates/invoice/`, `templates/invoice_payment/`, `translations/invoice*.yaml`, `config/roles/invoice.yaml`, `assets/module_scripts/invoice/`
- Composer: `composer require kwn/number-to-words` (виж `module_dependences/invoice/invoice.txt`)
- Меню: Фактури при `ROLE_INVOICE_VIEW`
- Роли: `ROLE_INVOICE_{VIEW,CREATE,EDIT,DELETE,APPROVE,SEND,CANCEL,SEND_EMAIL_TO_CLIENT}`
- Маршрути: `/invoices`, `/invoice/payment/{invoiceId}`, `/admin/invoice/setting/company-info`

## Структура

- Контролери: `InvoiceController`, `InvoicePaymentController`, `InvoiceSettingController`
- Ентитети: `Invoice`, `InvoiceItem`, `InvoicePayment`, `InvoiceLog`, `ClientInvoiceEmailLog`
- Услуги: `InvoiceService`, `InvoiceImportService`, `PriceTransformationService`, `Wkhtml2pdfService`
- Команда: `ImportInvoicesCommand`

## Зависимости

- **erp-core**
- **client** (задължителен)
- **bank-account** (FK към сметка)
- Composer пакет `kwn/number-to-words`

## Документация

- [docs/invoice/README.md](docs/invoice/README.md)
- [docs/invoice/installation-guide.md](docs/invoice/installation-guide.md)
- [docs/invoice/quick-start.md](docs/invoice/quick-start.md)
