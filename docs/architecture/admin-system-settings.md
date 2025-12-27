# Admin System Settings & Feature Flags Architecture
## Maatify Admin Infra

> **Authoritative Architecture Document — Phase 6 (LOCKED)**

---

## 🎯 Purpose

This document defines the **system settings and feature flags architecture** for  
**Maatify Admin Infra**.

It establishes:
- How global system behavior is configured
- How features are enabled/disabled safely
- How security-critical toggles are enforced
- Clear separation between **system-level** and **admin-level** preferences

This document is **authoritative** and binding for all implementations.

---

## 🧠 Core Principles (LOCKED)

- System settings are **global**
- Settings are **explicit**, never implicit
- Feature flags are **fail-safe**
- Security defaults are **ON**
- Settings changes are **auditable**
- No setting change bypasses authorization
- No runtime magic or hidden defaults

---

## 🧱 Conceptual Responsibility

System settings control **how the system behaves**, not **who the admin is**.

```

Admin Infra Core
|
v
SystemSettingsService
|
+--> Feature Flags
+--> Security Toggles
+--> Runtime Configuration

```

---

## 🧩 System Settings Model (Conceptual)

### `system_settings`

```

## system_settings

* key (PK)
* value
* type (bool | string | int | json)
* description
* updated_at
* updated_by (admin_id)

```

---

## 🔑 Categories of System Settings

System settings are grouped by **domain**, not by UI.

---

### 1️⃣ Authentication & Security

```

auth.enabled = true
auth.totp.enabled = true
auth.max_login_attempts = 5
auth.lockout.duration_minutes = 30
auth.password.min_length = 12

```

**Rules (LOCKED):**
- Security settings default to safest value
- Disabling security features requires explicit permission
- Changes are always audited

---

### 2️⃣ Notifications

```

notifications.enabled = true
notifications.channels.email.enabled = true
notifications.channels.telegram.enabled = true
notifications.channels.whatsapp.enabled = false   (future)

```

**Rules:**
- Channel toggles are independent
- Security notifications ignore global disable unless explicitly overridden
- No channel implies trust or authorization

---

### 3️⃣ Audit & Activity

```

audit.enabled = true
audit.log_views = false
audit.retention_days = 365

```

**Rules:**
- Audit enabled by default
- Disabling audit requires elevated permission
- Retention policy enforced by storage backend (e.g. Mongo TTL)

---

### 4️⃣ System Behavior

```

system.maintenance.enabled = false
system.read_only = false
system.default_language = en
system.default_timezone = UTC

```

**Rules:**
- Maintenance mode affects all non-privileged operations
- Read-only mode blocks mutating actions
- Defaults are used only when admin preferences are absent

---

### 5️⃣ Feature Flags (Extensibility)

```

features.oauth.enabled = false
features.impersonation.enabled = true
features.admin_exports.enabled = true

```

**Rules (LOCKED):**
- Feature flags do not bypass authorization
- Disabled feature = hard block
- Feature flags never auto-enable

---

## 🔐 Access Control Rules

- Only admins with explicit permission can:
  - View system settings
  - Modify system settings
- Sensitive keys require higher privilege

Example permissions:
- `system.settings.view`
- `system.settings.update`
- `system.settings.security.update`

---

## 🧾 Audit Requirements (Mandatory)

The following **must be logged** (via Phase 4):

- Any system setting change
- Old value → new value
- Admin who made the change
- Timestamp
- Origin (UI / API / CLI)

---

## 🧠 Runtime Behavior (LOCKED)

- Settings are:
  - Loaded centrally
  - Cached (where applicable)
- Cache invalidation:
  - Explicit
  - Immediate on change
- No service reads environment variables directly

---

## 🚫 Forbidden Patterns (LOCKED)

- ❌ Reading `.env` directly in business logic
- ❌ Hardcoding feature flags
- ❌ Silent fallback to insecure defaults
- ❌ Per-request mutation of settings
- ❌ Using settings to bypass permissions
- ❌ Storing secrets in system settings

---

## 🔒 Security Guarantees

- Settings changes are:
  - Authenticated
  - Authorized
  - Audited
- System can be placed in safe mode at any time
- Security features fail closed, not open

---

## 🏁 Status

> ✅ **FINAL · LOCKED · AUTHORITATIVE**

This document governs **all system-level configuration and feature flags**  
for **Maatify Admin Infra**.

---

## 🔜 Next Phase (Phase 7)

**Sessions, Devices & Impersonation Architecture**

Proposed file:
```

docs/architecture/admin-sessions-security.md

```

---

**Library:** `maatify/admin-infra`  
**Phase Coverage:** Phase 6 — System Settings & Feature Flags