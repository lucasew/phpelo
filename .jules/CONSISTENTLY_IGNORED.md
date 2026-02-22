# Consistently Ignored Changes

This file lists patterns of changes that have been consistently rejected by human reviewers. All agents MUST consult this file before proposing a new change. If a planned change matches any pattern described below, it MUST be abandoned.

---

## IGNORE: "Fixing" Stored XSS in Markdown Renderer by changing escaping

**- Pattern:** Do not attempt to alter the XSS protection mechanism in the custom Markdown renderer. Specifically, do not change the `htmlspecialchars()` call that escapes the entire input string *before* the regex replacements are applied.
**- Justification:** Multiple attempts (PRs #6, #8, #14, #31, #35, #36) to "fix" this have been rejected. The project prefers a defense-in-depth approach using Content Security Policy (CSP) rather than complicating the parser logic.
**- Files Affected:** `entrypoint.php`

---

## IGNORE: Hardening Tailscale Authentication Headers

**- Pattern:** Do not add extra validation or "hardening" to the Tailscale-provided HTTP headers (`HTTP_TAILSCALE_USER_NAME`, `HTTP_TAILSCALE_USER_LOGIN`, etc.).
**- Justification:** An attempt to harden these headers (PR #10) was rejected. The threat model assumes that these headers are trusted as they are injected by a verified sidecar proxy. Adding redundant checks is unnecessary and adds complexity for no security benefit.
**- Files Affected:** `entrypoint.php`

---

## IGNORE: Refactoring to Object-Oriented Design

**- Pattern:** Do not refactor the procedural code in `entrypoint.php` into Classes, Objects, or Singletons.
**- Justification:** The project strictly adheres to a procedural programming style with global state (PRs #25, #30). Refactoring to OOP is considered over-engineering for this specific architecture.
**- Files Affected:** `entrypoint.php`

---

## IGNORE: Multi-file Architecture

**- Pattern:** Do not extract code from `entrypoint.php` into separate files or directories (e.g., `lib/`, `src/`).
**- Justification:** The project enforces a strict single-file architecture to simplify deployment and maintenance (PRs #22, #30).
**- Files Affected:** `entrypoint.php`, `lib/*`

---

## IGNORE: Adding Standard Tooling Configuration

**- Pattern:** Do not add configuration files for standard tooling (e.g., `composer.json`, `mise.toml`, `phpcs.xml`, `.gitignore`) or CI workflows.
**- Justification:** The project maintains a minimal environment footprint. The build system (Nix) manages dependencies and environment, and adding these files is considered unnecessary noise (PRs #24, #30).
**- Files Affected:** `composer.json`, `mise.toml`, `.gitignore`, `.github/workflows/*`

---

## IGNORE: Replacing Magic Numbers/Strings with Constants

**- Pattern:** Do not replace literal values (magic numbers/strings) with named constants for HTTP status codes and content types.
**- Justification:** Attempts to refactor literals into constants (PR #20) have been rejected. The project prefers literal values for simplicity in this script, or the existing constants are considered sufficient.
**- Files Affected:** `entrypoint.php`

---

## IGNORE: Adding Manual CRLF Validation to Headers

**- Pattern:** Do not add manual validation for CRLF (`\r`, `\n`) in the custom `header()` and `set_header()` functions.
**- Justification:** Multiple attempts to add manual CRLF validation (PRs #13, #18, #19, #23) have been rejected. The custom implementation is intentionally kept minimal, and such validation is considered out of scope or redundant.
**- Files Affected:** `entrypoint.php`
