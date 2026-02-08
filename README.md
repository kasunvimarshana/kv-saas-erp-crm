# KV SaaS ERP/CRM

![Laravel Version](https://img.shields.io/badge/Laravel-12.x-FF2D20?logo=laravel)
![PHP Version](https://img.shields.io/badge/PHP-8.3+-777BB4?logo=php)
![License](https://img.shields.io/badge/License-MIT-green)

Dynamic, enterprise-grade SaaS ERP with a modular, maintainable architecture. Fully supports multi-tenant, multi-organization, multi-vendor, multi-branch, multi-location, multi-currency, multi-language, multi-time-zone, and multi-unit operations with nested structures. Designed for global scalability, complex workflows, and long-term maintainability.

## 🎯 Overview

This SaaS ERP/CRM system is built on Laravel framework, incorporating architectural patterns and best practices from leading systems including:

- **Laravel Framework** - Modern PHP framework with elegant syntax
- **Odoo ERP** - Modular business application architecture
- **Domain-Driven Design** - Business logic organization by domains

## ✨ Key Features

### Multi-Tenancy
- Complete tenant isolation with data segregation
- Subdomain and domain-based tenant identification
- Tenant-specific configuration and customization
- Shared infrastructure with per-tenant databases

### Multi-Entity Support
- **Multi-Organization**: Support for multiple organizations per tenant
- **Multi-Branch**: Hierarchical branch/location structures
- **Multi-Vendor**: Vendor and customer relationship management
- **Multi-Currency**: Real-time exchange rates and conversions
- **Multi-Language**: i18n support for global operations
- **Multi-Timezone**: Automatic timezone handling
- **Multi-UOM**: Unit of measurement conversions

### Core Modules

#### Accounting
- Chart of Accounts (COA)
- Journal Entries
- General Ledger
- Accounts Payable/Receivable
- Financial Reporting
- Multi-currency accounting

#### Sales & CRM
- Lead and Opportunity Management
- Customer Relationship Management
- Sales Orders and Quotations
- Invoicing and Payments
- Sales Analytics

#### Purchasing
- Vendor Management
- Purchase Requisitions
- Purchase Orders
- Goods Receipt
- Vendor Bills and Payments

#### Inventory Management
- Stock Management
- Warehouse Operations
- Stock Movements and Transfers
- Inventory Valuation
- Serial/Batch Number Tracking

#### Human Resources
- Employee Management
- Attendance Tracking
- Leave Management
- Payroll Processing
- Performance Management

## 🏗️ Architecture

### Domain-Driven Structure

```
app/
├── Domains/
│   ├── Accounting/        # Financial management
│   ├── Sales/             # Sales and CRM
│   ├── Purchasing/        # Procurement
│   ├── Inventory/         # Stock management
│   ├── HumanResources/    # HR and payroll
│   ├── Shared/            # Cross-domain utilities
│   └── Tenant/            # Multi-tenancy core
├── Http/
├── Models/
└── Providers/
```

### Technology Stack
- **Framework**: Laravel 12.x
- **PHP**: 8.3+
- **Database**: PostgreSQL (recommended), MySQL
- **Cache**: Redis
- **Queue**: Redis, Database
- **Search**: Laravel Scout (Meilisearch/Algolia)
- **Storage**: Local, S3-compatible

## 🚀 Getting Started

### Prerequisites
- PHP 8.3 or higher
- Composer
- Node.js & NPM
- PostgreSQL or MySQL
- Redis (optional, recommended)

### Installation

1. Clone the repository
```bash
git clone https://github.com/kasunvimarshana/kv-saas-erp-crm.git
cd kv-saas-erp-crm
```

2. Install dependencies
```bash
composer install
npm install
```

3. Configure environment
```bash
cp .env.example .env
php artisan key:generate
```

4. Setup database
```bash
php artisan migrate
php artisan db:seed
```

5. Build assets
```bash
npm run build
```

6. Start development server
```bash
php artisan serve
```

## 📚 Documentation

Detailed documentation is available in the `/docs` directory:

- [Architecture Overview](docs/architecture.md)
- [Multi-Tenancy Setup](docs/multi-tenancy.md)
- [Module Development](docs/module-development.md)
- [API Documentation](docs/api.md)

## 🧪 Testing

```bash
# Run all tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Feature

# Run with coverage
php artisan test --coverage
```

## 📄 License

This project is open-sourced software licensed under the [MIT license](LICENSE).

## 🤝 Contributing

Contributions are welcome! Please read our [Contributing Guide](CONTRIBUTING.md) for details.

## 📧 Support

For support, email support@kv-saas-erp.com or open an issue on GitHub.

## 🙏 Acknowledgments

This project incorporates architectural patterns and insights from:
- [Laravel](https://github.com/laravel/laravel)
- [Odoo](https://github.com/odoo/odoo)
- Domain-Driven Design principles
