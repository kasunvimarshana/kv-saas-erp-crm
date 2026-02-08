# API Documentation

## Overview

The KV SaaS ERP/CRM provides a comprehensive RESTful API for all business operations. This document outlines the API structure, authentication, and key endpoints.

## Base URL

```
Production: https://api.yourdomain.com/v1
Development: http://localhost:8000/api/v1
```

## Authentication

### API Token Authentication

The API uses Laravel Sanctum for token-based authentication.

#### Obtain Token

```http
POST /api/auth/login
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "password"
}
```

Response:
```json
{
  "token": "1|abc123...",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "user@example.com",
    "organization_id": 1
  }
}
```

#### Using Token

Include the token in the Authorization header:

```http
Authorization: Bearer {token}
```

### Tenant Identification

For multi-tenant requests, include:

```http
X-Tenant-ID: tenant-subdomain
```

Or use subdomain in the URL:
```
https://tenant1.yourdomain.com/api/v1
```

## Response Format

### Success Response

```json
{
  "data": {
    "id": 1,
    "name": "Example"
  },
  "meta": {
    "message": "Resource created successfully"
  }
}
```

### Error Response

```json
{
  "message": "Validation failed",
  "errors": {
    "email": ["The email field is required."]
  }
}
```

### Pagination

```json
{
  "data": [...],
  "links": {
    "first": "http://api.example.com/resource?page=1",
    "last": "http://api.example.com/resource?page=10",
    "prev": null,
    "next": "http://api.example.com/resource?page=2"
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 10,
    "per_page": 15,
    "to": 15,
    "total": 150
  }
}
```

## HTTP Status Codes

- `200 OK` - Request succeeded
- `201 Created` - Resource created
- `204 No Content` - Request succeeded with no response body
- `400 Bad Request` - Invalid request
- `401 Unauthorized` - Authentication required
- `403 Forbidden` - Insufficient permissions
- `404 Not Found` - Resource not found
- `422 Unprocessable Entity` - Validation failed
- `500 Internal Server Error` - Server error

## Core Endpoints

### Organizations

#### List Organizations
```http
GET /api/organizations
```

Query Parameters:
- `page` - Page number
- `per_page` - Items per page (default: 15)
- `status` - Filter by status (active/inactive)

#### Get Organization
```http
GET /api/organizations/{id}
```

#### Create Organization
```http
POST /api/organizations
Content-Type: application/json

{
  "name": "ACME Corp",
  "code": "ACME",
  "email": "contact@acme.com",
  "currency_code": "USD",
  "timezone": "UTC"
}
```

#### Update Organization
```http
PUT /api/organizations/{id}
```

#### Delete Organization
```http
DELETE /api/organizations/{id}
```

### Customers

#### List Customers
```http
GET /api/customers
```

Query Parameters:
- `search` - Search by name or email
- `status` - Filter by status

#### Create Customer
```http
POST /api/customers
Content-Type: application/json

{
  "name": "John Doe",
  "email": "john@example.com",
  "phone": "+1234567890",
  "billing_address": "123 Main St",
  "currency_code": "USD"
}
```

### Sales Orders

#### List Sales Orders
```http
GET /api/sales-orders
```

Query Parameters:
- `customer_id` - Filter by customer
- `status` - Filter by status (draft/confirmed/processing/completed/cancelled)
- `from_date` - Filter by date range
- `to_date` - Filter by date range

#### Create Sales Order
```http
POST /api/sales-orders
Content-Type: application/json

{
  "customer_id": 1,
  "order_date": "2026-02-08",
  "currency_code": "USD",
  "lines": [
    {
      "product_id": 1,
      "quantity": 10,
      "unit_price": 99.99
    }
  ]
}
```

#### Get Sales Order
```http
GET /api/sales-orders/{id}
```

Response:
```json
{
  "data": {
    "id": 1,
    "order_number": "SO-2026-0001",
    "customer": {
      "id": 1,
      "name": "John Doe"
    },
    "order_date": "2026-02-08",
    "total_amount": 999.90,
    "status": "confirmed",
    "lines": [
      {
        "product_id": 1,
        "quantity": 10,
        "unit_price": 99.99,
        "line_total": 999.90
      }
    ]
  }
}
```

