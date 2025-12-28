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

---

## 🚧 Status

This repository has completed **Phase 1 — Contracts Definition**.

- DTO-only contracts are defined
- No implementations exist yet
- No infrastructure drivers are provided
- Repository contracts are intentionally deferred to Phase 2

Phase 2 will introduce core orchestration and repository contracts based on these definitions.

The project is under active development toward a stable `1.0.0` release.
