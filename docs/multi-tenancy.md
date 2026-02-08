# Multi-Tenancy Implementation Guide

## Overview

This SaaS ERP/CRM system implements a flexible multi-tenancy architecture that supports both shared database and separate database strategies. The system is designed to handle multiple organizations, branches, and users while maintaining complete data isolation and security.

## Tenant Identification

### Methods

1. **Subdomain-based** (Primary)
   ```
   tenant1.erp-system.com
   tenant2.erp-system.com
   ```

2. **Custom Domain**
   ```
   company.com -> maps to tenant
   client.biz -> maps to tenant
   ```

3. **Header-based** (API)
   ```
   X-Tenant-ID: tenant_uuid
   ```

## Data Isolation Strategies

### Strategy 1: Shared Database with Tenant Scoping (Default)

All tenants share the same database, with tenant identification via foreign keys.

**Pros:**
- Cost-effective
- Easy maintenance
- Simple backups
- Good for small to medium tenants

**Cons:**
- Potential security concerns
- Risk of data leakage if not properly scoped
- Performance can degrade with many tenants

**Implementation:**
```php
// Global scope automatically applied to all tenant-scoped models
protected static function booted()
{
    static::addGlobalScope('tenant', function (Builder $builder) {
        if (auth()->check() && auth()->user()->tenant_id) {
            $builder->where('tenant_id', auth()->user()->tenant_id);
        }
    });
}
```

### Strategy 2: Separate Database per Tenant

Each tenant gets their own database with complete isolation.

**Pros:**
- Complete data isolation
- Better security
- Better performance for large tenants
- Easy to backup/restore individual tenants

**Cons:**
- Higher infrastructure costs
- Complex database management
- Schema migration complexity

**Implementation:**
```php
// Dynamic database connection switching
DB::purge('tenant');
Config::set('database.connections.tenant', [
    'driver' => 'pgsql',
    'host' => $tenant->database_host,
    'database' => $tenant->database_name,
    'username' => $tenant->database_username,
    'password' => decrypt($tenant->database_password),
]);
DB::reconnect('tenant');
```

## Tenant Context Management

### Middleware

```php
// app/Http/Middleware/SetTenantContext.php
public function handle(Request $request, Closure $next)
{
    $tenant = $this->identifyTenant($request);
    
    if (!$tenant) {
        abort(404, 'Tenant not found');
    }
    
    if (!$tenant->isActive()) {
        abort(403, 'Tenant is not active');
    }
    
    app()->instance('tenant', $tenant);
    
    return $next($request);
}
```

### Service Provider

```php
// app/Providers/TenantServiceProvider.php
public function boot()
{
    // Register tenant scopes
    Model::addGlobalScope(new TenantScope());
    
    // Register tenant-specific configuration
    if (app()->bound('tenant')) {
        $tenant = app('tenant');
        Config::set('app.timezone', $tenant->timezone);
        Config::set('app.locale', $tenant->locale);
    }
}
```

## Organization & Branch Hierarchy

### Structure

```
Tenant
└── Organization 1
    ├── Branch 1 (Headquarters)
    │   ├── Sub-branch 1.1
    │   └── Sub-branch 1.2
    └── Branch 2 (Regional Office)
        └── Sub-branch 2.1
└── Organization 2
    └── Branch 1
```

### Access Control

Users can be granted access at different levels:
- **Tenant Level**: Access to all organizations
- **Organization Level**: Access to specific organization and its branches
- **Branch Level**: Access to specific branch and sub-branches

## Multi-Organization Support

### Use Cases

1. **Holding Company**: Multiple subsidiaries under one tenant
2. **Franchise**: Central management with multiple franchisees
3. **Multi-Entity Business**: Different legal entities (e.g., LLC, Corp)

### Implementation

```php
class Organization extends Model
{
    public function branches()
    {
        return $this->hasMany(Branch::class);
    }
    
    public function users()
    {
        return $this->belongsToMany(User::class)
            ->withPivot('role', 'permissions');
    }
}
```

## Security Considerations

### Query Scoping

**Always** apply tenant/organization scopes to prevent data leakage:

```php
// Bad - Can access all tenants' data
Customer::all();

// Good - Automatically scoped to current tenant
Customer::all(); // With global scope applied

// Good - Explicit scoping
Customer::where('organization_id', auth()->user()->organization_id)->get();
```

### Authorization

```php
// Policy example
public function view(User $user, Customer $customer)
{
    return $user->organization_id === $customer->organization_id;
}
```

### Database Transactions

```php
// Always use transactions for multi-table operations
DB::transaction(function () {
    $order = SalesOrder::create([...]);
    $order->lines()->createMany([...]);
    $this->updateInventory($order);
});
```

## Testing Multi-Tenancy

### Unit Tests

```php
public function test_user_can_only_see_their_tenant_data()
{
    $tenant1 = Tenant::factory()->create();
    $tenant2 = Tenant::factory()->create();
    
    $user1 = User::factory()->for($tenant1)->create();
    $customer1 = Customer::factory()->for($tenant1)->create();
    $customer2 = Customer::factory()->for($tenant2)->create();
    
    $this->actingAs($user1);
    
    $this->assertCount(1, Customer::all());
    $this->assertTrue(Customer::all()->contains($customer1));
    $this->assertFalse(Customer::all()->contains($customer2));
}
```

## Migration Strategy

### New Tenant Setup

1. Create tenant record
2. Setup database (if using separate DB strategy)
3. Run migrations
4. Seed default data
5. Create admin user
6. Send welcome email

### Existing Tenant Migration

1. Backup current database
2. Run new migrations
3. Test thoroughly
4. Deploy during maintenance window

## Performance Optimization

### Indexing

```php
// Always index foreign keys
$table->foreignId('tenant_id')->index();
$table->foreignId('organization_id')->index();
$table->index(['tenant_id', 'status']); // Composite indexes
```

### Caching

```php
// Tenant-specific cache keys
Cache::remember("tenant.{$tenantId}.settings", 3600, function () {
    return Tenant::find($tenantId)->settings;
});
```

### Connection Pooling

For separate database strategy, use connection pooling (PgBouncer for PostgreSQL).

## Monitoring

### Metrics to Track

- Active tenants count
- Tenant database sizes
- Query performance per tenant
- API usage per tenant
- Storage usage per tenant

### Alerts

- Tenant exceeding storage quota
- Unusual activity patterns
- Failed login attempts
- Database connection errors

## Best Practices

1. **Never trust user input** - Always validate tenant context
2. **Use policies** - Implement authorization at every level
3. **Test thoroughly** - Write tests for cross-tenant data leakage
4. **Monitor actively** - Track tenant activity and performance
5. **Document changes** - Keep audit logs of all data modifications
6. **Plan for scale** - Design for growth from day one
7. **Backup regularly** - Implement automated backup strategies
8. **Encrypt sensitive data** - Use encryption for passwords, API keys, etc.

## Troubleshooting

### Common Issues

**Issue**: Data leakage between tenants
**Solution**: Check global scopes, use `withoutGlobalScope()` carefully

**Issue**: Performance degradation
**Solution**: Optimize queries, add indexes, consider read replicas

**Issue**: Database connection limits
**Solution**: Implement connection pooling, optimize query counts

## Additional Resources

- [Laravel Multi-Tenancy Package](https://github.com/spatie/laravel-multitenancy)
- [Stancl/tenancy](https://tenancyforlaravel.com/)
- [Multi-Tenancy Best Practices](https://docs.microsoft.com/en-us/azure/architecture/guide/multitenant/)
