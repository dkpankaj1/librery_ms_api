# Library Management System — Phase-wise Development Plan

> **Project:** Multi-Tenant (SaaS) Library / Study-Place Management System
> **Level:** 🟡 Medium (simple, no role/permission, no complex workflows)
> **Tech Stack:** Laravel (REST API) + MySQL + Flutter (Admin App)
> **Date:** 29 October 2026

## 📌 Scope — Medium Level (Simplification)

Ye **medium level** software hai, complex enterprise features nahi honge:

| Feature                              | Status           |
| ------------------------------------ | ---------------- |
| Role & Permission system             | ❌ **Nahi hoga** |
| Simple Auth (Owner/Staff login only) | ✅ Hoga          |
| Staff active/deactive (simple flag)  | ✅ Hoga          |
| Multi-tenancy (library_id)           | ✅ Hoga          |
| License/Activation                   | ✅ Hoga          |
| Complex granular permissions         | ❌ Nahi hoga     |
| Approval workflows                   | ❌ Nahi hoga     |

> **Access model:** Sirf 2 level — **Super Admin** (saari libraries) aur **Library User/Staff** (apni library ka poora access). Koi role/permission hierarchy nahi.

## 🚀 Big Update — Multi-Tenancy (SaaS) Model

**Ab ye app single library ke liye nahi, balki multiple libraries ko BECHEGI (SaaS).**

### Core Saas Concepts

1. **Har record ek library se associated** hoga (`library_id` foreign key har table me)
2. **Har library ka apna activation + license** hoga
3. **Data isolation** — ek library apni hi data dekh sakti hai, doosri ki nahi
4. **Ek hi codebase + ek hi database** — multiple libraries ko serve karega
5. **License/subscription** expire hone par app access block ho jayega

### SaaS Business Model

| Model                                     | Description                                          |
| ----------------------------------------- | ---------------------------------------------------- |
| **Multi-Schema**                          | Har library ka alag database (expensive, complex)    |
| **Single Database + `library_id`** ✅     | Shared tables, har row me `library_id` (recommended) |
| **Single Database + library-level model** | Landlord + tenant databases (overkill)               |

> **Hum `Single Database + library_id` model use karenge** — ye sabse practical, scalable aur maintainable hai is stage par.

### Data Isolation Rule (Sabse Important)

```
HAR query, HAR insert, HAR update, HAR delete
=> WHERE library_id = CURRENT_LIBRARY

Agar ye rule miss hua to:
  → Data leak (Library A ko Library B ka data dikh jayega)  ❌
```

---

## Phase Overview (Ek Nazar Me)

| Phase | Naam                             | Focus                                       | Estimated Duration |
| ----- | -------------------------------- | ------------------------------------------- | ------------------ |
| 0     | Project Foundation               | Environment, repo, structure, tenancy       | 3–4 din            |
| 1     | **Multi-Tenancy Core**           | Library, license, tenant scoping            | 5–7 din            |
| 2     | Database Design                  | Tables, relations, constraints + library_id | 4–5 din            |
| 3     | Authentication & User Management | Login, staff, tenant auth (no roles)        | 3–4 din            |
| 4     | Master Data Modules              | Zones, Seats, Shifts (library-scoped)       | 5–7 din            |
| 5     | Student Management               | Student CRUD, status (library-scoped)       | 3–4 din            |
| 6     | Seat Booking & Allotment         | Booking workflow, seat availability         | 6–8 din            |
| 7     | Fee Plans & Pricing              | Fee plans, discounts (per-library)          | 3–4 din            |
| 8     | Payment & Dues                   | Payments, invoices, ledger                  | 5–7 din            |
| 9     | Fee Reminders                    | Overdue, WhatsApp/SMS                       | 4–5 din            |
| 10    | Attendance                       | Check-in/out, no-show                       | 4–5 din            |
| 11    | Reports & Analytics              | Dashboard, exports (per-library)            | 5–7 din            |
| 12    | **Subscription & Billing**       | License renewal, billing, MT admin panel    | 5–7 din            |
| 13    | Testing & QA                     | Integration, UAT, tenancy isolation test    | 6–7 din            |
| 14    | Deployment & Go-Live             | Production, monitoring, multi-tenant        | 4–6 din            |

> Multitenancy isliye Phase 1 me hi hai kyunki ye **foundation** hai — baad me add karna bahut costly aur risky hoga.

