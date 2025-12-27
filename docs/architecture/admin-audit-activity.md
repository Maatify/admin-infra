# 📘 Admin Audit & Activity Architecture

## Maatify Admin Infra

> **Authoritative Architecture Document — Phase 4 (LOCKED)**

---

## 🎯 Purpose

This document defines the **audit and activity logging architecture** for
**Maatify Admin Infra**.

It establishes:

* What must be logged
* How events are structured
* Where data is stored
* How `maatify/mongo-activity` is integrated
* What guarantees the system provides (and does not provide)

This document is **authoritative** and binding for all implementations.

---

## 🧠 Core Principles (LOCKED)

* Audit is **append-only**
* Audit is **non-blocking**
* Audit must **never break core flows**
* Audit storage is **pluggable**
* Core system is **database-agnostic**
* Logs are **for traceability, not analytics**
* Sensitive data must be **redacted**
* Every privileged action must be auditable

---

## 🧩 Architectural Position

Audit & Activity logging is a **side-effect system**, not core state.

```
Admin Infra Core
        |
        v
AuditLoggerInterface
        |
        +--> MongoActivityDriver  (maatify/mongo-activity)
        |
        +--> SqlAuditDriver       (future)
        |
        +--> NullAuditDriver      (disabled / testing)
```

---

## 🔌 Integration with `maatify/mongo-activity`

### Official Position

> **`maatify/mongo-activity` is the recommended default audit backend**
> but **MUST NOT** be a hard dependency of `maatify/admin-infra`.

* Used via adapter/driver only
* Replaceable
* Optional
* Isolated from core DB

---

## 🧱 Audit Contract (Conceptual)

### `AuditLoggerInterface`

Conceptual responsibilities:

```
logAction()
logSecurityEvent()
logAuthEvent()
logView()            (optional / configurable)
```

**Rules (LOCKED):**

* Fire-and-forget
* No return value dependencies
* No exceptions bubble up
* Failure is silent but detectable

---

## 🧾 Event Taxonomy (Authoritative)

### 1. Authentication Events

* `auth.login.success`
* `auth.login.failed`
* `auth.logout`
* `auth.totp.enabled`
* `auth.totp.disabled`
* `auth.totp.failed`
* `auth.oauth.used`

---

### 2. Authorization Events

* `authz.permission.denied`
* `authz.role.assigned`
* `authz.role.revoked`
* `authz.permission.granted`
* `authz.permission.revoked`

---

### 3. Admin Actions

* `admin.created`
* `admin.status.changed`
* `admin.suspended`
* `admin.locked`
* `admin.impersonation.started`
* `admin.impersonation.ended`

---

### 4. System & Configuration

* `system.setting.updated`
* `system.maintenance.enabled`
* `system.maintenance.disabled`

---

### 5. Security Events

* `security.bruteforce.detected`
* `security.device.revoked`
* `security.session.revoked`
* `security.emergency.access`

---

### 6. View Events (Optional)

* `view.admin.profile`
* `view.audit.log`
* `view.security.dashboard`

> ⚠️ View logging is **opt-in** and **disabled by default**

---

## 🧬 Event Structure (Conceptual Schema)

All audit events share a **common envelope**.

```
{
  event_id,
  event_type,
  actor: {
    admin_id,
    impersonator_id (nullable)
  },
  target: {
    type,
    id (nullable)
  },
  context: {
    ip_address,
    user_agent,
    device_id
  },
  metadata: {
    key: value (sanitized)
  },
  severity,
  occurred_at
}
```

---

## 🔒 Data Safety Rules (LOCKED)

Audit logs **MUST NOT** contain:

* Passwords
* TOTP secrets
* OAuth tokens
* Session tokens
* Full request bodies
* PII beyond identifiers

All metadata must be:

* Minimal
* Redacted
* Explicit

---

## 🗄️ Storage Strategy

### Recommended Default

* MongoDB
* Collection per domain:

    * `admin_activity`
    * `security_events`
    * `auth_events`

### Retention

* TTL indexes
* Configurable per event type

---

## ⚡ Performance & Reliability

* Logging is **asynchronous**
* Logging failures:

    * Do NOT block requests
    * Do NOT affect user flows
* Optional fallback:

    * In-memory buffer
    * File-based queue (future)

---

## 🚨 Failure Behavior (LOCKED)

* Audit failure ≠ system failure
* Audit backend down:

    * Admin system continues
    * Alert may be triggered
* No retry loops inside request lifecycle

---

## 🔍 Querying & Access

Audit data:

* Is **read-only**
* Is **permission-protected**
* Is **never mutated**
* Is **never joined with core tables**

---

## 🧾 Compliance & Traceability

Audit guarantees:

* Non-repudiation
* Action traceability
* Historical integrity

Audit does **not** guarantee:

* Real-time analytics
* Business intelligence
* Metrics aggregation

---

## 🔒 Forbidden Patterns (LOCKED)

* ❌ Writing audit logs into core SQL tables
* ❌ Blocking requests on audit writes
* ❌ Mutating audit records
* ❌ Trusting audit storage for auth decisions
* ❌ Storing secrets or raw payloads

---

## 🏁 Status

> ✅ **FINAL · LOCKED · AUTHORITATIVE**

This document governs **all audit and activity behavior**
for **Maatify Admin Infra**.

---

## 🔜 Next Phase (Phase 5)

**Notifications Architecture**

Proposed file:

```
docs/architecture/admin-notifications.md
```

---