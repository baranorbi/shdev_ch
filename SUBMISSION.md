# Submission Notes - CodeStudio DevChallenge

## Executive Summary

For this take-home interview assignment, I selected **4 high-impact tasks** spanning **Levels 2, 4, and 5** from `todo.md`. Following the instructions to prioritize **quality over quantity**, I focused on clean architecture, automated CI validation, practical developer tooling, and database-level concurrency resilience.

| Task # | Level | Task Title | Key Outcome |
| :---: | :---: | :--- | :--- |
| **9** | Level 4 | **Service Layer & Validation Hardening** | Extracted booking logic into `BookingService`, introduced `BookingData` DTO, centralized validation in `BookingRequest` FormRequest, and added unit/feature tests. |
| **11** | Level 4 | **Continuous Integration (CI)** | Created a GitHub Actions workflow running tests (`php artisan test`), static analysis (`phpstan`), and code style checks (`pint`). |
| **3** | Level 2 | **Seeder & Factories + UX Polish** | Built rerunnable `DatabaseSeeder`, `BookingFactory`, and `HairdresserSeeder`, plus fixed Bootstrap 5 pagination styling. |
| **14** | Level 5 | **Concurrency-Safe Bookings** | Protected bookings with atomic `DB::transaction()`, pessimistic locking (`lockForUpdate`), unique index constraint handling, and race condition tests. |

---

## How to Run, Test, and Verify

### Run Automated Tests
```powershell
php artisan test
```
Runs the full PHPUnit test suite (Unit & Feature tests for service layer, validation, and concurrency safety).

### Check Code Formatting
```powershell
vendor/bin/pint --test
```
Verifies all PHP code adheres to Laravel code style guidelines.

### Seed Database with Sample Data
```powershell
php artisan migrate:fresh --seed
```
Seeds multiple hairdresser user accounts (`hairdresser@example.com` / password: `password`) and sample weekday bookings.

---

## Detailed Task Notes

### Task 9 - Service Layer and Validation Hardening (Level 4)
Refactored booking creation into a dedicated service layer (`BookingService`) and introduced a small DTO (`BookingData`) to carry validated booking data. Validation now lives in a dedicated FormRequest (`BookingRequest`), and JSON requests return a consistent validation error payload (`422 Unprocessable Entity`).

- **Why I picked it:**
  - Matches the existing booking flow while establishing clean separation of concerns and strong domain boundaries.
  - Demonstrates structuring business logic outside of HTTP controllers and testing services in isolation.
- **Trade-offs / assumptions:**
  - Kept public booking form and field names unchanged for backwards compatibility.
  - Centered the booking model around `scheduled_at`, keeping persistence simpler than a date/hour split.

---

### Task 11 - Continuous Integration (Level 4)
Added a GitHub Actions workflow (`.github/workflows/ci.yml`) that installs dependencies, builds frontend assets, runs the test suite, checks code style with Pint, and runs static analysis with PHPStan.

- **Why I picked it:**
  - Demonstrates engineering maturity and ensures incoming code changes are automatically verified.
  - Aligns the repo with collaborative, production-grade team workflows.
- **Trade-offs / assumptions:**
  - Scoped Pint and PHPStan to changed PHP files so pre-existing legacy issues in untouched files do not block new PRs.
  - Configured automated key generation and frontend asset compilation inside CI to prevent false-positive view rendering errors.

---

### Task 3 - Seeder and Factories + Pagination Fix (Level 2)
Added `BookingFactory` plus seed data for multiple hairdresser accounts (`HairdresserSeeder`) and sample weekday bookings. Made the main seeder rerunnable and idempotent (`DatabaseSeeder`), and configured Laravel pagination to use Bootstrap 5 (`Paginator::useBootstrapFive()`).

- **Why I picked it:**
  - Provides realistic sample data for reviews and local demos.
  - Improves local development workflow and fixes giant/broken default Tailwind pagination SVG arrows on Bootstrap 5 views.
- **Trade-offs / assumptions:**
  - Seeded multiple hairdresser accounts and realistic weekday bookings adhering to business hours (8:00 AM – 5:00 PM).

---

### Task 14 - Concurrency-Safe Bookings (Level 5)
Made booking creation concurrency-safe by wrapping `BookingService::create()` in an atomic database transaction with pessimistic row locking (`lockForUpdate()`) and catching unique constraint violations (`QueryException`) to prevent race conditions and overbooking.

- **How it fulfills the requirements:**
  - **Transaction + unique index / lock:**
    - The schema enforces a unique index on `scheduled_at` (`bookings_scheduled_at_unique`), providing database-level protection against duplicate bookings.
    - `BookingService::create()` wraps availability checking and booking insertion in `DB::transaction(..., 3)`. It checks existing slots with `lockForUpdate()` (`SELECT ... FOR UPDATE`), serializing concurrent requests targeting the same time slot.
    - If a concurrent request slips past the SELECT check under race conditions, the database unique constraint violation (`QueryException` SQLSTATE `23000`/`23505`) is caught and cleanly converted into a 422 `ValidationException` (`'hour' => 'This time slot is already booked. Please choose another time.'`) rather than throwing a 500 server error.
  - **Tests simulating concurrent requests:**
    - `BookingServiceTest::test_it_converts_unique_constraint_violation_to_validation_exception_on_race_condition`: Simulates an exact race condition where another process inserts the same time slot right before `Booking::create()` executes, verifying that the service catches the unique constraint violation and raises a clean domain validation error.
    - `BookingConcurrencyTest`: Feature tests that verify both sequential concurrent requests and mid-request race conditions return clean 422 JSON validation errors without creating duplicate bookings.
- **Why I picked it:**
  - Directly builds on top of the clean `BookingService` layer introduced in Task 9.
  - Demonstrates backend engineering rigor around race conditions, database transactions, and data integrity.
- **Trade-offs / assumptions:**
  - Configured up to 3 deadlock retry attempts on `DB::transaction()` to handle database lock contention gracefully.
  - Kept error messages identical to standard availability validation so API clients receive a uniform 422 response structure.
