<?php

/**
 * Single-file PHP framework entry point.
 *
 * This script serves as the core of the application, handling:
 * - Request parsing from STDIN (CGI-like behavior).
 * - Routing logic (exact, prefix, and parameterized).
 * - Response buffering and lifecycle management.
 * - Global state management ($_HEADERS, $ROUTE, etc.).
 *
 * Designed to run in a restricted environment (e.g., systemd socket activation)
 * where standard PHP SAPI features might be bypassed or customized.
 *
 * @package Core
 */

// phpcs:disable PSR1.Files.SideEffects
// phpcs:disable Generic.Files.LineLength

// 🧹 Janitor: Replace magic strings with named constants for special Tailscale login values.
const TS_LOGIN_TAGGED_DEVICES = "tagged-devices";
const TS_LOGIN_EMPTY = "";
const TS_LOGIN_ANONYMOUS = "anonymous";
const TS_NAME_ANONYMOUS = "Anônimo";
const TS_PROFILE_PIC_ANONYMOUS = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=";
const RICKROLL_URL = "https://www.youtube.com/watch?v=dQw4w9WgXcQ";

// TODO: Add other commonly used HTTP status codes as constants (e.g., 200, 404, 500).
const HTTP_STATUS_TEMPORARY_REDIRECT = 307;

// ==================================== Ingestão da request que vem do stdin =============

/**
 * Main Request Loop
 *
 * Reads the HTTP request from STDIN (provided by the systemd socket or local server wrapper).
 * - Parses the Request-Line (Method, URI).
 * - Reads headers line-by-line until an empty line is found.
 * - Populates $_SERVER with HTTP_* variables.
 * - Handles special "Tailscale-User-Login" header security fix.
 */
$_http_header = fgets(STDIN);
$_http_header = trim($_http_header);
$_http_header = explode(" ", $_http_header);

$_SERVER['REQUEST_METHOD'] = $_http_header[0];
$_SERVER['REQUEST_URI'] = $_http_header[1];
$_SERVER['QUERY_STRING'] = parse_url($_http_header[1], PHP_URL_QUERY);

/**
 * Global buffer for raw HTTP headers.
 * Populated during the initial request parsing loop.
 * @var array<string>
 */
$_HEADERS = array();

/**
 * Adds a raw HTTP header to the response buffer.
 *
 * This function serves as a secure replacement for PHP's native header() function,
 * which is disabled in this environment. It buffers the header to be sent later
 * during the shutdown phase.
 *
 * Security:
 * - Validates input to prevent HTTP Response Splitting (CRLF injection).
 * - Logs a security warning if control characters are detected.
 *
 * @param string $header The raw header string (e.g., "Location: /foo").
 */
function header(string $header)
{
    if (strpbrk($header, "\r\n") !== false) {
        error_log("Security Warning: Header injection attempt detected in header(): $header");
        return;
    }
    global $_HEADERS;
    array_push($_HEADERS, $header);
}

while (true) {
    $_header = fgets(STDIN);
    $_header = trim($_header);
    if (strlen($_header) == 0) {
        break;
    }
    $_header_name = explode(":", $_header)[0];
    $_header_value = substr($_header, strlen($_header_name) + 1);
    $_header_value = trim($_header_value);

    // fixes security issue where an attacker could
    // pass arbitrary stuff into the TAILSCALE_USER_LOGIN header
    if ($_header_name == "Tailscale-User-Login") {
        define("TS_LOGIN", $_header_value);
    }

    $_header_name = strtoupper($_header_name);
    $_header_name = str_replace("-", "_", $_header_name);

    $_SERVER['HTTP_' . $_header_name] = $_header_value;
}

// ==================================== Primitiva de header pra retorno =============

/**
 * Global key-value store for structured HTTP headers (e.g., Content-Type).
 * Used to prevent duplicates and allow easy modification before shutdown.
 * @var array<string, string>
 */
$_HEADERS_KV = array();

