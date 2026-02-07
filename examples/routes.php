<?php
/**
 * Example Routing Configuration
 *
 * This script demonstrates how to define routes using the helper functions
 * provided by the entrypoint. It is automatically included by the entrypoint
 * based on the SCRIPT_DIR environment variable.
 */

http_response_code(404);

exact_route('/ok', 'ok.php');

?>
