<?php

require('setup.php');

function formatDate($date, $format = 'l, F jS, Y') {
  $dt = new DateTime($date);
  return $dt->format($format);
}

function create_database() {
  global $dbname;

  if (!mysql_query('CREATE DATABASE ' . $dbname))
    die('Error creating database: ' . mysql_error());

  if (!mysql_select_db($dbname))
    die('Error selecting database: ' . mysql_error());


  $query =
    'CREATE TABLE calories (' .
    'id INT NOT NULL AUTO_INCREMENT, '.
    'ts TIMESTAMP, ' .
    'cals INT, ' .
    'qnty INT, ' .
    'item VARCHAR(255), ' .
    'user VARCHAR(255) NOT NULL, ' .
    'PRIMARY KEY(id))';

  if (!mysql_query($query))
    die('Error creating calories table: ' . mysql_error());

  $query =
    'CREATE TABLE activity (' .
    'id INT NOT NULL AUTO_INCREMENT, '.
    'ts TIMESTAMP, ' .
    'cals INT, ' .
    'qnty INT, ' .
    'item VARCHAR(255), ' .
    'user VARCHAR(255) NOT NULL, ' .
    'PRIMARY KEY(id))';

  if (!mysql_query($query))
    die('Error creating activity table: ' . mysql_error());

  $query =
    'CREATE TABLE users (' .
    'id INT NOT NULL AUTO_INCREMENT, '.
    'name VARCHAR(255) NOT NULL UNIQUE, ' .
    'height INT, ' .
    'sex INT, ' .
    'age INT, ' .
    'PRIMARY KEY(id))';

  if (!mysql_query($query))
    die('Error creating users table: ' . mysql_error());

  $query =
    'CREATE TABLE daily (' .
    'id INT NOT NULL AUTO_INCREMENT, '.
    'date DATE, '.
    'weight FLOAT, ' .
    'cal_goal INT, ' .
    'user VARCHAR(255) NOT NULL, ' .
    'PRIMARY KEY(id))';

  if (!mysql_query($query))
    die('Error creating daily table: ' . mysql_error());
}

function admin_interface() {
  echo "<table class='users'>";
  echo "<tr>";
  echo "<th class='name'>Name</th>";
  echo "<th class='feet'>Feet</th>";
  echo "<th class='inches'>Inches</th>";
  echo "<th class='age'>Age</th>";
  echo "<th class='sex'>Sex</th>";
  echo "<th class='action'>Action</th>";
  echo "</tr>";

  $query = "SELECT * FROM users";
  $result = mysql_query($query) or die(mysql_error());

  while ($row = mysql_fetch_array($result)) {
    $inches = $row['height'] % 12;
    $feet = floor($row['height'] / 12);
    if ($row['sex'] == MALE) $sex = 'Male';
    else $sex = 'Female';

    echo "<tr>";
    echo "<td class='name'><a href='?user=" . $row['name'] . "'>" .
      $row['name'] . "</a></td>";
    echo "<td class='feet'>" . $feet . "</td>";
    echo "<td class='inches'>" . $inches . "</td>";
    echo "<td class='age'>" . $row['age'] . "</td>";
    echo "<td class='sex'>" . $sex . "</td>";

    echo "<form method='post'><td class='action'>";
    echo "<input type='hidden' name='cmd' value='del-user'/>";
    echo "<input type='hidden' name='id' value='" . $row['id'] . "'/>";
    echo "<input type='submit' value='Delete'/>";
    echo "</td></form>";

    echo "</tr>";
  }

  echo "<form method='post'>";
  echo "<tr>";
  echo "<input type='hidden' name='cmd' value='add-user'/>";
  echo "<td><input class='name' name='name'/></td>";
  echo "<td><input class='feet' name='feet'/></td>";
  echo "<td><input class='inches' name='inches'/></td>";
  echo "<td><input class='age' name='age'/></td>";
  echo "<td><select name='sex'>";
  echo "  <option value='0'>Male</option>";
  echo "  <option value='1'>Female</option>";
  echo "</select></td>";
  echo "<td><input class='add-submit' type='submit' value='Add'/></td>";
  echo "</form>";
  echo "</tr>";
  echo "</table>";
}

