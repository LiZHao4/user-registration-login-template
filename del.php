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
			$dbConnection = new mysqli($host, $user, $pass, $db);
			$token = $_COOKIE["_"];
			$stmt = $dbConnection->prepare("SELECT id FROM users WHERE token = ?");
			$stmt->bind_param("s", $token);
			$stmt->execute();
			$res = $stmt->get_result();
			if ($res->num_rows > 0) {
				$row = $res->fetch_assoc();
				$userId = $row['id'];
				if (hash_equals($row['token'], $token)) {
					$avatarStmt = $dbConnection->prepare("SELECT user_avatar FROM users WHERE id = ?");
					$avatarStmt->bind_param("i", $userId);
					$avatarStmt->execute();
					$res = $avatarStmt->get_result();
					if ($res->num_rows > 0) {
						$avatarRow = $res->fetch_assoc();
						$avatar = $avatarRow['user_avatar'];
						if (!is_null($avatar)){
							$avatarPath = "avatar/" . $avatar . ".jpg";
							if (file_exists($avatarPath)) {
								unlink($avatarPath);
							}
						}
					}
					$avatarStmt->close();
					$deleteStmt = $dbConnection->prepare("DELETE FROM users WHERE id = ?");
					$deleteStmt->bind_param("i", $userId);
					$deleteStmt->execute();
					if ($deleteStmt->affected_rows > 0) {
						echo json_encode(["code" => 1, "msg" => $language["accountDeleted"]]);
					} else {
						echo json_encode(["code" => -1, "msg" => $language["accountDeletionFailed"]]);
					}
					$deleteStmt->close();
				} else {
					echo json_encode(["code" => 0, "msg" => $language["invalidUserOrToken"]]);
				}
			}
			$stmt->close();
		} catch (mysqli_sql_exception $sqlException) {
			echo json_encode(["code" => -1, "msg" => $language["databaseError"]]);
		} finally {
			$dbConnection->close();
		}
	} else {
		echo json_encode(["code" => -1, "msg" => $language["invalidRequest"]]);
	}
?>