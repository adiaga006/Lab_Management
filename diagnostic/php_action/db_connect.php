<?php
define('DB_SERVER','localhost');
define('DB_USER','root');
define('DB_PASS' ,'');
define('DB_NAME','data_shrimpvet');
define('DB_PORT', 2024);
$connect = mysqli_connect(DB_SERVER,DB_USER,DB_PASS,DB_NAME, port: DB_PORT);
// Check connection
if (mysqli_connect_errno())
{
 echo "Failed to connect to MySQL: " . mysqli_connect_error();
}
?>