---

## Phase 0 — Project Foundation

### Kya Karna Hai

1. **Laravel project setup** — `composer create-project laravel/laravel library-api`
2. **Versioned API structure** — `/api/v1` route prefix
3. **Environment config** — `.env` me MySQL DB, app key, timezone
4. **Sanctum install** — `composer require laravel/sanctum`
5. **Tenancy package install** — `stancl/tenancy` (tenant awareness base) YA custom trait approach
6. **Code structure & conventions** — Form Requests, Resource classes, Service classes, **Tenant-aware Base Model**
7. **Git repository + branch strategy** — `main`, `develop`, `feature/*`
8. **Base migrations** (`users`, `password_resets`) aur default seeders
9. **Postman collection** — API testing ke liye base setup
10. **Packages install**:

- `laravel/sanctum` (auth)
- `stancl/tenancy` (multi-tenant support)
- `barryvdh/laravel-dompdf` (reports/invoice PDF)
- `maatwebsite/excel` (report export)

> ❌ `spatie/laravel-permission` install NAHI karna — role/permission system nahi hai.

### Deliverables

- ✅ Running Laravel app with `/api/v1` prefix
- ✅ MySQL connection working
- ✅ Sanctum configured
- ✅ Tenancy base setup (library resolution)
- ✅ Git repo with proper branches
- ✅ Base folder structure (Models, Services, Resources, Requests)
- ✅ Postman base collection

### Outcome

> **Ek stable foundation** — jis par bina kisi configuration issue ke baki saare modules (including multi-tenancy) build ho sakenge. Har developer same structure follow karega.

---

## Phase 1 — Multi-Tenancy Core (LIBRARY + LICENSE)

Ye phase pura project ka **backbone** hai. Iske bina kuch bhi kaam nahi karega.

### Kya Karna Hai

#### A. Library Management

1. **`libraries` table banayein**:

```
libraries
├── id
├── name                    (Library ka naam)
├── slug / code             (unique, e.g., "sunrise-library")
├── owner_name
├── owner_email
├── owner_phone
├── address
├── logo
├── database_connection     (optional, agar multi-schema future me)
├── status                  (active / inactive / suspended)
└── timestamps
```

2. **Library CRUD** — Super Admin hi create/edit karega (Phase 12 se link)

#### B. License & Activation Management

3. **`licenses` table banayein**:

```
licenses
├── id
├── library_id              (which library)
├── license_key             (unique, generated)
├── plan                    (basic / pro / enterprise)
├── max_seats               (plan ke hisaab se seat limit)
├── max_staff               (kitne staff users allowed)
├── max_zones
├── starts_at               (license start date)
├── expires_at              (license expiry date)
├── status                  (active / expired / revoked / trial)
├── auto_renew              (bool)
└── timestamps
```

4. **Activation flow** — library activate → license generate → plan assign
5. **License validation** — har request par check:
   - Kya library active hai?
   - Kya license active hai?
   - Kya `expires_at` future me hai?
   - Kya plan ki limits (seats/staff) exceed to nahi?

#### C. Tenant Scoping (Data Isolation)

6. **Tenant-aware Base Model** — `Model` ko extend karke `library_id` auto-scope:

```php
// BaseModel.php
class BaseModel extends Model
{
    // Global scope: har query me auto library_id add
    protected static function booted()
    {
        static::addGlobalScope('library', function ($builder) {
            if (auth()->check() && auth()->user()->library_id) {
                $builder->where('library_id', auth()->user()->library_id);
            }
        });
    }
}
```

7. **Tenant Middleware** — har request me current library resolve karna
8. **Helper method** — `currentLibraryId()` har jagah use karna

#### D. Super Admin vs Library User Identity

9. **2-level access (simple)**:
   - `super_admin` — saari libraries dekh/control kar sakta hai (system ka admin)
   - `library_user` — sirf apni library ka poora data
   - ❌ Koi alag manager/staff role ya granular permission nahi

### Deliverables

- ✅ `libraries` table + CRUD (Super Admin)
- ✅ `licenses` table + activation flow
- ✅ License validation middleware
- ✅ Tenant-aware base model (auto `library_id` scope)
- ✅ Tenant resolution middleware
- ✅ Super Admin vs Library User (2-level) separation
- ✅ Library status (active/suspended) enforcement

### Outcome

