# 📘 Admin Failure & Exception Model

## Maatify Admin Infra

> **Authoritative Architecture Document — FAILURE & ERROR SEMANTICS (LOCKED)**

---

## 🎯 Purpose

This document defines the **authoritative failure, error, and exception model**
for **Maatify Admin Infra**.

It governs:

* How failures are represented
* When exceptions are allowed
* When results must be returned as DTOs
* How the library remains usable across:

    * Ajax / JSON APIs
    * PHP UI & templates
    * CLI tools
    * Background jobs
    * Callback-based orchestration

This document is **architectural**, not implementation-specific, and is
**binding for all future phases and implementations**.

---

## 🧠 Core Principle (LOCKED)

> **Exceptions represent system or contract failure — never user outcomes.**

User-facing outcomes **must never** be represented by exceptions.

---

## 🧱 Design Goal

The failure model is designed to ensure that:

* The library is **transport-agnostic**
* No UI or HTTP assumptions exist
* Failure behavior is **deterministic**
* Upper layers can safely translate outcomes
* Control flow is explicit and inspectable

---

## 🧩 Failure Categories (Authoritative Taxonomy)

All failures fall into **exactly one** of the following categories.

---

### 1️⃣ Contract Violations (EXCEPTION)

**Meaning:**
The developer misused the library or violated a defined contract.

**Examples:**

* Passing an invalid DTO
* Calling a command in an invalid phase
* Using a repository in an unsupported context
* Violating DTO invariants

**Handling:**

* ❗ Throw a typed exception
* ❗ Fail fast
* ❗ Do not attempt recovery

---

### 2️⃣ Invariant Violations (EXCEPTION)

**Meaning:**
An internal architectural rule was broken, and the system entered an
unexpected or impossible state.

**Examples:**

* Admin status transition not allowed by lifecycle rules
* Conflicting internal state detected
* Logical impossibility reached

**Handling:**

* ❗ Throw a typed exception
* ❗ Consider this a bug
* ❗ Must be detectable during testing

---

### 3️⃣ Infrastructure / Configuration Failures (EXCEPTION)

**Meaning:**
The system is unable to operate due to missing or invalid configuration
or unavailable infrastructure.

**Examples:**

* Repository backend unavailable
* System settings misconfigured
* Required dependency missing

**Handling:**

* ❗ Throw a typed exception
* ❗ No silent fallback
* ❗ No user-facing messaging

---

### 4️⃣ Business Outcomes (RESULT DTO)

**Meaning:**
A valid request was processed correctly, but resulted in a negative or
non-success outcome.

**Examples:**

* Permission denied
* Admin not found
* Session expired
* Invalid credentials
* TOTP validation failed
* Feature disabled

**Handling:**

* ✅ Return a Result DTO
* ❌ Never throw an exception
* ❌ Never rely on catch blocks for flow control

---

### 5️⃣ Validation Outcomes (RESULT DTO)

**Meaning:**
Input data was syntactically valid but failed domain or business validation.

**Examples:**

* Password policy violation
* Invalid state transition request
* Unsupported operation due to system settings

**Handling:**

* ✅ Return a Result DTO
* ❌ No exceptions
* ❌ No partial execution

---

## 🔐 Exception Rules (LOCKED)

Exceptions in **Maatify Admin Infra**:

* ✅ Represent **developer or system failure only**
* ✅ Are typed and explicit
* ❌ Do NOT represent user errors
* ❌ Do NOT contain user-facing messages
* ❌ Do NOT contain localization or presentation logic
* ❌ Do NOT encode HTTP status codes
* ❌ Do NOT control application flow

Exceptions are **never** part of the public behavioral contract.

---

## 📦 Result DTO Rules (LOCKED)

Result DTOs are the **only** mechanism for representing user-facing outcomes.

They MUST:

* Be explicit and typed
* Encode success or failure clearly
* Carry structured failure reasons (enums or value objects)
* Be transport-agnostic
* Contain no UI, HTTP, or presentation semantics

They MUST NOT:

* Contain arrays
* Contain generic payloads
* Rely on stringly-typed error messages
* Be ambiguous or context-dependent

---

## 🔁 Consumption Model (Informative)

The same Result DTO may be consumed by:

* Ajax / JSON APIs → serialized
* PHP UI templates → rendered
* CLI tools → printed
* Background jobs → decision branches
* Callback handlers → orchestration logic

**Without any change to admin-infra behavior.**

---

## 🚫 Forbidden Patterns (LOCKED)

The following patterns are **explicitly forbidden**:

* ❌ Throwing exceptions for:

    * Permission denied
    * Not found
    * Validation failure
    * Authentication failure
* ❌ Catching exceptions for control flow
* ❌ Returning arrays for error reporting
* ❌ Embedding UI or HTTP logic in errors
* ❌ Silent fallback on failure
* ❌ Context-dependent failure meaning

Any implementation using these patterns is **architecturally invalid**.

---

## 🧾 Audit & Observability Implications

* Exceptions indicate **system-level failure**
* Result DTO failures indicate **expected business outcomes**
* Both may be audited depending on severity and context
* Audit logging behavior is governed by Phase 4 (Audit & Activity)

---

## 🔒 Governance Rule

> Any implementation that violates this failure model
> **MUST be rejected**, regardless of convenience or backward compatibility.

This document supersedes:

* Coding style preferences
* Framework conventions
* Legacy patterns

---

## 🏁 Status

> ✅ **FINAL · LOCKED · AUTHORITATIVE**

This failure and exception model is mandatory for:

* All current contracts
* All future implementations
* All consuming systems

---

**Library:** `maatify/admin-infra`
**Architecture Scope:** Cross-cutting (All Phases)

---