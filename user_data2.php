<?php
	if (isset($_GET["lang"]) && file_exists("languages/" . $_GET["lang"] . ".json")) {
		$language = json_decode(file_get_contents("languages/" . $_GET["lang"] . ".json"), true);
	} else {
		$language = json_decode(file_get_contents("languages/zh-CN.json"), true);
	}
	header('Content-Type: application/json');
	if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_COOKIE['_'])) {
		mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
		$config = parse_ini_file('conf/settings.ini', true);
		$host = $config['database']['host'];
		$user = $config['database']['user'];
		$pass = $config['database']['pass'];
		$db = $config['database']['db'];
		try {
			$databaseConnection = new mysqli($host, $user, $pass, $db);
			$postedToken = $_COOKIE["_"];
			$query = "SELECT id, user, nick, created_at, user_avatar, token, gender, birth, bio FROM users WHERE token = ?";
			$statement = $databaseConnection->prepare($query);
			$statement->bind_param("s", $postedToken);
			$statement->execute();
			$result = $statement->get_result();
			if ($result->num_rows > 0) {
				$userData = $result->fetch_assoc();
				$data = [
					"user" => $userData["user"],
					"nick" => $userData["nick"],
					"id" => $userData["id"],
					"time" => $userData["created_at"],
					"avatar" => $userData["user_avatar"],
					"token" => $userData["token"],
					"gender" => $userData["gender"],
					"birth" => $userData["birth"],
					"bio" => $userData["bio"]
				];
				echo json_encode(["code" => 1, "msg" => $language["userInfoFetched"], "data" => $data]);
			} else {
				echo json_encode(["code" => 0, "msg" => $language["invalidUserOrToken"]]);
			}
			$statement->close();
			$databaseConnection->close();
		} catch (mysqli_sql_exception $sqlException) {
			echo json_encode(["code" => -1, "msg" => $language["databaseError"]]);
		}
	} else {
		echo json_encode(["code" => -1, "msg" => $language["invalidRequest"]]);
	}
?>