> **Multi-tenant core ready** — har library ka apna isolated data, apna license, aur apna activation. Kisi bhi feature me library ka data leak nahi hoga. License expire hote hi library ka access band ho jayega.

---

## Phase 2 — Database Design & Migrations

### Kya Karna Hai

1. **Complete ERD design** — saari tables aur relations ka diagram (with `library_id`)
2. **Migrations create karna** (neeche table list):

| #   | Table                | Description                                       | library_id? |
| --- | -------------------- | ------------------------------------------------- | ----------- |
| 0   | `libraries`          | Har library ka record (SaaS tenant)               | — (root)    |
| 1   | `admin_users`        | Owner/staff ke users (library-scoped)             | ✅          |
| 2   | `zones`              | General, AC, Silent, Cabin zones                  | ✅          |
| 3   | `seats`              | Zone ke andar ki seats                            | ✅          |
| 4   | `shifts`             | Morning/Evening/Night/custom shifts               | ✅          |
| 5   | `zone_shift_pricing` | Zone + Shift ka alag rate                         | ✅          |
| 6   | `students`           | Student details + guardian + photo                | ✅          |
| 7   | `fee_plans`          | Yearly/Half-yearly/Quarterly/Monthly/Weekly/Daily | ✅          |
| 8   | `bookings`           | Student ka seat booking record                    | ✅          |
| 9   | `invoices`           | Invoice/receipt records                           | ✅          |
| 10  | `payments`           | Payment transactions (partial/full)               | ✅          |
| 11  | `fee_reminders`      | Reminder records + follow-up                      | ✅          |
| 12  | `attendances`        | Check-in/out records                              | ✅          |
| 13  | `audit_logs`         | Har critical action ka log                        | ✅          |

3. **`library_id` foreign key** — HAR table me (except `libraries` khud)
4. **Foreign keys & constraints**:
   - `zones` → `seats` (one-to-many)
   - `bookings` → `zone`, `seat`, `shift`, `student`
   - `bookings` → `invoices` (one-to-many)
   - `invoices` → `payments` (one-to-many)
5. **Double booking prevention** — `bookings` table me unique constraint → `(library_id, seat_id, shift_id, booking_date)`
6. **Indexes** — `library_id` + frequently searched columns (date, zone, shift, student) par indexes
7. **Seeders** — default shifts, fee plans, sample zones + 1 demo library

### Deliverables

- ✅ Complete ERD diagram (with library_id)
- ✅ Saari migrations with `library_id` + foreign keys
- ✅ Unique constraints (library-scoped) for double-booking prevention
- ✅ Indexes added (library_id par composite)
- ✅ Base seeders + demo library seed

### Outcome

> **Production-ready multi-tenant database schema** — jisme koi bhi data integrity issue nahi hoga. Double booking, invalid seat, wrong fee, aur **cross-library data leak** — sab database level par prevent hoga.

---

## Phase 3 — Authentication & User Management (Simple)

### Kya Karna Hai

1. **Login API** — email/password → Sanctum token (token me library context)
2. **Logout API** — token revoke
3. **Library binding** — login ke time check karna ki user kaunsi library se belong karta hai
4. **Simple user type (2 level only)**:
   - `super_admin` — saari libraries manage karta hai (system ke liye)
   - `library_user` — apni library ka poora access
   - ❌ Koi alag manager/staff role ya granular permission NAHI
5. **Staff management CRUD** (simple):
   - Staff create/edit (apni library ke andar)
   - Activate/deactivate (ek `is_active` flag)
   - **Staff count check** (license `max_staff` limit)
6. **Password change API**
7. **Middleware** — tenant resolution + license check (simple auth middleware)
8. **Auth Resource** — user profile + library info response

### Deliverables

- ✅ Login/Logout working with token (tenant-aware)
- ✅ Library binding on auth
- ✅ 2-level user type (super_admin / library_user)
- ✅ Staff create/edit + activate/deactivate (`is_active` flag)
- ✅ Staff count limit check
- ✅ Password change
- ✅ Protected routes (unauthorized + cross-library access blocked)

### Outcome

> **Simple secure multi-tenant auth** — sirf 2 type ke users. Har user apni library ka data access karega, doosri library ka nahi. License expire ho to access band. Koi complex role/permission nahi.

---

## Phase 4 — Master Data Modules (Zones, Seats, Shifts)

