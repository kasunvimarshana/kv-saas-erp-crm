# Module Development Guide

## Introduction

This guide explains how to develop new modules for the KV SaaS ERP/CRM system following the established domain-driven architecture.

## Domain Structure

Each domain follows this structure:

```
app/Domains/{DomainName}/
├── Models/              # Eloquent models
├── Controllers/         # HTTP controllers
├── Services/            # Business logic services
├── Repositories/        # Data access layer
├── Requests/            # Form request validation
├── Resources/           # API resources
├── Policies/            # Authorization policies
├── Events/              # Domain events
└── Listeners/           # Event listeners
```

## Creating a New Module

### Step 1: Define the Domain

Identify the business domain and its boundaries:
- What is the primary business capability?
- What entities belong to this domain?
- What are the relationships with other domains?

### Step 2: Create Models

```php
// app/Domains/YourDomain/Models/YourModel.php
namespace App\Domains\YourDomain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class YourModel extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'organization_id',
        'name',
        // ... other fields
    ];

    protected $casts = [
        'settings' => 'array',
    ];

    // Relationships
    public function organization()
    {
        return $this->belongsTo(\App\Domains\Tenant\Models\Organization::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    // Business logic methods
    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
```

### Step 3: Create Migration

```bash
php artisan make:migration create_your_table_name
```

```php
Schema::create('your_table_name', function (Blueprint $table) {
    $table->id();
    $table->foreignId('organization_id')->constrained()->onDelete('cascade');
    $table->string('name');
    // ... other columns
    $table->enum('status', ['active', 'inactive'])->default('active');
    $table->timestamps();
    $table->softDeletes();

    // Indexes
    $table->index(['organization_id', 'status']);
});
```

### Step 4: Create Service Class

```php
// app/Domains/YourDomain/Services/YourService.php
namespace App\Domains\YourDomain\Services;

class YourService
{
    public function create(array $data)
    {
        DB::transaction(function () use ($data) {
            $model = YourModel::create($data);
            
            // Additional business logic
            event(new YourModelCreated($model));
            
            return $model;
        });
    }

    public function update(YourModel $model, array $data)
    {
        DB::transaction(function () use ($model, $data) {
            $model->update($data);
            
            event(new YourModelUpdated($model));
            
            return $model;
        });
    }
}
```

### Step 5: Create Controller

```php
// app/Domains/YourDomain/Controllers/YourController.php
namespace App\Domains\YourDomain\Controllers;

use App\Http\Controllers\Controller;
use App\Domains\YourDomain\Services\YourService;
use App\Domains\YourDomain\Requests\StoreYourModelRequest;

class YourController extends Controller
{
    public function __construct(
        private YourService $service
    ) {}

    public function index()
    {
        $this->authorize('viewAny', YourModel::class);
        
        return YourModelResource::collection(
            YourModel::paginate()
        );
    }

    public function store(StoreYourModelRequest $request)
    {
        $model = $this->service->create($request->validated());
        
        return new YourModelResource($model);
    }
}
```

### Step 6: Create Form Request

```php
// app/Domains/YourDomain/Requests/StoreYourModelRequest.php
namespace App\Domains\YourDomain\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreYourModelRequest extends FormRequest
{
    public function authorize()
    {
        return true; // Handle in controller
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'status' => 'required|in:active,inactive',
        ];
    }
}
```

### Step 7: Create API Resource

```php
// app/Domains/YourDomain/Resources/YourModelResource.php
namespace App\Domains\YourDomain\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class YourModelResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'status' => $this->status,
            'organization' => new OrganizationResource($this->whenLoaded('organization')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
```

### Step 8: Create Policy

```php
// app/Domains/YourDomain/Policies/YourModelPolicy.php
namespace App\Domains\YourDomain\Policies;

use App\Models\User;
use App\Domains\YourDomain\Models\YourModel;

class YourModelPolicy
{
    public function viewAny(User $user)
    {
        return $user->hasPermission('view_your_models');
    }

    public function view(User $user, YourModel $model)
    {
        return $user->organization_id === $model->organization_id;
    }

    public function create(User $user)
    {
        return $user->hasPermission('create_your_models');
    }

    public function update(User $user, YourModel $model)
    {
        return $user->organization_id === $model->organization_id
            && $user->hasPermission('update_your_models');
    }

    public function delete(User $user, YourModel $model)
    {
        return $user->organization_id === $model->organization_id
            && $user->hasPermission('delete_your_models');
    }
}
```

