# 🧭 Phase 4 Blueprint

## Orchestration Execution Logic

**Project:** `maatify/admin-infra`  
**Phase:** 4  
**Status:** IN PROGRESS  
**Implementation:** Incremental (Orchestrator-by-Orchestrator)  
**Governed by:** `docs/architecture/ARCHITECTURE_INDEX.md`

---

## 🎯 Phase Objective (LOCKED)

Phase 4 introduces **execution logic inside existing orchestrators**
without changing:

* Public contracts
* DTO shapes
* Responsibilities
* Architectural boundaries

> ❗ **No new features**  
> ❗ **No new responsibilities**  
> ❗ **No new abstractions unless explicitly required**

---

## 🧠 Core Definition

### What “Execution Logic” Means (Authoritative)

Execution logic is **pure sequencing and coordination**, nothing else.

It means:

* Calling repositories
* Calling side-effect interfaces
* Enforcing order
* Translating outcomes to Result DTOs
* Triggering audit / notification hooks

It does **NOT** mean:

* Business rules
* Policy decisions
* Data transformation logic
* Validation rules
* Framework glue
* DI container assumptions

---

## 🧱 Architectural Constraints (NON-NEGOTIABLE)

### 1️⃣ No Contract Changes

* ❌ No method signature changes  
* ❌ No new parameters  
* ❌ No new return types  
* ❌ No DTO edits  

Any need = **ADR or new phase**

---

### 2️⃣ Orchestrators Remain Thin

Each orchestrator method may only:

1. Assert preconditions (contract-level)
2. Call repositories
3. Call side-effect interfaces
4. Aggregate results
5. Return a **Result DTO**

❌ No branching based on UI or transport  
❌ No retry logic  
❌ No caching  
❌ No business interpretation  

---

### 3️⃣ Repositories Are Black Boxes

Orchestrators **must not assume**:

* Storage type
* Transaction semantics
* Query performance
* Existence guarantees beyond the interface

All repository failures = **exceptions (infrastructure)**  
All domain outcomes = **Result DTOs**

---

## 🧩 Allowed Dependencies (STRICT)

Inside orchestrators, **only**:

* Repository **interfaces**
* Side-effect **interfaces**
* DTOs
* Enums
* Failure/Result models

❌ No concrete classes  
❌ No adapters  
❌ No drivers  
❌ No helpers outside core  

---

## 🧾 Failure Semantics (MANDATORY)

Phase 4 **must strictly obey**  
`docs/architecture/admin-failure-and-exception-model.md`

### Exception Usage (Allowed Only For):

* Contract violation
* Invariant violation
* Infrastructure failure

### Result DTO Usage (MANDATORY For):

* Auth failure
* Permission denied
* Validation failure
* Not found
* Feature disabled
* Session invalid
* TOTP failure

> ❌ Throwing exceptions for user outcomes is **architecturally invalid**

---

## 🔁 Orchestration Flow Pattern (Canonical)

All orchestrator methods follow this shape:

```

INPUT DTO
↓
Precondition checks (system / admin state)
↓
Repository queries
↓
Decision via Result DTOs
↓
Side-effect calls (audit / notifications)
↓
Return Result DTO

```

No deviation.

---

## 🔐 Authorization Enforcement Rule

* Orchestrators **MUST NOT** inline permission logic
* Orchestrators **MUST** delegate to the authorization resolver
* Permission denial:
  * ❌ No exception
  * ✅ Result DTO
  * ✅ Audited

---

## 🔔 Audit & Notification Rules

### Audit

* All privileged actions:
  * Must emit audit intent
  * Fire-and-forget
  * No dependency on success

### Notifications

* Emitted as **intents**
* Dispatcher decides:
  * Channel
  * Availability
  * Preferences
* Orchestrator never knows channel details

---

## 🧱 Scope of Phase 4 (Explicit)

### ✅ Included

* Implement logic inside:

```

src/Core/Orchestration/*

```

* Wire repositories to orchestration flow
* Emit audit and notification intents
* Replace fail-fast stubs with real sequencing

---

### ❌ Explicitly Excluded

* No concrete repositories
* No DB drivers
* No Mongo / Redis usage
* No framework adapters
* No async workers
* No caching
* No performance optimization

---

## 🧪 Testing Expectations (Verified)

Phase 4 orchestration logic must be:

* Fully unit-tested
* Isolated via mocked repositories and side-effect interfaces
* Verified to emit correct audit and notification intents
* Verified to preserve strict Result DTO semantics
* Free of framework or infrastructure assumptions

PHPStan Level MAX must pass with zero errors.

---

## 🔒 Phase Lock Rules

Once Phase 4 implementation is complete:

* Any change to:
  * Orchestrator responsibility
  * Method semantics
  * Failure behavior

requires **ADR or Phase 5+**

---

## 🏁 Definition of Phase 4 Completion

Phase 4 will be considered **complete** when:

* All orchestrators contain execution logic
* No TODO / fail-fast placeholders remain
* PHPUnit tests exist and pass for all orchestration paths
* PHPStan Level MAX passes
* No architectural document is violated
* No new public surface is introduced

---

## 🧭 Phase 4 — Implementation Progress

### Orchestrator Status

- [x] AdminLifecycleOrchestrator
- [ ] SystemSettingsOrchestrator
- [ ] AuthorizationOrchestrator
- [ ] SessionSecurityOrchestrator
- [ ] AuthenticationOrchestrator

---

## 📌 Transition Rule

After Phase 4 is closed:

➡️ System is **behaviorally complete but infrastructure-free**  
➡️ Subsequent phases may safely introduce drivers and adapters

---

## 🏛 Governance State

Phase 4 is currently **IN PROGRESS**.

This document serves as the **authoritative execution blueprint**
for Phase 4 until all completion criteria are met.

Any attempt to mark Phase 4 as closed before satisfying the above
conditions is **architecturally invalid**.