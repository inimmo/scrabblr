<?php
require_once __DIR__ . '/autoload.php';

$queries = [];

$queries['scores'] = <<<SQL
select player_name, match_id, score
  from results
union
select 'average', match_id, avg(score)
  from results
group by match_id
SQL;

$queries['average'] = <<<SQL
select * from (
  select r.player_name, r.match_id, avg(r_old.score) as score
    from results r
    join results r_old
      on r_old.match_id <= r.match_id
     and r_old.player_name = r.player_name
group by 1, 2
union
  select 'average', r.match_id, avg(r_old.score) as score
    from results r
    join results r_old
      on r_old.match_id <= r.match_id
group by 1, 2
) tbl order by 1, 2
SQL;

$queries['rolling'] = <<<SQL
select * from (
  select r.player_name, r.match_id, avg(r_old.score) as score
    from results r
    join results r_old
      on r_old.match_id <= r.match_id
     and r_old.match_id >= r.match_id - 10
     and r_old.player_name = r.player_name
group by 1, 2
union
  select 'average', r.match_id, avg(r_old.score) as score
    from results r
    join results r_old
      on r_old.match_id <= r.match_id
     and r_old.match_id >= r.match_id - 10
group by r.match_id
) tbl order by 1, 2
SQL;

$queries['turns'] = <<<SQL
  select player_name, turn as match_id, avg(s.score) as score
    from scores s
group by 1, 2
order by 1, 2
SQL;

$queries['best_turns'] = <<<SQL
  select player_name, turn as match_id, max(score) as score
    from scores
group by 1, 2
SQL;

$queries['optimum'] = <<<SQL
with opt as (
    select player_name, turn, max(score) as score
      from scores
  group by 1, 2
)
  select o.player_name, o.turn as match_id, sum(opt_a.score) as score
    from opt o
    join opt opt_a
      on o.turn >= opt_a.turn
     and o.player_name = opt_a.player_name
group by 1, 2
order by 1, 2
SQL;

$queries['total'] = <<<SQL
  select 'average' as player_name, match_id, winner_score + loser_score as score
    from winners
order by 1
SQL;

$queries['hourly'] = <<<SQL
  select player_name,
         case
           when hour(match_date) = 0 then 24
           else hour(match_date)
         end as match_id,
         avg(r.score) as score
    from matches m
    join results r on m.id = r.match_id
   where match_date is not null
group by 1, 2
order by 2
SQL;

$queries['sevens'] = <<<SQL
   select 'average' as player_name,
          datediff(date(m.match_date), (select date(min(match_date)) from matches)) as match_id,
          count(s.id) as score
     from matches m
left join scores s on m.id = s.match_id and s.bonus > 0
 group by 1, 2
 order by 2
SQL;

$queries['turn_sevens'] = <<<SQL
  select player_name, turn as match_id, count(s.id) as score
    from scores s
   where bonus > 0
group by 1, 2
order by 1, 2
SQL;

$queries['margin'] = <<<SQL
   select 'average' as player_name,
          match_id,
          case
            when w.winner = 'Han' then winner_score - loser_score
            else loser_score - winner_score
          end as score
     from winners w
order by 2
SQL;

$results = iterator_to_array(query($queries[$_GET['option'] ?? 'scores']));

if ($min = $_GET['min']) {
    $results = array_filter($results, function ($row) use ($min) {
        return $row['match_id'] >= $min;
    });
}

if ($max = $_GET['max']) {
    $results = array_filter($results, function ($row) use ($max) {
        return $row['match_id'] <= $max;
    });
}

$playerNames = array_unique(array_column($results, 'player_name'));

$chartColors = [
    'han' => 'rgb(255, 99, 132)',
    'iain' => 'rgb(54, 162, 235)',
    'average' => 'rgb(54, 235, 162)',
];

$dataSets = [];

foreach ($playerNames as $playerName) {
    $dataSets[] = [
        'label' => ucfirst($playerName),
        'backgroundColor' => $chartColors[strtolower($playerName)],
        'borderColor' => $chartColors[strtolower($playerName)],
        'data' => array_column(array_filter($results, function ($e) use ($playerName) { return $e['player_name'] === $playerName;}), 'score'),
        'fill' => false,
    ];
}
?>

<!DOCTYPE html>
<html lang="">
<head>
    <title>Scrabblr - Status</title>
    <script src="/scrabblr/static/Chart.min.js"></script>

    <script>
        var players = <?= json_encode($playerNames); ?>;

        var config = {
            type: 'line',

            data: {
                labels: <?= json_encode(range(
                    min(array_column($results, 'match_id')),
                    max(array_column($results, 'match_id'))
                )); ?>,
                datasets: <?= json_encode($dataSets); ?>
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

        window.onload = function() {
            window.myLine = new Chart(
                document.getElementById('canvas').getContext('2d'),
                config
            );
        };
    </script>
</head>
<body>
    <canvas id="canvas"></canvas>
