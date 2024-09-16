<?php
	if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['token'])) {
		mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
		try {
			$databaseConnection = new mysqli("localhost", "yuancheng", "yc@123456", "yc");
			$postedToken = $_POST["token"];
			if ($postedToken) {
				$query = "SELECT id, user, nick, user_avatar, token FROM users WHERE token = ?";
				$statement = $databaseConnection->prepare($query);
				$statement->bind_param("s", $postedToken);
				$statement->execute();
				$result = $statement->get_result();
				if ($result) {
					$userData = $result->fetch_assoc();
					echo $userData["user"] . "\n" . $userData["nick"]. "\n" . $userData["id"] . "\n" . $userData["user_avatar"] . "\n" . $userData["token"];
				}
				$statement->close();
			}
			$databaseConnection->close();
		} catch (mysqli_sql_exception $sqlException) {
			echo "fail";
		}
	}
?>