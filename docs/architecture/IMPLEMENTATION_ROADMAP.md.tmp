# 🛠️ Implementation Roadmap

## Maatify Admin Infra

> **Authoritative Implementation Plan — GOVERNED BY ARCHITECTURE_INDEX**

---

## 🎯 Purpose

This document defines the **step-by-step implementation roadmap** for
**Maatify Admin Infra**, translating locked architecture into executable phases.

This roadmap:

* Does **not** redefine architecture
* Does **not** introduce new decisions
* Exists solely to **sequence implementation safely**

---

## 🧭 Execution Principles (LOCKED)

* Architecture first, code second
* No phase may violate a previous phase
* No shortcut implementations
* Each phase must be independently testable
* Core before integrations
* No UI assumptions

---

## 🧱 Implementation Strategy

Implementation is divided into **incremental milestones**, each producing:

* Concrete code
* Tests
* Internal documentation
* Zero architectural drift

---

# 🧩 Phase I — Project Bootstrap & Governance

### Scope

* Repository structure
* Composer setup
* Coding standards
* CI skeleton

### Deliverables

* `/src` base namespace
* `/contracts` (interfaces only)
* `/docs/architecture` (existing files)
* PHPStan (max level)
* PHPUnit setup
* Coding standard (PSR-12)

### Constraints

* ❌ No business logic
* ❌ No DB drivers yet

---

# 🧩 Phase II — Core Domain Models (No Persistence)

### Scope

* Admin entity
* Status enums
* Value objects

### Deliverables

* Admin lifecycle logic
* State transitions
* Validation rules

### Constraints

* ❌ No database
* ❌ No storage assumptions

---

# 🧩 Phase III — Identity & Authentication Core

### Scope

* Credentials logic
* Password handling
* Login flow orchestration

### Deliverables

* Auth service contracts
* Deterministic failure handling
* Password policies

### Constraints

* ❌ No OAuth
* ❌ No sessions yet

---

# 🧩 Phase IV — Optional TOTP (MFA)

### Scope

* TOTP enrollment
* TOTP verification
* System-level toggle

### Deliverables

* RFC 6238 compliant logic
* Enrollment lifecycle
* Disable / revoke flows

### Constraints

* ❌ No SMS fallback
* ❌ No external MFA

---

# 🧩 Phase V — Authorization Engine

### Scope

* Permissions
* Roles
* Ability resolver

### Deliverables

* Permission registry
* Role-permission mapping
* Central `can()` resolver

### Constraints

* ❌ No implicit roles
* ❌ No super-admin bypass

---

# 🧩 Phase VI — Sessions & Device Security

### Scope

* Session creation
* Session revocation
* Device identification
* Impersonation

### Deliverables

* Session manager
* Device registry
* Impersonation guard

### Constraints

* ❌ No silent renew
* ❌ No device auto-login

---

# 🧩 Phase VII — Audit & Activity Integration

### Scope

* Audit contracts
* Event emission
* Mongo integration (optional)

### Deliverables

* `AuditLoggerInterface`
* Mongo driver (`maatify/mongo-activity`)
* Null driver

### Constraints

* ❌ No blocking writes
* ❌ No audit-driven logic

---

# 🧩 Phase VIII — Notifications Infrastructure

### Scope

* Notification dispatcher
* Channel abstraction
* Telegram & Email channels

### Deliverables

* Channel interface
* Dispatcher
* Preference resolution

### Constraints

* ❌ No inbound commands
* ❌ No auth via notifications

---

# 🧩 Phase IX — System Settings & Feature Flags

### Scope

* Global settings
* Feature toggles
* Security flags

### Deliverables

* Settings service
* Cache invalidation
* Permission enforcement

---

# 🧩 Phase X — Operations & Exports

### Scope

* Async operations
* Export engine
* Secure downloads

### Deliverables

* Operation runner
* Export jobs
* TTL-based file handling

---

# 🧩 Phase XI — Observability

### Scope

* Logs
* Metrics
* Health checks

### Deliverables

* Log adapters
* Metrics exporters
* Health endpoints

### Constraints

* ❌ No analytics
* ❌ No auth decisions

---

# 🧪 Phase XII — Test Strategy & Hardening

### Scope

* Unit tests
* Security tests
* Edge cases

### Deliverables

* ≥85% coverage
* Auth & security test suites
* Regression tests

---

# 📦 Phase XIII — Packaging & Release

### Scope

* Versioning
* Release artifacts
* Documentation polish

### Deliverables

* `v1.0.0` tag
* CHANGELOG
* README (usage-focused)
* Migration notes

---

## 🏁 Final Release Criteria

The library may be released **only if**:

* All architectural phases are implemented
* No forbidden pattern exists
* All security rules are enforced
* All critical paths are tested
* No external service is required to boot

---

## 🔒 Governance Rule

> Any implementation that violates **ARCHITECTURE_INDEX.md**
> is considered **invalid**, regardless of functionality.

---

**Status:** ✅ READY FOR EXECUTION
**Library:** `maatify/admin-infra`

---