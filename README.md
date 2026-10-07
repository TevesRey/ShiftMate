# ShiftMate API

ShiftMate is a workforce management system designed to handle shifts, employee schedules, absence requests, and rest day modifications.

## 🛠 Architecture & Design Patterns

The project follows the **Standard Laravel API Pattern**, ensuring a clean separation of concerns:

### 1. Request Validation Layer (`app/Http/Requests`)
Instead of validating data inside controllers, we use **Form Requests**.
- **Store Requests**: Handle strict validation for creating new records (e.g., `StoreEmployeesRequest`).
- **Update Requests**: Use the `sometimes` rule, allowing partial updates without requiring the full object.
- **Enum Validation**: All status fields are validated using `in:value1,value2` to match database migration constraints.

### 2. Controller Layer (`app/Http/Controllers/API`)
Controllers are kept thin and focus on directing traffic.
- **Standard CRUD**: Every resource implements `index`, `store`, `show`, `update`, and `destroy`.
- **Eager Loading**: Uses `.with()` in `index` and `show` methods to prevent "N+1" query problems.
- **Response Format**: Returns consistent JSON responses with appropriate HTTP status codes (200 OK, 201 Created, 204 No Content, 404 Not Found).

### 3. Data Layer (`app/Models`)
Models define the business entity and its relationships.
- **Fillable**: Only safe attributes are mass-assignable.
- **Relations**:- `Schedules` $\rightarrow$ `User`, `Shifts`
- `AbsenceRequests` $\rightarrow$ `Employees`
- `RestDayRequests` $\rightarrow$ `Employees`
- `Employees` $\rightarrow$ `User`

---

## 📂 File Structure

```text
app/
├── Http/
│   ├── Controllers/
│   │   └── API/
│   │       ├── AuthController.php        # Login, Register, Logout
│   │       ├── EmployeeController.php    # Employee Management
│   │       ├── ShiftController.php       # Shift Definitions
│   │       ├── ScheduleController.php    # Work Assignments
│   │       ├── AbsenceRequestController.php # Absence Tracking
│   │       ├── RestDayRequestController.php # Rest Day Changes
│   │       └── NotificationController.php  # User Alerts
│   └── Requests/
│       ├── Store...Request.php          # Create Validation
│       └── Update...Request.php          # Update Validation
├── Models/
│   ├── User.php                          # Core User Account
│   ├── Employees.php                      # Employee Details
│   ├── Shifts.php                        # Shift Times/Names
│   ├── Schedules.php                     # User-Shift mappings
│   ├── AbsenceRequests.php                # Absence tracking
│   ├── RestDayRequests.php               # Rest day tracking
│   └── Notifications.php                  # System alerts
routes/
└── api.php                               # API Endpoint Definitions
database/
└── migrations/                           # DB Schema Definitions
```

---

## 🔐 Security & Middleware

All API endpoints (except Register and Login) are protected by the **Sanctum Middleware**:
- **Middleware**: `auth:sanctum`
- **Requirement**: A valid `Bearer Token` must be provided in the Authorization header.
- **Flow**: Login $\rightarrow$ Receive Token $\rightarrow$ Attach Token to Requests $\rightarrow$ Access Resources.

---

## 🚀 Getting Started & Sample Data

To set up the project with sample data for testing, run:
```bash
php artisan migrate:fresh --seed
```

### Sample Credentials
All seeded users share the same default password: `password`.

**List of Sample Accounts:**
- user-fadel.lexie@example.net : password
- user-bertha72@example.net : password
- user-alycia.mayer@example.com : password
- user-olangosh@example.net : password
- user-rowe.jan@example.net : password
- user-shammes@example.net : password
- user-xmcglynn@example.org : password
- user-virgie54@example.com : password
- user-ernser.dion@example.org : password
- user-lgorczany@example.org : password
- user-briana38@example.com : password
- user-fbeer@example.org : password
- user-jwisoky@example.com : password
- user-alejandra37@example.net : password
- user-amani13@example.org : password
- oreilly.joseph@example.org : password
- gkulas@example.net : password
- tiara.kihn@example.net : password
- raoul.brown@example.net : password
- bdouglas@example.com : password
- gleason.emily@example.net : password
- ufeil@example.net : password
- rboehm@example.com : password
- billie.emmerich@example.org : password
- devon.corkery@example.com : password
- laurel.spinka@example.org : password
- stamm.hazel@example.org : password
- kayli22@example.net : password
- wrunolfsson@example.org : password
- frederick82@example.org : password
- tressie.yundt@example.org : password
- moreilly@example.net : password
- ehagenes@example.com : password

---

## 🔍 Debugging Guide

If you encounter issues, follow these steps to identify the root cause:

### 1. Common HTTP Errors
| Code | Meaning | Common Cause | Fix |
| :--- | :--- | :--- | :--- |
| **401** | Unauthorized | Missing or expired token | Check `Authorization: Bearer {token}` header |
| **403** | Forbidden | Insufficient permissions | Check if the User role/status is `active` |
| **422** | Unprocessable | Validation failed | Check request body against `app/Http/Requests` |
| **404** | Not Found | ID does not exist | Verify the record ID in the database |
| **500** | Server Error | Code crash / DB error | Check `storage/logs/laravel.log` |

### 2. Log Checking
The most detailed information is stored in the Laravel logs:
```bash
tail -f storage/logs/laravel.log
```

### 3. Database Verification
If data isn't appearing as expected, check the migration state:
```bash
php artisan migrate:status
```

### 4. API Testing
Use a tool like **Postman** or **Insomnia**. 
**Pro Tip**: Set a "Collection Variable" for your `token` so you don't have to copy-paste it into every single request.
