<?php

/**
 * Example Route Handler
 *
 * This script is executed when the '/ok' route is matched.
 * It demonstrates how to set response codes, content types, and output HTML.
 */

http_response_code(200);
content_html();
?>

<?php sakuracss_auto(); ?>
<h1>It works!</h1>

<p>Random number: <?php echo rand(); ?></p>
