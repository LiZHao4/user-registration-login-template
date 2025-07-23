<?php
	header("Content-Type: application/json");
	if ($_SERVER["REQUEST_METHOD"] === "GET"){
		if (isset($_COOKIE["_"])) {
			mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
			$config = parse_ini_file("conf/settings.ini", true);
			$host = $config["database"]["host"];
			$user = $config["database"]["user"];
			$pass = $config["database"]["pass"];
			$db = $config["database"]["db"];
			try {
				$databaseConnection = new mysqli($host, $user, $pass, $db);
				$receivedToken = $_COOKIE["_"];
				$userData = null;
				$sessionStatement = $databaseConnection->prepare("SELECT user FROM user_session WHERE token = ? AND expires >= NOW()");
				$sessionStatement->bind_param("s", $receivedToken);
				$sessionStatement->execute();
				$sessionResult = $sessionStatement->get_result();
				if ($sessionResult && ($sessionData = $sessionResult->fetch_assoc())) {
					$userId = $sessionData["user"];
					$userStatement = $databaseConnection->prepare("SELECT id, nick, user, user_avatar, is_admin FROM users WHERE id = ?");
					$userStatement->bind_param("i", $userId);
					$userStatement->execute();
					$userResult = $userStatement->get_result();
					if ($userResult) {
						$userData = $userResult->fetch_assoc();
					}
					$userStatement->close();
				}
				$sessionStatement->close();
				if ($userData) {
					$unreadStmt = $databaseConnection->prepare("SELECT COUNT(*) AS unread_count FROM system_messages WHERE target = ? AND is_read = 0");
					$unreadStmt->bind_param("i", $userId);
					$unreadStmt->execute();
					$unreadResult = $unreadStmt->get_result();
					$unreadData = $unreadResult->fetch_assoc();
					$unreadCount = $unreadData["unread_count"];
					$unreadStmt->close();
					$sessionExpiresStmt = $databaseConnection->prepare("SELECT expires FROM user_session WHERE token = ?");
					$sessionExpiresStmt->bind_param("s", $receivedToken);
					$sessionExpiresStmt->execute();
					$sessionExpiresResult = $sessionExpiresStmt->get_result();
					if ($sessionExpiresResult && ($sessionExpiresData = $sessionExpiresResult->fetch_assoc())) {
						$utcTime = new DateTime($sessionExpiresData["expires"], new DateTimeZone('UTC'));
						$sessionExpiresTimestamp = $utcTime->getTimestamp();
					}
					$sessionExpiresStmt->close();
					$data = [
						"id" => $userData["id"],
						"nick" => $userData["nick"],
						"user" => $userData["user"],
						"avatar" => $userData["user_avatar"], 
						"isAdmin" => (bool)($userData["is_admin"] ?? false),
						"systemMessageUnreadCount" => $unreadCount,
						"token_expires" => $sessionExpiresTimestamp
					];
					echo json_encode(["code" => 1, "msg" => "用户信息获取成功。", "data" => $data]);
				} else {
					http_response_code(401);
					echo json_encode(["code" => 0, "msg" => "用户不存在或token无效，请重新登录。"]);
				}
				$deleteStmt = $databaseConnection->prepare("DELETE FROM user_session WHERE expires < NOW()");
				$deleteStmt->execute();
				$deleteStmt->close();
				$databaseConnection->close();
			} catch (mysqli_sql_exception $sqlException) {
				http_response_code(500);
				echo json_encode(["code" => -1, "msg" => "数据库错误。"]);
			}
		} else {
			http_response_code(400);
			echo json_encode(["code" => -1, "msg" => "缺少必要的参数。"]);
		}
	} else {
		http_response_code(405);
		echo json_encode(["code" => -1, "msg" => "请求方法不正确。"]);
	}
?>