function get_daily($user, $date) {
  $query = "SELECT * FROM daily WHERE user = '$user' AND date = '$date'";
  $result = mysql_query($query) or die(mysql_error());

  if (mysql_num_rows($result) == 0) return NULL;

  return mysql_fetch_array($result);
}

function set_daily($field, $value, $user, $date) {
  $query = "SELECT * FROM daily WHERE user = '$user' AND date = '$date'";
  $result = mysql_query($query) or die(mysql_error());

  if (mysql_num_rows($result) == 0)
    $query = "INSERT INTO daily (date, user, $field) VALUES (" .
      "'$date', '$user', '$value')";
  else
    $query = "UPDATE daily SET $field = '$value' " .
      "WHERE user = '$user' AND date = '$date'";

  if (!mysql_query($query)) die(mysql_error());
}

function cal_interface() {
  global $user, $date, $url;

  $query = "SELECT * FROM users WHERE name = '$user'";
  $result = mysql_query($query) or die(mysql_error());

  if (mysql_num_rows($result) == 0) {
    echo "<h3>User '$user' does not exist'</h3>";
    admin_interface();
    exit;
  }

  $row = mysql_fetch_array($result);
  $height = $row['height'];
  $feet = floor($height / 12);
  $inches = $height % 12;
  $sexType = $row['sex'];
  if ($sexType == MALE) $sex = 'Male';
  else $sex = 'Female';
  $age = $row['age'];

  echo "<a href='?'>Admin</a> <a href='$url'>Reload</a> ";
  echo "<a href='?user=$user'>Today</a> ";
  echo "<a href='stats.php?user=$user'>Stats</a>";

  $prev = modify_date($date, '-1 day');
  $next = modify_date($date, '+1 day');
  $prevWeek = modify_date($date, '-1 week');
  $nextWeek = modify_date($date, '+1 week');

  echo "<table class='no-border'><tr>";
  echo "<th><a href='?user=$user&date=$prevWeek'>Previous Week</a></th>";
  echo "<th><a href='?user=$user&date=$prev'>Previous Day</a></th>";
  echo "<th class='today'>" . formatDate($date) . "</th>";
  echo "<th><a href='?user=$user&date=$next'>Next Day</a></th>";
  echo "<th><a href='?user=$user&date=$nextWeek'>Next Week</a></th>";
  echo "</tr></table>";

  echo "<div id='calories'>";
  echo "<h3>Caloric Intake</h3>";
  echo "<table class='calories'>";
  echo "<tr>";
  echo "<th class='cals'>Calories</th>";
  echo "<th class='item'>Item</th>";
  echo "<th class='time'>Time</th>";
  echo "<th class='action'>Action</th>";
  echo "</tr>";

  $gday = floor(strtotime($date) / 60 / 60 / 24 + 719529);

  $query = "SELECT * FROM calories WHERE $gday = TO_DAYS(ts) " .
    "AND user = '$user' ORDER BY ts, id";
  $result = mysql_query($query) or die(mysql_error());

  $calsum = 0;

  while ($row = mysql_fetch_array($result)) {
    echo "<tr>";
    echo "<td class='cals'>" . $row['cals'] . "</td>";
    echo "<td class='item'>" . $row['item'] . "</td>";
    echo "<td class='time'>" . date_create($row['ts'])->format('H:i:s') .
      "</td>";

    echo "<form method='post' action='$url'>";
    echo "<td class='action'>";
    echo "<input type='hidden' name='cmd' value='del'/>";
    echo "<input type='hidden' name='id' value='" . $row['id'] . "'/>";
    echo "<input type='submit' value='Delete'/>";
    echo "</td></form>";

    echo "</tr>";


    $calsum += $row['cals'];
  }

  echo "<form method='post' action='$url'>";
  echo "<tr>";
  echo "<input type='hidden' name='cmd' value='add'/>";
  echo "<td><input class='add-cals' name='cals'/></td>";
  echo "<td><input id='firstField' class='add-item' name='item'/></td>";
  echo "<td></td>";
  echo "<td><input class='add-submit' type='submit' value='Add'/></td>";
  echo "</form>";
  echo "</tr>";
  echo "</table>";
  echo "</div>";

  echo "<div id='activity'>";
  echo "<h3>Activity</h3>";
  echo "<table class='activity'>";
  echo "<tr>";
  echo "<th class='cals'>Calories</th>";
  echo "<th class='item'>Item</th>";
  echo "<th class='time'>Time</th>";
  echo "<th class='action'>Action</th>";
  echo "</tr>";

  $query = "SELECT * FROM activity WHERE $gday = TO_DAYS(ts) " .
    "AND user = '$user' ORDER BY ts";
  $result = mysql_query($query) or die(mysql_error());

  $actsum = 0;

  while ($row = mysql_fetch_array($result)) {
    echo "<tr>";
    echo "<td class='cals'>" . $row['cals'] . "</td>";
    echo "<td class='item'>" . $row['item'] . "</td>";
    echo "<td class='time'>" . date_create($row['ts'])->format('H:i:s') .
      "</td>";

    echo "<form method='post' action='$url'>";
    echo "<td class='action'>";
    echo "<input type='hidden' name='cmd' value='del-activity'/>";
    echo "<input type='hidden' name='id' value='" . $row['id'] . "'/>";
    echo "<input type='submit' value='Delete'/>";
    echo "</td></form>";

    echo "</tr>";


    $actsum += $row['cals'];
  }

  echo "<form method='post' action='$url'>";
  echo "<tr>";
  echo "<input type='hidden' name='cmd' value='add-activity'/>";
  echo "<td><input class='add-cals' name='cals'/></td>";
  echo "<td><input class='add-item' name='item'/></td>";
  echo "<td></td>";
  echo "<td><input class='add-submit' type='submit' value='Add'/></td>";
  echo "</form>";
  echo "</tr>";
  echo "</table>";
  echo "</div>";

  $result = get_daily($user, $date);
  $todays = get_daily($user, $date);

  // Get last weight
  $query = "SELECT * FROM daily " .
    "WHERE user = '$user' AND date < '$date' AND weight > 0 " .
    "ORDER BY date DESC LIMIT 1";

  $result = mysql_query($query) or die(mysql_error());

  if (mysql_num_rows($result)) {
    $row = mysql_fetch_array($result);
    $last_weight = $row['weight'];
  }

  // Get last target
  $query = "SELECT * FROM daily " .
    "WHERE user = '$user' AND date < '$date' AND cal_goal > 0 " .
    "ORDER BY date DESC LIMIT 1";

  $result = mysql_query($query) or die(mysql_error());

  if (mysql_num_rows($result)) {
    $row = mysql_fetch_array($result);
    $target = $row['cal_goal'];

    if ($todays == NULL)
      set_daily('cal_goal', $target, $user, $date);
  }

  if ($todays != NULL) {
    $target = $todays['cal_goal'];
    $weight = $todays['weight'];
  }

  if ($last_weight && $weight)
    $change = round($weight - $last_weight, 2);
  else $change = '?';

  $remain = $target - $calsum;

  if ($weight) $w = $weight;
  else $w = $last_weight;

  if ($sexType == MALE)
    $bmr = 66 + (2.2 * 6.23 * $w) + (12.7 * $height) - 6.8 * $age;
  else
    $bmr = 655 + (2.2 * 4.35 * $w) + (4.7 * $height) - 4.7 * $age;

  $smr = $bmr * 1.2;

  echo "<div class='clear'></div>";

  echo "<div id='profile'>";
  echo "<h3>Profile</h3>";
  echo "<table class='profile'>";
  echo "<tr><th>User</th><td>$user</td>";
  echo "<th>Sex</th><td>$sex</td></tr>";
  echo "<tr><th>Height</th><td>$feet'$inches&quot;</td>";
  echo "<th>Age</th><td>$age</td></tr>";

  echo "<tr><th>Weight</th>";
  echo "<form method='post' action='$url'>";
  echo "<td class='weight'>";
  echo "<input type='hidden' name='cmd' value='set-weight'/>";
  echo "<input name='weight' value='$weight'/>kg";
  echo "<input type='submit' value='Set'/></td>";
  echo "</form>";
  echo "</td>";
  echo "<th>BMR</th><td>$bmr</td></tr>";

  if ($change > 0) $class = 'red';
  else $class = '';
  echo "<tr><th>Weight Change</th><td class='$class'>$change kg</td>";
  echo "<th>SMR</th><td>$smr</td></tr>";
  echo "</table>";
  echo "</div>";


  echo "<div id='results'>";
  echo "<h3>Results</h3>";
  echo "<table class='results'>";
  echo "<tr><th>Total Calories</th><td>$calsum</td></tr>";
  echo "<tr><th>Target Calories</th>";
  echo "<form method='post' action='$url'>";
  echo "<td class='target'>";
  echo "<input type='hidden' name='cmd' value='set-target'/>";
  echo "<input name='target' value='$target'/>";
  echo "<input type='submit' value='Set'/></td>";
  echo "</form>";
  echo "</td></tr>";

  if ($remain < 0) $class = 'red';
  else $class = '';
  echo "<tr><th>Remaining Calories</th><td class='remain $class'>" .
    "$remain</td></tr>";

  echo "<tr><th>Calories Burned</th><td>$actsum</td></tr>";

  echo "</table>";
  echo "</div>";

  echo "<div class='clear'></div>";

  //echo "<iframe id='frame' src='http://www.calorieking.com/'>";
  //echo "</iframe>";
}


