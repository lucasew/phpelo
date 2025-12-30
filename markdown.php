<?php
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
        error_log("match: link='$link' offset='$offset'");
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
