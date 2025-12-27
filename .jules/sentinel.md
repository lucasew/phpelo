# Sentinel's Journal

## 2024-10-25 - Stored XSS in Markdown Renderer
**Vulnerability:** The custom markdown renderer in `entrypoint.php` (function `content_scope_pop_markdown`) allowed arbitrary user input to be echoed into HTML attributes (`alt`, `title`, `src`, `href`) and HTML content (`<a>` body). This enabled Stored XSS via Attribute Injection and HTML Injection.
**Learning:** Custom output rendering functions often lack the context-aware sanitization provided by mature template engines or libraries. Escaping must be applied at the point of output, specific to the context (HTML attribute vs HTML body).
**Prevention:** Always use `htmlspecialchars($var, ENT_QUOTES)` when echoing user-controlled variables into HTML attributes. Avoid building HTML via string concatenation with raw user input.
