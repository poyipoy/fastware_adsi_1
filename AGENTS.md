# AGENTS.md

## Purpose

This file defines the working rules for AI coding agents operating in the **Fastware ADSI** repository.

The primary objective is to make safe, minimal, repository-aligned changes without unintentionally breaking existing business workflows.

Always treat the existing codebase as the source of truth.

Do not redesign architecture, introduce abstractions, or change business behavior unless the task explicitly requires it.

---

# Project Overview

Fastware ADSI is an internal business application built with Laravel.

The application contains multiple operational modules used by different business functions, including:

* administration
* maintenance
* production
* sales
* CRM/reporting
* HR / people development
* Knowledge Management
* Item Code
* Outstanding Material
* Warehouse
* approval workflows
* operational dashboards and reporting

This is a mature application containing both legacy implementations and newer domain-oriented implementations.

Do not assume every module follows the same architectural style.

---

# Technology Stack

## Backend

* PHP `^8.1`
* Laravel `^10.10`
* Laravel Sanctum
* Eloquent ORM
* MySQL

Important backend packages include:

* `maatwebsite/excel`
* `phpoffice/phpspreadsheet`
* `phpoffice/phpword`
* `barryvdh/laravel-dompdf`
* `dompdf/dompdf`
* `tecnickcom/tcpdf`
* `intervention/image`
* `kreait/firebase-php`
* `kreait/laravel-firebase`

## Frontend

* Blade
* JavaScript
* Bootstrap
* DataTables
* Vite 5
* Axios
* PDF.js

The application is primarily server-rendered.

Do not introduce SPA frameworks such as React, Vue, or another frontend framework unless explicitly requested.

---

# Important Repository Structure

```text
app/
├── Data/
├── Enums/
├── Http/
│   ├── Controllers/
│   │   └── Warehouse/
│   ├── Middleware/
│   └── Requests/
├── Models/
└── Services/
    ├── Competency/
    ├── Dashboard/
    ├── HR/
    ├── KnowledgeManagement/
    └── Warehouse/

database/
├── factories/
├── migrations/
└── seeders/

resources/
├── css/
│   ├── km/
│   └── warehouse/
├── js/
│   ├── km/
│   └── warehouse/
└── views/

routes/
└── web.php

tests/
├── Feature/
│   ├── Dashboard/
│   ├── ItemCode/
│   ├── KnowledgeManagement/
│   └── Warehouse/
├── Support/
└── Unit/

warehouse-docs/
```

This overview is not exhaustive.

Always inspect the actual module before making changes.

---

# Core Engineering Principles

## 1. Existing Architecture First

Before writing code:

1. inspect the relevant route,
2. inspect its controller,
3. inspect the related request classes,
4. inspect services used by the controller,
5. inspect relevant models and relationships,
6. inspect Blade views and frontend assets,
7. inspect existing tests,
8. inspect neighboring implementations for conventions.

Do not design the solution before understanding the existing implementation.

Repository evidence takes priority over assumptions.

---

## 2. Minimal Necessary Change

Implement the smallest change that completely solves the requested problem.

Do not perform unrelated cleanup.

Do not automatically:

* rename unrelated classes
* reorganize folders
* normalize old controllers
* rewrite existing queries
* introduce repositories
* introduce DTOs
* create traits
* create generic helpers
* create new service layers
* change route naming
* replace frontend technology

unless required by the task.

A large diff is not inherently better than a small diff.

---

## 3. Reuse Before Creating

Before creating a new implementation, search for an existing equivalent.

Check for reusable:

* controllers
* services
* Blade partials
* components
* CSS classes
* JavaScript utilities
* validation rules
* request classes
* model scopes
* authorization logic
* export implementations
* modal patterns
* DataTable setup
* form layouts
* notification patterns

Extend existing module conventions where practical.

---

# Legacy Code Policy

This repository contains legacy files and historical copies of controllers.

Examples include filenames containing dates, prefixes, or backup-style naming.

Do not assume the newest-looking filename is the active implementation.

Always trace usage from:

```text
routes/web.php
```

and imports/references before editing a controller.

Never delete an apparently unused historical file merely because another similarly named implementation exists.

Cleanup of legacy files must be treated as a separate task.

---

# Routes

The primary application web routes are defined in:

```text
routes/web.php
```

The file contains routes for many independent business modules.

When modifying a route:

* search for the existing route name first,
* search for all usages of that route name,
* preserve existing route names unless a rename is explicitly required,
* preserve HTTP methods unless business behavior requires otherwise,
* verify middleware requirements,
* inspect controller consumers before changing parameters.

Many authenticated application routes run inside the authenticated web middleware group.

Do not move routes in or out of authentication middleware without understanding access requirements.

Do not introduce duplicate route names.

