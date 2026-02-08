# Database Schema

## Overview

This document describes the database schema for the KV SaaS ERP/CRM system.

## Schema Design Principles

1. **Multi-Tenancy**: All tables include tenant/organization foreign keys
2. **Soft Deletes**: Most tables use soft deletes for data recovery
3. **Audit Trail**: Timestamps on all tables
4. **Indexing**: Strategic indexes for performance
5. **Constraints**: Foreign keys with cascade rules

## Core Tables

### Tenant Management

#### tenants
```sql
id                  BIGINT PRIMARY KEY
name                VARCHAR(255)
subdomain           VARCHAR(255) UNIQUE
domain              VARCHAR(255) UNIQUE NULLABLE
database_name       VARCHAR(255) NULLABLE
database_host       VARCHAR(255) NULLABLE
database_port       INT NULLABLE
database_username   VARCHAR(255) NULLABLE
database_password   TEXT NULLABLE
status              ENUM('active', 'inactive', 'suspended')
settings            JSON NULLABLE
expires_at          TIMESTAMP NULLABLE
created_at          TIMESTAMP
updated_at          TIMESTAMP
deleted_at          TIMESTAMP NULLABLE

INDEX(status)
```

#### organizations
```sql
id                  BIGINT PRIMARY KEY
tenant_id           BIGINT FK(tenants.id) ON DELETE CASCADE
name                VARCHAR(255)
code                VARCHAR(255) UNIQUE
email               VARCHAR(255) NULLABLE
phone               VARCHAR(255) NULLABLE
address             TEXT NULLABLE
city                VARCHAR(255) NULLABLE
state               VARCHAR(255) NULLABLE
country             VARCHAR(255) NULLABLE
postal_code         VARCHAR(255) NULLABLE
tax_id              VARCHAR(255) NULLABLE
currency_code       CHAR(3) DEFAULT 'USD'
timezone            VARCHAR(255) DEFAULT 'UTC'
locale              VARCHAR(10) DEFAULT 'en'
settings            JSON NULLABLE
status              ENUM('active', 'inactive')
created_at          TIMESTAMP
updated_at          TIMESTAMP
deleted_at          TIMESTAMP NULLABLE

INDEX(tenant_id, status)
```

#### branches
```sql
id                  BIGINT PRIMARY KEY
organization_id     BIGINT FK(organizations.id) ON DELETE CASCADE
parent_id           BIGINT FK(branches.id) ON DELETE SET NULL NULLABLE
name                VARCHAR(255)
code                VARCHAR(255)
email               VARCHAR(255) NULLABLE
phone               VARCHAR(255) NULLABLE
address             TEXT NULLABLE
city                VARCHAR(255) NULLABLE
state               VARCHAR(255) NULLABLE
country             VARCHAR(255) NULLABLE
postal_code         VARCHAR(255) NULLABLE
timezone            VARCHAR(255) DEFAULT 'UTC'
settings            JSON NULLABLE
status              ENUM('active', 'inactive')
created_at          TIMESTAMP
updated_at          TIMESTAMP
deleted_at          TIMESTAMP NULLABLE

UNIQUE(organization_id, code)
INDEX(organization_id, parent_id, status)
```

### Shared Domain

#### currencies
```sql
id                  BIGINT PRIMARY KEY
code                CHAR(3) UNIQUE
name                VARCHAR(255)
symbol              VARCHAR(10)
decimal_places      INT DEFAULT 2
exchange_rate       DECIMAL(10,6) DEFAULT 1.0
is_active           BOOLEAN DEFAULT TRUE
created_at          TIMESTAMP
updated_at          TIMESTAMP

INDEX(is_active)
```

#### languages
```sql
id                  BIGINT PRIMARY KEY
code                VARCHAR(10) UNIQUE
name                VARCHAR(255)
native_name         VARCHAR(255)
direction           ENUM('ltr', 'rtl') DEFAULT 'ltr'
is_active           BOOLEAN DEFAULT TRUE
created_at          TIMESTAMP
updated_at          TIMESTAMP

INDEX(is_active)
```

#### unit_of_measures
```sql
id                  BIGINT PRIMARY KEY
name                VARCHAR(255)
code                VARCHAR(50)
category            VARCHAR(255)
base_unit_id        BIGINT FK(unit_of_measures.id) NULLABLE
conversion_factor   DECIMAL(10,6) DEFAULT 1.0
is_active           BOOLEAN DEFAULT TRUE
created_at          TIMESTAMP
updated_at          TIMESTAMP

INDEX(category, is_active)
```