/**
 * Sets a Key-Value HTTP header.
 *
 * Similar to header(), but accepts a key and value separately. These are stored
 * in a key-value array, allowing for specific lookups (e.g., Content-Type) later.
 *
 * Security:
 * - Validates both key and value for CRLF characters to prevent injection.
 *
 * @param string $key The header name (e.g., "Content-Type").
 * @param string $value The header value (e.g., "application/json").
 */
function set_header(string $key, string $value)
{
    if (strpbrk($key, "\r\n") !== false || strpbrk($value, "\r\n") !== false) {
        error_log("Security Warning: Header injection attempt detected in set_header(): $key: $value");
        return;
    }
    global $_HEADERS_KV;
    $_HEADERS_KV[$key] = $value;
}

set_header("Server", "phpelo");
set_header("Connection", "close");
set_header("Content-Security-Policy", "default-src 'self'; style-src 'self' https://unpkg.com; img-src *; script-src 'none'; object-src 'none'; base-uri 'none';");

// ==================================== Utilitários para content-type =============

/**
 * Helper to set the Content-Type header.
 * @param string $content_type The MIME type (e.g., "text/html").
 */
function set_contenttype(string $content_type)
{
    set_header("Content-Type", $content_type);
}

set_contenttype("auto"); // default

/** Sets Content-Type to text/plain; charset=utf-8 */
function content_text()
{
    set_contenttype("text/plain; charset=utf-8");
}

/** Sets Content-Type to text/html */
function content_html()
{
    set_contenttype("text/html");
}

/**
 * Detects MIME type from binary data buffer using finfo.
 * Used when Content-Type is set to "auto".
 * @param string $buffer The content buffer.
 * @return string The detected MIME type.
 */
function mime_from_buffer($buffer)
{
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    return $finfo->buffer($buffer);
}


// ==================================== Utilitários para roteamento =============

/**
 * Global input data array.
 * Merges GET, POST, and route parameters into a single accessible structure.
 * @var array<string, mixed>
 */
$INPUT_DATA = array_merge_recursive($_GET, $_POST);

/**
 * Current route path being processed.
 * Modified by `use_route` as it traverses the URI segments.
 * @var string
 */
$ROUTE = parse_url($_SERVER["REQUEST_URI"])["path"] ?? '';

/**
 * Flag indicating if a route has been fully matched and handled.
 * Prevents subsequent routes from executing once a match is found.
 * @var bool
 */
$IS_ROUTED = false;

/**
 * Marks the current request as routed.
 *
 * This function is used to prevent multiple routes from matching the same request.
 * It updates the global $IS_ROUTED flag.
 *
 * @return bool The previous state of the routed flag (true if already routed).
 */
function mark_routed()
{
    global $IS_ROUTED;
    $orig_VALUE = $IS_ROUTED;
    $IS_ROUTED = true;
    return $orig_VALUE;
}


/**
 * Executes a PHP script within the current scope.
 *
 * Imports global variables ($INPUT_DATA, $ROUTE) so the included script
 * can access them.
 *
 * Security:
 * - Prevents Local File Inclusion (LFI) by validating that the script path
 *   resolves to a location inside the current working directory ($base_path).
 *
 * @param string $script The path to the PHP script to execute.
 */
function execphp(string $script)
{
    global $INPUT_DATA, $ROUTE;
    $base_path = getcwd(); // We've already chdir'd to SCRIPT_DIR

    // 🛡️ Sentinel: Prevent LFI / Path Traversal.
    // Ensure the requested script is within the allowed directory.
    $real_script_path = realpath($script);
    if ($real_script_path === false || !str_starts_with($real_script_path, $base_path)) {
        error_log("Path Traversal attempt blocked: " . $script);
        http_response_code(404);
        return;
    }

    require $real_script_path;
}

/**
 * Registers a prefix route (middleware-style).
 *
 * Matches any request URI that starts with $base_route. If matched:
 * - Updates global $ROUTE by removing the prefix.
 * - Executes the handler script.
 *
 * Useful for mounting sub-applications or grouping routes.
 *
 * @param string $base_route The URI prefix to match (e.g., "/api").
 * @param string $handler_script The script to handle the request.
 */
