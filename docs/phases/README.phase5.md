# Phase 5 — Session Decision Pipeline

## Status
**COMPLETED & LOCKED**

---

## Purpose

Phase 5 establishes a **strict, layered decision pipeline** for admin sessions.
The goal is to separate *evaluation*, *enforcement*, and *access decisions*
into clearly isolated, testable, and composable layers.

This phase guarantees that:
- Each layer has **exactly one responsibility**
- No layer bypasses another
- All mappings are explicit and exhaustive
- The full pipeline is deterministic and stateless

---

## Architectural Layers

Phase 5 introduces **three consecutive decision layers**:

```

SessionViewDTO
↓
[ Guard ]
↓
[ Enforcement ]
↓
[ Access ]

```

Each layer:
- Has its own Contract
- Uses its own Result Enum
- Is tested in complete isolation

---

## Layer Breakdown

### 1. Session Guard

**Responsibility**
- Decide whether a session is *allowed*, *expired*, or *revoked*
- Pure decision logic
- No side effects

**Key Artifacts**
- `SessionGuardInterface`
- `SessionGuardResultEnum`
- `DefaultSessionGuard`

**Decision Outputs**
- `ALLOWED`
- `DENIED_EXPIRED`
- `DENIED_REVOKED`

**Tests**
- Mapping correctness
- Delegation contract
- Determinism
- Statelessness
- DTO immutability
- Exhaustive enum coverage

---

### 2. Session Enforcement

**Responsibility**
- Convert Guard decisions into **enforcement outcomes**
- Represents system-level blocking semantics
- Still decision-only (no IO, no side effects)

**Key Artifacts**
- `SessionEnforcerInterface`
- `SessionEnforcementResultEnum`
- `DefaultSessionEnforcer`

**Mapping**
| Guard Result          | Enforcement Result |
|----------------------|--------------------|
| ALLOWED              | ALLOWED            |
| DENIED_EXPIRED       | BLOCKED_EXPIRED    |
| DENIED_REVOKED       | BLOCKED_REVOKED    |

**Tests**
- Full mapping coverage
- Strict delegation to Guard
- Determinism & statelessness
- Exhaustive enum traversal

---

### 3. Session Access

**Responsibility**
- Final access decision exposed to consumers
- Represents whether a session can be *used*
- No knowledge of lower layers’ internals

**Key Artifacts**
- `SessionAccessInterface`
- `SessionAccessResultEnum`
- `DefaultSessionAccessService`

**Mapping**
| Enforcement Result    | Access Result      |
|----------------------|--------------------|
| ALLOWED              | ALLOWED            |
| BLOCKED_EXPIRED      | BLOCKED_EXPIRED    |
| BLOCKED_REVOKED      | BLOCKED_REVOKED    |

**Tests**
- Mapping correctness
- Delegation contract
- Determinism
- Statelessness
- DTO immutability
- Exhaustive enum coverage

---

## Test Strategy

- **All tests are pure unit tests**
- No mocks of concrete classes
- Only dedicated *Spy* implementations per Contract:
  - `SessionPolicySpy`
  - `SessionGuardSpy`
  - `SessionEnforcerSpy`
- 100% logical branch coverage
- PHPStan Level MAX compliant

---

## Design Guarantees

Phase 5 guarantees that:

- No layer depends on implementation details of another
- No enum is reused across layers
- No layer performs IO, persistence, or mutation
- The full pipeline is referentially transparent
- The system is extensible without breaking existing contracts

---

## Explicit Non-Goals

Phase 5 does **NOT**:
- Manage sessions lifecycle persistence
- Handle HTTP, middleware, or controllers
- Perform logging or notifications
- Implement orchestration or composition logic

These concerns are intentionally deferred to later phases.

---

## Lock Statement

Phase 5 is **architecturally complete and locked**.

Any future changes must:
- Occur in a new phase
- Preserve all existing contracts
- Not alter decision semantics

---

## Next Phase

**Phase 6 — Session Orchestration**

Introduce a single high-level orchestration entry point
that composes the Phase 5 pipeline without modifying it.