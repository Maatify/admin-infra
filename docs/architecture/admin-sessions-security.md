# Admin Sessions, Devices & Security Architecture
## Maatify Admin Infra

> **Authoritative Architecture Document — Phase 7 (LOCKED)**

---

## 🎯 Purpose

This document defines the **session management, device control, and advanced security architecture** for **Maatify Admin Infra**.

It governs:
- How admin sessions are created and revoked
- How devices are identified and managed
- How impersonation is handled safely
- How emergency and high-risk actions are controlled

This document is **authoritative** and binding for all implementations.

---

## 🧠 Core Principles (LOCKED)

- Sessions are **explicit**
- Devices are **identified, not trusted**
- Security controls are **internal only**
- No login method bypasses session rules
- No irreversible actions without traceability
- All security-sensitive actions are auditable
- Fail closed, never open

---

## 🧱 Architectural Position

Sessions and device security sit **after authentication and authorization**.

```

Authentication (Password / OAuth)
↓
Optional TOTP
↓
Session Creation
↓
Device Association
↓
Authorization Enforcement

```

---

## 🧩 Session Model (Conceptual)

### `admin_sessions`

```

## admin_sessions

* id (PK)
* admin_id (FK → admins.id)
* device_id
* ip_address
* user_agent
* created_at
* last_activity_at
* expires_at
* revoked_at
* revoked_by (admin_id | system)

```

**Rules (LOCKED):**
- Multiple concurrent sessions allowed (policy-based)
- Expired sessions are invalid
- Revoked sessions are immediately invalid
- No silent session extension

---

## 🧩 Device Model (Conceptual)

### `admin_devices`

```

## admin_devices

* id (PK)
* admin_id (FK → admins.id)
* device_fingerprint
* device_name
* first_seen_at
* last_seen_at
* is_trusted
* revoked_at

```

**Rules (LOCKED):**
- Devices are advisory, not authoritative
- Trust is explicit and revocable
- New devices can trigger security notifications
- Device trust never bypasses TOTP

---

## 🔐 Session Creation Rules

A session may be created **only if**:

- Admin status == `active`
- Authentication succeeded
- TOTP passed (if enabled)
- System is not in maintenance read-only mode

---

## 🔁 Session Lifecycle

```

created → active → expired
↓
revoked

```

- Revocation may be:
  - Manual
  - Automatic (security)
  - System-triggered

---

## 🔒 Session Revocation

Sessions may be revoked by:
- Admin themselves
- Super-admin with permission
- System (security events)

Examples:
- Password change
- TOTP disabled/enabled
- Device revoked
- Emergency access triggered

---

## 🧩 Impersonation Architecture

### Concept

Impersonation allows a privileged admin to **temporarily act as another admin**, strictly for support or investigation.

---

### `admin_impersonation_sessions`

```

## admin_impersonation_sessions

* id (PK)
* impersonator_admin_id
* target_admin_id
* started_at
* ended_at
* reason

```

**Rules (LOCKED):**
- Requires explicit permission (`security.impersonate`)
- Always time-limited
- Clearly visible in UI/context
- Cannot be chained
- Cannot impersonate higher-privileged admins
- Fully audited

---

## 🚨 Emergency Security Controls

### Emergency Access Mode

System may enter emergency mode when:
- Massive auth failures detected
- Admin compromise suspected
- Manual activation by super-admin

Effects:
- Forces logout of all sessions
- Disables impersonation
- Restricts sensitive actions
- Triggers security notifications

---

## 🔔 Notifications & Audit Integration

The following events **must trigger notifications and audit logs**:

- New session created
- Login from new device
- Session revoked
- Device revoked
- Impersonation started / ended
- Emergency mode enabled / disabled

---

## 🧾 Audit Requirements (Mandatory)

All session-related actions are audited:
- Session creation
- Session expiration
- Session revocation
- Device trust changes
- Impersonation lifecycle

(Logged via Phase 4 — Audit & Activity)

---

## 🔒 Forbidden Patterns (LOCKED)

- ❌ Sharing sessions across admins
- ❌ Reusing session identifiers
- ❌ Bypassing session checks
- ❌ Hiding impersonation state
- ❌ Permanent impersonation
- ❌ Device trust without admin consent
- ❌ Auto-login based on device trust

---

## 🏁 Status

> ✅ **FINAL · LOCKED · AUTHORITATIVE**

This document governs **all session, device, and impersonation behavior**  
for **Maatify Admin Infra**.

---

## 🔜 Next Phase (Phase 8)

**Export, Operations & Observability Architecture**

Proposed file:
```

docs/architecture/admin-ops-observability.md

```

---

**Library:** `maatify/admin-infra`  
**Phase Coverage:** Phase 7 — Sessions, Devices & Security