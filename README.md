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
Models define the business entity and their relationships.
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
- user-fadel.lexie@example.net : employee : password
- user-bertha72@example.net : employee : password
- user-alycia.mayer@example.com : employee : password
- user-olangosh@example.net : employee : password
- user-rowe.jan@example.net : employee : password
- user-shammes@example.net : employee : password
- user-xmcglynn@example.org : employee : password
- user-virgie54@example.com : employee : password
- user-ernser.dion@example.org : employee : password
- user-lgorczany@example.org : employee : password
- user-briana38@example.com : employee : password
- user-fbeer@example.org : employee : password
- user-jwisoky@example.com : password
- user-alejandra37@example.net : employee : password
- user-amani13@example.org : employee : password
- oreilly.joseph@example.org : employee : password
- gkulas@example.net : employee : password
- tiara.kihn@example.net : employee : password
- raoul.brown@example.net : employee : password
- bdouglas@example.com : employee : password
- gleason.emily@example.net : employee : password
- ufeil@example.net : employee : password
- rboehm@example.com : employee : password
- billie.emmerich@example.org : employee : password
- devon.corkery@example.com : employee : password
- laurel.spinka@example.org : employee : password
- stamm.hazel@example.org : employee : password
- kayli22@example.net : employee : password
- wrunolfsson@example.org : employee : password
- frederick82@example.org : employee : password
- tressie.yundt@example.org : employee : password
- moreilly@example.net : employee : password
- ehagenes@example.com : employee : password

---

## 📡 API Testing (cURL Commands)

Replace `YOUR_DOMAIN` with your actual domain (e.g., `http://localhost:8000`) and `YOUR_TOKEN` with the token received after login.

### 🔑 Authentication
**Register**
```bash
curl -X POST YOUR_DOMAIN/api/register -H "Content-Type: application/json" -d '{"name":"John Doe", "email":"john@example.com", "password":"password", "password_confirmation":"password"}'
```

**Login**
```bash
curl -X POST YOUR_DOMAIN/api/login -H "Content-Type: application/json" -d '{"email":"john@example.com", "password":"password"}'
```

**Get Current User**
```bash
curl -X GET YOUR_DOMAIN/api/me -H "Authorization: Bearer YOUR_TOKEN" -H "Accept: application/json"
```

**Logout**
```bash
curl -X POST YOUR_DOMAIN/api/logout -H "Authorization: Bearer YOUR_TOKEN" -H "Accept: application/json"
```

### 👥 Employees
**List All**
```bash
curl -X GET YOUR_DOMAIN/api/employees -H "Authorization: Bearer YOUR_TOKEN" -H "Accept: application/json"
```

**Create**
```bash
curl -X POST YOUR_DOMAIN/api/employees -H "Authorization: Bearer YOUR_TOKEN" -H "Content-Type: application/json" -H "Accept: application/json" -d '{"name":"Jane Doe", "user_id":1, "position":"Developer"}'
```

**Get One**
```bash
curl -X GET YOUR_DOMAIN/api/employees/1 -H "Authorization: Bearer YOUR_TOKEN" -H "Accept: application/json"
```

**Update**
```bash
curl -X PUT YOUR_DOMAIN/api/employees/1 -H "Authorization: Bearer YOUR_TOKEN" -H "Content-Type: application/json" -H "Accept: application/json" -d '{"position":"Senior Developer"}'
```

**Delete**
```bash
curl -X DELETE YOUR_DOMAIN/api/employees/1 -H "Authorization: Bearer YOUR_TOKEN" -H "Accept: application/json"
```

### 🕒 Shifts
**List All**
```bash
curl -X GET YOUR_DOMAIN/api/shifts -H "Authorization: Bearer YOUR_TOKEN" -H "Accept: application/json"
```

**Create**
```bash
curl -X POST YOUR_DOMAIN/api/shifts -H "Authorization: Bearer YOUR_TOKEN" -H "Content-Type: application/json" -H "Accept: application/json" -d '{"name":"Morning Shift", "start_time":"08:00", "end_time":"16:00"}'
```

**Get One**
```bash
curl -X GET YOUR_DOMAIN/api/shifts/1 -H "Authorization: Bearer YOUR_TOKEN" -H "Accept: application/json"
```

**Update**
```bash
curl -X PUT YOUR_DOMAIN/api/shifts/1 -H "Authorization: Bearer YOUR_TOKEN" -H "Content-Type: application/json" -H "Accept: application/json" -d '{"name":"Early Morning Shift"}'
```

**Delete**
```bash
curl -X DELETE YOUR_DOMAIN/api/shifts/1 -H "Authorization: Bearer YOUR_TOKEN" -H "Accept: application/json"
```

