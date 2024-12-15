<?php
	if (isset($_GET["lang"]) && file_exists("languages/" . $_GET["lang"] . ".json")) {
		$language = json_decode(file_get_contents("languages/" . $_GET["lang"] . ".json"), true);
	} else {
		$language = json_decode(file_get_contents("languages/zh-CN.json"), true);
	}
	header('Content-Type: application/json');
	if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_COOKIE['_']) && isset($_POST['target'])) {
		mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
		$config = parse_ini_file('conf/settings.ini', true);
		$host = $config['database']['host'];
		$user = $config['database']['user'];
		$pass = $config['database']['pass'];
		$db = $config['database']['db'];
		$intTarget = (int)$_POST['target'];
		try {
			$databaseConnection = new mysqli($host, $user, $pass, $db);
			$stmt = $databaseConnection->prepare("SELECT id FROM users WHERE token = ?");
			$stmt->bind_param("s", $_COOKIE['_']);
			$stmt->execute();
			$stmt->store_result();
			$stmt->bind_result($id);
			if ($stmt->fetch()) {
				try {
					$queryStmt = $databaseConnection->prepare("SELECT COUNT(*) > 0 AS is_friend FROM friendships WHERE source = ? AND target = ? OR source = ? AND target = ?");
					$queryStmt->bind_param("iiii", $id, $intTarget, $intTarget, $id);
					$queryStmt->execute();
					$queryStmt->bind_result($is_friend);
					$queryStmt->fetch();
					echo json_encode(["code" => 1, "msg" => $language["success"], "added" => (bool)$is_friend]);
				} catch (Exception $e) {
					echo json_encode(["code" => -1, "msg" => $language["databaseError"] . " " . $e->getMessage()]);
				}
			} else {
				echo json_encode(["code" => 0, "msg" => $language["invalidUserOrToken"]]);
			}
			$stmt->close();
		} catch (mysqli_sql_exception $sqlException) {
			echo json_encode(["code" => -1, "msg" => $language["databaseError"] . " " . $sqlException->getMessage()]);
		}
	} else {
		echo json_encode(["code" => -1, "msg" => $language["invalidRequest"]]);
	}
?>