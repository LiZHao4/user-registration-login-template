<?php
	require "main.php";
	header("Content-Type: application/json");
	function getUniqueToken() {
		global $db;
		while (true) {
			$token = generateRandomToken();
			$stmtCheckToken = $db->prepare("SELECT COUNT(*) AS count FROM user_session WHERE token = ?");
			$stmtCheckToken->bind_param("s", $token);
			$stmtCheckToken->execute();
			$resultCheckToken = $stmtCheckToken->get_result();
			$totalCount = 0;
			while ($row = $resultCheckToken->fetch_assoc()) {
				$totalCount += $row["count"];
			}
			$stmtCheckToken->close();
			if ($totalCount == 0) {
				return $token;
			}
		}
	}
	if ($_SERVER["REQUEST_METHOD"] === "POST") {
		if (isset($_POST["user"]) && isset($_POST["pass"]) && isset($_POST["from"])) {
			mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
			try {
				$config = parse_ini_file("conf/settings.ini", true);
				$host = $config["database"]["host"];
				$user = $config["database"]["user"];
				$pass = $config["database"]["pass"];
				$db_name = $config["database"]["db"];
				$db = new mysqli($host, $user, $pass, $db_name);
				$username = $_POST["user"];
				$password = $_POST["pass"];
				$action = $_POST["from"];
				if (!preg_match("/^[a-zA-Z_$][a-zA-Z0-9_$]{0,31}$/", $username) || strlen($password) < 8 || strlen($password) > 32 || !preg_match("/[a-z]/", $password) || !preg_match("/[A-Z]/", $password) || !preg_match("/\d/", $password)) {
					http_response_code(400);
					echo json_encode(["code" => -1, "msg" => "用户名或密码格式不正确。"]);
					$db->close();
					exit();
				}
				if ($action == "regist") {
					$stmtCheckUsername = $db->prepare("SELECT COUNT(*) AS count FROM users WHERE user = ?");
					$stmtCheckUsername->bind_param("s", $username);
					$stmtCheckUsername->execute();
					$resultCheckUsername = $stmtCheckUsername->get_result();
					$rowCheckUsername = $resultCheckUsername->fetch_assoc();
					if ($rowCheckUsername["count"] > 0) {
						http_response_code(409);
						echo json_encode(["code" => -1, "msg" => "用户名已存在。"]);
						$stmtCheckUsername->close();
						$db->close();
						exit();
					}
					$hashedPassword = password_hash($password, PASSWORD_DEFAULT);
					$nextUserIdStmt = $db->query("SELECT COALESCE((SELECT MIN(id) + 1 FROM users t1 WHERE NOT EXISTS (SELECT 1 FROM users t2 WHERE t2.id = t1.id + 1) AND id < (SELECT MAX(id) FROM users)), (SELECT 1 WHERE NOT EXISTS (SELECT 1 FROM users WHERE id = 1)), (SELECT COALESCE(MAX(id), 0) + 1 FROM users)) AS next_user_id");
					$nextUserIdRow = $nextUserIdStmt->fetch_assoc();
					$nextUserId = $nextUserIdRow["next_user_id"];
					$stmtInsertUser = $db->prepare("INSERT INTO users (id, user, password, nick) VALUES (?, ?, ?, ?)");
					$stmtInsertUser->bind_param("isss", $nextUserId, $username, $hashedPassword, $username);
					$stmtInsertUser->execute();
					echo json_encode(["code" => 1, "msg" => "注册成功！"]);
					$stmtCheckUsername->close();
					$stmtInsertUser->close();
				} else if ($action == "login") {
					$stmtLogin = $db->prepare("SELECT id, password, UNIX_TIMESTAMP(unbanned_at) AS unbanned_at FROM users WHERE user = ?");
					$stmtLogin->bind_param("s", $username);
					$stmtLogin->execute();
					$resultLogin = $stmtLogin->get_result();
					if ($resultLogin && $rowLogin = $resultLogin->fetch_assoc()) {
						if (password_verify($password, $rowLogin["password"])) {
							$unbannedAt = $rowLogin["unbanned_at"];
							if ($unbannedAt !== null && $unbannedAt > time()) {
								http_response_code(403);
								echo json_encode([
									"code" => -1,
									"msg" => "您的账号已被封禁，解封时间：#t",
									"unbanned_at" => $unbannedAt
								]);
							} else {
								$userId = $rowLogin["id"];
								$token = getUniqueToken();
								$expires = strtotime("+30 days");
								$stmtInsertSession = $db->prepare("INSERT INTO user_session (user, token, expires) VALUES (?, ?, FROM_UNIXTIME(?))");
								$stmtInsertSession->bind_param("isi", $userId, $token, $expires);
								$stmtInsertSession->execute();
								$stmtInsertSession->close();
								setcookie("_", $token, [
									'expires' => $expires,
									'path' => '/',
									'httponly' => true,
									'samesite' => 'Lax'
								]);
								echo json_encode(["code" => 1, "msg" => "登录成功。", "token" => $token, "expires" => $expires]);
							}
						} else {
							http_response_code(401);
							echo json_encode(["code" => -1, "msg" => "密码错误。"]);
						}
					} else {
						http_response_code(404);
						echo json_encode(["code" => -1, "msg" => "用户不存在。"]);
					}
					$stmtLogin->close();
				} else {
					http_response_code(400);
					echo json_encode(["code" => -1, "msg" => "未知的动作。"]);
				}
				$deleteStmt = $db->prepare("DELETE FROM user_session WHERE expires <= NOW()");
				$deleteStmt->execute();
				$deleteStmt->close();
				$db->close();
			} catch (mysqli_sql_exception $e) {
				http_response_code(500);
				echo json_encode(["code" => -1, "msg" => "数据库错误。" . $e->getMessage()]);	
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