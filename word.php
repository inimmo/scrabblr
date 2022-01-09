<?php
$word = strtolower($_GET['word']);

$dom = new DOMDocument;
$src = file_get_contents("compress.zlib://http://1word.ws/{$word}");

$dom->loadHTML($src);
$xpath = new DOMXPath($dom);

$header = $xpath->query("//h1");
$def = $xpath->query("//h1/following-sibling::ul");

$valid = strpos($header[0]->nodeValue, "is a valid") !== false;
$defs = $def[0]->childNodes;

if (count($defs)) {
    $defs = array_map(function ($e) {
        return $e->nodeValue;
    }, iterator_to_array($defs));
}
?>

<!DOCTYPE html>
<html lang="">
    <head>
        <title>Is <?= htmlentities($word); ?> valid in Scrabble?</title>
        <style type="text/css">
            * {
                font-family: Georgia, serif;
            }
            h1 {
                font-size: 22pt;
            }
            li {
                font-size: 16pt;
            }
            a {
                text-decoration: none;
                color: inherit;
            }
        </style>
    </head>
    <body>
    <?php if ($valid): ?>
        <h1>Hooray you are good at Scrabble!</h1>
    <?php elseif(count($defs)): ?>
        <h1>"Officially" you are bad at Scrabble, HOWEVER:</h1>
    <?php else: ?>
        <h1>Oh no you are bad at Scrabble!</h1>
    <?php endif; ?>

    <?php if ($defs): ?>
        <ul>
            <?php foreach ($defs as $def): ?>
            <li
                <?php
                $pieces = array_map(function ($piece) { return strlen($piece) > 3
                    ? "<a href='word/$piece'>{$piece}</a>"
                    : $piece;
                }, explode(' ', $def));

                echo implode(' ', $pieces);
                ?>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
    <form id="search">
        <label>
            Search
            <input id="q" type="text" />
        </label>
        <input type="submit" />
    </form>
    </body>
    <script>
        document.getElementById('search').onsubmit = function (e) {
            window.location.href = 'word.php?word=' + document.getElementById('q').value;

            return false;
        };
    </script>