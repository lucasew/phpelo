<?php

// Mocks
function content_html() {}
function content_scope_push() { ob_start(); }
function content_scope_pop() { $c = ob_get_contents(); ob_end_clean(); return $c; }

// Extracted function from entrypoint.php (AFTER FIX)
function content_scope_pop_markdown($input)
{
    // Simulate content_scope_pop() getting the input
    // $lines = content_scope_pop();
    $lines = $input;

    $lines = htmlspecialchars($lines, ENT_QUOTES);
    $lines = preg_replace('/^&gt;/m', '>', $lines);

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
        printf('<img alt="%s" title="%s" src="%s">', $label, $label, $link);
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
            printf('<a href="%s">%s</a>', $link, $label);
            $replace_term = content_scope_pop();
            $lines = str_replace($search_term, $replace_term, $lines);
        } else {
            $search_term = $link;
            content_scope_push();
            printf('<a href="%s">%s</a>', $link, $link);
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

// TESTS
$tests = [
    "XSS" => [
        "input" => "<script>alert('XSS')</script>",
        "expected_contain" => "&lt;script&gt;",
        "should_not_contain" => "<script>"
    ],
    "Bold" => [
        "input" => "**Bold**",
        "expected_contain" => "<b>Bold</b>"
    ],
    "Quote" => [
        "input" => "> Quote",
        "expected_contain" => "<blockquote>Quote</blockquote>"
    ],
    "Link" => [
        "input" => "[Link](http://google.com)",
        "expected_contain" => '<a href="http://google.com">Link</a>'
    ],
    "LinkSpecial" => [
        "input" => "[A&B](http://site.com?x=1&y=2)",
        "expected_contain" => '<a href="http://site.com?x=1&amp;y=2">A&amp;B</a>'
    ],
    "Heading" => [
        "input" => "# Heading",
        "expected_contain" => "<h1>Heading</h1>"
    ]
];

$failed = false;

foreach ($tests as $name => $case) {
    $output = content_scope_pop_markdown($case["input"]);
    echo "Test $name: ";

    if (isset($case["expected_contain"]) && !str_contains($output, $case["expected_contain"])) {
        echo "FAILED. Expected to contain '{$case['expected_contain']}', got '$output'\n";
        $failed = true;
    } elseif (isset($case["should_not_contain"]) && str_contains($output, $case["should_not_contain"])) {
        echo "FAILED. Should NOT contain '{$case['should_not_contain']}', got '$output'\n";
        $failed = true;
    } else {
        echo "PASSED\n";
    }
}

if ($failed) {
    exit(1);
}
