# Invoice Module - Installation Guide

This guide provides step-by-step instructions for installing the Invoice module along with the Clients module in the ERP Core Module System.

## Prerequisites

- ERP Core Module System must be installed and running
- Access to the project root directory
- Composer installed and configured
- Database access for migrations

## Module Dependencies

The Invoice module depends on the **Clients module**. Both modules must be installed together.

## Installation Steps

### 1. Copy Module Files

Copy both the Invoice and Clients modules to your project:

```bash
# Copy the invoice module files
# Copy the clients module files
# Ensure both modules are placed in their respective directories
```

**Important:** Both modules must be copied together as the Invoice module depends on the Clients module.

### 2. Install Module Dependencies

Execute the dependency script for the Invoice module:

```bash
# Run the dependency installation script
composer require kwn/number-to-words
```

This installs the `kwn/number-to-words` package which is required for converting numbers to words in invoices.

### 3. Generate Database Migrations

Create the database migrations for the new modules:

```bash
php bin\console make:migration
```

This command will generate migration files based on the new entity definitions from both the Invoice and Clients modules.

### 4. Execute Database Migrations

Apply the migrations to create the database schema:

```bash
php bin/console doctrine:migrations:migrate
```

This will create all necessary tables for both the Invoice and Clients modules.

### 5. Configure JavaScript Module Loader

Edit the file `assets/js/module-loader.js` and add the invoice module to the MODULES registry:

```javascript
// Registry of all available modules with their importmap names
const MODULES = [
    { name: 'invoice', importName: 'module-invoice' }
    // Add new modules here: { name: 'moduleName', importName: 'module-moduleName' }
];
```

### 6. Configure Import Map

Edit the `importmap.php` file and add the invoice module entries:

```php
'module-invoice' => [
    'path' => './assets/module_scripts/invoice/module.js',
],
'module-invoice-css' => [
    'path' => './assets/module_scripts/invoice/invoice.scss',
    'type' => 'css',
],
```

### 7. Configure Menu Structure

Edit the file `src/Menu/Builder.php` and add the menu items for both modules:

```php
// Add company info settings menu
$administration->addChild('invoice.leftMenu.settingCompanyInfo', [
    'label' => $this->domainTranslationService->translate('invoice.leftMenu.settingCompanyInfo'),
    'route' => 'invoice_setting_company_info',
]);

// Add invoices menu
$menu->addChild('invoice.leftMenu.invoices', [
    'label' => $this->domainTranslationService->translate('invoice.leftMenu.invoices'),
    'route' => 'invoice_list',
    'extras' => [
        'routes' => [
            'invoice_create',
            'invoice_edit',
            'invoice_view',
            'invoice_download',
            'invoice_preview',
            'invoice_template',
        ],
    ],
]);

// Add clients menu
$menu->addChild('client.leftMenu.clients', [
    'label' => $this->domainTranslationService->translate('client.leftMenu.clients'),
    'route' => 'client_list',
    'extras' => [
        'routes' => [
            'client_create',
            'client_edit',
        ],
    ],
]);
```

### 8. Configure Company Information

Navigate to the company information settings page to configure your business details:

```
/admin/invoice/setting/company-info
```

This step is crucial for generating proper invoices with your company information.

## Module Features

### Invoice Module
- **Invoice Management**: Create, edit, view, and manage invoices
- **Invoice Templates**: Customizable invoice templates
- **PDF Generation**: Generate PDF invoices for download
- **Number to Words**: Automatic conversion of amounts to words
- **Company Settings**: Configure company information for invoices

### Clients Module
- **Client Management**: Create and manage client information
- **Client Database**: Store client contact and billing information
- **Integration**: Seamless integration with the Invoice module

## Troubleshooting

### Common Issues

1. **Module Not Loading**
   - Verify all files are copied to correct locations
   - Check importmap.php configuration
   - Clear cache: `php bin/console cache:clear`

2. **Migration Errors**
   - Ensure database connection is working
   - Check if previous migrations were applied
   - Verify entity definitions are correct

3. **Menu Items Not Appearing**
   - Clear cache after menu configuration
   - Check translation keys exist
   - Verify route names are correct

4. **JavaScript Errors**
   - Check browser console for errors
   - Verify module-loader.js configuration
   - Ensure module files exist in assets/module_scripts/

### Verification Steps

1. **Check Module Installation**
   ```bash
   # Verify composer dependencies
   composer show kwn/number-to-words
   
   # Check if routes are registered
   php bin/console debug:router | grep invoice
   php bin/console debug:router | grep client
   ```

2. **Verify Database Tables**
   ```sql
   -- Check if invoice tables exist
   SHOW TABLES LIKE '%invoice%';
   
   -- Check if client tables exist
   SHOW TABLES LIKE '%client%';
   ```

3. **Test Module Access**
   - Navigate to `/admin/invoice/setting/company-info`
   - Check if invoice and client menus appear in navigation
   - Verify JavaScript modules load without errors

## Post-Installation Configuration

### 1. Company Information Setup
- Navigate to Company Info settings
- Enter your business details
- Configure invoice numbering format
- Set default currency and tax rates

### 2. Client Management
- Add your first clients
- Configure client categories if needed
- Set up default payment terms

### 3. Invoice Templates
- Customize invoice templates
- Configure default invoice settings
- Set up email templates for invoice delivery

## Security Considerations

- Ensure proper access controls are configured
- Set up user permissions for invoice and client management
- Configure audit logging for invoice operations
- Implement proper data backup procedures

## Support

For additional support or questions:
- Check the ERP Core documentation
- Review Symfony documentation for framework-specific issues
- Contact: support@devscale.bg

## Next Steps

After successful installation:
1. Configure company information
2. Add initial clients
3. Create your first invoice
4. Test PDF generation
5. Set up email notifications
6. Configure user permissions 