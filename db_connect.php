<?php

// Database Connection Strings here
$SQLpass = "xxxxx";
$SQLuser = "xxxxxxxxxx";
$SQLhost = "xxxxxxxxxxxx";
$SQLdb = "xxxxxxxxx";
mysql_connect ($SQLhost,$SQLuser,$SQLpass);
mysql_select_db($SQLdb);
echo mysql_error();

?>