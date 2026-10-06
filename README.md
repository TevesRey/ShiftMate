# ShiftMate

## Suggested Additions

- **Installation**: Provide step‑by‑step instructions for setting up the project (e.g., cloning, installing dependencies via `composer install` and `npm install`, configuring environment variables).
- **API Documentation**: Add a section describing the available API endpoints, request/response formats, and authentication requirements. Consider generating OpenAPI/Swagger spec and linking it.
- **Usage Examples**: Include example commands or code snippets showing how to call the API (cURL examples, Postman collection).
- **Testing**: Explain how to run the test suite (`php artisan test`), and add guidelines for writing new tests.
- **CI/CD**: Recommend setting up GitHub Actions for linting, testing, and deployment. Provide a sample workflow file.
- **Contribution Guide**: Add a `CONTRIBUTING.md` with coding standards, branch strategy, and pull‑request process.
- **License**: Specify the project license (e.g., MIT) if not already present.

These additions will make the project easier to onboard, improve maintainability, and help collaborators understand and extend the system.

## Implementation Notes

- **Controllers**: Add new API controllers under `app/Http/Controllers/Api` for each resource. Follow Laravel conventions, inject services via the constructor, and use request validation.
- **Form Requests**: Create dedicated Form Request classes (`php artisan make:request <Name>Request`) to handle validation logic for create/update endpoints.
- **Routes**: Register API routes in `routes/api.php` using `Route::apiResource` or explicit route definitions with proper middleware (`auth:sanctum`).
- **Resources / Transformers**: Use Laravel API resources (`php artisan make:resource <Model>Resource`) to shape JSON responses.
- **Service Layer**: Optionally introduce service classes in `app/Services` to keep controllers thin.
- **Testing**: Write feature tests for each endpoint (`tests/Feature/Api/<Model>Test.php`) covering success, validation errors, and auth scenarios.
- **Documentation**: Generate OpenAPI schema with tools like `scribe` or `laravel-openapi` and link in the README.

## Detailed Implementation Guide

### Employees
- **Model**: `app/Models/Employee.php` – belongsTo `User`.
  ```php
  class Employee extends Model
  {
      protected $fillable = [
          'user_id', 'employee_number', 'first_name', 'last_name',
          'position', 'department', 'contact_number', 'status',
      ];

      public function user()
      {
          return $this->belongsTo(User::class);
      }
  }
  ```
- **Form Request**: `StoreEmployeeRequest` (create) and `UpdateEmployeeRequest` (update). Validation rules include `employee_number` numeric, `first_name`/`last_name` required strings, `contact_number` regex, `status` in:active,inactive.
- **Controller**: `EmployeeController` (API) with methods `index`, `store`, `show`, `update`, `destroy`. Use Form Requests, inject `Employee` model, return `EmployeeResource`.
- **Resource**: `EmployeeResource` formats JSON with `id`, `employee_number`, `full_name`, `position`, `department`, `contact_number`, `status`.
- **Routes** (`routes/api.php`): `Route::apiResource('employees', EmployeeController::class)->middleware('auth:sanctum');`
- **Tests**: Feature tests covering CRUD, validation errors, and authorization.

### Shifts
- **Model**: `app/Models/Shift.php` – hasMany `Schedule`.
- **Migration notes**: fields `shift_name`, `start_time`, `date_time` (should be `end_time` – we recommend renaming), `description`.
- **Form Request**: `StoreShiftRequest` / `UpdateShiftRequest` – validate `shift_name` required, `start_time` and `date_time` as `date_format:H:i:s` (or correct to `end_time`).
- **Controller**: `ShiftController` with standard resource actions, plus optional `assign` method to link shifts to schedules.
- **Resource**: `ShiftResource`.
- **Routes**: `Route::apiResource('shifts', ShiftController::class);
- **Tests**: CRUD tests, ensure time fields are stored correctly.

### Schedules
- **Model**: `app/Models/Schedule.php` – belongsTo `User` and `Shift`.
- **Form Request**: `StoreScheduleRequest` validates `user_id` exists, `shift_id` exists, `work_date` as `date`, `status` enum.
- **Controller**: `ScheduleController` – includes `index` (filter by date/user), `store`, `show`, `update`, `destroy`.
- **Resource**: `ScheduleResource` includes nested `ShiftResource` for shift details.
- **Routes**: `Route::apiResource('schedules', ScheduleController::class);
- **Tests**: Verify schedule creation respects foreign keys and status defaults.