Because `routes/web.php` is large, make localized edits rather than reorganizing the entire file during unrelated tasks.

---

# Controllers

The repository contains both large legacy controllers and newer domain-oriented controllers.

Do not refactor a large controller solely because it is large.

If the surrounding module already uses services, follow that pattern.

If the surrounding module keeps straightforward logic in the controller, do not automatically introduce a service.

For newer modules, prefer existing domain structure where already established.

Example:

```text
app/Http/Controllers/Warehouse/
```

contains dedicated controllers for concerns such as:

```text
WarehouseCatalogController
WarehouseCategoryController
WarehouseConsumableController
WarehouseDashboardController
WarehouseExportController
WarehouseReportController
WarehouseScanController
WarehouseStockAdjustmentController
WarehouseStockInController
WarehouseStockValidationController
WarehouseTransactionController
WarehouseTransactionHistoryController
```

Do not merge these responsibilities back into one controller.

---

# Services

Services are already used in selected areas of the application.

Examples exist under:

```text
app/Services/
app/Services/HR/
app/Services/Dashboard/
app/Services/KnowledgeManagement/
app/Services/Warehouse/
```

Before placing substantial business logic inside a controller, inspect whether the affected module already has an appropriate service.

However, do not introduce a service merely to wrap one simple query or method.

Services should represent actual business or reusable application behavior.

---

# Models and Database

The application uses Eloquent extensively.

Before modifying database-related behavior:

* inspect the model,
* inspect its relationships,
* inspect existing migrations,
* inspect all usages of affected fields,
* inspect legacy naming carefully.

Some relationships and database fields use project-specific or historical naming rather than Laravel defaults.

Do not "correct" unusual column names without verifying the database contract.

Do not rename existing columns merely for stylistic consistency.

Do not change relationship foreign keys without tracing dependent modules.

---

# Database Migrations

Never modify an old production migration simply to change an existing schema unless the task specifically requires editing an unreleased migration.

For an existing deployed schema, create a new migration.

Do not create migrations for UI-only or code-only tasks.

Never execute destructive database commands automatically.

Do not run:

```bash
php artisan migrate:fresh
php artisan migrate:reset
php artisan db:wipe
```

unless explicitly requested and the environment is confirmed safe.

---

# Authentication and Authorization

Authentication uses Laravel authentication around the application `User` model.

The application also contains role-, position-, section-, and workflow-specific access behavior.

Do not infer authorization from UI visibility alone.

When modifying protected functionality, inspect:

* routes and middleware,
* controller authorization checks,
* `User` relationships,
* role relationships,
* job-position relationships,
* menu/access services,
* module-specific access logic.

A hidden menu does not necessarily mean the endpoint is authorized.

Preserve server-side authorization.

---

# Security-Sensitive Legacy Behavior

The repository contains legacy authentication-related behavior and fields that should be treated carefully.

Do not replicate insecure patterns into new code merely because legacy code uses them.

In particular:

* never expose credentials,
* never log passwords,
* never include secrets in responses,
* never add plaintext credential storage,
* never expose `.env` contents,
* never commit API keys or Firebase credentials.

If a task touches legacy password compatibility or authentication behavior, preserve existing business compatibility unless a migration is explicitly approved, but clearly identify security implications.

Do not silently redesign authentication as part of an unrelated task.

---

# Validation

Prefer dedicated Laravel Form Request classes when the affected module already uses them.

Warehouse already contains request/domain-related files under areas such as:

```text
app/Http/Requests/Warehouse/
```

Do not duplicate validation in JavaScript as the only protection.

Client-side validation improves UX.

Server-side validation remains authoritative.

Preserve existing validation messages where user-facing behavior depends on them.

---

# Knowledge Management

Knowledge Management has dedicated backend services, tests, CSS, and JavaScript.

Relevant areas include:

```text
app/Services/KnowledgeManagement/
resources/css/km/
resources/js/km/
tests/Feature/KnowledgeManagement/
```

When modifying KM:

* inspect KM-specific services first,
* keep KM-specific frontend behavior scoped to KM,
* reuse existing KM foundation styles,
* preserve approval and content workflow behavior,
* do not change points, statuses, approval logic, or permission rules without explicit requirements.

Do not move KM styles into the global application stylesheet unless there is a deliberate cross-application requirement.

---

# Warehouse

Warehouse is one of the more structured modules in the repository.

Relevant areas include:

```text
app/Http/Controllers/Warehouse/
app/Http/Requests/Warehouse/
app/Services/Warehouse/
app/Enums/Warehouse/
app/Data/Warehouse/
resources/css/warehouse/
resources/js/warehouse/
tests/Feature/Warehouse/
warehouse-docs/
```

Before modifying Warehouse, also review relevant documentation under:

```text
warehouse-docs/
```

Available project documentation includes discovery, deployment, operating, rollback, execution, and structural-refactor information.

