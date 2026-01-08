# Sentinel's Journal

## 2024-10-25 - Stored XSS in Markdown Renderer
**Vulnerability:** The custom markdown renderer in `entrypoint.php` (function `content_scope_pop_markdown`) allowed arbitrary user input to be echoed into HTML attributes (`alt`, `title`, `src`, `href`) and HTML content (`<a>` body). This enabled Stored XSS via Attribute Injection and HTML Injection.
**Learning:** Custom output rendering functions often lack the context-aware sanitization provided by mature template engines or libraries. Escaping must be applied at the point of output, specific to the context (HTML attribute vs HTML body).
**Prevention:** Always use `htmlspecialchars($var, ENT_QUOTES)` when echoing user-controlled variables into HTML attributes. Avoid building HTML via string concatenation with raw user input.

## 2025-12-28 - Architectural Constraint on Header Case-Sensitivity
**Rejected Change:** A change was proposed to make the `Tailscale-User-Login` header check case-insensitive to align with RFC 2616.
**Learning:** In this architecture, the `Tailscale-User-Login` header is only considered valid when passed with exact casing from the upstream `ts-proxy` reverse proxy. Making the check case-insensitive would introduce a vulnerability, as an attacker could send a header with a different casing that the proxy might not overwrite, potentially bypassing authentication. This is a key example of an architectural decision overriding a general security best practice.
**Prevention:** Before applying general security principles, always confirm if specific architectural constraints justify the existing implementation. The case-sensitive check is an intentional security measure in this context.

## 2026-01-08 - Defense-in-Depth for Tailscale Headers
**Vulnerability:** The `auth_tailscale` function in `entrypoint.php` directly assigned user-provided data from `HTTP_TAILSCALE_USER_NAME` and `HTTP_TAILSCALE_USER_PROFILE_PIC` headers to constants. While no immediate vulnerability was present, any downstream PHP script that used these constants without proper escaping could be vulnerable to XSS.
**Learning:** Implementing security controls at the point of data ingestion provides a strong defense-in-depth posture. Sanitizing input at the source ensures that even if developers forget to escape output in the view layer, the application remains protected. This is especially critical in a system that uses `include` to execute downstream scripts.
**Prevention:** All user-controlled input, including trusted headers from an authentication proxy, should be sanitized or validated at the earliest possible point. For `TS_NAME`, this means applying `htmlspecialchars`. For `TS_PROFILE_PIC`, it involves both validating the URL scheme (to prevent `javascript:`) and then applying `htmlspecialchars`.
