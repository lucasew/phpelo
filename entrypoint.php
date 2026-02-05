<?php

// 🧹 Janitor: Replace magic strings with named constants for special Tailscale login values.
const TS_LOGIN_TAGGED_DEVICES = "tagged-devices";
const TS_LOGIN_EMPTY = "";
const TS_LOGIN_ANONYMOUS = "anonymous";
const TS_NAME_ANONYMOUS = "Anônimo";
const TS_PROFILE_PIC_ANONYMOUS = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=";
const RICKROLL_URL = "https://www.youtube.com/watch?v=dQw4w9WgXcQ";

// TODO: Add other commonly used HTTP status codes as constants (e.g., 200, 404, 500).
const HTTP_STATUS_TEMPORARY_REDIRECT = 307;

// ==================================== Ingestão da request que vem do stdin =============

$_http_header = fgets(STDIN);
$_http_header = trim($_http_header);
$_http_header = explode(" ", $_http_header);

$_SERVER['REQUEST_METHOD'] = $_http_header[0];
$_SERVER['REQUEST_URI'] = $_http_header[1];
$_SERVER['QUERY_STRING'] = parse_url($_http_header[1], PHP_URL_QUERY);

$_HEADERS = array();
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
$_HEADERS_KV = array();
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
function set_contenttype(string $content_type)
{
    set_header("Content-Type", $content_type);
}

set_contenttype("auto"); // default

function content_text()
{
    set_contenttype("text/plain; charset=utf-8");
}

function content_html()
{
    set_contenttype("text/html");
}

function mime_from_buffer($buffer)
{
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    return $finfo->buffer($buffer);
}


// ==================================== Utilitários para roteamento =============
$INPUT_DATA = array_merge_recursive($_GET, $_POST);
$ROUTE = parse_url($_SERVER["REQUEST_URI"])["path"] ?? '';
$IS_ROUTED = false;

function mark_routed()
{
    global $IS_ROUTED;
    $orig_VALUE = $IS_ROUTED;
    $IS_ROUTED = true;
    return $orig_VALUE;
}


/**
 * bring required variables to scope then require the new file
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
 * create a route that matches anything starting with $base_route
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
 * create a route that matches exactly $selected_route
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
 * create a route with route params, like /users/:user/info
 * and pass the route param with input_data
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

function content_scope_push()
{
    ob_start();
}

function content_scope_pop()
{
    $data = ob_get_contents();
    ob_end_clean();
    return $data;
}

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

function sakuracss_auto()
{
    ?>
        <link rel="stylesheet" href="https://unpkg.com/sakura.css/css/sakura.css" media="screen" />
        <link rel="stylesheet" href="https://unpkg.com/sakura.css/css/sakura-dark.css" media="screen and (prefers-color-scheme: dark)" />
    <?php
}

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

function rickroll_user()
{
    http_response_code(HTTP_STATUS_TEMPORARY_REDIRECT);
    set_header("Location", RICKROLL_URL);
    exit(0); // dispara shutdown, que vai enviar o que precisa ser enviado
}

function rickroll_unlogged()
{
    if (!TS_HAS_LOGIN) {
        rickroll_user();
    }
}

content_scope_push(); // saporra appenda os echo num buffer pq nessa fase ainda não tem nada retornado

// ==================================== Finalização =========================

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

?>