function use_route(string $base_route, string $handler_script)
{
    global $ROUTE, $IS_ROUTED;
    if (str_starts_with($ROUTE, $base_route)) {
        if ($IS_ROUTED) {
            return;
        }
        $ROUTE = substr($ROUTE, strlen($base_route));
        execphp($handler_script);
    }
}

/**
 * Registers an exact match route.
 *
 * Matches only if the current global $ROUTE is identical to $selected_route.
 * If matched, marks the request as routed and executes the handler.
 *
 * @param string $selected_route The exact URI to match (e.g., "/about").
 * @param string $handler_script The script to handle the request.
 */
function exact_route(string $selected_route, string $handler_script)
{
    global $ROUTE;
    if (strcmp($ROUTE, $selected_route) == 0) {
        if (mark_routed()) {
            return;
        }
        execphp($handler_script);
    }
}

/**
 * Registers a parameterized route.
 *
 * Supports dynamic segments in the route pattern (e.g., `/user/:id`).
 *
 * Logic:
 * 1. Splits both the route pattern and current URI into segments.
 * 2. Matches segments:
 *    - Static segments must match exactly.
 *    - Dynamic segments (`:param`) are extracted.
 * 3. If matched, merges extracted parameters into `$INPUT_DATA`.
 * 4. Marks the request as routed and executes the handler.
 *
 * @param string $selected_route The route pattern (e.g., "/post/:id").
 * @param string $handler_script The script to handle the request.
 * @return void
 */
function exact_with_route_param(string $selected_route, string $handler_script)
{
    global $INPUT_DATA, $ROUTE;
    $preprocess = function (string $raw_route) {
        $splitted = preg_split("/\//", $raw_route);
        // 🧹 Janitor: The original code called an undefined function `is_empty_string`.
        // Replaced with the correct inline check to filter out empty path segments.
        $splitted = array_filter($splitted, function ($v, $k) {
            return $v !== '';
        }, ARRAY_FILTER_USE_BOTH);
        return array_values($splitted);
    };
    $params_parts = $preprocess($selected_route);
    $route_parts = $preprocess($ROUTE);

    $extra_params = [];
    if (count($params_parts) == count($route_parts)) {
        for ($i = 0; $i < count($params_parts); $i++) {
            if (str_starts_with($params_parts[$i], ":")) {
                $extra_params[substr($params_parts[$i], 1)] = $route_parts[$i];
            } else {
                if (strcmp($params_parts[$i], $route_parts[$i]) != 0) {
                    return;
                }
            }
        }
    } else {
        return;
    }
    if (mark_routed()) {
        return;
    }
    $INPUT_DATA = array_merge_recursive($INPUT_DATA, $extra_params);
    execphp($handler_script);
}

/**
 * Starts a new output buffer.
 *
 * This allows capturing echo output into a variable, which is useful for
 * building the response body or processing template content.
 */
function content_scope_push()
{
    ob_start();
}

/**
 * Ends the current output buffer and returns its contents.
 * @return string|false The buffered content.
 */
function content_scope_pop()
{
    $data = ob_get_contents();
    ob_end_clean();
    return $data;
}

/**
 * Pops the current scope, renders the content as Markdown, and returns HTML.
 *
 * Supported Syntax:
 * - Headings (# H1, ## H2...)
 * - Bold (**text**, __text__)
 * - Italics (*text*, _text_)
 * - Strikethrough (~text~)
 * - Links ([label](url))
 * - Images (![alt](url))
 * - Blockquotes (> text)
 *
 * @security WARNING: This renderer has known XSS vulnerabilities.
 * It does not escape HTML content before wrapping it in block tags.
 * Input should be trusted or sanitized before using this function.
 *
 * @return string The rendered HTML.
 */