### 📅 Schedules
**List All**
```bash
curl -X GET YOUR_DOMAIN/api/schedules -H "Authorization: Bearer YOUR_TOKEN" -H "Accept: application/json"
```

**Create**
```bash
curl -X POST YOUR_DOMAIN/api/schedules -H "Authorization: Bearer YOUR_TOKEN" -H "Content-Type: application/json" -H "Accept: application/json" -d '{"employee_id":1, "shift_id":1, "date":"2023-10-10"}'
```

**Get One**
```bash
curl -X GET YOUR_DOMAIN/api/schedules/1 -H "Authorization: Bearer YOUR_TOKEN" -H "Accept: application/json"
```

**Update**
```bash
curl -X PUT YOUR_DOMAIN/api/schedules/1 -H "Authorization: Bearer YOUR_TOKEN" -H "Content-Type: application/json" -H "Accept: application/json" -d '{"shift_id":2}'
```

**Delete**
```bash
curl -X DELETE YOUR_DOMAIN/api/schedules/1 -H "Authorization: Bearer YOUR_TOKEN" -H "Accept: application/json"
```

### 🏖 Rest Day Requests
**List All**
```bash
curl -X GET YOUR_DOMAIN/api/rest-day-requests -H "Authorization: Bearer YOUR_TOKEN" -H "Accept: application/json"
```

**Create**
```bash
curl -X POST YOUR_DOMAIN/api/rest-day-requests -H "Authorization: Bearer YOUR_TOKEN" -H "Content-Type: application/json" -H "Accept: application/json" -d '{"employee_id":1, "requested_date":"2023-10-15", "reason":"Personal"}'
```

**Get One**
```bash
curl -X GET YOUR_DOMAIN/api/rest-day-requests/1 -H "Authorization: Bearer YOUR_TOKEN" -H "Accept: application/json"
```

**Update**
```bash
curl -X PUT YOUR_DOMAIN/api/rest-day-requests/1 -H "Authorization: Bearer YOUR_TOKEN" -H "Content-Type: application/json" -H "Accept: application/json" -d '{"status":"approved"}'
```

**Delete**
```bash
curl -X DELETE YOUR_DOMAIN/api/rest-day-requests/1 -H "Authorization: Bearer YOUR_TOKEN" -H "Accept: application/json"
```

### 🤒 Absence Requests
**List All**
```bash
curl -X GET YOUR_DOMAIN/api/absence-requests -H "Authorization: Bearer YOUR_TOKEN" -H "Accept: application/json"
```

**Create**
```bash
curl -X POST YOUR_DOMAIN/api/absence-requests -H "Authorization: Bearer YOUR_TOKEN" -H "Content-Type: application/json" -H "Accept: application/json" -d '{"employee_id":1, "start_date":"2023-10-12", "end_date":"2023-10-14", "reason":"Sick Leave"}'
```

**Get One**
```bash
curl -X GET YOUR_DOMAIN/api/absence-requests/1 -H "Authorization: Bearer YOUR_TOKEN" -H "Accept: application/json"
```

**Update**
```bash
curl -X PUT YOUR_DOMAIN/api/absence-requests/1 -H "Authorization: Bearer YOUR_TOKEN" -H "Content-Type: application/json" -H "Accept: application/json" -d '{"status":"rejected"}'
```

**Delete**
```bash
curl -X DELETE YOUR_DOMAIN/api/absence-requests/1 -H "Authorization: Bearer YOUR_TOKEN" -H "Accept: application/json"
```

### 🔔 Notifications
**List All**
```bash
curl -X GET YOUR_DOMAIN/api/notifications -H "Authorization: Bearer YOUR_TOKEN" -H "Accept: application/json"
```

**Create**
```bash
curl -X POST YOUR_DOMAIN/api/notifications -H "Authorization: Bearer YOUR_TOKEN" -H "Content-Type: application/json" -H "Accept: application/json" -d '{"user_id":1, "message":"Your shift has been updated"}'
```

**Get One**
```bash
curl -X GET YOUR_DOMAIN/api/notifications/1 -H "Authorization: Bearer YOUR_TOKEN" -H "Accept: application/json"
```

**Update**
```bash
curl -X PUT YOUR_DOMAIN/api/notifications/1 -H "Authorization: Bearer YOUR_TOKEN" -H "Content-Type: application/json" -H "Accept: application/json" -d '{"read":true}'
```

**Delete**
```bash
curl -X DELETE YOUR_DOMAIN/api/notifications/1 -H "Authorization: Bearer YOUR_TOKEN" -H "Accept: application/json"
```

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