### Step 9: Register Routes

```php
// routes/api.php or domain-specific routes
Route::middleware(['auth:sanctum', 'tenant'])->group(function () {
    Route::apiResource('your-models', YourController::class);
});
```

### Step 10: Create Tests

```php
// tests/Feature/YourDomain/YourModelTest.php
namespace Tests\Feature\YourDomain;

use Tests\TestCase;
use App\Domains\YourDomain\Models\YourModel;

class YourModelTest extends TestCase
{
    public function test_user_can_create_model()
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)
            ->postJson('/api/your-models', [
                'name' => 'Test Model',
                'status' => 'active',
            ]);

        $response->assertCreated();
        $this->assertDatabaseHas('your_table_name', [
            'name' => 'Test Model',
        ]);
    }
}
```

## Best Practices

### 1. Model Design

- Keep models focused on data representation
- Move complex business logic to services
- Use proper relationships and eager loading
- Implement proper scopes for common queries

### 2. Service Layer

- Encapsulate business logic in services
- Use transactions for multi-step operations
- Fire events for important state changes
- Return consistent response formats

### 3. Controllers

- Keep controllers thin
- Delegate to services
- Use form requests for validation
- Return API resources for consistent formatting

### 4. Validation

- Use form requests for complex validation
- Create reusable validation rules
- Provide clear error messages
- Validate at multiple levels (request, model, database)

### 5. Authorization

- Use policies for authorization
- Check permissions at controller level
- Implement organization/tenant scoping
- Log authorization failures

### 6. Testing

- Write tests for all critical paths
- Test authorization rules
- Test tenant isolation
- Use factories for test data

### 7. Events & Listeners

- Fire events for important actions
- Keep listeners focused
- Use queued listeners for heavy operations
- Document event contracts

## Example: Complete Module

See the existing domains for complete examples:
- **Tenant Domain**: Multi-tenancy infrastructure
- **Accounting Domain**: Financial management
- **Sales Domain**: CRM and sales orders
- **Inventory Domain**: Stock management

## Common Patterns

### Repository Pattern

```php
interface YourRepositoryInterface
{
    public function findById(int $id);
    public function findByOrganization(int $organizationId);
    public function create(array $data);
    public function update(int $id, array $data);
    public function delete(int $id);
}
```

### Action Classes

```php
class CreateYourModelAction
{
    public function execute(array $data): YourModel
    {
        return DB::transaction(function () use ($data) {
            $model = YourModel::create($data);
            
            // Additional logic
            
            return $model;
        });
    }
}
```

### Event Sourcing

```php
class YourModelCreated
{
    public function __construct(
        public YourModel $model
    ) {}
}

class LogYourModelCreated
{
    public function handle(YourModelCreated $event)
    {
        AuditLog::create([
            'model_type' => get_class($event->model),
            'model_id' => $event->model->id,
            'action' => 'created',
            'user_id' => auth()->id(),
        ]);
    }
}
```

## Integration with Other Modules

### Cross-Domain Communication

Use events for loose coupling between domains:

```php
// In Sales domain
event(new OrderCompleted($order));

// In Inventory domain (listener)
public function handle(OrderCompleted $event)
{
    $this->inventoryService->reserveStock($event->order);
}
```

### Shared Services

Place shared utilities in the Shared domain:
- Currency conversion
- Date/time utilities
- File handling
- Notification services

## Deployment

1. Run migrations: `php artisan migrate`
2. Register routes
3. Register policies in `AuthServiceProvider`
4. Clear caches: `php artisan optimize:clear`
5. Run tests: `php artisan test`

## Additional Resources

- [Laravel Documentation](https://laravel.com/docs)
- [Domain-Driven Design](https://martinfowler.com/bliki/DomainDrivenDesign.html)
- [Laravel Best Practices](https://github.com/alexeymezenin/laravel-best-practices)