### Kya Karna Hai

1. **Zone Management** (library-scoped):
   - Zone create/edit/deactivate
   - Floor/location, description, capacity, display order
   - Zone-wise seat list aur occupancy
2. **Seat Management**:
   - Zone ke andar seat add/edit/disable
   - Seat number, row/section
   - Status: Available, Booked, Maintenance, Inactive
   - Seat transfer aur maintenance block
3. **Shift Management**:
   - Shift create/edit (Morning, Evening, Night, Full-day, Custom)
   - Start time, end time, active/inactive
4. **Zone-Shift Pricing**:
   - Har zone + shift combination ka alag rate
5. **List APIs with pagination, search, filter**

### Deliverables

- ✅ Zone full CRUD + status
- ✅ Seat full CRUD + status + maintenance block
- ✅ Shift full CRUD
- ✅ Zone-shift pricing setup
- ✅ Pagination/search/filter on all lists

### Outcome

> **Complete master data module** — Admin kisi bhi zone, seat, shift ko create/edit/block kar sakega. Ye sab modules Phase 5 (booking) ke liye base banenge.

---

## Phase 5 — Student Management

### Kya Karna Hai

1. **Student CRUD** (library-scoped):
   - Name, mobile, guardian mobile, address
   - Photo upload
   - Admission date, student ID (auto-generate — library-specific prefix)
   - Notes
2. **Student status** — Active/Inactive
3. **Student search** — name, mobile, student ID se (sirf apni library me)
4. **Student detail API** — booking, fee, payment, attendance, reminder history ek saath
5. **Duplicate check** — same library me same mobile number se duplicate student na bane
6. **Student ID format** — `{library_code}-{sequence}` (har library ka apna sequence)

### Deliverables

- ✅ Student full CRUD (library-scoped)
- ✅ Photo upload (storage — per-library folder)
- ✅ Auto student ID (library-specific)
- ✅ Search by name/mobile/ID
- ✅ Complete student profile with full history

### Outcome

> **Library-wise isolated student database** — har library ke students bilkul alag honge. Student ID se pata chalega ki wo kaunsi library ka hai.

---

## Phase 6 — Seat Booking & Allotment (CORE Module)

### Kya Karna Hai

1. **Seat Availability API**:
   - Date + Zone + Shift select
   - Active seats load
   - Booked/maintenance seats filter
   - Available/Booked/Checked-in status
2. **New Booking Workflow**:
   - Date → Zone → Shift → Seat select
   - Existing student / new student
   - Fee plan select
   - Validity + amount auto-calculate
   - Booking confirm
3. **Booking Edit/Cancel/Renew/Transfer**
4. **Seat Transfer (Shift Change)**:
   - Purani booking cancel
   - Nayi booking create
   - Seat change history save
5. **Double booking prevention** — database unique constraint
6. **Booking History**:
   - Student-wise booking history
   - Seat-wise history (2 month pahle tak)

### Deliverables

- ✅ Seat availability with live status
- ✅ New booking full workflow
- ✅ Booking edit/cancel/renew/transfer
- ✅ Seat/shift change with history
- ✅ Double booking blocked
- ✅ Booking history APIs

### Outcome

> **Poora booking system working** — admin kisi bhi student ko bina conflict ke seat allot karega, transfer karega, renew karega. Ye system ka sabse important module hai.

---

## Phase 7 — Fee Plans & Pricing

### Kya Karna Hai

1. **Fee Plan CRUD**:
   - Yearly (12 months)
   - Half-yearly (6 months)
   - Quarterly (3 months)
   - Monthly (1 month)
   - Weekly (7 days)
   - Daily (1 day)
2. **Zone/Shift-wise rate setup**
3. **Additional charges**:
   - Discount (amount / percentage)
   - Admission fee
   - Security deposit
   - Fine
   - Miscellaneous
4. **Amount auto-calculation logic** — plan + duration + additional charges

### Deliverables

- ✅ Saare fee plans ready
- ✅ Zone/shift-wise rate
- ✅ Discount/admission/deposit/fine handling
- ✅ Auto amount calculation

### Outcome

> **Flexible pricing engine** — koi bhi plan, koi bhi rate, koi bhi discount easily configure ho sakega aur amount automatically calculate hoga.

---

## Phase 8 — Payment & Dues Management

### Kya Karna Hai

