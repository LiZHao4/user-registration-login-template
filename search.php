<?php
	if (isset($_GET["lang"]) && file_exists("languages/" . $_GET["lang"] . ".json")) {
		$language = json_decode(file_get_contents("languages/" . $_GET["lang"] . ".json"), true);
	} else {
		$language = json_decode(file_get_contents("languages/zh-CN.json"), true);
	}
	header('Content-Type: application/json');
	if ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET['q'])) {
		mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
		$config = parse_ini_file('conf/settings.ini', true);
		$host = $config['database']['host'];
		$user = $config['database']['user'];
		$pass = $config['database']['pass'];
		$db = $config['database']['db'];
		try {
			$mysqli = new mysqli($host, $user, $pass, $db);
			if (is_numeric($_GET['q'][0])) {
				$statement = "SELECT id, user, nick, user_avatar AS avatar FROM users WHERE id = ?";
				$intQ = (int)$_GET['q'];
				$stmt = $mysqli->prepare($statement);
				$stmt->bind_param("i", $intQ);
				$stmt->execute();
				$result = $stmt->get_result();
				if ($result->num_rows > 0) {
					$row = $result->fetch_assoc();
					echo json_encode(["code" => 1, "msg" => $language["userInfoFetched"], "data" => $row]);
				} else {
					echo json_encode(["code" => -1, "msg" => $language["userNotFound"]]);
				}
			} else {
				$statement = "SELECT id, user, nick, user_avatar AS avatar FROM users WHERE user = ?";
				$strQ = $_GET['q'];
				$stmt = $mysqli->prepare($statement);
				$stmt->bind_param("s", $strQ);
				$stmt->execute();
				$result = $stmt->get_result();
				if ($result->num_rows > 0) {
					$row = $result->fetch_assoc();
					echo json_encode(["code" => 1, "msg" => $language["userInfoFetched"], "data" => $row]);
				} else {
					echo json_encode(["code" => -1, "msg" => $language["userNotFound"]]);
				}
			}
		} catch (mysqli_sql_exception $sqlException) {
			echo json_encode(["code" => -1, "msg" => $language["databaseError"]]);
		}
	} else {
		echo json_encode(["code" => -1, "msg" => $language["invalidRequest"]]);
	}
?>