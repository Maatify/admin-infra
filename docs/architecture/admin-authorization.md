# 📘 Admin Authorization Architecture

## Maatify Admin Infra

> **Authoritative Architecture Document — Phase 3 (LOCKED)**

---

## 🎯 Purpose

This document defines the **authoritative authorization model** for
**Maatify Admin Infra**, covering:

* Roles
* Permissions
* Ability checks
* Scope boundaries

This document is **independent of authentication** and **must not be bypassed** by any login method (Password, OAuth, etc.).

---

## 🧠 Core Principles (LOCKED)

* Authorization is **internal only**
* Authentication ≠ Authorization
* No implicit permissions
* Least privilege by default
* Explicit grants only
* Everything must be auditable
* No permission inheritance by accident

---

## 🧱 Authorization Model Overview

Authorization is built on **three layers**:

1. **Permissions** (atomic capabilities)
2. **Roles** (permission bundles)
3. **Assignments** (who has what)

There is **no direct permission logic inside authentication flows**.

---

## 🧩 1. Permissions (Atomic Capabilities)

### Concept

A permission represents **one specific allowed action**.

Examples:

* `admins.view`
* `admins.create`
* `admins.update`
* `admins.suspend`
* `settings.update`
* `audit.view`
* `security.impersonate`

Permissions are:

* Explicit
* Immutable (once defined)
* Environment-agnostic

---

### Conceptual Table: `admin_permissions`

```
admin_permissions
-------------------------
- id (PK)
- key (unique)
- description
- created_at
```

**Rules (LOCKED):**

* Permission keys are immutable
* No wildcard permissions
* No dynamic generation
* No deletion

---

## 🧩 2. Roles (Permission Containers)

### Concept

A role is a **named collection of permissions**.

Examples:

* super_admin
* admin
* support
* auditor
* finance

Roles do **not** imply hierarchy by default.

---

### Conceptual Table: `admin_roles`

```
admin_roles
-------------------------
- id (PK)
- name (unique)
- description
- created_at
```

**Rules:**

* Roles are explicit
* No implicit superpowers
* No auto-inheritance

---

## 🧩 3. Role ↔ Permission Mapping

### Conceptual Table: `admin_role_permissions`

```
admin_role_permissions
-------------------------
- role_id (FK → admin_roles.id)
- permission_id (FK → admin_permissions.id)
```

**Rules (LOCKED):**

* Explicit mapping only
* No wildcard grants
* Removal is allowed (audited)

---

## 🧩 4. Admin ↔ Role Assignment

### Conceptual Table: `admin_role_assignments`

```
admin_role_assignments
-------------------------
- admin_id (FK → admins.id)
- role_id (FK → admin_roles.id)
- assigned_at
- assigned_by (admin_id)
```

**Rules (LOCKED):**

* Admin can have multiple roles
* Role assignment is auditable
* No implicit role assignment
* No auto-role on creation

---

## 🧠 Permission Resolution Logic (Authoritative)

```
Admin
  → Roles
    → Permissions
```

* Permissions are **unioned**
* No deny rules
* No priority conflicts
* No runtime inference

---

## 🔐 Ability Check Contract

Authorization checks **must** use a centralized ability resolver.

### Conceptual API

```
can(admin, permission_key) → true | false
```

**Rules:**

* No inline checks
* No string comparisons scattered in code
* All checks go through one resolver
* Resolver is auditable

---

## 🚨 Super Admin Policy (IMPORTANT)

There is **NO implicit super admin**.

If a role named `super_admin` exists:

* It must explicitly list its permissions
* It must still go through authorization checks
* It can still be audited and suspended

> **No account is above the system**

---

## 🔒 Forbidden Authorization Patterns (LOCKED)

* ❌ Permission checks in controllers directly
* ❌ Hardcoded admin IDs
* ❌ `if (isAdmin)` logic
* ❌ Role name checks (`role == 'admin'`)
* ❌ Wildcard permissions (`*`)
* ❌ Permission inference from OAuth
* ❌ Permission inference from status

---

## 🧾 Audit Requirements (Future Phase)

The following **must be logged**:

* Role creation
* Permission assignment
* Role assignment / removal
* Authorization failures
* Privileged actions

---

## 🔗 Relationship Summary

```
admins
  └── N : M ── admin_roles
                   └── N : M ── admin_permissions
```

---

## 🏁 Status

> ✅ **FINAL · LOCKED · AUTHORITATIVE**

This authorization model is **mandatory** for all systems using
**Maatify Admin Infra**.

---

## 🔜 Next Phase (Phase 4)

**Audit & Activity Architecture**

Proposed file:

```
docs/architecture/admin-audit-activity.md
```

---