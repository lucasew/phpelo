## 2024-05-20 - Replace Magic Strings with Named Constants in Tailscale Auth
**Issue:** The `auth_tailscale` function used hardcoded "magic strings" like "tagged-devices", "", and "anonymous" for checking special Tailscale login states. These strings lacked context and made the code harder to understand and maintain.
**Root Cause:** The values were likely implemented as simple string literals during initial development without considering future readability or the need for a centralized definition of special authentication-related values.
**Solution:** I introduced named constants (`TS_LOGIN_TAGGED_DEVICES`, `TS_LOGIN_EMPTY`, `TS_LOGIN_ANONYMOUS`, `TS_NAME_ANONYMOUS`, `TS_PROFILE_PIC_ANONYMOUS`) to replace the magic strings. This makes the logic in `auth_tailscale` self-documenting and ensures that these special values are defined in a single, clear location.
**Pattern:** When encountering hardcoded, special-case strings or numbers that affect control flow, refactor them into named constants. This improves code clarity, reduces the risk of typos, and makes the codebase easier to maintain.

## 2026-01-14 - Replace Magic Number with Named Constant for HTTP Redirect
**Issue:** The `rickroll_user` function used the magic number `307` to set the HTTP status code for a temporary redirect. This number lacks context and makes the code's intent less obvious to future maintainers.
**Root Cause:** The HTTP status code was likely hardcoded as a quick implementation, without considering the long-term readability and maintainability benefits of using a named constant.
**Solution:** I introduced a named constant, `HTTP_STATUS_TEMPORARY_REDIRECT`, and replaced the magic number `307` in the `rickroll_user` function. This change makes the code more self-documenting and aligns with best practices for writing clean, maintainable code.
**Pattern:** Replace magic numbers, especially those representing status codes, flags, or other non-obvious values, with named constants to improve code clarity and maintainability.

## 2026-01-22 - Replace Magic Numbers/Strings with Named Constants for HTTP Status and Content Types
**Issue:** `entrypoint.php` contained hardcoded magic numbers (200, 404) and magic strings ("auto", "text/plain; charset=utf-8", "text/html") for HTTP status codes and Content-Type headers, reducing readability and scattering configuration values.
**Root Cause:** Initial development likely prioritized speed, leaving a TODO to address standardizing these values later.
**Solution:** Defined constants `HTTP_STATUS_OK`, `HTTP_STATUS_NOT_FOUND`, `HTTP_STATUS_INTERNAL_SERVER_ERROR` and content type constants, replacing the literal values throughout `entrypoint.php` and resolving the existing TODO.
**Pattern:** Centralize standard protocol values (like HTTP status codes and MIME types) into named constants to improve code clarity, facilitate reuse, and reduce the risk of inconsistencies.
