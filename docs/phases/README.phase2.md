### 1️⃣ Purpose

**Phase 2** defines **Repository Contracts and Core Planning** for
`maatify/admin-infra`.

This phase introduces **no implementations**, **no infrastructure**, and
**no executable logic**.

Its sole purpose is to define **stable, explicit contracts** that future
phases will implement.

---

### 2️⃣ Scope (What Phase 2 DOES)

Phase 2 is limited to:

* Repository **interfaces only**
* DTO-only method signatures
* Explicit separation of:

    * Query (read)
    * Command (write)
* Cross-cutting DTO definitions
* Contract-level consistency guarantees

---

### 3️⃣ Non-Goals (Hard Forbidden)

The following are **explicitly forbidden** in Phase 2:

* ❌ Any concrete implementation
* ❌ Any infrastructure dependency
* ❌ Any database, cache, or queue assumption
* ❌ Any business logic
* ❌ Any lifecycle orchestration
* ❌ Any framework integration
* ❌ Any HTTP, UI, or transport concern

---

### 4️⃣ DTO-Only Rule (LOCKED)

All repository contracts **MUST** comply with:

* ❌ No `array`
* ❌ No `iterable`
* ❌ No generic payloads
* ❌ No associative structures
* ❌ No metadata bags

**Every** input and output is a **typed DTO or Value Object**.

Collections:

* Represented only via **explicit Collection DTOs**
* No raw arrays are exposed in contracts

---

### 5️⃣ Repository Contract Rules (LOCKED)

All repositories in Phase 2:

* Are **pure contracts**
* Do not imply storage technology
* Do not imply transaction behavior
* Do not encode authorization or validation logic
* Do not throw user-facing exceptions

---

### 6️⃣ Failure & Exception Model (MANDATORY)

All contracts defined in Phase 2 **MUST** comply with:

```
docs/architecture/admin-failure-and-exception-model.md
```

In particular:

* Exceptions are allowed **only** for:

    * Contract violations
    * Invariant violations
    * Infrastructure/configuration failures
* Business and validation outcomes:

    * MUST be returned via Result DTOs
    * MUST NOT use exceptions

---

### 7️⃣ Naming & Structure Rules

* Repository interfaces are named explicitly:

    * `*QueryRepositoryInterface`
    * `*CommandRepositoryInterface`
* DTOs:

    * Are immutable
    * Are explicitly typed
    * Do not include optional catch-all fields
* No interface mixes Query and Command responsibilities

---

### 8️⃣ Phase Completion Criteria

Phase 2.0 is considered **complete** when:

* This document exists and is approved
* All Phase 2 sub-phases reference it
* No contracts have been defined yet

---

### 9️⃣ Governance Rule

> Any Phase 2 artifact that violates this document is **architecturally invalid**
> and MUST be rejected, regardless of convenience or backward compatibility.

---

## 🏁 Status

> **Phase 2.0 — COMPLETE & LOCKED**

This document governs **all subsequent Phase 2 sub-phases**.

---