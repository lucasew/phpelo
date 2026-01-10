## 2024-03-25 - Fix Stored XSS in Markdown Renderer

**Vulnerability:** The `content_scope_pop_markdown` function in `entrypoint.php` did not sanitize input before wrapping it in HTML tags. This meant that if user input (e.g., query parameters or database content) was processed by this function, an attacker could inject arbitrary HTML and JavaScript (XSS), as the function only replaced markdown syntax but left other HTML tags intact.

**Learning:** When implementing a custom text-to-HTML renderer (like a Markdown parser), it is crucial to sanitize the *entire* input string (e.g., using `htmlspecialchars`) *before* applying any formatting rules. Relying on the assumption that regex replacements for markdown syntax are sufficient is dangerous because it leaves non-matching content (like `<script>` tags) raw in the output.

**Prevention:** Always follow the "Sanitize First, Format Second" principle for custom renderers. If the renderer must support raw HTML, use a dedicated HTML sanitizer library (like HTML Purifier) instead of a simple regex-based approach. For this codebase, we applied `htmlspecialchars($lines, ENT_QUOTES)` at the start of the function and removed subsequent redundant escaping to prevent double-encoding.
