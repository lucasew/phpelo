<?php

// 🧹 Janitor: Replace magic strings with named constants for special Tailscale login values.
const TS_LOGIN_TAGGED_DEVICES = "tagged-devices";
const TS_LOGIN_EMPTY = "";
const TS_LOGIN_ANONYMOUS = "anonymous";
const TS_NAME_ANONYMOUS = "Anônimo";
const TS_PROFILE_PIC_ANONYMOUS = "data:image/png;base64,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
const RICKROLL_URL = "https://www.youtube.com/watch?v=dQw4w9WgXcQ";

const HTTP_STATUS_OK = 200;
const HTTP_STATUS_TEMPORARY_REDIRECT = 307;
const HTTP_STATUS_NOT_FOUND = 404;
const HTTP_STATUS_INTERNAL_SERVER_ERROR = 500;

const CONTENT_TYPE_AUTO = "auto";
const CONTENT_TYPE_TEXT_PLAIN = "text/plain; charset=utf-8";
const CONTENT_TYPE_TEXT_HTML = "text/html";

// ==================================== Ingestão da request que vem do stdin =============

$_http_header = fgets(STDIN);
$_http_header = trim($_http_header);
$_http_header = explode(" ", $_http_header);

$_SERVER['REQUEST_METHOD'] = $_http_header[0];
$_SERVER['REQUEST_URI'] = $_http_header[1];
$_SERVER['QUERY_STRING'] = parse_url($_http_header[1], PHP_URL_QUERY);

$_HEADERS = array();
function header(string $header) {
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
    $_header_value = substr($_header, strlen($_header_name)+1);
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
set_header("Server", "phpelo");
set_header("Connection", "close");
set_header("Content-Security-Policy", "default-src 'self'; style-src 'self' https://unpkg.com; img-src *; script-src 'none'; object-src 'none'; base-uri 'none';");

$_HEADERS_KV = array();
function set_header(string $key, string $value) {
    global $_HEADERS_KV;
    $_HEADERS_KV[$key] = $value;
}

// ==================================== Utilitários para content-type =============
function set_contenttype(string $content_type) {
    set_header("Content-Type", $content_type);
}

set_contenttype(CONTENT_TYPE_AUTO); // default

function content_text() {
    set_contenttype(CONTENT_TYPE_TEXT_PLAIN);
}

function content_html() {
    set_contenttype(CONTENT_TYPE_TEXT_HTML);
}

function mime_from_buffer($buffer) {
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    return $finfo->buffer($buffer);
}


// ==================================== Utilitários para roteamento =============
$INPUT_DATA = array_merge_recursive($_GET, $_POST);
$ROUTE = parse_url($_SERVER["REQUEST_URI"])["path"] ?? '';
$IS_ROUTED = false;

function mark_routed() {
    global $IS_ROUTED;
    $orig_VALUE = $IS_ROUTED;
    $IS_ROUTED = true;
    return $orig_VALUE;
}


/**
 * bring required variables to scope then require the new file
 */
function execphp(string $script) {
    global $INPUT_DATA, $ROUTE;
    $base_path = getcwd(); // We've already chdir'd to SCRIPT_DIR

    // 🛡️ Sentinel: Prevent LFI / Path Traversal.
    // Ensure the requested script is within the allowed directory.
    $real_script_path = realpath($script);
    if ($real_script_path === false || !str_starts_with($real_script_path, $base_path)) {
        error_log("Path Traversal attempt blocked: " . $script);
        http_response_code(HTTP_STATUS_NOT_FOUND);
        return;
    }

    require $real_script_path;
}

/**
 * create a route that matches anything starting with $base_route
 */
function use_route(string $base_route, string $handler_script) {
    global $ROUTE, $IS_ROUTED;
    if (str_starts_with($ROUTE, $base_route)) {
        if ($IS_ROUTED) return;
        $ROUTE = substr($ROUTE, strlen($base_route));
        execphp($handler_script);
    }
}

/**
 * create a route that matches exactly $selected_route
 */
function exact_route(string $selected_route, string $handler_script) {
    global $ROUTE;
    if (strcmp($ROUTE, $selected_route) == 0) {
        if (mark_routed()) return;
        execphp($handler_script);
    }
}

/**
 * create a route with route params, like /users/:user/info 
 * and pass the route param with input_data
 */
function exact_with_route_param(string $selected_route, string $handler_script) {
    global $INPUT_DATA, $ROUTE;
    $preprocess = function (string $raw_route) {
        $splitted = preg_split("/\//", $raw_route);
        // 🧹 Janitor: The original code called an undefined function `is_empty_string`.
        // Replaced with the correct inline check to filter out empty path segments.
        $splitted = array_filter($splitted, function($v, $k) {
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
    if (mark_routed()) return;
    $INPUT_DATA = array_merge_recursive($INPUT_DATA, $extra_params);
    execphp($handler_script);
}

function content_scope_push() {
    ob_start();
}

function content_scope_pop() {
    $data = ob_get_contents();
    ob_end_clean();
    return $data;
}

function content_scope_pop_markdown() {
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

function sakuracss_auto () {
    ?>
        <link rel="stylesheet" href="https://unpkg.com/sakura.css/css/sakura.css" media="screen" />
        <link rel="stylesheet" href="https://unpkg.com/sakura.css/css/sakura-dark.css" media="screen and (prefers-color-scheme: dark)" />
    <?php
}

function auth_tailscale() {
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

function rickroll_user() {
    http_response_code(HTTP_STATUS_TEMPORARY_REDIRECT);
    set_header("Location", RICKROLL_URL);
    exit(0); // dispara shutdown, que vai enviar o que precisa ser enviado
}

function rickroll_unlogged() {
    if (!TS_HAS_LOGIN) {
        rickroll_user();
    }
}

content_scope_push(); // saporra appenda os echo num buffer pq nessa fase ainda não tem nada retornado

// ==================================== Finalização =========================

function shutdown() {
    global $_HEADERS;
    global $_HEADERS_KV;
    $data = content_scope_pop();

    if (!http_response_code()) {
        http_response_code(HTTP_STATUS_OK); // default response code
    }
    echo "HTTP/1.0 ";
    echo http_response_code();
    echo "\r\n";

    foreach ($_HEADERS as $header) {
        echo "$header\r\n";
    }

    if ($_HEADERS_KV['Content-Type'] == CONTENT_TYPE_AUTO) {
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
    http_response_code(HTTP_STATUS_NOT_FOUND);
} else {
    include "$ROUTES_SCRIPT";
}

?>
