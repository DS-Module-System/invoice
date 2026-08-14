# Invoice Module - Quick Start Guide

Quick installation guide for the Invoice and Clients modules.

## Prerequisites Check

```bash
# Ensure ERP Core is running
php bin/console about

# Check if composer is available
composer --version
```

## Quick Installation

### 1. Install Dependencies
```bash
composer require kwn/number-to-words
```

### 2. Database Setup
```bash
# Generate migrations
php bin\console make:migration

# Apply migrations
php bin/console doctrine:migrations:migrate
```

### 3. Configure Module Loader
Edit `assets/js/module-loader.js`:
```javascript
const MODULES = [
    { name: 'invoice', importName: 'module-invoice' }
];
```

### 4. Configure Import Map
Edit `importmap.php`:
```php
'module-invoice' => [
    'path' => './assets/module_scripts/invoice/module.js',
],
'module-invoice-css' => [
    'path' => './assets/module_scripts/invoice/invoice.scss',
    'type' => 'css',
],
```

### 5. Add Menu Items
Edit `src/Menu/Builder.php`:
```php
// Company settings
$administration->addChild('invoice.leftMenu.settingCompanyInfo', [
    'label' => $this->domainTranslationService->translate('invoice.leftMenu.settingCompanyInfo'),
    'route' => 'invoice_setting_company_info',
]);

// Invoices menu
$menu->addChild('invoice.leftMenu.invoices', [
    'label' => $this->domainTranslationService->translate('invoice.leftMenu.invoices'),
    'route' => 'invoice_list',
    'extras' => [
        'routes' => [
            'invoice_create', 'invoice_edit', 'invoice_view',
            'invoice_download', 'invoice_preview', 'invoice_template',
        ],
    ],
]);

// Clients menu
$menu->addChild('client.leftMenu.clients', [
    'label' => $this->domainTranslationService->translate('client.leftMenu.clients'),
    'route' => 'client_list',
    'extras' => [
        'routes' => ['client_create', 'client_edit'],
    ],
]);
```

### 6. Clear Cache
```bash
php bin/console cache:clear
```

### 7. Configure Company Info
Navigate to: `/admin/invoice/setting/company-info`

## Verification Commands

```bash
# Check if dependency is installed
composer show kwn/number-to-words

# Verify routes are registered
php bin/console debug:router | grep invoice
php bin/console debug:router | grep client

# Check database tables
php bin/console doctrine:schema:validate
```

## Quick Troubleshooting

```bash
# Clear all caches
php bin/console cache:clear --env=dev
php bin/console cache:clear --env=prod

# Reset database (WARNING: Deletes all data)
php bin/console doctrine:database:drop --force
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate

# Check for errors
tail -f var/log/dev.log
```

## Module Access

- **Company Settings**: `/admin/invoice/setting/company-info`
- **Invoices**: Check left menu for "Invoices"
- **Clients**: Check left menu for "Clients"

## Development Tips

1. **Module Dependencies**: Invoice module requires Clients module
2. **Cache**: Clear cache after configuration changes
3. **Routes**: Verify all routes are properly registered
4. **Translations**: Ensure translation keys exist
5. **JavaScript**: Check browser console for module loading errors 