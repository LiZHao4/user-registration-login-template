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
			$postedToken = $_POST["token"];
			if ($postedToken) {
				$query = "SELECT id, user, nick, user_avatar, token, gender, birth, bio FROM $table WHERE token = ?";
				$statement = $databaseConnection->prepare($query);
				$statement->bind_param("s", $postedToken);
				$statement->execute();
				$result = $statement->get_result();
				if ($result->num_rows > 0) {
					$userData = $result->fetch_assoc();
					echo $userData["user"] . "\n" . $userData["nick"]. "\n" . $userData["id"] . "\n" . $userData["user_avatar"] . "\n" . $userData["token"] . "\n" . $userData["gender"] . "\n" . $userData["birth"] . "\n" . $userData["bio"];
				} else {
					echo "fail";
				}
				$statement->close();
			}
			$databaseConnection->close();
		} catch (mysqli_sql_exception $sqlException) {
			echo "fail";
		}
	}
?>