### Rest Day Requests
- **Model**: `app/Models/RestDayRequest.php` – belongsTo `User`.
- **Migration**: contains fields for request date, reason, status.
- **Form Request**: `StoreRestDayRequest` validates `requested_date` as future date, `reason` required.
- **Controller**: `RestDayRequestController` with `store` (user submits), `index` (admin view), `update` (approve/deny).
- **Resource**: `RestDayRequestResource`.
- **Routes**: `Route::apiResource('rest-day-requests', RestDayRequestController::class);
- **Tests**: Submission and approval flow.

### Absence Requests
- Same pattern as Rest Day Requests – model `AbsenceRequest`, fields for `type` (sick, personal), `start_date`, `end_date`, `reason`, `status`.
- Provide dedicated controller, form requests, resources, routes, and tests.

### Notifications
- **Model**: `app/Models/Notification.php` – polymorphic relation to notifiable (User, Team, etc.).
- Use Laravel's built‑in notification system; create custom notification classes in `app/Notifications`.
- Provide API endpoint to fetch user notifications: `NotificationController@index` returns paginated `NotificationResource`.
- Routes: `Route::get('notifications', [NotificationController::class, 'index'])->middleware('auth:sanctum');`

### Teams (if applicable)
- **Model**: `Team` (Laravel Jetstream style) – many‑to‑many with users.
- Controllers for team management: `TeamController` (create, update, add/remove members).
- Form Requests for team name, owner.
- Resources and routes as needed.

### General Guidelines
- **Policy**: Create policies (`php artisan make:policy EmployeePolicy`) to restrict actions to owners or admins.
- **Service Layer**: For complex business logic (e.g., schedule conflict detection), create services in `app/Services/ScheduleService.php`.
- **Exception Handling**: Use Form Request validation errors and return standard JSON error format.
- **OpenAPI**: Document each endpoint with request/response schema; generate `openapi.yaml` via `scribe` and place it under `docs/`.
- **CI**: Add GitHub Actions workflow (`.github/workflows/ci.yml`) to run `php artisan test`, `npm run lint`, and `composer validate` on push/PR.
- **Seeders**: Provide database seeders for demo data (`database/seeders/EmployeeSeeder.php`).
- **Docker**: Optional Dockerfile and `docker-compose.yml` for local dev environment.

These specifications translate the database schema into a full Laravel API stack, ensuring each table has a corresponding Model, Controller, Form Request, Resource, routes, tests, and documentation.

## Example: Category Resource

### Model (`app/Models/Category.php`)
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = [
        'name',
        'description',
    ];

    // Optional relationship example
    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
```

### Form Request (`app/Http/Requests/StoreCategoryRequest.php`)
```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCategoryRequest extends FormRequest
{
    public function authorize()
    {
        // Adjust as needed, e.g., only admins can create
        return true;
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255|unique:categories,name',
            'description' => 'nullable|string|max:1000',
        ];
    }
}
```

### Update Form Request (`app/Http/Requests/UpdateCategoryRequest.php`)
```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $categoryId = $this->route('category');
        return [
            'name' => "required|string|max:255|unique:categories,name,$categoryId",
            'description' => 'nullable|string|max:1000',
        ];
    }
}
```

### Resource (`app/Http/Resources/CategoryResource.php`)
```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
```

### Controller (`app/Http/Controllers/Api/CategoryController.php`)
```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;

class CategoryController extends Controller
{
    public function index()
    {
        return CategoryResource::collection(Category::paginate(20));
    }

    public function store(StoreCategoryRequest $request)
    {
        $category = Category::create($request->validated());
        return new CategoryResource($category);
    }

    public function show(Category $category)
    {
        return new CategoryResource($category);
    }

    public function update(UpdateCategoryRequest $request, Category $category)
    {
        $category->update($request->validated());
        return new CategoryResource($category);
    }

    public function destroy(Category $category)
    {
        $category->delete();
        return response(null, 204);
    }
}
```

### Routes (`routes/api.php`)
```php
use App\Http\Controllers\Api\CategoryController;

Route::apiResource('categories', CategoryController::class)->middleware('auth:sanctum');
```

### Test Example (`tests/Feature/Api/CategoryTest.php`)
```php
<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_category()
    {
        $payload = ['name' => 'Demo', 'description' => 'Demo category'];
        $response = $this->actingAs($this->user, 'sanctum')
                         ->postJson('/api/categories', $payload);
        $response->assertCreated()->assertJsonFragment(['name' => 'Demo']);
        $this->assertDatabaseHas('categories', $payload);
    }

    // Additional tests for index, show, update, delete ...
}
```

These snippets illustrate a complete Laravel CRUD cycle for a `Category` entity and can be copied as a template for other resources.