1. **Payment Methods** — Cash, UPI, Card, Bank Transfer
2. **Payment Entry** — Full payment & Partial payment
3. **Invoice/Receipt generation** — PDF download
4. **Due status tracking**:
   - Unpaid, Partial, Paid, Overdue
5. **Due date & outstanding amount**
6. **Student-wise dues & payment ledger**
7. **Payment history**

### Deliverables

- ✅ Multi-method payment entry
- ✅ Partial + full payment support
- ✅ PDF invoice/receipt
- ✅ Due status + outstanding calculation
- ✅ Payment ledger

### Outcome

> **Complete financial record** — har payment track hoga, outstanding automatically calculate hoga, aur invoice PDF generate hogi.

---

## Phase 9 — Fee Reminders

### Kya Karna Hai

1. **Overdue students list** — filter by due date
2. **WhatsApp/SMS pre-filled message**:
   - Student name, amount, due date auto-add
   - Editable template
3. **Reminder status** — Sent, Follow-up required, Confirmed
4. **Follow-up tracking** — reminder date, note, next follow-up date
5. **Message templates CRUD**

### Deliverables

- ✅ Overdue students filter
- ✅ Pre-filled WhatsApp/SMS message
- ✅ Editable templates
- ✅ Reminder + follow-up status tracking

### Outcome

> **Automated reminder system** — admin ek click me overdue students ko WhatsApp/SMS reminder bhej sakega aur follow-up track kar sakega.

---

## Phase 10 — Attendance

### Kya Karna Hai

1. **Check-in / Check-out**
2. **Shift-wise attendance**
3. **Attendance status** — Present, Absent, No-show
4. **No-show handling** — seat manually release
5. **Attendance reports** — daily/weekly/monthly

### Deliverables

- ✅ Check-in/out system
- ✅ Shift-wise attendance
- ✅ No-show seat release
- ✅ Attendance history/reports

### Outcome

> **Saadharan attendance tracking** — kaun aaya, kaun absent, kaun no-show, aur no-show ki seat wapas available kar sakte hain.

---

## Phase 11 — Reports & Analytics

### Kya Karna Hai

1. **Dashboard** — Daily/Weekly/Monthly/Yearly
2. **Occupancy Reports** — zone, shift, seat-wise
3. **Financial Reports**:
   - Total collection
   - Outstanding dues
   - Revenue by fee plan & payment method
4. **Student Reports**:
   - Active students, new admissions, renewals
5. **Attendance Reports** — no-show, seat utilization
6. **Due Ageing** — 0–7 days, 8–30 days, 31+ days
7. **Export** — Excel/PDF export functionality
8. **Charts** — `fl_chart` integration (Flutter side)

### Deliverables

- ✅ Admin dashboard
- ✅ Occupancy, financial, student, attendance reports
- ✅ Due ageing report
- ✅ Excel/PDF export
- ✅ Chart visualizations

### Outcome

> **Poora business intelligence** — owner ko ek nazar me pata chalega kitna revenue, kitna pending, kitni occupancy.

---

## Phase 12 — Subscription & Billing (License Management)

### Kya Karna Hai

1. **Plan Management** (system level):
   - Basic / Pro / Enterprise plans
   - Har plan ki limits (max seats, max staff, max zones)
2. **License Generation**:
   - Nayi library banate hi license key generate
   - License key expiry date set
3. **License Renewal**:
   - Expiry aane par renew flow
   - Auto-renew option
4. **Billing/Invoice** (library ke liye):
   - Hamare business ki taraf se library ko bill
   - Payment record (offline/online)
5. **Trial Period**:
   - Nayi library ko trial (e.g., 15 din)
6. **Suspension/Revoke**:
   - Payment nahi aaya to library suspend
   - License revoke option
7. **Super Admin Panel** (multi-library management):
   - Saari libraries ki list
   - Har library ka status, license, expiry
   - Library activate/suspend/delete

### Deliverables

- ✅ Plan CRUD (Basic/Pro/Enterprise)
- ✅ License generation + expiry
- ✅ Renewal + auto-renew flow
- ✅ Library billing/invoice
- ✅ Trial period
- ✅ Suspend/revoke
- ✅ Super Admin multi-library panel

### Outcome

> **Complete subscription lifecycle** — Super Admin har library ka license, expiry, aur payment manage karega. License expire → app block, renew → app active. Ye SaaS business ka revenue engine hai.

