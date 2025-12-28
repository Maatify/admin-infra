# 🧭 Phase 3.5 Blueprint

## Contracts & Context Refinement

**Project:** `maatify/admin-infra`
**Phase:** 3.5
**Status:** DESIGN BLUEPRINT — CONTRACTS ONLY
**Governance:** **LOCKED** (No execution allowed)

---

## 🎯 Phase Objective (LOCKED)

Phase 3.5 exists to **remove architectural deadlocks** in the current contracts that
prevent correct execution of Phase 4 orchestration logic.

This phase is **purely design-level** and focuses on:

* Completing missing contracts
* Clarifying ambiguous semantics
* Resolving type-level contradictions

> ❌ No execution
> ❌ No orchestration logic
> ❌ No business rules
> ❌ No infrastructure
> ❌ No runtime behavior

---

## 🧠 Problems Addressed by This Phase (Authoritative)

### P1 — Missing Actor Context

* Audit contracts require an `actorAdminId`
* Orchestrators currently have **no access** to actor identity
* Inventing or defaulting actor IDs is forbidden

### P2 — Read-Only Orchestration Ambiguity

* Read-only methods cannot meaningfully “orchestrate”
* Passthrough behavior was previously ambiguous or implicitly forbidden

### P3 — `listAdminContacts` Not-Found Deadlock

* Return type cannot represent “admin not found”
* Returning empty collections is forbidden
* Throwing exceptions for user outcomes is forbidden

---

## 🧱 Scope Definition

### ✅ Phase 3.5 MAY:

* Add or refine **contracts (interfaces & DTOs)**
* Clarify and lock **semantic rules**
* Introduce **explicit context contracts**
* Update architecture documentation
* Introduce ADRs if required

### ❌ Phase 3.5 MUST NOT:

* Add orchestration logic
* Add execution logic
* Add repositories or drivers
* Add framework or DI assumptions
* Change runtime behavior implicitly

---

## 🧩 Deliverable 1 — Execution Context Contract (CRITICAL)

### 🎯 Purpose

Provide orchestrators with an **explicit execution context** that identifies
the acting administrator.

### 📜 Contract Definition (Design-Level)

**Interface:** `AdminExecutionContextInterface`

**Responsibility:**

* Expose actor identity explicitly

**Conceptual Method:**

```php
getActorAdminId(): AdminIdDTO
```

### 🔒 Rules

* Context is passed explicitly (constructor injection in later phases)
* ❌ No session access
* ❌ No HTTP awareness
* ❌ No authentication logic
* ❌ No global/static state

**Outcome:**
Audit intents become possible **without inventing actor identifiers**.

---

## 🧩 Deliverable 2 — Read-Only Orchestration Semantics (LOCKED)

### 🎯 Official Decision

> ✅ **Read-only orchestrator methods are allowed to be pure passthrough.**

### Definition

A read-only method is one that:

* Does not mutate state
* Does not trigger side effects

### Implications

* ❌ No audit intents
* ❌ No notification intents
* ❌ No artificial sequencing
* ✅ Direct delegation to query repositories is allowed

**Outcome:**
Removes the contradiction between “no passthrough” and read-only reality.

---

## 🧩 Deliverable 3 — `listAdminContacts` Not-Found Semantics (CRITICAL)

### 🎯 Problem

The current contract cannot represent the “admin not found” case.

### 🔒 Locked Design Decision

> ✅ **Expand the return type to explicitly include `NotFoundResultDTO`.**

### Before

```php
listAdminContacts(AdminIdDTO): AdminContactListDTO
```

### After (Conceptual)

```php
listAdminContacts(AdminIdDTO): AdminContactListDTO | NotFoundResultDTO
```

### Rules

* ❌ Empty list MUST NOT represent “not found”
* ❌ Exceptions MUST NOT be thrown
* ✅ Not-found must be explicit and typed

**Outcome:**
Orchestration can handle not-found cases without violating failure rules.

---

## 🧾 Failure Model Alignment

Phase 3.5 does **not modify** the failure model.

Instead, it:

* Removes structural contradictions
* Makes strict compliance **possible in practice**

---

## 🔒 Governance Rules

* Phase 3.5 MUST be completed before resuming Phase 4
* Any execution code added during this phase is **invalid**
* All changes must be explicitly documented

---

## 🔁 Transition Back to Phase 4

Once Phase 3.5 is completed:

* ✅ Actor context is available
* ✅ Read-only passthrough is legal
* ✅ `listAdminContacts` semantics are solvable
* ❌ No remaining architectural blockers

➡️ **Phase 4 resumes at PASS 1 with no exceptions.**

---

## 🏁 Definition of Phase 3.5 Completion

Phase 3.5 is complete when:

* Execution context contract is defined
* Read-only semantics are locked
* `listAdminContacts` semantics are resolved
* Architecture documents are updated
* No execution logic was introduced

---