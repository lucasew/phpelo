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
