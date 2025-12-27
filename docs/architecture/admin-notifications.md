# 📘 Admin Notifications Architecture

## Maatify Admin Infra

> **Authoritative Architecture Document — Phase 5 (LOCKED & EXTENSIBLE)**

---

## 🎯 Purpose

This document defines the **extensible notifications infrastructure** for
**Maatify Admin Infra**.

It guarantees that:

* Notification delivery is decoupled from core logic
* New channels (e.g. WhatsApp, Slack, SMS) can be added **without modifying core**
* Security rules remain enforced regardless of channel

This document is **authoritative** and binding for all implementations.

---

## 🧠 Core Principles (LOCKED)

* Notifications are **side effects**, not system state
* Delivery is **best-effort**
* Core logic must be **channel-agnostic**
* Channels are **pluggable**
* No channel is trusted for security decisions
* User preferences are respected
* Security notifications have priority
* Every dispatch attempt is auditable

---

## 🧱 Architectural Position

Notifications are implemented via a **Channel Abstraction Layer**.

```
Admin Infra Core
        |
        v
NotificationDispatcher
        |
        +--> NotificationChannelInterface
                |
                +--> TelegramChannel
                +--> EmailChannel
                +--> WhatsAppChannel   (future)
                +--> SlackChannel      (future)
                +--> SMSChannel        (future)
                +--> NullChannel
```

> **Core knows only the interface, never the concrete channel.**

---

## 🧩 Notification Channel Contract (Conceptual)

### `NotificationChannelInterface`

Each channel must implement the same contract.

**Conceptual responsibilities:**

```
send(notification): DeliveryResult
supports(notification_type): bool
isAvailable(): bool
```

**Rules (LOCKED):**

* No exceptions bubble to core
* No blocking behavior
* No retries inside request lifecycle
* Channel failures are reported, not thrown

---

## 🧩 Notification Types (Authoritative)

### 1. Security Notifications (CRITICAL)

* New login detected
* Failed login attempts
* TOTP enabled / disabled
* Device revoked
* Session revoked
* Impersonation started / ended
* Emergency access triggered

🔒 **Rules:**

* Cannot be fully disabled
* Always attempted on at least one channel

---

### 2. System Notifications (IMPORTANT)

* Maintenance enabled / disabled
* Global settings changed
* Feature flags toggled

---

### 3. Administrative Notifications (INFORMATIONAL)

* Admin created
* Role assigned / revoked
* Permissions updated

---

## 🔔 Supported Channels (Initial)

### Telegram

* Notifications only
* Bot-based, outbound only
* No commands
* No auth
* No MFA

### Email

* SMTP-based
* Mandatory fallback channel
* Used for:

    * Verification
    * Critical alerts
    * Recovery

### Null Channel

* Discards notifications
* Used for testing / development

---

## 🔮 Future Channels (Explicitly Supported by Design)

The architecture **explicitly supports** adding:

* WhatsApp (Business API)
* Slack
* Microsoft Teams
* SMS
* Push notifications

Adding a new channel **MUST NOT** require:

* Changes to core logic
* Changes to authorization
* Changes to audit logic
* Changes to notification types

---

## ⚙️ System-level Configuration

### Feature Flags (`system_settings`)

```
notifications.enabled = true | false
notifications.channels.telegram.enabled = true | false
notifications.channels.email.enabled = true | false
notifications.channels.whatsapp.enabled = true | false   (future)
```

* Channels are enabled/disabled independently
* Security notifications ignore global disable unless explicitly overridden

---

## 👤 Admin-level Notification Preferences

Admins can control **how** they receive notifications.

### Conceptual Preferences Model

```
admin_notification_preferences
-------------------------
- admin_id
- channel
- notification_type
- is_enabled
```

**Rules (LOCKED):**

* Preferences are opt-in
* Admin preferences never override system-level disables
* Security notifications:

    * Can be partially muted
    * Cannot be completely disabled

---

## 🧠 Dispatch & Routing Rules

1. Core emits a notification intent
2. Dispatcher resolves:

    * Notification type
    * Enabled channels
    * Admin preferences
3. Dispatcher sends to all eligible channels
4. Failures are logged, not retried inline

---

## 🧾 Audit Requirements

The following **must be logged** (via Phase 4):

* Notification dispatched
* Channel selected
* Delivery success
* Delivery failure
* Channel unavailable
* Preference suppression
* Security override

---

## 🔒 Data Safety Rules (LOCKED)

Notifications **MUST NOT** include:

* Passwords
* TOTP secrets
* OAuth tokens
* Session tokens
* Sensitive payloads
* Raw PII

Message content must be:

* Minimal
* Informational
* Non-interactive
* Non-actionable

---

## 🚫 Forbidden Patterns (LOCKED)

* ❌ Hardcoding channels in core
* ❌ Channel-specific logic in core
* ❌ Blocking execution on delivery
* ❌ Trusting delivery confirmation
* ❌ Using notifications for authentication
* ❌ Inbound commands via messaging platforms

---

## 🏁 Status

> ✅ **FINAL · LOCKED · EXTENSIBLE**

This architecture guarantees that **any future notification channel**
(e.g. WhatsApp) can be added safely **without breaking the system**.

---

## 🔜 Next Phase (Phase 6)

**System Settings & Feature Flags Architecture**

Proposed file:

```
docs/architecture/admin-system-settings.md
```

---