function content_scope_pop_markdown()
{
    content_html(); // would be always html anyway
    $lines = content_scope_pop();

    $lines = preg_replace("/\n\#/", "\n\n#", $lines);
    $lines = preg_replace("/\n+/", "\n", $lines);

    $lines = preg_replace('/\*\*(.*)\*\*/', '<b>\\1</b>', $lines);
    $lines = preg_replace('/\_\_(.*)\_\_/', '<b>\\1</b>', $lines);
    $lines = preg_replace('/\*(.*)\*/', '<em>\\1</em>', $lines);
    $lines = preg_replace('/\_(.*)\_/', '<em>\\1</em>', $lines);
    $lines = preg_replace('/\~(.*)\~/', '<del>\\1</del>', $lines);

    while (true) {
        if (!preg_match('/\!\[([^\]]*?)\]\(([a-z]*:\/\/([a-z-0-9]*\.?)+(:[0-9]+)?[^\s\)]*)\)/m', $lines, $link_found, 0)) {
            break;
        }
        $search_term = $link_found[0];
        $label = $link_found[1];
        $link = $link_found[2];
        $json = json_encode($link_found);
        content_scope_push();
        printf('<img alt="%s" title="%s" src="%s">', htmlspecialchars($label, ENT_QUOTES), htmlspecialchars($label, ENT_QUOTES), htmlspecialchars($link, ENT_QUOTES));
        $replace_term = content_scope_pop();
        $lines = str_replace($search_term, $replace_term, $lines);
    }
    while (true) {
        if (!preg_match('/[\(\s]([a-z]*:\/\/([a-z-0-9]*\.?)+(:[0-9]+)?[^\s\)]*)[\)\s]/m', $lines, $link_found, PREG_OFFSET_CAPTURE, 0)) {
            break;
        }
        $link = substr($link_found[0][0], 1, -1);
        $offset = $link_found[0][1] + 1;
        if (substr($lines, $offset - 2, 2) == "](") {
            $exploded_label = explode("[", substr($lines, 0, $offset - 2));
            $label = $exploded_label[array_key_last($exploded_label)];
            $search_term = "[" . $label . "](" . $link . ")";
            content_scope_push();
            printf('<a href="%s">%s</a>', htmlspecialchars($link, ENT_QUOTES), htmlspecialchars($label, ENT_QUOTES));
            $replace_term = content_scope_pop();
            $lines = str_replace($search_term, $replace_term, $lines);
        } else {
            $search_term = $link;
            content_scope_push();
            printf('<a href="%s">%s</a>', htmlspecialchars($link, ENT_QUOTES), htmlspecialchars($link, ENT_QUOTES));
            $replace_term = content_scope_pop();
            $lines = str_replace($search_term, $replace_term, $lines);
        }
    }

    content_scope_push(); // output accumulator
    // $replaces = array()

    $lines = explode("\n", $lines);

    foreach ($lines as $i => $line) {
        $line = trim($line);
        if ($line == "") {
            continue;
        }
        $tag = 'p';
        preg_match('/^#*/', $line, $heading_level);
        $heading_level = strlen($heading_level[0]);

        if ($heading_level > 0) {
            $tag = "h$heading_level";
            $line = substr($line, $heading_level);
            $line = trim($line);
        } else {
            preg_match('/^>*/', $line, $blockquote_level);
            $blockquote_level = strlen($blockquote_level[0]);
            $line = substr($line, $blockquote_level);
            $line = trim($line);
            for ($i = 0; $i < $blockquote_level; $i++) {
                $line = "<blockquote>$line</blockquote>";
            }
        }
        $line = "<$tag>$line</$tag>";
        echo $line;
    }
    return content_scope_pop();
}

/**
 * Injects SakuraCSS for instant, classless styling.
 *
 * Appends `<link>` tags to the output buffer, enabling:
 * - Clean, minimal defaults for HTML elements.
 * - Automatic dark mode support via `media="screen and (prefers-color-scheme: dark)"`.
 *
 * @return void
 */
function sakuracss_auto()
{
    ?>
        <link rel="stylesheet" href="https://unpkg.com/sakura.css/css/sakura.css" media="screen" />
        <link rel="stylesheet" href="https://unpkg.com/sakura.css/css/sakura-dark.css" media="screen and (prefers-color-scheme: dark)" />
    <?php
}