Treat Warehouse stock calculations and transaction rules as business-critical.

Do not change:

* stock movement semantics,
* transaction direction,
* validation workflow,
* adjustment behavior,
* scan behavior,
* stock-in behavior,

without tracing the full domain flow and relevant tests.

Prefer the established Warehouse domain structure instead of adding logic to unrelated global controllers.

---

# Frontend Architecture

The application uses Blade with JavaScript and CSS rather than a SPA framework.

Global Vite entry points include:

```text
resources/css/app.css
resources/js/app.js
```

There are also dedicated module assets.

Knowledge Management includes entries such as:

```text
resources/css/km/foundation.css
resources/css/km/dashboard.css
resources/js/km/dashboard.js
resources/js/km/authoring.js
resources/js/km/approval.js
resources/js/km/shell.js
```

Warehouse includes entries such as:

```text
resources/css/warehouse/foundation.css
resources/css/warehouse/dashboard.css
resources/css/warehouse/transaction-form.css
resources/css/warehouse/management.css
resources/css/warehouse/reporting.css
resources/css/warehouse/stock-in.css

resources/js/warehouse/dashboard.js
resources/js/warehouse/transaction-form.js
resources/js/warehouse/stock-in.js
```

Keep module-specific behavior inside module-specific assets when possible.

Avoid leaking module styling globally.

---

# UI/UX Changes

When performing redesign work, preserve functionality first.

Before editing a page:

1. inspect the Blade template,
2. inspect its parent layout,
3. inspect included partials,
4. inspect its CSS,
5. inspect its JavaScript,
6. inspect DataTables or plugin initialization,
7. inspect route-generated links and forms.

A redesign must not silently alter business logic.

Preserve:

* form field names
* route targets
* HTTP methods
* CSRF fields
* IDs used by JavaScript
* `data-*` attributes
* modal triggers
* validation rendering
* DataTable hooks
* permission-based conditions

unless the related code is updated intentionally.

---

# CSS Rules

Prefer existing design tokens and module foundations where available.

Do not introduce a new CSS framework for isolated pages.

Avoid broad selectors that affect unrelated modules.

Prefer module-scoped selectors for module redesigns.

Do not put large page-specific styling into global `app.css` if a module stylesheet already exists.

When changing responsive behavior, verify desktop and narrow viewport behavior.

---

# JavaScript Rules

Prefer plain JavaScript and existing repository conventions.

Do not introduce another frontend framework for simple interaction.

Avoid globally-scoped variables when module-scoped behavior is sufficient.

When changing JavaScript tied to Blade markup, search for all corresponding:

* IDs
* classes
* selectors
* `data-*` attributes
* endpoint URLs
* route-generated URLs

before changing the markup.

---

# Vite

Frontend development commands:

```bash
npm install
npm run dev
```

Production frontend build:

```bash
npm run build
```

When adding a new dedicated Vite entry, update:

```text
vite.config.js
```

only when the asset genuinely needs to be an independent entry.

Do not create unnecessary Vite entries.

---

# PHP / Laravel Setup

Typical dependency installation:

```bash
composer install
npm install
```

Initial Laravel environment setup where required:

```bash
cp .env.example .env
php artisan key:generate
```

Do not overwrite an existing `.env`.

Do not assume migration or seeding is safe to execute against an existing database.

---

# Testing

The repository uses PHPUnit through Laravel's testing stack.

Tests are organized under:

```text
tests/Feature/
tests/Unit/
```

Existing feature coverage includes areas such as:

```text
Dashboard
ItemCode
KnowledgeManagement
Warehouse
Outstanding Material
Training History
Working Experience import
```

Run the smallest relevant test set first.

Examples:

```bash
php artisan test tests/Feature/Warehouse
php artisan test tests/Feature/KnowledgeManagement
php artisan test tests/Feature/ItemCode
```

For a specific test:

```bash
php artisan test tests/Feature/path/to/TestFile.php
```

For the complete suite:

```bash
php artisan test
```

Do not claim tests passed unless they were actually executed.

If tests cannot be run because of database, environment, dependency, or infrastructure constraints, state this clearly.

---

# Formatting

Laravel Pint is available as a development dependency.

For changed PHP files, use:

```bash
./vendor/bin/pint --test
```

or format deliberately with:

```bash
./vendor/bin/pint
```

Do not use formatting as an excuse to modify unrelated files.

Keep formatting diffs scoped to files involved in the task.

---

# Build Validation

For frontend changes, run:

```bash
npm run build
```

when practical.

A successful development view is not sufficient evidence that the production Vite build succeeds.

For Laravel changes, relevant validation may include:

```bash
php artisan test
php artisan route:list
```

Use the validation appropriate to the change.

---

# Business Rule Preservation

Never silently change existing business rules.