if (!mysql_select_db($dbname)) create_database();


echo '<html><head>';
echo '<link rel="stylesheet" type="text/css" href="calories.css"/>';

if ($user != '')
  echo "<title>$user's Calories - " . formatDate($date) . "</title>";

echo "<script type='text/javascript'>" .
"  function firstFocus() {document.getElementById('firstField').focus();}" .
"</script>";

echo '</head><body onload="javascript:firstFocus()">';

if ($date != $now) $ts = "TIMESTAMPADD(SECOND, 86399, '$date')";
else $ts = 'NOW()';

switch ($_POST['cmd'] ?? '') {
 case "add":
   $query = "INSERT INTO calories (cals,item,user,ts) VALUES(" .
     $_POST['cals'] . ", '" . $_POST['item'] . "', " .
     "'" . $user . "', $ts)";

   if (!mysql_query($query)) die(mysql_error());
   cal_interface();
   break;

 case "del":
   $query = "DELETE FROM calories WHERE id = " . $_POST['id'];
   if (!mysql_query($query)) die(mysql_error());
   cal_interface();
   break;

 case "add-activity":
   $query = "INSERT INTO activity (cals,item,user,ts) VALUES(" .
     $_POST['cals'] . ", '" . $_POST['item'] . "', " .
     "'" . $user . "', $ts)";

   if (!mysql_query($query)) die(mysql_error());
   cal_interface();
   break;

 case "del-activity":
   $query = "DELETE FROM activity WHERE id = " . $_POST['id'];
   if (!mysql_query($query)) die(mysql_error());
   cal_interface();
   break;

 case "set-target":
   set_daily('cal_goal', $_POST['target'], $user, $date);
   cal_interface();
   break;

 case "set-weight":
   set_daily('weight', $_POST['weight'], $user, $date);
   cal_interface();
   break;

 case "del-user":
   $query = "DELETE FROM users WHERE id = " . $_POST['id'];
   if (!mysql_query($query)) die(mysql_error());

   admin_interface();
   break;

 case "add-user":
   $height = $_POST['feet'] * 12 + $_POST['inches'];

   $query = "INSERT INTO users (name, height, age, sex) VALUES(" .
     "'" . $_POST['name'] . "', " .
     "$height, " . $_POST['age'] . "," .
     $_POST['sex'] . ")";

   if (!mysql_query($query)) die(mysql_error());

   admin_interface();
   break;

 case "admin":
   admin_interface();
   break;

 default:
   if ($user == '') admin_interface();
   else cal_interface();
 }

echo "</body></html>";
?>
