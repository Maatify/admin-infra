# Implementation Roadmap
## maatify/admin-infra

> **Authoritative Implementation Plan**
> Governed by: `ARCHITECTURE_INDEX.md`

---

## 🎯 Purpose

This document defines the **execution sequence** for implementing
the already-locked architecture of `maatify/admin-infra`.

This roadmap:
- Translates architecture → executable phases
- Does NOT introduce new architectural decisions
- MUST NOT contradict any LOCKED document

---

## 🧭 Execution Principles (LOCKED)

- Architecture before implementation
- Contracts before drivers
- Core before integrations
- No phase may violate architectural requirements
- Side-effect systems must be available as **contracts** before use

---

## 🧱 PHASE 0 — Repository Bootstrap & Governance

### Scope
- Repository structure
- Tooling & governance

### Deliverables
- `/src` base namespace
- `/contracts` directory
- Coding standards (PSR-12)
- PHPUnit + PHPStan (max level)
- CI skeleton

### Constraints
- ❌ No domain logic
- ❌ No drivers
- ❌ No persistence

---

## 🧱 PHASE 1 — Core Contracts (Foundational)

> **CRITICAL PHASE**
> All side-effect and infrastructure contracts MUST exist before any domain logic.

### Scope
- Foundational interfaces only

### Deliverables
- `AuditLoggerInterface`
- `NotificationDispatcherInterface`
- `NotificationChannelInterface`
- `SystemSettingsReaderInterface`
- `SessionStorageInterface`
- Repository interfaces (Admin, Credentials, Roles, Sessions)

### Constraints
- ❌ No implementations
- ❌ No external dependencies
- ❌ No storage drivers

---

## 🧱 PHASE 2 — Core Domain Models (Pure Domain)

### Scope
- Domain entities & value objects

### Deliverables
- Admin entity (composed identity)
- Status enums
- Lifecycle rules
- Domain validation

### Constraints
- ❌ No persistence logic
- ❌ No drivers
- ❌ No side effects

---

## 🧱 PHASE 3 — Identity & Authentication Core

### Scope
- Authentication orchestration
- Credential handling

### Deliverables
- Password policies
- Login orchestration
- Deterministic failure handling
- Mandatory audit hooks (via interfaces)

### Constraints
- ❌ No OAuth
- ❌ No sessions yet
- ❌ No concrete audit implementation

---

## 🧱 PHASE 4 — Optional TOTP (MFA)

### Scope
- Internal TOTP lifecycle

### Deliverables
- RFC 6238 compliant TOTP logic
- Enrollment / revoke flows
- System toggle integration

### Constraints
- ❌ No external MFA
- ❌ No SMS fallback

---

## 🧱 PHASE 5 — Authorization Engine

### Scope
- RBAC & permission enforcement

### Deliverables
- Permission registry
- Role-permission mapping
- Central `can()` resolver
- Mandatory audit hooks

### Constraints
- ❌ No implicit permissions
- ❌ No super-admin bypass

---

## 🧱 PHASE 6 — Sessions & Device Security

### Scope
- Session lifecycle
- Device awareness
- Impersonation

### Deliverables
- Session manager
- Revocation rules
- Impersonation guard
- Security notifications (via interfaces)

### Constraints
- ❌ No silent renew
- ❌ No local-only storage assumptions

---

## 🧱 PHASE 7 — Audit & Activity (Implementation)

### Scope
- Concrete audit drivers

### Deliverables
- Mongo audit driver (`maatify/mongo-activity`)
- Null audit driver
- Failure detection & observability hooks

### Constraints
- ❌ No blocking behavior
- ❌ No business logic dependency

---

## 🧱 PHASE 8 — Notifications (Implementation)

### Scope
- Concrete notification channels

### Deliverables
- Email channel
- Telegram channel
- Dispatcher implementation

### Constraints
- ❌ No inbound commands
- ❌ No auth via notifications

---

## 🧱 PHASE 9 — System Settings (Implementation)

### Scope
- Settings persistence & caching

### Deliverables
- Settings storage implementation
- Feature flags
- Security toggles

---

## 🧱 PHASE 10 — Operations & Exports

### Scope
- Async operations
- Secure exports

### Deliverables
- Job runner
- Export engine
- TTL-based delivery

---

## 🧱 PHASE 11 — Observability

### Scope
- Logs, metrics, health

### Deliverables
- Health checks
- Metrics exporters
- Alert hooks

---

## 🧱 PHASE 12 — Testing & Hardening

### Scope
- Comprehensive testing

### Deliverables
- ≥85% coverage
- Security edge cases
- Regression suites

---

## 🧱 PHASE 13 — Packaging & Release

### Scope
- Public release

### Deliverables
- v1.0.0 tag
- CHANGELOG
- README
- Migration notes

---

## 🔒 Governance Rule

If any implementation:
- Uses a side-effect system before its contract exists
- Bypasses an interface
- Violates a LOCKED document

➡️ The implementation is INVALID.

---

**Status:** READY FOR EXECUTION  
**Blocking Issues:** NONE (after roadmap correction)