---

## Phase 13 — Testing & QA

### Kya Karna Hai

1. **API Testing** — Postman/feature tests
2. **Unit Tests** — Services, calculations (amount, due, validity, license)
3. **Integration Testing**:
   - Booking workflow end-to-end
   - Payment + invoice + due status
   - Seat transfer + history
   - License expiry → access block
4. **Double booking attempt test** — ensure block hota hai
5. **Cross-library isolation test** — Library A user ko Library B ka data nahi dikhna chahiye
6. **UAT (User Acceptance Testing)** — owner/staff ke saath workflows verify

### Deliverables

- ✅ Test cases with expected results
- ✅ Passing unit & feature tests
- ✅ Integration tests
- ✅ Multi-tenant isolation tests
- ✅ UAT sign-off

### Outcome

> **Bug-free application** — har critical workflow test hokar pass ho chuka hoga, koi data loss ya wrong calculation nahi hogi.

---

## Phase 14 — Deployment & Go-Live

### Kya Karna Hai

1. **Server setup** — Linux VPS, Nginx, PHP-FPM
2. **SSL certificate** — HTTPS secure
3. **Database backup strategy** — daily automated backup
4. **Env config** — production settings
5. **Laravel optimize** — cache config, route, view
6. **Queue setup** — background jobs (reminders, reports)
7. **Monitoring** — error logging (Sentry/Bugsnag optional)
8. **Production data migration** — seed data import
9. **Go-live checklist**

### Deliverables

- ✅ Production server running
- ✅ SSL + HTTPS
- ✅ Automated backups
- ✅ Error monitoring
- ✅ Optimized Laravel
- ✅ Live application

### Outcome

> **Live & stable application** — users use kar sakte hain, data secure hai, backups regular hain, aur errors track hote hain.

---

## Delivery Timeline (Summary)

```
Phase 0–1   : Foundation + Multi-tenant   (Week 1)
Phase 2–3   : Database + Auth               (Week 1–2)
Phase 4–5   : Master + Student              (Week 2)
Phase 6     : Booking (Core)                (Week 2–3)
Phase 7–8   : Fee + Payment                 (Week 3)
Phase 9–10  : Reminder + Attendance         (Week 4)
Phase 11    : Reports                       (Week 4–5)
Phase 12    : Subscription/Billing          (Week 5)
Phase 13    : Testing & QA                  (Week 5)
Phase 14    : Deployment                    (Week 5–6)
```

> **Total estimated: ~5–6 weeks** (medium level — role/permission complexity hata di gayi hai)

---

## Cross-Cutting Concerns (Har Phase Me Apply Honge)

1. **Validation** — har input par proper validation (Form Requests)
2. **Error Handling** — consistent error response format
3. **Loading/Empty/Success/Error states** — Flutter UI me
4. **Audit Logs** — critical actions log honge
5. **API Versioning** — `/api/v1` stable
6. **Security** — sanitization, SQL injection prevention (Eloquent), library scope check
7. **Multi-tenancy isolation** — har query me `library_id` filter (data leak prevention)
8. **Code Reviews** — feature branch se develop me merge karne se pehle

---

## Definition of Done (Har Phase Ke Liye)

Ek phase tabhi complete maana jayega jab:

- ✅ Saare deliverables ready hon
- ✅ Code test pass kare
- ✅ No critical bug
- ✅ Code review ho chuka ho
- ✅ Postman/Karate tests updated hon
- ✅ Documentation updated ho
- ✅ Seed/test data available ho

---

## Risks & Mitigations

| Risk                        | Mitigation                                         |
| --------------------------- | -------------------------------------------------- |
| Double booking              | Database unique constraint (Phase 2)               |
| Wrong fee calculation       | Central calculation service + unit tests (Phase 7) |
| Data loss                   | Daily backup (Phase 14)                            |
| Unauthorized access         | Sanctum + tenant middleware (Phase 3)              |
| **Cross-library data leak** | `library_id` global scope + tests (Phase 1, 12)    |
| License misuse              | License validation middleware (Phase 1)            |
| Scope creep                 | Phase-wise approval, freeze requirements per phase |
| Performance (large data)    | Indexes + pagination (Phase 2, 4)                  |

---

> **Ye document ek living document hai** — jaise-jaise development aage badhegi, isme updates hote rahenge.
