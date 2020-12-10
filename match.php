<?php
require_once __DIR__ . '/autoload.php';

$scores = query('Select match_id, player_name, turn, score From scores Where match_id = ?', [$_GET['id']]);

$playerScores = $cumulative = [];

foreach ($scores as $score) {
    if (!isset($playerScores[$score['player_name']])) {
        $playerScores[$score['player_name']] = [];
        $cumulative[$score['player_name']] = 0;
    }

    $playerScores[$score['player_name']][] = $score['score'] + $cumulative[$score['player_name']];
    $cumulative[$score['player_name']] += $score['score'];
}
?>

<!DOCTYPE html>
<html lang="">
<head>
    <title>Scrabblr - View Game</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.min.js"></script>

    <script>
        var chartColors = {
            han: 'rgb(255, 99, 132)',
            iain: 'rgb(54, 162, 235)'
        };

        var players = <?= json_encode(array_keys($playerScores)); ?>;

        var config = {
            type: 'line',

            data: {
                labels: <?= json_encode(range(1, max(array_keys($playerScores[array_keys($playerScores)[0]])) + 1)); ?>,

                datasets: [{
                    label: 'Han',
                    backgroundColor: window.chartColors.han,
                    borderColor: window.chartColors.han,
                    data: <?= json_encode($playerScores['Han']); ?>,
                    fill: false
                }, {
                    label: 'Iain',
                    backgroundColor: window.chartColors.iain,
                    borderColor: window.chartColors.iain,
                    data: <?= json_encode($playerScores['Iain']); ?>,
                    fill: false
                }]
            },
            options: {
                responsive: true,
                hover: {
                    mode: 'nearest',
                    intersect: true
                },
                scales: {
                    xAxes: [{
                        display: true,
                        scaleLabel: {
                            display: true,
                            labelString: 'Turn'
                        }
                    }]
                }
            }
        };

        window.onload = function() {;
            window.myLine = new Chart(
                document.getElementById('canvas').getContext('2d'),
                config
            );
        };
    </script>
</head>
<body>
    <canvas id="canvas"></canvas>
