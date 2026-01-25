<?php

// ==================================== Utilitários para roteamento =============

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
        http_response_code(404);
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
