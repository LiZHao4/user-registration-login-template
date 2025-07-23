<?php
	header("Content-Type: application/json");
	if ($_SERVER["REQUEST_METHOD"] === "GET") {
		if (isset($_COOKIE["_"])) {
			mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
			$config = parse_ini_file("conf/settings.ini", true);
			$host = $config["database"]["host"];
			$user = $config["database"]["user"];
			$pass = $config["database"]["pass"];
			$db = $config["database"]["db"];
			try {
				$databaseConnection = new mysqli($host, $user, $pass, $db);
				$postedToken = $_COOKIE["_"];
				$userId = null;
				$querySession = "SELECT user FROM user_session WHERE token = ? AND expires >= NOW()";
				$statementSession = $databaseConnection->prepare($querySession);
				$statementSession->bind_param("s", $postedToken);
				$statementSession->execute();
				$resultSession = $statementSession->get_result();
				if ($resultSession->num_rows > 0) {
					$sessionData = $resultSession->fetch_assoc();
					$userId = $sessionData["user"];
				}
				if ($userId !== null) {
					$query = "SELECT id, user, nick, UNIX_TIMESTAMP(created_at) AS created_at, user_avatar, gender, birth, bio, background FROM users WHERE id = ?";
					$statement = $databaseConnection->prepare($query);
					$statement->bind_param("s", $userId);
					$statement->execute();
					$result = $statement->get_result();
					if ($result->num_rows > 0) {
						$userData = $result->fetch_assoc();
						$data = [
							"user" => $userData["user"],
							"nick" => $userData["nick"],
							"id" => $userData["id"],
							"created_at" => $userData["created_at"],
							"avatar" => $userData["user_avatar"],
							"gender" => $userData["gender"],
							"birth" => $userData["birth"],
							"bio" => $userData["bio"],
							"background" => $userData["background"]
						];
						if ($data["birth"] == null) {
							$data["birth"] = "";
						}
						echo json_encode(["code" => 1, "msg" => "用户信息获取成功。", "data" => $data]);
					} else {
						http_response_code(404);
						echo json_encode(["code" => 0, "msg" => "未找到匹配的用户信息。"]);
					}
				} else {
					http_response_code(404);
					echo json_encode(["code" => 0, "msg" => "未找到匹配的用户信息。"]);
				}
				if (isset($statementSession)) {
					$statementSession->close();
				}
				if (isset($statement)) {
					$statement->close();
				}
				if (isset($statementAdmin)) {
					$statementAdmin->close();
				}
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