<?Php check your WhatsApp or emails for the love of god
require_once __DIR__ . '/autoload.php';

$matches = query('Select id, match_date From matches');
$totals = query('Select name, wins, max_score, min_score, avg_score, sevens, total_points from summary Order by name');
$crossTab = query('select first_player, winner, count(*) as count from matches m join winners w on m.id = w.match_id group by 1, 2');
$sets = query(<<<SQL
  select `set`,
         count(case when w.winner = 'Han' then 1 end) as Han,
         count(case when w.winner = 'Iain' then 1 end) as Iain
    from matches
    join winners w on matches.id = w.match_id
group by 1
SQL
);
$highestScores = query(<<<SQL
  select winner, winner_score, match_date
    from winners
    join matches m on winners.match_id = m.id
order by winner_score desc, match_id
   limit 10
SQL
);
$headlines = [
    'total' => [
        'line' => 'Matches: {count}',
        'query' => query('select count(*) as count from matches'),
    ],
    'turns' => [
        'line' => 'Turns: {count}',
        'query' => query('select count(*) as count from scores'),
    ],
    'avg_total' => [
        'line' => 'Average Total: {avg}',
        'query' => query('select avg(winner_score + loser_score) as avg from winners'),
    ],
    'avg_turn' => [
        'line' => 'Average Turn: {avg}',
        'query' => query('select avg(score) as avg from scores'),
    ],
    'busiest_day' => [
        'line' => 'Most games in a day: {count} ({date})',
        'query' => query('select date(match_date) as `date`, count(*) as count from matches m where match_date is not null group by 1 order by 2 desc limit 1'),
    ],
    'best_score' => [
        'line' => 'Best turn: {score} ({player_name})',
        'query' => query('select player_name, score from scores order by 2 desc limit 1'),
    ],
    'longest_streak' => [
        'line' => 'Longest winning streak: {streak} ({winner})',
        'query' => query(<<<SQL
   select w.match_id,
          w.winner,
          prev.match_id,
          @streak := (case when prev.winner = w.winner then @streak + 1 else 1 end) as streak
     from winners w
left join winners prev on w.match_id = prev.match_id + 1
 order by 4 desc
    limit 1
SQL
        )
    ],
    'unreplied_sevens' => [
        'line' => 'Most sevens without reply: {streak} ({player_name})',
        'setup' => query('set @row_number = 0;'),
        'query' => query(<<<SQL
with sevens as (
  select (@row_number:=@row_number + 1) AS id, s.score, s.player_name
    from scores s
   where s.bonus > 0
)
   select sevens.player_name,
          @streak := (case when prev.player_name = sevens.player_name then @streak + 1 else 1 end) as streak
     from sevens
left join sevens prev on sevens.id = prev.id + 1
 order by 2 desc
SQL
        )
    ],
];

$winners = [];

foreach ($crossTab as $row) {
    $winners[$row['first_player']][$row['winner']] = $row['count'];
}
?>

<!DOCTYPE html>
<html lang="">
<head>
    <title>Scrabblr</title>
    <link rel="stylesheet" href="./static/style.css" />
</head>
<body>
    <!-- <img src="./static/tile.jpg" style="position: absolute; right: 20%; top: 1em;" alt="What a lovely tile" /> -->
    <h1>Summary</h1>
    <?php
        foreach ($headlines as $headline):
            $results = iterator_to_array($headline['query']);
    ?>
        <p><?=
            preg_replace_callback('/{([^}]+?)}/', function ($match) use ($results) {
                return $results[0][$match[1]];
            }, $headline['line']);; ?></p>
    <?php endforeach; ?>
    <table>
        <tr>
            <th>Player</th>
            <th>Wins</th>
            <th>Highest</th>
            <th>Lowest</th>
            <th>Average</th>
            <th>Sevens</th>
            <th>Total Points</th>
        </tr>
        <?php foreach ($totals as $total): ?>
        <tr>
            <td><?= $total['name']; ?></td>
            <td><?= $total['wins']; ?></td>
            <td><?= $total['max_score']; ?></td>
            <td><?= $total['min_score']; ?></td>
            <td><?= sprintf('%.2f', $total['avg_score']); ?></td>
            <td><?= $total['sevens']; ?></td>
            <td><?= $total['total_points']; ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <table>
        <tr>
            <th>Set</th>
            <th>Han</th>
            <th>Iain</th>
        </tr>
        <?php foreach ($sets as $set): ?>
        <tr>
            <td><?= $set['set']; ?></td>
            <td><?= $set['Han']; ?></td>
            <td><?= $set['Iain']; ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <table>
        <tr>
            <th></th>
            <th>Han Wins</th>
            <th>Iain Wins</th>
        </tr>

        <tr>
            <th>Han First</th>
            <td><?= $winners['Han']['Han']; ?></td>
            <td><?= $winners['Han']['Iain']; ?></td>
        </tr>
        <tr>
            <th>Iain First</th>
            <td><?= $winners['Iain']['Han']; ?></td>
            <td><?= $winners['Iain']['Iain']; ?></td>
        </tr>
    </table>
    <table>
        <tr>
            <th>Player</th>
            <th>Score</th>
            <th>Date</th>
        </tr>
        <?php foreach ($highestScores as $highestScore): ?>
            <tr>
                <td><?= $highestScore['winner']; ?></td>
                <td><?= $highestScore['winner_score']; ?></td>
                <td><?= (new DateTime($highestScore['match_date']))->format('jS F Y, H:i'); ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
    <!--
    <h1>View Match</h1>
    <form action="./match" method="get">
        <label>
            <select name="id">
                <?php foreach ($matches as $match): ?>
                <option value="<?= $match['id'] ?>">Match <?= $match['id'] ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <input type="submit" value="Go" />
    </form>
    -->

    <h1>Actions</h1>
    <ul>
        <li>
            <a href="./add">Add new match</a>
        <li>
            Stats
            <ul>
                <li><a href="./history/?option=scores">All scores</a>
                <li><a href="./history/?option=average">Average score</a>
                <li><a href="./history/?option=rolling">Ten game rolling average</a>
                <li><a href="./history/?option=set_averages">Set averages</a>
                <li><a href="./history/?option=best_turns">Best turns</a>
                <li><a href="./history/?option=turns">Average score per turns</a>
                <li>
                    <a href="./history/?option=optimum">Optimum game</a>
                    /
                    <a href="./history/?option=pessimum">Pessimum game</a>

                <li><a href="./history/?option=total">Overall total scores</a>
                <li><a href="./history/?option=turn_sevens">Sevens per turn</a>
    </ul>
