<?php
// list of the books
$f = scandir('books');

foreach ($f as $file) {
    if (strstr($file, 'html')) {
        echo '<a href="books/' . $file . '">' . substr($file, 0, -4) . '</a>';
    }
}