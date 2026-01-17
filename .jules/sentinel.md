# Sentinel's Journal

## 2024-10-25 - Stored XSS in Markdown Renderer
**Vulnerability:** The custom markdown renderer in `entrypoint.php` (function `content_scope_pop_markdown`) allowed arbitrary user input to be echoed into HTML attributes (`alt`, `title`, `src`, `href`) and HTML content (`<a>` body). This enabled Stored XSS via Attribute Injection and HTML Injection.
**Learning:** Custom output rendering functions often lack the context-aware sanitization provided by mature template engines or libraries. Escaping must be applied at the point of output, specific to the context (HTML attribute vs HTML body).
**Prevention:** Always use `htmlspecialchars($var, ENT_QUOTES)` when echoing user-controlled variables into HTML attributes. Avoid building HTML via string concatenation with raw user input.

## 2025-12-28 - Architectural Constraint on Header Case-Sensitivity
**Rejected Change:** A change was proposed to make the `Tailscale-User-Login` header check case-insensitive to align with RFC 2616.
**Learning:** In this architecture, the `Tailscale-User-Login` header is only considered valid when passed with exact casing from the upstream `ts-proxy` reverse proxy. Making the check case-insensitive would introduce a vulnerability, as an attacker could send a header with a different casing that the proxy might not overwrite, in order to bypass authentication. This is a key example of an architectural decision overriding a general security best practice.
**Prevention:** Before applying general security principles, always confirm if specific architectural constraints justify the existing implementation. The case-sensitive check is an intentional security measure in this context.

## 2026-01-14 - Mitigate XSS with Content Security Policy
**Vulnerability:** The `content_scope_pop_markdown` function has a history of incomplete XSS fixes. A full, secure refactor of the custom parser is complex and would exceed the scope of a small change. The risk of unpatched XSS vulnerabilities remains.
**Learning:** When a core component is inherently insecure and a complete rewrite is not feasible, a defense-in-depth approach can provide effective mitigation. In this case, adding a strict Content Security Policy (CSP) header neutralizes the XSS risk by instructing the browser to block all script execution, regardless of whether a vulnerability is successfully exploited.
**Prevention:** For any application that renders user-generated content, a Content Security Policy should be implemented as a baseline security measure. This ensures that even if an XSS vulnerability is introduced, its impact is significantly reduced or eliminated entirely.

## 2026-10-25 - Custom Header Function Vulnerability
**Vulnerability:** The custom `header()` and `set_header()` functions in `entrypoint.php` blindly echoed input to STDOUT without checking for CRLF characters. This allowed HTTP Response Splitting (CRLF Injection) if an attacker could control header content.
**Learning:** Re-implementing core language features (like `header()`) often bypasses built-in security protections that have been refined over years. The native PHP `header()` function has built-in CRLF protection, but the custom one did not.
**Prevention:** When replacing core functionality, always audit the original implementation's security guarantees and replicate them. For HTTP headers, strictly validate that keys and values do not contain `\r` or `\n`.
