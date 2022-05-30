<?php
$db = 'profiler';
$host = 'localhost';
$uname = 'root';
$pword = '';

define('DB', 'profiler');
define('HOST', 'localhost');
define('USERNAME', 'root');
define('PASSWORD', '');

// new PDO("mysql:host=hostname;dbname=database", username, password, options:optional);

function connect()
{
    $host = HOST;
    $db = DB;
    $uname = USERNAME;
    $pword = PASSWORD;
    $dsn = "mysql:host=$host;dbname=$db";
    $conn = new PDO($dsn, $uname, $pword);
    return $conn;
}
