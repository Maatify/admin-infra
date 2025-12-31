# Maatify Admin Infra

**Admin Infrastructure Library (Backend Only)**

Maatify Admin Infra provides a secure, auditable, and extensible infrastructure
foundation for administrative systems.

This library is **NOT**:

* A UI framework
* A CMS
* An authentication-as-a-service
* A frontend package

---

## 🎯 Scope

This library is responsible for:

* Admin identity lifecycle
* Authentication & optional MFA
* Authorization (roles, permissions & abilities)
* Session & device security
* Audit & activity logging
* Notifications (infrastructure only)
* System settings & feature flags

---

## 🚫 Non-Goals

This library will NOT:

* Provide UI components
* Read environment variables directly
* Define business logic
* Perform analytics or reporting
* Allow admin deletion

---

## 🧱 Architecture Governance

All design decisions are governed by the authoritative architecture documents:

* `docs/architecture/admin-vision.md`
* `docs/architecture/ARCHITECTURE_INDEX.md`

Any implementation **MUST NOT** violate these documents.

---

## 🧩 Contracts Design

This library follows a **DTO-only contract design**:

* All public contracts accept and return explicit DTOs
* No arrays or generic payloads are used in interfaces
* Contracts are isolated under `Maatify\AdminInfra\Contracts`
* Implementations are strictly constrained by architectural phases

This ensures strict typing, static analysis safety, and long-term API stability.

Repository interfaces are introduced early and remain **contract-only** until
explicitly implemented in later phases.

---

## 🧠 Orchestration Model

This library uses **orchestrators** to coordinate admin infrastructure behavior.

Orchestrators:

* Perform **pure sequencing and coordination**
* Call repositories and side-effect interfaces
* Emit audit and notification intents
* Return explicit Result DTOs

Orchestrators do **NOT**:

* Contain business logic
* Perform validation or policy decisions
* Assume infrastructure details
* Interact with frameworks or transport layers

Orchestrators rely on:

* The **Session Decision Pipeline (Phase 5)** for access gating
* The **Authorization Ability Resolver (Phase 5.1)** for authorization decisions

---

## 🚧 Project Status

This repository has completed the following phases:

---

### ✅ Phase 1 — Contracts Definition

* DTO-only contracts defined
* No implementations
* No infrastructure drivers

---

### ✅ Phase 2 — Repository Contracts & Core Boundaries

* Repository interfaces defined (Query / Command)
* Complete DTO catalog (Value, View, Command, Result)
* No implementations or orchestration logic
* Full compliance with the Failure & Exception Model

---

### ✅ Phase 3 — Orchestration Design & Planning

* Core orchestration responsibilities formally defined
* Execution boundaries and non-responsibilities locked
* Orchestration skeletons introduced (structure only)
* No execution logic
* No infrastructure or wiring

---

### ✅ Phase 3.5 — Contracts & Context Refinement

* Execution context contract introduced
* Read-only orchestration semantics clarified
* Not-found handling ambiguities resolved
* Architectural deadlocks removed
* No execution logic introduced

---

### ✅ Phase 4 — Orchestration Execution Logic (LOCKED)

* Real execution logic implemented inside orchestrators
* Read-before-write sequencing enforced
* Audit and notification intents wired (where permitted)
* Strict Result DTO failure semantics enforced
* Read-only methods implemented as pure passthrough
* Explicit architectural locks enforced where required
* Full PHPUnit coverage for orchestration logic
* PHPStan Level MAX passes with zero errors
* No contract changes
* No infrastructure assumptions

---

### ✅ Phase 5 — Session Decision Pipeline (LOCKED)

* Layered session decision model introduced
* Guard → Enforcement → Access separation
* Explicit enums per decision layer
* Deterministic & stateless behavior enforced
* No orchestration or infrastructure coupling
* Full unit test coverage with dedicated spies
* PHPStan Level MAX compliant

---

### ✅ Phase 5.1 — Authorization Ability Resolver (LOCKED)

* Centralized **ability-based authorization contract**
* Unified `can()` decision point via `AbilityResolverInterface`
* Authorization decisions expressed via Result DTOs (never boolean)
* Explicit denial reasons via `AbilityDecisionReasonEnum`
* Impersonation supported via explicit context (no flags)
* Generic authorization targets (not admin-only)
* Strict governance tests (contracts, DTOs, enums, reflection)
* No execution logic
* No infrastructure assumptions

