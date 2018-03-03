<?php

// database might not exist, so let's try creating it (just to be safe)
$stderr = fopen('php://stderr', 'w');
// https://codex.wordpress.org/Editing_wp-config.php#MySQL_Alternate_Port
//   "hostname:port"
// https://codex.wordpress.org/Editing_wp-config.php#MySQL_Sockets_or_Pipes
//   "hostname:unix-socket-path"
list($host, $socket) = explode(':', getenv('POSTFIX_DB_HOST'), 2);
$port = 0;
if (is_numeric($socket)) {
	$port = (int) $socket;
	$socket = null;
}
$user = getenv('POSTFIX_DB_USER');
$pass = getenv('POSTFIX_DB_PASSWORD');
$dbName = getenv('POSTFIX_DB_NAME');
$mysqlUser = getenv('MYSQL_USER');
$mysqlPass = getenv('MYSQL_PASSWORD');
$maxTries = 10;

function exit_for_mysql_error($error_msg, $mysql) {
	global $stderr;
	fwrite($stderr, "\n" . $error_msg . "\n");
	$mysql->close();
	exit(1);
}

do {
	$mysql = new mysqli($host, $mysqlUser, $mysqlPass, '', $port, $socket);
	if ($mysql->connect_error) {
		fwrite($stderr, "\n" . 'MySQL Connection Error: (' . $mysql->connect_errno . ') ' . $mysql->connect_error . "\n");
		--$maxTries;
		if ($maxTries <= 0) {
			exit(1);
		}
		sleep(3);
	}
} while ($mysql->connect_error);
if (!$mysql->query('CREATE DATABASE IF NOT EXISTS `' . $mysql->real_escape_string($dbName) . '`')) {
	exit_for_mysql_error('MySQL "CREATE DATABASE" Error: ' . $mysql->error, $mysql);
}
if ($result = $mysql->query('SELECT EXISTS(SELECT 1 FROM mysql.user WHERE user = \'' . $mysql->real_escape_string($user) . '\')')) {
	$exists = 0;
	if ($row = $result->fetch_array(MYSQLI_NUM)) {
		$exists = $row[0];
	}
	$result->close();
	if (!$exists) {
		if (!$mysql->query('CREATE USER `' . $user . '`@`%` IDENTIFIED BY \'' . $pass . '\'')) {
			exit_for_mysql_error('MySQL "CREATE USER" Error: ' . $mysql->error, $mysql);
		}
		if (!$mysql->query('GRANT ALL PRIVILEGES ON ' . $dbName . '.* TO `' . $user  . '`@`%`')) {
			exit_for_mysql_error('MySQL "GRANT ALL PRIVILEGES" Error: ' . $mysql->error, $mysql);
		}
		if (!$mysql->query('FLUSH PRIVILEGES')) {
			exit_for_mysql_error('MySQL "FLUSH PRIVILEGES" Error: ' . $mysql->error, $mysql);
		}
	}
} else {
	exit_for_mysql_error('MySQL "SELECT EXISTS FROM mysql.user" Error: ' . $mysql->error, $mysql);
}
$mysql->close();

?>
