<?php

// phpcs:disable PSR1.Files.SideEffects
// phpcs:disable Generic.Files.LineLength
// phpcs:disable PSR1.Classes.ClassDeclaration

// 🧹 Janitor: Replace magic strings with named constants for special Tailscale login values.
const TS_LOGIN_TAGGED_DEVICES = "tagged-devices";
const TS_LOGIN_EMPTY = "";
const TS_LOGIN_ANONYMOUS = "anonymous";
const TS_NAME_ANONYMOUS = "Anônimo";
const TS_PROFILE_PIC_ANONYMOUS = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=";
const RICKROLL_URL = "https://www.youtube.com/watch?v=dQw4w9WgXcQ";

// TODO: Add other commonly used HTTP status codes as constants (e.g., 200, 404, 500).
const HTTP_STATUS_TEMPORARY_REDIRECT = 307;

// ==================================== CLASSES ====================================

class Request
{
    private static $instance;
    public $method;
    public $uri;
    public $queryString;

    public static function instance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function capture()
    {
        $line = fgets(STDIN);
        if ($line === false) {
            return;
        }
        $line = trim($line);
        $parts = explode(" ", $line);

        $this->method = $parts[0] ?? 'GET';
        $this->uri = $parts[1] ?? '/';
        $this->queryString = parse_url($this->uri, PHP_URL_QUERY);

        $_SERVER['REQUEST_METHOD'] = $this->method;
        $_SERVER['REQUEST_URI'] = $this->uri;
        $_SERVER['QUERY_STRING'] = $this->queryString;

        // Headers loop
        while (true) {
            $h = fgets(STDIN);
            if ($h === false) {
                break;
            }
            $h = trim($h);
            if ($h === "") {
                break;
            }

            $parts = explode(":", $h, 2);
            $name = $parts[0];
            $value = isset($parts[1]) ? trim($parts[1]) : '';

            // fixes security issue where an attacker could
            // pass arbitrary stuff into the TAILSCALE_USER_LOGIN header
            if ($name == "Tailscale-User-Login") {
                if (!defined("TS_LOGIN")) {
                    define("TS_LOGIN", $value);
                }
            }

            $name = strtoupper($name);
            $name = str_replace("-", "_", $name);

            $_SERVER['HTTP_' . $name] = $value;
        }
    }
}

class Response
{
    private static $instance;
    private $rawHeaders = [];
    private $kvHeaders = [];

    public static function instance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function addRawHeader(string $header)
    {
        if (strpbrk($header, "\r\n") !== false) {
            error_log("Security Warning: Header injection attempt detected in header(): $header");
            return;
        }
        $this->rawHeaders[] = $header;
    }

    public function setHeader(string $key, string $value)
    {
        if (strpbrk($key, "\r\n") !== false || strpbrk($value, "\r\n") !== false) {
            error_log("Security Warning: Header injection attempt detected in set_header(): $key: $value");
            return;
        }
        $this->kvHeaders[$key] = $value;
    }

    public function getKvHeaders()
    {
        return $this->kvHeaders;
    }

    public function send($body)
    {
        if (!http_response_code()) {
            http_response_code(200);
        }
        echo "HTTP/1.0 ";
        echo http_response_code();
        echo "\r\n";

        foreach ($this->rawHeaders as $header) {
            echo "$header\r\n";
        }

        if (($this->kvHeaders['Content-Type'] ?? 'auto') == 'auto') {
            $this->setHeader("Content-Type", mime_from_buffer($body));
        }

        foreach ($this->kvHeaders as $key => $value) {
            echo "$key: $value\r\n";
        }

        echo "\r\n";
        echo $body;
    }
}

class Router
{
    private static $instance;
    private $isRouted = false;
    private $currentRoute;
    private $inputData;

    public static function instance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct()
    {
        $this->currentRoute = parse_url($_SERVER["REQUEST_URI"] ?? '', PHP_URL_PATH) ?? '';
        $this->inputData = array_merge_recursive($_GET, $_POST);
    }

    public function markRouted()
    {
        $orig = $this->isRouted;
        $this->isRouted = true;
        return $orig;
    }

    public function isRouted()
    {
        return $this->isRouted;
    }

    public function getCurrentRoute()
    {
        return $this->currentRoute;
    }

    public function updateRoute($newRoute)
    {
        $this->currentRoute = $newRoute;
    }

    public function execPhp(string $script)
    {
        // Expose global variables for included scripts
        global $INPUT_DATA, $ROUTE;
        $INPUT_DATA = $this->inputData; // update global
        $ROUTE = $this->currentRoute;   // update global

        $base_path = getcwd();
        $real_script_path = realpath($script);
        if ($real_script_path === false || !str_starts_with($real_script_path, $base_path)) {
            error_log("Path Traversal attempt blocked: " . $script);
            http_response_code(404);
            return;
        }
        require $real_script_path;
    }

    public function getInputData()
    {
        return $this->inputData;
    }

    public function setInputData($data)
    {
        $this->inputData = $data;
    }
}

