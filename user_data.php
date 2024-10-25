<?php
	if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['token'])) {
		mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
		$config = parse_ini_file('conf/settings.ini', true);
		$host = $config['database']['host'];
		$user = $config['database']['user'];
		$pass = $config['database']['pass'];
		$db = $config['database']['db'];
		$table = $config['database']['table'];
		try {
			$databaseConnection = new mysqli($host, $user, $pass, $db);
			$receivedToken = $_POST["token"];
			if ($receivedToken) {
				$query = "SELECT id, user, created_at, token, nick, user_avatar FROM $table WHERE token = ?";
				$preparedStatement = $databaseConnection->prepare($query);
				$preparedStatement->bind_param("s", $receivedToken);
				$preparedStatement->execute();
				$result = $preparedStatement->get_result();
				if ($result && $userData = $result->fetch_assoc()) {
					echo $userData["id"] . "\n" . $userData["user"] . "\n" . $userData["created_at"] . "\n" . $userData["token"] . "\n" . $userData["nick"] . "\n" . $userData["user_avatar"];
				} else {
					echo "relogin";
				}
				$preparedStatement->close();
			}
			$databaseConnection->close();
		} catch (mysqli_sql_exception $sqlException) {
			echo "fail";
		}
	}
?>