Business rules include:

* statuses
* approval sequences
* role access
* position access
* section access
* stock calculations
* scoring
* point calculations
* report calculations
* workflow transitions
* visibility conditions
* notification recipients
* export semantics

If a task requires changing one of these, identify the affected behavior explicitly before implementation.

---

# Transactions and Multi-Step Writes

For operations that update multiple related database records, inspect whether the existing implementation uses database transactions.

Do not split an atomic business operation into independent writes.

Warehouse, approval, inventory, scoring, and similar workflows must be treated as consistency-sensitive.

Use the module's existing transaction strategy.

---

# Exports and Documents

The repository supports several document/export technologies.

Before implementing a new export, inspect existing implementations using:

* Laravel Excel
* PhpSpreadsheet
* PhpWord
* DomPDF
* TCPDF

Reuse the existing approach used by the relevant module.

Do not introduce a new export dependency simply because another package appears easier.

---

# External Integrations

The project includes Firebase-related dependencies and FCM-related application behavior.

Treat notification and external integration changes as integration-sensitive.

Do not change credentials, Firebase configuration, tokens, or notification routing unless explicitly required.

Never hardcode secrets.

---

# Documentation

For modules with dedicated documentation, read it before changing the module.

Warehouse documentation is located under:

```text
warehouse-docs/
```

Do not treat historical documentation as more authoritative than current executable code.

When documentation and implementation disagree:

1. identify the discrepancy,
2. inspect recent implementation/tests,
3. follow the requested task,
4. report the discrepancy.

---

# Debug Code

The repository contains debug-oriented routes and legacy diagnostics.

Do not add new public debug endpoints as a normal debugging technique.

Do not expose:

* authenticated user data,
* approval structures,
* database dumps,
* environment details,
* stack traces,
* secrets

through temporary routes.

If temporary diagnostic code is unavoidable during implementation, remove it before considering the task complete unless the user explicitly requests it to remain.

Do not automatically clean existing debug routes during unrelated work.

---

# Git Safety

Before substantial changes, inspect:

```bash
git status
git diff
```

Respect existing uncommitted user work.

Never overwrite or revert unrelated modifications.

Do not use destructive commands such as:

```bash
git reset --hard
git clean -fd
git checkout -- .
```

unless explicitly requested.

Do not automatically:

* commit
* push
* merge
* rebase
* force-push

unless explicitly instructed.

---

# Dependency Policy

Do not add Composer or npm packages by default.

Before adding a dependency:

1. verify the repository does not already provide the capability,
2. determine whether existing dependencies can solve the problem,
3. explain why the dependency is required.

Avoid introducing packages for functionality that can be implemented cleanly with the existing stack.

---

# Change Workflow

For every coding task, follow this sequence:

```text
Understand request
    ↓
Inspect current implementation
    ↓
Trace route/controller/service/model/view
    ↓
Inspect related tests and documentation
    ↓
Identify reusable patterns
    ↓
Define smallest safe change
    ↓
Implement
    ↓
Review diff
    ↓
Run targeted validation
    ↓
Run broader validation when warranted
    ↓
Report result and remaining risk
```

Do not skip repository discovery for apparently simple changes.

---

# Planning Rules

Create an implementation plan before coding when the task:

* affects multiple modules,
* changes database schema,
* changes business workflows,
* changes permissions,
* changes approval logic,
* performs a substantial redesign,
* introduces architectural changes,
* touches many files,
* carries meaningful regression risk.

Plans should identify concrete files and flows after repository inspection.

Do not create speculative plans based solely on directory names.

---

# Definition of Done

A task is complete only when:

1. the requested behavior is implemented,
2. unrelated behavior remains unchanged,
3. relevant existing patterns were followed,
4. the diff was reviewed,
5. applicable tests were run,
6. frontend builds were run when relevant,
7. database changes were validated when relevant,
8. no debugging artifacts were unintentionally left behind,
9. no secrets were exposed,
10. limitations or unverified behavior are reported.

---

# Agent Response Expectations

When completing an implementation, report:

## Changed

Briefly state what changed.

## Files

List the important files modified.

## Validation

State exactly what was run.

Example:

```text
php artisan test tests/Feature/Warehouse
npm run build
```

## Not Validated

Clearly state anything that could not be validated.

## Notes / Risks

Mention only meaningful remaining risks or follow-up considerations.

Do not claim:

* "fully working"
* "production ready"
* "no regressions"
* "all tests pass"

unless evidence supports the statement.

---

# Final Rule

**Understand the existing Fastware ADSI implementation before changing it.**

Prefer:

```text
existing convention
+ minimal change
+ targeted validation
```

over:

```text
new abstraction
+ broad refactor
+ speculative architecture
```

Repository behavior and established business rules take priority over theoretical architectural purity.