### Accounting Domain

#### accounts
```sql
id                  BIGINT PRIMARY KEY
organization_id     BIGINT FK(organizations.id) ON DELETE CASCADE
parent_id           BIGINT FK(accounts.id) ON DELETE SET NULL NULLABLE
code                VARCHAR(255)
name                VARCHAR(255)
account_type        ENUM('asset', 'liability', 'equity', 'revenue', 'expense')
currency_code       CHAR(3)
description         TEXT NULLABLE
is_active           BOOLEAN DEFAULT TRUE
created_at          TIMESTAMP
updated_at          TIMESTAMP
deleted_at          TIMESTAMP NULLABLE

UNIQUE(organization_id, code)
INDEX(organization_id, account_type, is_active)
```

#### journal_entries
```sql
id                  BIGINT PRIMARY KEY
organization_id     BIGINT FK(organizations.id) ON DELETE CASCADE
branch_id           BIGINT FK(branches.id) ON DELETE CASCADE
entry_number        VARCHAR(255)
entry_date          DATE
reference           VARCHAR(255) NULLABLE
description         TEXT NULLABLE
currency_code       CHAR(3)
status              ENUM('draft', 'posted', 'cancelled')
posted_at           TIMESTAMP NULLABLE
posted_by           BIGINT FK(users.id) NULLABLE
created_at          TIMESTAMP
updated_at          TIMESTAMP
deleted_at          TIMESTAMP NULLABLE

UNIQUE(organization_id, entry_number)
INDEX(organization_id, status, entry_date)
```

#### journal_entry_lines
```sql
id                  BIGINT PRIMARY KEY
journal_entry_id    BIGINT FK(journal_entries.id) ON DELETE CASCADE
account_id          BIGINT FK(accounts.id)
description         TEXT NULLABLE
debit               DECIMAL(15,2) DEFAULT 0
credit              DECIMAL(15,2) DEFAULT 0
currency_code       CHAR(3)
created_at          TIMESTAMP
updated_at          TIMESTAMP

INDEX(journal_entry_id)
INDEX(account_id)
```

### Sales Domain

#### customers
```sql
id                      BIGINT PRIMARY KEY
organization_id         BIGINT FK(organizations.id) ON DELETE CASCADE
code                    VARCHAR(255)
name                    VARCHAR(255)
email                   VARCHAR(255) NULLABLE
phone                   VARCHAR(255) NULLABLE
mobile                  VARCHAR(255) NULLABLE
website                 VARCHAR(255) NULLABLE
tax_id                  VARCHAR(255) NULLABLE
billing_address         TEXT NULLABLE
billing_city            VARCHAR(255) NULLABLE
billing_state           VARCHAR(255) NULLABLE
billing_country         VARCHAR(255) NULLABLE
billing_postal_code     VARCHAR(255) NULLABLE
shipping_address        TEXT NULLABLE
shipping_city           VARCHAR(255) NULLABLE
shipping_state          VARCHAR(255) NULLABLE
shipping_country        VARCHAR(255) NULLABLE
shipping_postal_code    VARCHAR(255) NULLABLE
payment_terms           VARCHAR(255) NULLABLE
credit_limit            DECIMAL(15,2) DEFAULT 0
currency_code           CHAR(3)
status                  ENUM('active', 'inactive')
created_at              TIMESTAMP
updated_at              TIMESTAMP
deleted_at              TIMESTAMP NULLABLE

UNIQUE(organization_id, code)
INDEX(organization_id, status)
```

#### sales_orders
```sql
id                  BIGINT PRIMARY KEY
organization_id     BIGINT FK(organizations.id) ON DELETE CASCADE
branch_id           BIGINT FK(branches.id) ON DELETE CASCADE
customer_id         BIGINT FK(customers.id)
order_number        VARCHAR(255)
order_date          DATE
delivery_date       DATE NULLABLE
reference           VARCHAR(255) NULLABLE
currency_code       CHAR(3)
subtotal            DECIMAL(15,2) DEFAULT 0
tax_amount          DECIMAL(15,2) DEFAULT 0
discount_amount     DECIMAL(15,2) DEFAULT 0
total_amount        DECIMAL(15,2) DEFAULT 0
status              ENUM('draft', 'confirmed', 'processing', 'completed', 'cancelled')
notes               TEXT NULLABLE
created_at          TIMESTAMP
updated_at          TIMESTAMP
deleted_at          TIMESTAMP NULLABLE

UNIQUE(organization_id, order_number)
INDEX(organization_id, customer_id, status)
INDEX(order_date)
```

