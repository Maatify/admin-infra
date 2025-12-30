# Maatify Admin Infra

**Admin Infrastructure Library (Backend Only)**

Maatify Admin Infra provides a secure, auditable, and extensible infrastructure
foundation for administrative systems.

This library is **NOT**:
- A UI framework
- A CMS
- An authentication-as-a-service
- A frontend package

---

## 🎯 Scope

This library is responsible for:
- Admin identity lifecycle
- Authentication & optional MFA
- Authorization (roles & permissions)
- Session & device security
- Audit & activity logging
- Notifications (infrastructure only)
- System settings & feature flags

---

## 🚫 Non-Goals

This library will NOT:
- Provide UI components
- Read environment variables directly
- Define business logic
- Perform analytics or reporting
- Allow admin deletion

---

## 🧱 Architecture Governance

All design decisions are governed by the authoritative architecture documents:

- `docs/architecture/admin-vision.md`
- `docs/architecture/ARCHITECTURE_INDEX.md`

Any implementation **MUST NOT** violate these documents.

---

## 🧩 Contracts Design

This library follows a **DTO-only contract design**:

- All public contracts accept and return explicit DTOs
- No arrays or generic payloads are used in interfaces
- Contracts are isolated under `Maatify\AdminInfra\Contracts`
- Implementations are strictly constrained by architectural phases

This ensures strict typing, static analysis safety, and long-term API stability.

Repository interfaces are introduced early and remain **contract-only** until
explicitly implemented in later phases.

---

## 🧠 Orchestration Model

This library uses **orchestrators** to coordinate admin infrastructure behavior.

Orchestrators:
- Perform **pure sequencing and coordination**
- Call repositories and side-effect interfaces
- Emit audit and notification intents
- Return explicit Result DTOs

Orchestrators do **NOT**:
- Contain business logic
- Perform validation or policy decisions
- Assume infrastructure details
- Interact with frameworks or transport layers

Orchestrators rely on the **Session Decision Pipeline (Phase 5)**
to determine whether execution is permitted before performing actions.

---

## 🚧 Project Status

This repository has completed the following phases:

### ✅ Phase 1 — Contracts Definition
- DTO-only contracts defined
- No implementations
- No infrastructure drivers

### ✅ Phase 2 — Repository Contracts & Core Boundaries
- Repository interfaces defined (Query / Command)
- Complete DTO catalog (Value, View, Command, Result)
- No implementations or orchestration logic
- Full compliance with the Failure & Exception Model

### ✅ Phase 3 — Orchestration Design & Planning
- Core orchestration responsibilities formally defined
- Execution boundaries and non-responsibilities locked
- Orchestration skeletons introduced (structure only)
- No execution logic
- No infrastructure or wiring

### ✅ Phase 3.5 — Contracts & Context Refinement
- Execution context contract introduced
- Read-only orchestration semantics clarified
- Not-found handling ambiguities resolved
- Architectural deadlocks removed
- No execution logic introduced

### ✅ Phase 4 — Orchestration Execution Logic (LOCKED)
- Real execution logic implemented inside orchestrators
- Read-before-write sequencing enforced
- Audit and notification intents wired (where permitted)
- Strict Result DTO failure semantics enforced
- Read-only methods implemented as pure passthrough
- Explicit architectural locks enforced via LogicException where required
- Full PHPUnit coverage for orchestration logic
- PHPStan Level MAX passes with zero errors
- No contract changes
- No infrastructure assumptions

### ✅ Phase 5 — Session Decision Pipeline (LOCKED)
- Layered session decision model introduced
- Guard → Enforcement → Access separation
- Explicit enums per decision layer
- Deterministic & stateless behavior enforced
- No orchestration or infrastructure coupling
- Full unit test coverage with dedicated spies
- PHPStan Level MAX compliant

### ✅ Phase 6 — TOTP / Multi-Factor Authentication (LOCKED)
- Full Time-based One-Time Password (TOTP) support introduced
- Secure Base32 secret generation with safe bit-level handling
- Deterministic OTP generation and verification via injected time provider
- Configurable verification window policy (past / future tolerance)
- Explicit classification of valid, invalid, and expired codes
- Complete TOTP orchestration lifecycle:
    - enroll
    - verify
    - disable
- Mandatory audit logging for all TOTP actions using `AuditAuthEventDTO`
    - `auth.totp.failed`
    - `auth.totp.verified`
    - `auth.totp.disabled`
- Comprehensive PHPUnit test suite:
    - 100% domain coverage
    - High orchestration coverage
- PHPStan Level MAX passes with zero errors
- No contract changes
- No infrastructure assumptions

---

## 🔒 Phase 4 Lock Statement

Phase 4 is **officially CLOSED and LOCKED**.

From this point forward:

- Any change to orchestrator responsibilities
- Any change to execution semantics
- Any modification to failure behavior

**requires an ADR or a new phase (Phase 5+)**.

Remaining LogicException paths are **intentional architectural locks**
and do NOT represent missing functionality.

---

## 🔒 Phase 5 Lock Statement

Phase 5 is **officially CLOSED and LOCKED**.

From this point forward:

- No changes to session decision semantics
- No enum reuse across decision layers
- No cross-layer shortcuts
- No orchestration logic inside decision layers

Any modification requires a new phase (Phase 6+).

---

## 🔒 Phase 6 Lock Statement

Phase 6 is **officially CLOSED and LOCKED**.

From this point forward:

- No changes to TOTP algorithms or encoding strategy
- No modification to verification window semantics
- No changes to TOTP orchestration behavior
- No alteration to audit event types or structure

Any modification requires a new phase (Phase 7+) or an explicit ADR.

---

## 🔒 Architectural Guarantees

At the end of Phase 6:

- The system has a **fully deterministic session decision pipeline**
- Execution and decision responsibilities are strictly separated
- No infrastructure drivers exist
- No framework coupling exists
- All behavior is fully testable in isolation
- All boundaries are strictly enforced
- Optional Multi-Factor Authentication (TOTP) is fully supported
- All authentication-critical flows are fully auditable

---

## 🧱 End of Phase 6 Guarantees

At the end of Phase 6, this library is:

- Behaviorally complete at the decision layer
- Infrastructure-free
- Fully testable in isolation
- Strictly governed by locked architectural boundaries
- Optional MFA (TOTP) is deterministic, test-covered, and infrastructure-agnostic
- All security-critical actions emit structured audit events

This state is intentional and required before introducing
drivers, adapters, or runtime wiring in later phases.

---

## 🛣️ Roadmap

Upcoming phases will focus on:
- Infrastructure drivers (DB, Redis, Mongo)
- Adapter implementations
- Runtime wiring
- Deployment concerns

All future work must comply with the locked architecture and failure model.

---

## 📦 Release Direction

The project is progressing toward a stable **`v1.0.0`** release with:
- Locked contracts
- Deterministic behavior
- Infrastructure-agnostic design
- Strong governance guarantees

---

© 2025 Maatify.dev  
Engineered by **Mohamed Abdulalim**