class MarkdownRenderer
{
    public function render($content)
    {
        $lines = $content;

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

            $replace_term = sprintf(
                '<img alt="%s" title="%s" src="%s">',
                htmlspecialchars($label, ENT_QUOTES),
                htmlspecialchars($label, ENT_QUOTES),
                htmlspecialchars($link, ENT_QUOTES)
            );
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
                $replace_term = sprintf('<a href="%s">%s</a>', htmlspecialchars($link, ENT_QUOTES), htmlspecialchars($label, ENT_QUOTES));
                $lines = str_replace($search_term, $replace_term, $lines);
            } else {
                $search_term = $link;
                $replace_term = sprintf('<a href="%s">%s</a>', htmlspecialchars($link, ENT_QUOTES), htmlspecialchars($link, ENT_QUOTES));
                $lines = str_replace($search_term, $replace_term, $lines);
            }
        }

        $lines_arr = explode("\n", $lines);
        ob_start();

        foreach ($lines_arr as $i => $line) {
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
        return ob_get_clean();
    }
}

class TailscaleAuth
{
    public static function authenticate()
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
}

class App
{
    public function run()
    {
        Request::instance()->capture();

        // Default Headers
        $response = Response::instance();
        $response->setHeader("Server", "phpelo");
        $response->setHeader("Connection", "close");
        $response->setHeader("Content-Security-Policy", "default-src 'self'; style-src 'self' https://unpkg.com; img-src *; script-src 'none'; object-src 'none'; base-uri 'none';");
        $response->setHeader("Content-Type", "auto");

        // Auth
        TailscaleAuth::authenticate();

        // Output Buffer
        content_scope_push();

        // Register Shutdown
        register_shutdown_function([$this, 'shutdown']);

        // Routing
        $SCRIPT_DIR = getenv("SCRIPT_DIR");
        if ($SCRIPT_DIR) {
            chdir($SCRIPT_DIR);
            $ROUTES_SCRIPT = "$SCRIPT_DIR/routes.php";

            if (!file_exists($ROUTES_SCRIPT)) {
                http_response_code(404);
            } else {
                // Initialize Router global state for backward compatibility
                global $ROUTE, $INPUT_DATA;
                $router = Router::instance();
                $ROUTE = $router->getCurrentRoute();
                $INPUT_DATA = $router->getInputData();

                include "$ROUTES_SCRIPT";
            }
        }
    }

    public function shutdown()
    {
        $data = content_scope_pop();
        Response::instance()->send($data);
    }
}

// ==================================== GLOBAL HELPERS (FACADES) ====================================

if (!function_exists('header')) {
    function header(string $header)
    {
        Response::instance()->addRawHeader($header);
    }
}

function set_header(string $key, string $value)
{
    Response::instance()->setHeader($key, $value);
}

function set_contenttype(string $content_type)
{
    set_header("Content-Type", $content_type);
}

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

function mark_routed()
{
    return Router::instance()->markRouted();
}

function execphp(string $script)
{
    Router::instance()->execPhp($script);
}

function use_route(string $base_route, string $handler_script)
{
    $router = Router::instance();
    if ($router->isRouted()) {
        return;
    }

    $currentRoute = $router->getCurrentRoute();
    if (str_starts_with($currentRoute, $base_route)) {
        $router->updateRoute(substr($currentRoute, strlen($base_route)));
        $router->execPhp($handler_script);
    }
}

function exact_route(string $selected_route, string $handler_script)
{
    $router = Router::instance();
    if (strcmp($router->getCurrentRoute(), $selected_route) == 0) {
        if ($router->markRouted()) {
            return;
        }
        $router->execPhp($handler_script);
    }
}

function exact_with_route_param(string $selected_route, string $handler_script)
{
    $router = Router::instance();
    $preprocess = function (string $raw_route) {
        $splitted = preg_split("/\//", $raw_route);
        $splitted = array_filter($splitted, function ($v, $k) {
            return $v !== '';
        }, ARRAY_FILTER_USE_BOTH);
        return array_values($splitted);
    };
    $params_parts = $preprocess($selected_route);
    $route_parts = $preprocess($router->getCurrentRoute());

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
    if ($router->markRouted()) {
        return;
    }

    $inputData = array_merge_recursive($router->getInputData(), $extra_params);
    $router->setInputData($inputData);

    $router->execPhp($handler_script);
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
    content_html();
    $content = content_scope_pop();
    return (new MarkdownRenderer())->render($content);
}

function sakuracss_auto()
{
    ?>
        <link rel="stylesheet" href="https://unpkg.com/sakura.css/css/sakura.css" media="screen" />
        <link rel="stylesheet" href="https://unpkg.com/sakura.css/css/sakura-dark.css" media="screen and (prefers-color-scheme: dark)" />
    <?php
}

function rickroll_user()
{
    http_response_code(HTTP_STATUS_TEMPORARY_REDIRECT);
    set_header("Location", RICKROLL_URL);
    exit(0);
}

function rickroll_unlogged()
{
    if (!TS_HAS_LOGIN) {
        rickroll_user();
    }
}

// ==================================== EXECUTION ====================================

$app = new App();
$app->run();
