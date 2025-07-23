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
				$token = $_COOKIE["_"];
				$stmt = $databaseConnection->prepare("SELECT user FROM user_session WHERE token = ? AND expires >= NOW()");
				$stmt->bind_param("s", $token);
				$stmt->execute();
				$res = $stmt->get_result();
				if ($res->num_rows > 0) {
					$row = $res->fetch_assoc();
					$userId = $row["user"];
					$stmtQuery = $databaseConnection->prepare("SELECT content, UNIX_TIMESTAMP(sent_at) AS sent_at FROM system_messages WHERE target = ? ORDER BY id DESC");
					$stmtQuery->bind_param("i", $userId);
					$stmtQuery->execute();
					$result = $stmtQuery->get_result();
					$messages = [];
					while ($row = $result->fetch_assoc()) {
						$decodedContent = json_decode($row["content"], true);
						$messages[] = [
							"content" => $decodedContent,
							"sent_at" => (int)$row["sent_at"]
						];
					}
					$stmtQuery->close();
					$update = "UPDATE system_messages SET is_read = 1 WHERE target = ? AND is_read = 0";
					$stmtUpdate = $databaseConnection->prepare($update);
					$stmtUpdate->bind_param("i", $userId);
					$stmtUpdate->execute();
					$stmtUpdate->close();
					echo json_encode(["code" => 1, "msg" => "系统消息获取成功。", "data" => $messages]);
				} else {
					http_response_code(401);
					echo json_encode(["code" => 0, "msg" => "用户不存在或token无效，请重新登录。"]);
				}
				$stmt->close();
				$databaseConnection->close();
			} catch (mysqli_sql_exception $sqlException) {
				http_response_code(500);
				echo json_encode(["code" => -1, "msg" => "数据库错误。"]);
				if (isset($databaseConnection)) {
					$databaseConnection->close();
				}
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