/**
 * Authenticates the user via Tailscale headers.
 *
 * Populates global constants (`TS_NAME`, `TS_PROFILE_PIC`, `TS_HAS_LOGIN`).
 *
 * Fallback:
 * If no valid Tailscale headers are found (`HTTP_TAILSCALE_USER_*`), or if the
 * `TS_LOGIN` constant (defined during parse) is missing/invalid, it defaults
 * to the "Anonymous" profile.
 *
 * @return void
 */
function auth_tailscale()
{
    $name = "";
    $profile_pic = "";
    $has_login = true;
    if (array_key_exists("HTTP_TAILSCALE_USER_NAME", $_SERVER)) {
        $name = $_SERVER["HTTP_TAILSCALE_USER_NAME"];
    }
    if (array_key_exists("HTTP_TAILSCALE_USER_PROFILE_PIC", $_SERVER)) {
        $profile_pic = $_SERVER["HTTP_TAILSCALE_USER_PROFILE_PIC"];
    }
    if (!defined("TS_LOGIN") || TS_LOGIN == TS_LOGIN_TAGGED_DEVICES || TS_LOGIN == TS_LOGIN_EMPTY) {
        $login = TS_LOGIN_ANONYMOUS;
        $name = TS_NAME_ANONYMOUS;
        $profile_pic = TS_PROFILE_PIC_ANONYMOUS;
        $has_login = false;
    }
    define("TS_NAME", $name);
    define("TS_PROFILE_PIC", $profile_pic);
    define("TS_HAS_LOGIN", $has_login);
}
auth_tailscale();

/**
 * Redirects the user to a "Rick Roll" video.
 *
 * Used as a playful penalty for unauthorized access or invalid states.
 * Terminates execution immediately, triggering the shutdown handler.
 *
 * @return never Exits script execution.
 */
function rickroll_user()
{
    http_response_code(HTTP_STATUS_TEMPORARY_REDIRECT);
    set_header("Location", RICKROLL_URL);
    exit(0); // dispara shutdown, que vai enviar o que precisa ser enviado
}

/**
 * Checks if the user is logged in via Tailscale.
 *
 * If the user is not authenticated (anonymous), they are redirected
 * to the Rick Roll URL.
 */
function rickroll_unlogged()
{
    if (!TS_HAS_LOGIN) {
        rickroll_user();
    }
}

content_scope_push(); // saporra appenda os echo num buffer pq nessa fase ainda não tem nada retornado

// ==================================== Finalização =========================

/**
 * Global Shutdown Handler.
 *
 * Registered via `register_shutdown_function`, this function constructs and sends
 * the final HTTP response after script execution ends.
 *
 * Workflow:
 * 1. Retrieves buffered output content (`content_scope_pop`).
 * 2. Sends HTTP status line (e.g., "HTTP/1.0 200 OK").
 * 3. Sends collected raw headers (`$_HEADERS`).
 * 4. Determines Content-Type (auto-detects via `finfo` if set to "auto").
 * 5. Sends Key-Value headers (`$_HEADERS_KV`).
 * 6. Flushes the response body.
 *
 * @return void
 */
function shutdown()
{
    global $_HEADERS;
    global $_HEADERS_KV;
    $data = content_scope_pop();

    if (!http_response_code()) {
        http_response_code(200); // default response code
    }
    echo "HTTP/1.0 ";
    echo http_response_code();
    echo "\r\n";

    foreach ($_HEADERS as $header) {
        echo "$header\r\n";
    }

    if ($_HEADERS_KV['Content-Type'] == 'auto') {
        set_contenttype(mime_from_buffer($data));
    }

    foreach ($_HEADERS_KV as $key => $value) {
        echo "$key: $value\r\n";
    }

    echo "\r\n";

    echo $data;
}
register_shutdown_function('shutdown');

// ==================================== ROTAS ===============================

$SCRIPT_DIR = getenv("SCRIPT_DIR");
chdir($SCRIPT_DIR);

$ROUTES_SCRIPT = "$SCRIPT_DIR/routes.php";

if (!file_exists($ROUTES_SCRIPT)) {
    http_response_code(404);
} else {
    include "$ROUTES_SCRIPT";
}

