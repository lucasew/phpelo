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

## 2026-01-24 - Header Injection and Lost Security Headers
**Vulnerability:** The custom `header()` and `set_header()` functions lacked validation for CRLF characters, enabling HTTP Response Splitting. Additionally, improper initialization order of `$_HEADERS_KV` caused default security headers (CSP, Server) to be silently overwritten and lost.
**Learning:** Custom implementations of core HTTP functionality require rigorous validation that standard libraries often provide implicitly. Also, execution order in procedural scripts can lead to silent failures of security controls (like CSP) if initialization logic resets state after configuration.
**Prevention:** Always validate header inputs for control characters. Ensure global state initialization happens before any function calls that modify that state.

## 2024-10-25 - Fixing XSS in Custom Markdown Parsers
**Vulnerability:** The `content_scope_pop_markdown` function allowed Stored XSS because it processed raw input with regexes and wrapped lines in HTML tags without escaping the content first. It only escaped attributes in generated links/images.
**Learning:** When implementing a custom parser for a markup language (like Markdown), sanitizing the input *before* any processing is the most robust strategy. This prevents "regex injection" or HTML injection. However, this strategy requires careful handling of markup syntax characters (like `>` for blockquotes) which might be escaped by the sanitizer.
**Prevention:** Sanitize the entire input buffer using `htmlspecialchars` at the start of the rendering function. Then, restore any necessary syntax characters (like `>` at the start of lines) using specific regex replacements. This ensures all other content is safe by default.
