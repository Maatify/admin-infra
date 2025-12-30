# Phase 5.1 — Authorization Ability Resolver (Contracts)

**Project:** `maatify/admin-infra`  
**Phase:** 5.1  
**Status:** ✅ COMPLETED & LOCKED  
**Type:** Contract Definition + Governance Tests  
**Governed by:** `docs/architecture/ARCHITECTURE_INDEX.md`

---

## 🎯 Phase Objective

Phase 5.1 introduces a **centralized, deterministic authorization decision contract**
based on **abilities**, not raw permissions.

This phase exists to:

- Unify all `can()` authorization decisions behind a single contract
- Prevent scattered or implicit authorization logic
- Enable future impersonation, hierarchy, and system-level checks
- Preserve strict separation between **decision**, **orchestration**, and **infrastructure**

> ⚠️ This phase defines **contracts only** — no execution, no logic, no storage.

---

## 🧱 Scope (What This Phase DOES)

### ✅ Introduced Contracts

**Interfaces**
- `AbilityResolverInterface`
- `AbilityHierarchyComparatorInterface`

**DTOs**
- `AbilityDTO`
- `AbilityContextDTO`
- `AbilityTargetDTO`
- `AbilityDecisionResultDTO`

**Enums**
- `AbilityDecisionReasonEnum`

---

## 🚫 Explicit Non-Goals (Hard Locked)

Phase 5.1 does **NOT**:

- Implement authorization logic
- Evaluate permissions or roles
- Access repositories or sessions
- Perform orchestration
- Assume hierarchy semantics
- Introduce caching or side effects
- Return booleans for authorization decisions
- Throw exceptions for allow/deny outcomes

Any of the above would be an **architectural violation**.

---

## 🧠 Core Design Decisions

### 1️⃣ Central Ability Resolver

All authorization checks converge into:

```php
AbilityResolverInterface::can(
    AdminIdDTO $actorAdminId,
    AbilityDTO $ability,
    AbilityContextDTO $context
): AbilityDecisionResultDTO
````

* Deterministic
* Stateless
* Infrastructure-agnostic
* Result-based (never boolean)

---

### 2️⃣ Result DTO Instead of Boolean

Authorization outcomes are represented by:

```php
AbilityDecisionResultDTO
```

Backed by:

```php
AbilityDecisionReasonEnum
```

This guarantees:

* Explicit denial reasons
* Audit-safe decisions
* No implicit authorization paths

---

### 3️⃣ Impersonation Without Flags

Impersonation is **inferred**, not declared:

* `AbilityContextDTO::$impersonatorAdminId`
* No boolean flags
* No derived logic

This preserves:

* Contract purity
* Future session integration (Phase 7)
* Deterministic behavior

---

### 4️⃣ Generic Authorization Targets

Targets are not admin-only.

```php
AbilityTargetDTO {
    string  $targetType;
    ?string $targetId;
}
```

This supports:

* Admins
* Roles
* System resources
* Settings
* Future domain objects

---

### 5️⃣ Hierarchy as a Contract Only

Hierarchy rules are **not implemented** here.

```php
AbilityHierarchyComparatorInterface
```

* No assumptions
* No ordering logic
* Purely declarative
* Future-phase responsibility

---

## 🧪 Testing Strategy (Governance Tests)

Phase 5.1 includes **strict contract-level tests** only.

### What the tests verify

* Interface existence and method signatures
* Constructor signatures and nullability
* DTO immutability (`readonly`)
* Enum exhaustiveness and backed values
* Convenience API correctness (`isAllowed()`)

### What the tests do NOT verify

* Authorization logic
* Permission evaluation
* Hierarchy rules
* Session behavior
* Orchestration flows

### Tooling Guarantees

* PHPUnit 11
* PHP 8.2+
* PHPStan Level MAX
* Reflection-based assertions
* No skipped tests
* No mocks of domain behavior

---

## 🔐 Failure & Exception Model Compliance

Phase 5.1 fully complies with:

```
docs/architecture/admin-failure-and-exception-model.md
```

Rules enforced:

* ❌ No exceptions for allow/deny
* ❌ No boolean authorization APIs
* ✅ Result DTOs only
* ✅ Explicit denial reasons

---

## 🏁 Phase Completion Criteria

Phase 5.1 is considered **complete** when:

* All contracts are defined
* All governance tests pass
* PHPStan Level MAX passes
* No execution logic exists
* No ADR is required
* No architectural constraints are violated

✔️ **All criteria satisfied**

---

## 🔒 Lock Statement

Phase 5.1 is **ARCHITECTURALLY LOCKED**.

Any future changes must:

* Occur in a new phase
* Preserve all existing contracts
* Not alter authorization semantics

---

## ➡️ Next Phase

**Phase 6 — TOTP / MFA**

Introduce internal MFA flows that:

* Integrate with authorization decisions
* Preserve contract boundaries
* Emit audit intents only
