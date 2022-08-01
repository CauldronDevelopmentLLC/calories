<?php

include_once('fix_mysql.inc.php');
require('config.php');

putenv("TZ=$timezone");

define("MALE", 0);
define("FEMALE", 1);

# Get variables
$date = $_GET['date'];
$user = $_GET['user'];

$url = "?user=$user";
$now = date("Y-m-d");

if ($date == '')
  $date = $now;
 else if ($date != $now) $url .= "&date=$date";

# Connect to database
$con = mysql_connect($dbhost, $dbuser, $dbpass) or
  die('Error connecting to database');

if (!mysql_query("SET time_zone = '$timezone'"))
  die('Error setting timezone: ' . mysql_error());

function modify_date($date, $mod) {
  $dt = new DateTime($date);
  $dt->modify($mod);
  return $dt->format("Y-m-d");
}
?>
