# Phase 8 — Audit Logging Integration (Mongo Activity)

## Status
✅ **COMPLETED, HARDENED & LOCKED**

Phase 8 has been fully implemented, hardened, and verified across multiple
stabilization passes (**8.1 → 8.3e**).

All architectural, typing, boundary, and tooling issues have been explicitly
resolved. The phase is now **fully locked** with no known risks or deferred work.

---

## Purpose

Phase 8 introduces a **robust audit logging integration** for AdminInfra
using the external infrastructure library:

- `maatify/mongo-activity`

The goals of this phase are to:

- Persist all admin audit events into MongoDB
- Maintain strict separation between domain contracts and infrastructure
- Ensure audit failures never affect business execution
- Respect final-class constraints imposed by vendor infrastructure
- Preserve identity and typing guarantees inside the domain

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
- Explicit observability via `E_USER_WARNING`
- Full PHPUnit coverage for audit logging
- PHPStan (level max) compliance
- **Strict admin identity handling via `AdminIdDTO`**
- **Explicit scalar casting at Mongo driver boundary only**
- **Explicit boundary adapter for numeric-only vendor constraints**
- **Toolchain-safe testing strategy compatible with PHPUnit 11**

### ❌ Explicitly Excluded

- No changes to domain audit contracts
- No runtime configuration logic
- No business logic branching based on audit results
- No mocking of final vendor infrastructure classes
- No cross-phase behavioral changes
- No vendor library modifications

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
[ Boundary / ACL ]
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
- Tests rely on **concrete fake repositories**
- Real behavior is exercised without violating architecture

This is intentional and enforced.

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

* All admin identity references use **`AdminIdDTO`**
* No scalar admin IDs are used in Core or Contracts
* Identity conversion is forbidden outside infrastructure

This guarantees:

* Strong typing across orchestration & security layers
* Clear ownership of identity semantics
* No accidental scalar leakage

---

### 4. Mongo Driver Type Alignment (Phase 8.2)

The vendor DTO `ActivityRecordDTO` requires **numeric scalar identifiers**.

AdminInfra does **not** enforce numeric identity at the domain level.

Resolution:

* Scalar casting (`int`) is performed **only inside `MongoAuditMapper`**
* Domain DTOs remain untouched
* No contract or orchestration changes were introduced

Mongo remains a **pure infrastructure concern**.

---

### 5. Boundary Adapter for Vendor Constraints (Phase 8.3b)

A hard mismatch exists:

* AdminInfra supports **string-based admin IDs**
* `maatify/mongo-activity` v1 supports **numeric IDs only**

To avoid breaking either side, Phase 8 introduces an explicit
**Anti-Corruption Layer (Boundary Adapter)**:

* Numeric IDs → cast and persisted
* Non-numeric IDs → **explicitly skipped**
* No silent casting
* No fake identifiers
* No mixed-type writes in MongoDB

Skipped records emit an explicit warning but never interrupt execution.

This constraint is:

* Explicit
* Tested
* Localized
* Temporary (until vendor v2)

---

### 6. Fire-and-Forget Safety

All audit logging:

* Is wrapped in `try/catch`
* Never throws upstream
* Never alters business flow
* Emits explicit warnings on failure

Audit logging is treated as a **pure side-effect**.

---

### 7. Test Strategy & Toolchain Alignment (Phase 8.3e)

PHPUnit 11 removed all warning-specific assertion APIs.

To correctly test audit observability:

* PHP-level `set_error_handler()` is used
* `E_USER_WARNING` messages are captured explicitly
* Warnings are asserted without leaking to PHPUnit
* No global suppression is applied

This strategy is:

* PHPUnit 11 compatible
* phpstan compliant
* Toolchain-stable
* Explicitly documented

---

## Files Added / Modified

### Added

* `src/Drivers/Audit/Mongo/MongoAuditLogger.php`
* `src/Drivers/Audit/Mongo/MongoAuditMapper.php`
* `src/Drivers/Audit/Mongo/Enum/AdminInfraAppModuleEnum.php`
* `tests/Phase8/Audit/MongoAuditLoggerTest.php`
* `tests/Phase8/Audit/NullAuditLoggerTest.php`

### Modified

* Mongo audit mapper (boundary enforcement only)
* Mongo audit logger (observability signaling only)
* Test suite (warning capture & toolchain alignment)
* No domain contracts modified
* No orchestration logic modified

---

## Validation

* ✅ `composer run-script phpstan` — **PASS**
* ✅ `composer test` — **PASS**
* ✅ PHPUnit 11 compatible
* ✅ No architectural violations
* ✅ No deferred risks
* ✅ Phase fully hardened

---

## Governance Notes

* Phase 8 is **infrastructure-only**
* No domain contracts were changed
* Identity boundaries remain enforced
* Vendor constraints are isolated and explicit
* No backward-compatibility debt introduced
* Phase 8 (**8.0 → 8.3e**) is now **LOCKED**

---

## Phase Outcome

✔ AdminInfra has a **production-ready, Mongo-backed audit system**
✔ Identity handling is explicit, typed, and bounded
✔ Infrastructure constraints are isolated and controlled
✔ Toolchain behavior is fully accounted for
✔ Ready for downstream integrations (monitoring, SIEM, analytics)

---

**PHASE 8 — COMPLETE, HARDENED & LOCKED**
