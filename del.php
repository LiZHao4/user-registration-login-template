<?php
	if (isset($_GET["lang"]) && file_exists("languages/" . $_GET["lang"] . ".json")) {
		$language = json_decode(file_get_contents("languages/" . $_GET["lang"] . ".json"), true);
	} else {
		$language = json_decode(file_get_contents("languages/zh-CN.json"), true);
	}
	if (isset($_POST["v"]) && $_POST["v"] == "2") header('Content-Type: application/json');
	else header('Content-Type: text/plain');
	if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['id']) && isset($_POST['token'])) {
		mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
		$config = parse_ini_file('conf/settings.ini', true);
		$host = $config['database']['host'];
		$user = $config['database']['user'];
		$pass = $config['database']['pass'];
		$db = $config['database']['db'];
		try {
			$dbConnection = new mysqli($host, $user, $pass, $db);
			$userId = (int)$_POST["id"];
			$token = $_POST["token"];
			$stmt = $dbConnection->prepare("SELECT id, token FROM users WHERE id = ?");
			$stmt->bind_param("i", $userId);
			$stmt->execute();
			$res = $stmt->get_result();
			if ($res->num_rows > 0) {
				$row = $res->fetch_assoc();
				if (hash_equals($row['token'], $token)) {
					$avatarStmt = $dbConnection->prepare("SELECT user_avatar FROM users WHERE id = ?");
					$avatarStmt->bind_param("i", $userId);
					$avatarStmt->execute();
					$res = $avatarStmt->get_result();
					if ($res->num_rows > 0) {
						$avatarRow = $res->fetch_assoc();
						$avatar = $avatarRow['user_avatar'];
						if ($avatar == 1) {
							$avatarPath = "avatar/" . $token . ".jpg";
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
						if (isset($_POST["v"]) && $_POST["v"] == "2") echo json_encode(["code" => 1, "msg" => $language["accountDeleted"]]);
						else echo "success";
					} else {
						if (isset($_POST["v"]) && $_POST["v"] == "2") echo json_encode(["code" => -1, "msg" => $language["accountDeletionFailed"]]);
						else echo "fail";
					}
					$deleteStmt->close();
				} else {
					if (isset($_POST["v"]) && $_POST["v"] == "2") echo json_encode(["code" => 0, "msg" => $language["invalidUserOrToken"]]);
					else echo "fail";
				}
			}
			$stmt->close();
		} catch (mysqli_sql_exception $sqlException) {
			if (isset($_POST["v"]) && $_POST["v"] == "2") echo json_encode(["code" => -1, "msg" => $language["databaseError"]]);
			else echo "fail";
		} finally {
			$dbConnection->close();
		}
	} else {
		if (isset($_POST["v"]) && $_POST["v"] == "2") echo json_encode(["code" => -1, "msg" => $language["invalidRequest"]]);
		else echo "fail";
	}
?>