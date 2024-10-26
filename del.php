<?php
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
						if (isset($_POST["v"]) && $_POST["v"] == "2") echo json_encode(["code" => 1, "msg" => "账户注销成功"]);
						else echo "success";
					} else {
						if (isset($_POST["v"]) && $_POST["v"] == "2") echo json_encode(["code" => -1, "msg" => "账户注销失败"]);
						else echo "fail";
					}
					$deleteStmt->close();
				} else {
					if (isset($_POST["v"]) && $_POST["v"] == "2") echo json_encode(["code" => 0, "msg" => "token不匹配，请重新登录"]);
					else echo "fail";
				}
			}
			$stmt->close();
		} catch (mysqli_sql_exception $sqlException) {
			if (isset($_POST["v"]) && $_POST["v"] == "2") echo json_encode(["code" => -1, "msg" => "数据库错误：" . $sqlException->getMessage()]);
			else echo "fail";
		} finally {
			$dbConnection->close();
		}
	} else {
		if (isset($_POST["v"]) && $_POST["v"] == "2") echo json_encode(["code" => -1, "msg" => "请求方式错误，或者参数不完整"]);
		else echo "fail";
	}
?>