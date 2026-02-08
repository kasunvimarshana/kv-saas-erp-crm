# Architecture Overview

## Introduction

This document provides a comprehensive overview of the KV SaaS ERP/CRM architecture. The system is built using Laravel 12 and follows Domain-Driven Design (DDD) principles to ensure maintainability, scalability, and modularity.

## Architectural Patterns

### 1. Domain-Driven Design (DDD)

The application is organized into distinct business domains:

- **Tenant Domain**: Multi-tenancy core functionality
- **Accounting Domain**: Financial management and reporting
- **Sales Domain**: CRM, sales orders, and customer management  
- **Purchasing Domain**: Vendor management and procurement
- **Inventory Domain**: Stock management and warehouse operations
- **HumanResources Domain**: Employee and payroll management
- **Shared Domain**: Cross-domain utilities and common functionality

### 2. Multi-Tenancy Architecture

**Hybrid multi-tenancy** approach supporting:
- Subdomain-based identification
- Custom domain support
- Complete data isolation per tenant
- Tenant-specific configurations

### 3. Multi-Entity Support

- Multi-Organization: Multiple legal entities per tenant
- Multi-Branch: Hierarchical location structures
- Multi-Currency: Real-time exchange rates
- Multi-Language: Full i18n support
- Multi-Timezone: Automatic timezone handling

## Core Components

### Models Created

**Tenant Domain:**
- Tenant, Organization, Branch (with hierarchical support)

**Shared Domain:**
- Currency, Language, UnitOfMeasure

**Accounting Domain:**
- Account (Chart of Accounts), JournalEntry, JournalEntryLine

**Sales Domain:**
- Customer, SalesOrder, SalesOrderLine

**Inventory Domain:**
- Product, StockMovement

## Technology Stack

- **Framework**: Laravel 12.x
- **PHP**: 8.3+
- **Database**: PostgreSQL/MySQL
- **Cache**: Redis
- **Queue**: Redis/Database

## References

Architecture inspired by:
- [Laravel](https://laravel.com) - Modern PHP framework
- [Odoo](https://www.odoo.com) - Modular ERP patterns
- Domain-Driven Design principles
