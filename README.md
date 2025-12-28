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
- Implementations will be introduced in later phases

This ensures strict typing, static analysis safety, and long-term API stability.

Repository interfaces are introduced in Phase 2 and remain contract-only,
with no persistence or infrastructure assumptions.
---

## 🚧 Status

This repository has completed:

### ✅ Phase 1 — Contracts Definition
- DTO-only contracts defined
- No implementations
- No infrastructure drivers

### ✅ Phase 2 — Repository Contracts & Core Boundaries
- Repository interfaces defined (Query / Command)
- Complete DTO catalog (Value, View, Command, Result)
- No implementations or orchestration logic
- Full compliance with the Failure & Exception Model

### ✅ Phase 3 — Core Orchestration Planning
- Core orchestration responsibilities formally defined
- Execution boundaries and non-responsibilities locked
- Orchestration skeletons introduced (structure only, no logic)
- No infrastructure, wiring, or implementations

### ⏭️ Next Phase
**Phase 4 — Execution Planning**

The project is under active development toward a stable `1.0.0` release.

