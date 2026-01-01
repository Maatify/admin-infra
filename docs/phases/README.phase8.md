# Phase 8 — Audit Logging Integration (Mongo Activity)

## Status
✅ **COMPLETED, STABILIZED & TYPE-ALIGNED**

Phase 8 has been fully implemented, stabilized, and verified.
Sub-phases **8.1** and **8.2** finalized type alignment and infrastructure
boundary correctness without altering architectural intent.

All changes remain infrastructure-only and fully compliant with locked phases.

---

## Purpose

Phase 8 introduces a **robust audit logging integration** for AdminInfra
using the external library:

- `maatify/mongo-activity`

The goal of this phase is to:
- Persist all admin audit events into MongoDB
- Maintain strict separation between domain contracts and infrastructure
- Ensure audit failures never affect business execution
- Preserve architectural constraints (final classes, infra-only responsibilities)

---

## Scope

### ✅ Included

- Mongo-based audit logger implementation
- Mapping of AdminInfra audit DTOs to Mongo Activity records
- Support for:
  - Authentication events
  - Security events
  - Action events
  - View events
- Fire-and-forget audit persistence (exception swallowing)
- Full PHPUnit coverage for audit logging
- PHPStan level-max compliance
- **Strict admin identity handling via `AdminIdDTO`**
- **Explicit scalar casting at Mongo driver boundary only**

### ❌ Explicitly Excluded

- No changes to domain audit contracts
- No runtime configuration logic
- No business logic branching based on audit results
- No mocking of infrastructure internals
- No cross-phase behavioral changes

---

## Architecture Overview

```
[ Core Domain ]
        |
        |  AuditLoggerInterface
        v
[ Infra Adapter ]
        |
        |  MongoAuditLogger
        v
[ Mapper Layer ]
        |
        |  MongoAuditMapper
        v
[ Vendor DTO ]
        |
        |  ActivityRecordDTO
        v
[ Vendor Infrastructure ]
        |
        |  ActivityManager (final)
        v
[ Persistence ]
        |
        |  ActivityRepository (final)
        v
     MongoDB

````

---

## Key Design Decisions

### 1. Final-Class Compliance

The vendor library (`maatify/mongo-activity`) exposes:

- `ActivityManager` as **final**
- `ActivityRepository` as **final**

➡️ As a result:
- **No PHPUnit mocks are allowed**
- Tests rely on **concrete Fake repositories**
- This preserves real behavior without violating architecture

---

### 2. Custom App Module Enum

The vendor library does not include an `ADMIN` application module.

To resolve this **without modifying vendor code**, Phase 8 introduces:

```php
AdminInfraAppModuleEnum::ADMIN
````

This enum:

* Implements `AppLogModuleInterface`
* Fully satisfies the vendor contract
* Keeps AdminInfra-specific concerns isolated

---

### 3. Admin Identity Boundary (Phase 8.1)

To preserve domain integrity and type safety:

* All admin identity references inside AdminInfra use **`AdminIdDTO`**
* No scalar admin IDs are used inside Core or Contracts
* Identity conversion is **not allowed** outside infrastructure

➡️ This ensures:

* Strong typing across orchestration & security layers
* No accidental leakage of scalar identifiers
* Clear identity ownership inside the domain

---

### 4. Mongo Driver Type Alignment (Phase 8.2)

The vendor DTO `ActivityRecordDTO` requires **scalar identifiers**.

To respect both sides:

* Scalar casting (`int`) is performed **only inside `MongoAuditMapper`**
* Domain DTOs remain untouched
* No contract or orchestration changes were introduced

➡️ Mongo remains a **pure infrastructure concern**
➡️ Domain stays strictly DTO-based

---

### 5. Fire-and-Forget Safety

All audit logging is wrapped in `try/catch` blocks:

* Audit failures are **never propagated**
* Business logic remains deterministic
* Logging is treated as a side-effect only

---

### 6. Test Strategy (Critical)

Due to final infrastructure classes:

* PHPUnit uses **FakeActivityRepository**
* Assertions validate persisted payloads
* Exception paths are tested by forcing repository failures

This is **intentional and correct**, not a workaround.

---

## Files Added / Modified

### Added

* `src/Drivers/Audit/Mongo/MongoAuditLogger.php`
* `src/Drivers/Audit/Mongo/MongoAuditMapper.php`
* `src/Drivers/Audit/Mongo/Enum/AdminInfraAppModuleEnum.php`
* `tests/Phase8/Audit/MongoAuditLoggerTest.php`
* `tests/Phase8/Audit/NullAuditLoggerTest.php`

### Modified

* Mongo audit mapper (type alignment only)
* Core services (typing alignment only)
* No domain contract changes
* No orchestration behavior changes

---

## Validation

* ✅ `composer run-script phpstan` — **PASS**
* ✅ `composer test` — **PASS**
* ✅ Phase 7 regressions resolved
* ✅ Phase 8.1 & 8.2 type alignment verified
* ✅ No architectural violations introduced

---

## Governance Notes

* Phase 8 introduces **infrastructure only**
* No domain contract breaking changes
* Identity boundaries are preserved
* Scalar conversion is strictly contained
* Final-class constraints are respected
* Phase 8 (including 8.1 & 8.2) is now **LOCKED**

---

## Phase Outcome

✔ AdminInfra now has a **production-ready, Mongo-backed audit system**
✔ Identity handling is explicit, typed, and bounded
✔ Infrastructure concerns are fully isolated
✔ Ready for downstream integrations (monitoring, SIEM, analytics)

---

**PHASE 8 — COMPLETE & LOCKED**