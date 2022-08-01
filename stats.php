<?php

require('setup.php');

mysql_select_db($dbname);

$start = $_POST['start'];
$end = $_POST['end'];

if ($start == '') $start = modify_date($now, '-1 month');
if ($end == '') $end = $now;

echo '<html><head>';
echo '<link rel="stylesheet" type="text/css" href="calories.css"/>';

if ($user != '')
  echo "<title>$user's Calories</title>";

echo '</head><body>';


echo "<table><tr>";
echo "<form method='post'>";
echo "<th>Start Date</th>";
echo "<td><input name='start' value='$start'/></td>";
echo "<th>End Date</th>";
echo "<td><input name='end' value='$end'/></td>";
echo "<td><input type='submit' value='Go'/></td>";
echo "</form>";
echo "</tr></table>";

$wavg = 0;
$wmin = 0;
$wmax = 0;
$wexpavg = 0;

$count = 0;

if ($start != $end) {
  $query = "SELECT daily.*, SUM(calories.cals) AS 'calories' FROM daily " .
    "LEFT JOIN calories ON calories.user = daily.user AND " .
    "TO_DAYS(daily.date) = TO_DAYS(calories.ts) WHERE " .
    "daily.user = '$user' AND date >= '$start' AND date <= '$end' " .
    "GROUP BY daily.date ORDER BY date";
  
  $result = mysql_query($query) or die(mysql_error());

  echo "<table id='stats'>";
  echo "<tr><th>Date</th><th>Calorie Intake</th><th>Calorie Goal</th>" .
    "<th>Calorie Diff</th>" .
    "<th>Weight</th><th>Exp Avg Weight</th></tr>";

  while ($row = mysql_fetch_array($result)) {
    $cals = $row['calories'];
    $weight = $row['weight'];
    $date = $row['date'];
    $cal_goal = $row['cal_goal'];
    $cal_diff = $cal_goal - $cals;

    if ($cal_diff < 0) $color = 'class="red"';
    else $color = '';

    if ($weight == 0) continue;

    $wavg += $weight;
    if ($wmax < $weight) $wmax = $weight;
    if ($wmin == 0 || $weight < $wmin) $wmin = $weight;
    if ($expavg == 0) $expavg = $weight;
    else $expavg = 0.70 * $expavg + 0.30 * $weight;

    echo "<tr><td>$date</td>" .
      "<td>$cals</td><td>$cal_goal</td><td $color>$cal_diff</td>" .
      "<td>$weight</td><td>" .
      number_format($expavg, 2) . "</td></tr>";

    $count++;
  }
  echo "</table>";
 }

if ($count) {
  $wavg /= $count;
  
  echo "<h2>Results</h2>";
  echo "<table>";
  echo "<tr><th>Maximum Weight</th>";
  echo "<td>" . number_format($wmax, 2) . "</td></tr>";
  echo "<tr><th>Minimum Weight</th>";
  echo "<td>" . number_format($wmin, 2) . "</td></tr>";
  echo "<tr><th>Average Weight</th>";
  echo "<td>" . number_format($wavg, 2) . "</td></tr>";
  echo "</table>";
 }

echo "</body></html>";

?>
