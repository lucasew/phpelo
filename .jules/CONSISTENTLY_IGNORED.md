# Consistently Ignored Changes

This file lists patterns of changes that have been consistently rejected by human reviewers. All agents MUST consult this file before proposing a new change. If a planned change matches any pattern described below, it MUST be abandoned.

---

## IGNORE: "Fixing" Stored XSS in Markdown Renderer by changing escaping

**- Pattern:** Do not attempt to alter the XSS protection mechanism in the custom Markdown renderer. Specifically, do not change the `htmlspecialchars()` call that escapes the entire input string *before* the regex replacements are applied.
**- Justification:** Multiple attempts (PRs #6, #8, #14) to "fix" this have been rejected. The premise of these changes is flawed because the application's threat model does not consider the rendered Markdown to be from an untrusted source. Therefore, changes to harden the renderer against XSS are unnecessary and considered noise.
**- Files Affected:** `entrypoint.php`

---

## IGNORE: Hardening Tailscale Authentication Headers

**- Pattern:** Do not add extra validation or "hardening" to the Tailscale-provided HTTP headers (`HTTP_TAILSCALE_USER_NAME`, `HTTP_TAILSCALE_USER_LOGIN`, etc.).
**- Justification:** An attempt to harden these headers (PR #10) was rejected. The threat model assumes that these headers are trusted as they are injected by a verified sidecar proxy. Adding redundant checks is unnecessary and adds complexity for no security benefit.
**- Files Affected:** `entrypoint.php`

---

## IGNORE: Splitting entrypoint.php

**- Pattern:** Extracting code (e.g., routing logic, markdown renderer) from `entrypoint.php` into separate files (e.g., `lib/router.php`, `markdown.php`).
**- Justification:** The project is designed as a single-file application to simplify deployment (e.g., via Nix). Splitting the code complicates the deployment model and has been rejected (PRs #4, #22).
**- Files Affected:** `entrypoint.php`

---

## IGNORE: Refactoring to OOP/Classes

**- Pattern:** Refactoring global functions and state in `entrypoint.php` into encapsulated classes (e.g., Singleton `Response` class).
**- Justification:** The project follows a procedural style appropriate for a simple script. Introducing Object-Oriented patterns adds unnecessary complexity and has been rejected (PR #25).
**- Files Affected:** `entrypoint.php`

---

## IGNORE: Hardening Header Functions

**- Pattern:** Adding validation (e.g., `strpbrk`, regex) to the custom `header()` and `set_header()` functions to prevent HTTP Response Splitting (CRLF injection).
**- Justification:** Multiple attempts (PRs #13, #18, #19, #23) to add these checks have been rejected, suggesting that either the execution environment provides sufficient protection or the added complexity is considered unnecessary for this internal tool.
**- Files Affected:** `entrypoint.php`

---

## IGNORE: Replacing Magic Numbers/Strings

**- Pattern:** Replacing hardcoded values (like HTTP status codes `200`, `404` or content types) with named constants.
**- Justification:** The project prefers the immediacy of raw values over the abstraction of constants for standard protocol values. Attempts to introduce such constants (PR #20) have been rejected.
**- Files Affected:** `entrypoint.php`

---

## IGNORE: Configuring Tooling and CI

**- Pattern:** Adding configuration files for tooling (`mise.toml`, `composer.json`, `phpcs.xml`) or configuring CI workflows (`.github/workflows/autorelease.yml`).
**- Justification:** An attempt to configure standard tooling and CI (PR #24) was rejected. The project likely relies on a minimal or external configuration strategy, and adding these files is considered out of scope or unwanted.
**- Files Affected:** `mise.toml`, `composer.json`, `phpcs.xml`, `.github/workflows/autorelease.yml`
