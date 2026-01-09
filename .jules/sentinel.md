# Sentinel's Journal

## 2024-10-25 - Stored XSS in Markdown Renderer
**Vulnerability:** The custom markdown renderer in `entrypoint.php` (function `content_scope_pop_markdown`) allowed arbitrary user input to be echoed into HTML attributes (`alt`, `title`, `src`, `href`) and HTML content (`<a>` body). This enabled Stored XSS via Attribute Injection and HTML Injection.
**Learning:** Custom output rendering functions often lack the context-aware sanitization provided by mature template engines or libraries. Escaping must be applied at the point of output, specific to the context (HTML attribute vs HTML body).
**Prevention:** Always use `htmlspecialchars($var, ENT_QUOTES)` when echoing user-controlled variables into HTML attributes. Avoid building HTML via string concatenation with raw user input.

## 2025-12-28 - Architectural Constraint on Header Case-Sensitivity
**Rejected Change:** A change was proposed to make the `Tailscale-User-Login` header check case-insensitive to align with RFC 2616.
**Learning:** In this architecture, the `Tailscale-User-Login` header is only considered valid when passed with exact casing from the upstream `ts-proxy` reverse proxy. Making the check case-insensitive would introduce a vulnerability, as an attacker could send a header with a different casing that the proxy might not overwrite, potentially bypassing authentication. This is a key example of an architectural decision overriding a general security best practice.
**Prevention:** Before applying general security principles, always confirm if specific architectural constraints justify the existing implementation. The case-sensitive check is an intentional security measure in this context.

## 2024-07-15 - XSS in Route Parameters
**Vulnerability:** The `exact_with_route_param` function did not sanitize URL path parameters before passing them to handler scripts.
**Learning:** This oversight existed because the routing logic and application logic are tightly coupled, making it easy to miss the need for sanitization at the framework level. The fix applies a defense-in-depth principle by sanitizing the input at the earliest possible point.
**Prevention:** Sanitize all user-controllable data, including URL path segments, as soon as it is processed by the application.
