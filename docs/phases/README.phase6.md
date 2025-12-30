# Phase 6 — TOTP / Multi-Factor Authentication (MFA)

## Status
**COMPLETED & LOCKED** 🔒

Phase 6 introduces full Time-based One-Time Password (TOTP) support as an optional
multi-factor authentication mechanism for admin accounts.

This phase is fully implemented, fully tested, and architecturally locked.
No further changes are permitted without entering a new phase or issuing an ADR.

---

## Scope

Phase 6 is responsible for:

- TOTP enrollment lifecycle
- TOTP verification during authentication
- TOTP disablement
- Deterministic time handling for verification windows
- Audit logging for all TOTP actions

This phase strictly focuses on **infrastructure behavior only**.
No UI, transport, or business logic is included.

---

## Core Components

### Domain Layer

- **TotpSecretGenerator**
  - Secure Base32 secret generation
  - RFC-compatible encoding
  - Safe bit-level handling (no arithmetic errors)

- **TotpCodeGenerator**
  - Time-based OTP generation (30-second window)
  - Deterministic generation via injected time provider

- **TotpWindowPolicy**
  - Configurable past/future acceptance windows
  - Defensive handling of invalid values

- **TotpVerifier**
  - Valid / invalid / expired classification
  - Window-aware verification
  - Time-provider driven (testable and deterministic)

---

### Orchestration Layer

- **TotpOrchestrator**
  - `enroll(AdminIdDTO)`
  - `verify(AdminIdDTO, TotpCodeDTO)`
  - `disable(AdminIdDTO)`

Responsibilities:
- Enforces system feature flags
- Validates admin existence and status
- Coordinates repositories
- Emits audit events
- Never exposes domain internals

---

## Audit & Observability

All TOTP actions emit structured audit events using
`AuditAuthEventDTO` with explicit `eventType` values:

- `auth.totp.failed`
- `auth.totp.verified`
- `auth.totp.disabled`

Audit logging is mandatory and enforced by tests.

---

## Testing Strategy

Phase 6 includes a **complete and first-class test suite**.

### Domain Tests
- TotpSecretGeneratorTest
- TotpCodeGeneratorTest
- TotpWindowPolicyTest
- TotpVerifierTest

### Orchestration Tests
- TotpOrchestratorTest
  - Enrollment success & failure paths
  - Verification success, invalid code, expired code
  - Disable flow
  - Audit logging verification

### Test Infrastructure
- **FakeTimeProvider**
  - Deterministic time control
  - Eliminates flaky time-based tests

### Coverage
- **Domain:** 100%
- **Orchestration:** High coverage (behavior-focused)

---

## Architectural Rules Enforced

- DTO-only contracts
- No anonymous objects in contract returns
- Nominal typing compliance (PHPUnit safe)
- No infrastructure leakage into domain logic
- No side effects outside orchestrators
- Tests validate behavior, not implementation details

---

## Breaking Changes

None.

Phase 6 introduces new capabilities without modifying existing public APIs.

---

## Lock Statement

Phase 6 is **architecturally locked**.

Any of the following requires a new phase or ADR:
- Changing TOTP algorithms
- Modifying verification window semantics
- Altering audit event structure
- Introducing new MFA factors

---

## Next Phases

- **Phase 7** — Sessions, Devices & MFA Binding
- **Phase 8** — Advanced Security Policies (planned)

---

## Summary

Phase 6 delivers a secure, deterministic, and fully test-covered TOTP
implementation aligned with the Admin Infra architectural vision.

This phase establishes a reliable MFA foundation for all subsequent
authentication and session-security features.
