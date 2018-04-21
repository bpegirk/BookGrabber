<?php
// list of the books
$f = scandir('books');

$books = [];
foreach ($f as $file) {

    if (strstr($file, 'html')) {
        $y = date('Y-m', filemtime('books/' . $file));
        $books[$y][] = '<p><a href="books/' . $file . '">' . substr($file, 0, -4) . '</a></p>';
    }
}

foreach ($books as $year => $html) {
    echo "<h1>$year</h1>" . implode('', $html);

}