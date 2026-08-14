# Invoice Module - Documentation

Welcome to the Invoice Module documentation. This module provides comprehensive invoice management capabilities along with client management functionality.

## Documentation Index

### 📖 [Installation Guide](installation-guide.md)
Complete step-by-step installation instructions for the Invoice and Clients modules. Includes dependencies, configuration, and troubleshooting.

### ⚡ [Quick Start Guide](quick-start.md)
Condensed installation process for developers who need to get the Invoice module running quickly.

## Module Overview

The Invoice Module is a comprehensive billing solution that includes:

### 🧾 Invoice Management
- **Invoice Creation**: Generate professional invoices with customizable templates
- **Invoice Editing**: Modify existing invoices with full editing capabilities
- **Invoice Viewing**: Preview invoices before sending
- **PDF Generation**: Export invoices as PDF documents
- **Invoice Templates**: Customizable invoice layouts and designs
- **Number to Words**: Automatic conversion of amounts to text

### 👥 Client Management
- **Client Database**: Store and manage client information
- **Client Categories**: Organize clients by categories
- **Contact Information**: Complete contact and billing details
- **Integration**: Seamless integration with invoice generation

### ⚙️ Company Settings
- **Company Information**: Configure business details for invoices
- **Invoice Numbering**: Customizable invoice numbering system
- **Tax Configuration**: Set up tax rates and calculations
- **Currency Settings**: Configure default currency and formatting

## Key Features

- **Modular Architecture**: Built on the ERP Core Module System
- **Multi-language Support**: Internationalization ready
- **Responsive Design**: Works on desktop and mobile devices
- **PDF Export**: Professional PDF invoice generation
- **Template System**: Customizable invoice templates
- **Client Integration**: Direct integration with client management
- **Security**: Role-based access control

## Dependencies

### Required Modules
- **ERP Core Module**: Base system requirements
- **Clients Module**: Required for invoice functionality

### External Dependencies
- **kwn/number-to-words**: PHP package for number to text conversion

## File Structure

```
src/
├── Controller/
│   ├── Invoice/          # Invoice controllers
│   └── Client/           # Client controllers
├── Entity/
│   ├── Invoice/          # Invoice entities
│   └── Client/           # Client entities
├── Repository/
│   ├── Invoice/          # Invoice repositories
│   └── Client/           # Client repositories
├── Service/
│   ├── Invoice/          # Invoice services
│   └── Client/           # Client services
└── Form/
    ├── Invoice/          # Invoice forms
    └── Client/           # Client forms

assets/
└── module_scripts/
    └── invoice/          # JavaScript and CSS files
        ├── module.js
        └── invoice.scss
```

## Configuration Files

### JavaScript Module Loader
- **File**: `assets/js/module-loader.js`
- **Purpose**: Register invoice module for dynamic loading

### Import Map
- **File**: `importmap.php`
- **Purpose**: Define module JavaScript and CSS file paths

### Menu Configuration
- **File**: `src/Menu/Builder.php`
- **Purpose**: Configure navigation menu items

## Routes

### Invoice Routes
- `invoice_list` - List all invoices
- `invoice_create` - Create new invoice
- `invoice_edit` - Edit existing invoice
- `invoice_view` - View invoice details
- `invoice_download` - Download invoice PDF
- `invoice_preview` - Preview invoice
- `invoice_template` - Manage invoice templates
- `invoice_setting_company_info` - Company information settings

### Client Routes
- `client_list` - List all clients
- `client_create` - Create new client
- `client_edit` - Edit existing client

## Installation Checklist

- [ ] Copy Invoice and Clients modules
- [ ] Install `kwn/number-to-words` dependency
- [ ] Generate and run database migrations
- [ ] Configure JavaScript module loader
- [ ] Update import map configuration
- [ ] Add menu items to Builder.php
- [ ] Clear application cache
- [ ] Configure company information
- [ ] Test module functionality

## Post-Installation Setup

1. **Company Configuration**
   - Navigate to Company Info settings
   - Enter business details
   - Configure invoice numbering
   - Set default currency and tax rates

2. **Client Management**
   - Add initial clients
   - Configure client categories
   - Set up payment terms

3. **Invoice Templates**
   - Customize invoice layouts
   - Configure default settings
   - Set up email templates

## Troubleshooting

### Common Issues
- **Module not loading**: Check importmap.php and clear cache
- **Menu items missing**: Verify menu configuration and translations
- **Migration errors**: Check database connection and entity definitions
- **JavaScript errors**: Verify module files exist and are accessible

### Verification Steps
- Check if routes are registered: `php bin/console debug:router | grep invoice`
- Verify database tables: `php bin/console doctrine:schema:validate`
- Test module access: Navigate to `/admin/invoice/setting/company-info`

## Security Considerations

- Configure proper user permissions for invoice and client management
- Set up audit logging for invoice operations
- Implement data backup procedures
- Ensure secure PDF generation and storage

## Support

For additional support:
- Check the ERP Core documentation
- Review Symfony documentation
- Contact: support@devscale.bg

## Version Information

- **Module Version**: 1.0.0
- **Compatibility**: ERP Core Module System
- **PHP Version**: 8.0+
- **Symfony Version**: 6.0+ 