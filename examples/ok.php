<?php

http_response_code(200);
content_html();
?>

<?php sakuracss_auto(); ?>
<h1>It works!</h1>

<p>Random number: <?php echo rand(); ?></p>