---

### ✅ Phase 6 — TOTP / Multi-Factor Authentication (LOCKED)

* Full TOTP support with deterministic behavior
* Secure Base32 secret generation
* Configurable verification window policy
* Explicit valid / invalid / expired classification
* Full TOTP orchestration lifecycle:

  * enroll
  * verify
  * disable
* Mandatory audit logging for all TOTP actions
* Comprehensive PHPUnit coverage
* PHPStan Level MAX passes with zero errors
* No contract changes
* No infrastructure assumptions

---

### ✅ Phase 7 — Sessions, Impersonation & Emergency Security (LOCKED)

* Session creation and revocation orchestration finalized
* Explicit denial semantics via Result DTOs (no silent success)
* Stateless `ImpersonationGuard` with isolated authorization logic
* Emergency mode orchestration:

  * session revocation
  * audit logging
  * security notifications
* Device security enforcement:

  * new device detection
  * explicit device revocation
* No hidden policy flags
* No implicit actor defaults
* No business exceptions
* Full compliance with the Failure & Exception Model

---

### ✅ Phase 8 — Audit Logging Infrastructure (MongoDB) (LOCKED)

* Mongo-based audit logging implemented via `maatify/mongo-activity`
* Strict mapping from AdminInfra Audit DTOs to Mongo Activity records
* Fire-and-forget persistence (audit failures never affect execution)
* Final-class vendor constraints fully respected
* Custom `AdminInfraAppModuleEnum` introduced (no vendor modification)
* Full PHPUnit coverage with real repositories (no mocks)
* PHPStan Level MAX passes with zero errors
* No changes to domain contracts
* Infrastructure-only implementation

---

## 🔒 Phase Lock Statements

### Phase 4 — Orchestration

Phase 4 is **officially CLOSED and LOCKED**.

---

### Phase 5 — Session Decision Pipeline

Phase 5 is **officially CLOSED and LOCKED**.

---

### Phase 5.1 — Authorization Ability Resolver

Phase 5.1 is **officially CLOSED and LOCKED**.

---

### Phase 6 — TOTP / MFA

Phase 6 is **officially CLOSED and LOCKED**.

---

### Phase 7 — Session & Security Orchestration

Phase 7 is **officially CLOSED and LOCKED**.

* No changes to session creation / revocation semantics
* No emergency policy extensions
* No impersonation state logic
* No new flags or implicit behaviors

Any modification requires a **new phase or explicit ADR**.

---

### Phase 8 — Audit Infrastructure (Mongo)

Phase 8 is **officially CLOSED and LOCKED**.

* No changes to audit DTO contracts
* No audit-driven business logic
* No synchronous dependency on audit persistence
* No vendor contract modifications
* No framework or runtime coupling

Any modification requires a **new phase or explicit ADR**.

---

## 🔒 Architectural Guarantees (Post Phase 8)

At the end of Phase 8:

* Session lifecycle is **fully deterministic**
* Authorization is centralized and explicit
* Impersonation is isolated, stateless, and auditable
* Emergency security behavior is explicit and bounded
* All failures are expressed via Result DTOs
* No infrastructure drivers exist
* No framework coupling exists
* All behavior is testable in isolation
* All boundaries are strictly enforced
* Audit logging is durable, isolated, and non-blocking
* Infrastructure side-effects are fully contained
* Final-class vendor integrations are respected
* No audit failure can affect security or session behavior
* 
---

## 🛣️ Roadmap

Upcoming phases will focus on:

* Remaining infrastructure drivers (DB, Redis)
* Adapter implementations
* Runtime wiring
* Deployment concerns

All future work must comply with locked architecture and failure model.

---

## 📦 Release Direction

The project is progressing toward a stable **`v1.0.0`** release with:

* Locked contracts
* Locked orchestration semantics
* Deterministic security behavior
* Infrastructure-agnostic design
* Strong governance guarantees

---

© 2025 Maatify.dev
Engineered by **Mohamed Abdulalim**

---