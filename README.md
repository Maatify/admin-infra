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

## 🚧 Status

This repository is currently in **Phase 0 — Bootstrap & Governance**.

- No domain logic exists yet
- No interfaces are defined yet
- No drivers or implementations exist yet

Implementation begins in subsequent phases only.