#### sales_order_lines
```sql
id                  BIGINT PRIMARY KEY
sales_order_id      BIGINT FK(sales_orders.id) ON DELETE CASCADE
product_id          BIGINT FK(products.id)
description         TEXT NULLABLE
quantity            DECIMAL(10,2)
unit_price          DECIMAL(15,2)
discount_percent    DECIMAL(5,2) DEFAULT 0
tax_percent         DECIMAL(5,2) DEFAULT 0
line_total          DECIMAL(15,2)
created_at          TIMESTAMP
updated_at          TIMESTAMP

INDEX(sales_order_id)
INDEX(product_id)
```

### Inventory Domain

#### products
```sql
id                  BIGINT PRIMARY KEY
organization_id     BIGINT FK(organizations.id) ON DELETE CASCADE
code                VARCHAR(255)
name                VARCHAR(255)
description         TEXT NULLABLE
product_type        ENUM('goods', 'service', 'consumable')
category_id         BIGINT FK(categories.id) NULLABLE
unit_of_measure_id  BIGINT FK(unit_of_measures.id)
cost_price          DECIMAL(15,2) DEFAULT 0
selling_price       DECIMAL(15,2) DEFAULT 0
barcode             VARCHAR(255) NULLABLE
sku                 VARCHAR(255) NULLABLE
track_inventory     BOOLEAN DEFAULT TRUE
reorder_level       DECIMAL(10,2) DEFAULT 0
status              ENUM('active', 'inactive')
created_at          TIMESTAMP
updated_at          TIMESTAMP
deleted_at          TIMESTAMP NULLABLE

UNIQUE(organization_id, code)
INDEX(organization_id, status)
INDEX(barcode)
INDEX(sku)
```

#### stock_movements
```sql
id                  BIGINT PRIMARY KEY
organization_id     BIGINT FK(organizations.id) ON DELETE CASCADE
product_id          BIGINT FK(products.id)
location_id         BIGINT FK(branches.id)
movement_type       ENUM('in', 'out', 'adjustment', 'transfer')
reference_type      VARCHAR(255) NULLABLE
reference_id        BIGINT NULLABLE
quantity            DECIMAL(10,2)
unit_cost           DECIMAL(15,2) DEFAULT 0
movement_date       TIMESTAMP
notes               TEXT NULLABLE
created_at          TIMESTAMP
updated_at          TIMESTAMP

INDEX(organization_id, product_id, location_id)
INDEX(movement_date)
INDEX(reference_type, reference_id)
```

## Relationships

### One-to-Many
- Tenant → Organizations
- Organization → Branches
- Organization → Customers
- Organization → Products
- Customer → SalesOrders
- SalesOrder → SalesOrderLines
- Product → StockMovements

### Many-to-One
- Organization → Tenant
- Branch → Organization
- Branch → Branch (parent)
- Account → Organization
- JournalEntry → Organization

### Hierarchical
- Branch → Branch (self-referencing for tree structure)
- Account → Account (self-referencing for COA hierarchy)

## Indexing Strategy

1. **Foreign Keys**: All foreign keys are indexed
2. **Composite Indexes**: For common query patterns (e.g., organization_id + status)
3. **Unique Constraints**: For business keys (e.g., code, order_number)
4. **Date Indexes**: For date-range queries
5. **Status Indexes**: For filtering by status

## Data Integrity

1. **Foreign Key Constraints**: Ensure referential integrity
2. **Cascade Deletes**: When parent is deleted, children are deleted
3. **Soft Deletes**: Most records are soft-deleted for recovery
4. **Check Constraints**: For enum values and ranges
5. **Unique Constraints**: Prevent duplicate business keys

## Performance Considerations

1. Use appropriate column types (INT vs BIGINT, VARCHAR lengths)
2. Index frequently queried columns
3. Partition large tables by date if needed
4. Regular VACUUM/ANALYZE (PostgreSQL) or OPTIMIZE (MySQL)
5. Monitor slow queries and add indexes as needed