#### Confirm Sales Order
```http
POST /api/sales-orders/{id}/confirm
```

### Products

#### List Products
```http
GET /api/products
```

Query Parameters:
- `search` - Search by name or SKU
- `category_id` - Filter by category
- `status` - Filter by status

#### Create Product
```http
POST /api/products
Content-Type: application/json

{
  "code": "PROD-001",
  "name": "Widget",
  "description": "A high-quality widget",
  "product_type": "goods",
  "cost_price": 50.00,
  "selling_price": 99.99,
  "track_inventory": true
}
```

### Inventory

#### Get Stock Level
```http
GET /api/products/{id}/stock
```

Query Parameters:
- `location_id` - Specific location (optional)

Response:
```json
{
  "data": {
    "product_id": 1,
    "total_quantity": 150,
    "locations": [
      {
        "location_id": 1,
        "location_name": "Main Warehouse",
        "quantity": 100
      },
      {
        "location_id": 2,
        "location_name": "Store #1",
        "quantity": 50
      }
    ]
  }
}
```

#### Record Stock Movement
```http
POST /api/stock-movements
Content-Type: application/json

{
  "product_id": 1,
  "location_id": 1,
  "movement_type": "in",
  "quantity": 100,
  "unit_cost": 50.00,
  "notes": "Initial stock"
}
```

### Accounting

#### Create Journal Entry
```http
POST /api/journal-entries
Content-Type: application/json

{
  "entry_date": "2026-02-08",
  "reference": "INV-001",
  "description": "Payment received",
  "lines": [
    {
      "account_id": 1,
      "debit": 1000.00,
      "credit": 0
    },
    {
      "account_id": 2,
      "debit": 0,
      "credit": 1000.00
    }
  ]
}
```

#### Post Journal Entry
```http
POST /api/journal-entries/{id}/post
```

#### Get Account Balance
```http
GET /api/accounts/{id}/balance
```

Response:
```json
{
  "data": {
    "account_id": 1,
    "account_name": "Cash",
    "balance": 15000.00,
    "currency_code": "USD"
  }
}
```

## Filtering and Sorting

### Filtering

Use query parameters for filtering:

```http
GET /api/customers?status=active&currency_code=USD
```

### Sorting

```http
GET /api/sales-orders?sort=-created_at
```

Use `-` prefix for descending order.

### Advanced Filters

```http
GET /api/products?filter[price][gte]=100&filter[price][lte]=500
```

Operators:
- `eq` - Equal
- `ne` - Not equal
- `gt` - Greater than
- `gte` - Greater than or equal
- `lt` - Less than
- `lte` - Less than or equal
- `like` - Contains (case-insensitive)

## Rate Limiting

- Default: 60 requests per minute per user
- Burst: 100 requests in 5 minutes

Rate limit headers:
```
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 59
X-RateLimit-Reset: 1612883400
```

## Webhooks

### Available Events

- `customer.created`
- `customer.updated`
- `order.created`
- `order.confirmed`
- `order.completed`
- `payment.received`
- `invoice.created`

### Webhook Payload

```json
{
  "event": "order.confirmed",
  "data": {
    "id": 1,
    "order_number": "SO-2026-0001"
  },
  "timestamp": "2026-02-08T15:30:00Z"
}
```

## Error Handling

### Validation Errors

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": [
      "The email field is required."
    ],
    "password": [
      "The password must be at least 8 characters."
    ]
  }
}
```

### Business Logic Errors

```json
{
  "message": "Cannot delete customer with existing orders",
  "code": "CUSTOMER_HAS_ORDERS"
}
```

## SDK & Libraries

### PHP
```php
composer require yourdomain/erp-api-client
```

### JavaScript
```bash
npm install @yourdomain/erp-api-client
```

### Python
```bash
pip install yourdomain-erp-client
```

## API Versioning

The API uses URL-based versioning:

```
/api/v1/...  # Current stable version
/api/v2/...  # Next version (when available)
```

## Support

For API support:
- Email: api-support@yourdomain.com
- Documentation: https://docs.yourdomain.com
- Status Page: https://status.yourdomain.com

## Changelog

### v1.0.0 (2026-02-08)
- Initial API release
- Core endpoints for organizations, customers, products, orders
- Authentication and authorization
- Multi-tenancy support
