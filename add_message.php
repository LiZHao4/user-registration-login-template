<?php
	header("Content-Type: application/json");
	if ($_SERVER["REQUEST_METHOD"] === "POST") {
		if (isset($_COOKIE["_"]) && isset($_POST["target"]) && isset($_POST["msg"])) {
			if (trim($_POST["msg"]) === "") {
				http_response_code(400);
				echo json_encode(["code" => -1, "msg" => "参数不合法。"]);
				exit;
			}
			mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
			$config = parse_ini_file("conf/settings.ini", true);
			$host = $config["database"]["host"];
			$user = $config["database"]["user"];
			$pass = $config["database"]["pass"];
			$db = $config["database"]["db"];
			try {
				$conn = new mysqli($host, $user, $pass, $db);
				$stmt = $conn->prepare("SELECT user FROM user_session WHERE token = ? AND expires >= NOW()");
				$stmt->bind_param("s", $_COOKIE["_"]);
				$stmt->execute();
				$result = $stmt->get_result();
				$data = $result->fetch_assoc();
				if ($data) {
					$userId = $data["user"];
				} else {
					http_response_code(404);
					echo json_encode(["code" => -1, "msg" => "未找到匹配的用户信息。"]);
					$stmt->close();
					$conn->close();
					exit;
				}
				$target = (int)$_POST["target"];
				$testStmt = $conn->prepare("SELECT (SELECT COUNT(*) FROM friendships WHERE id = ? AND (source = ? OR target = ?)) + (SELECT COUNT(*) FROM group_members WHERE `group` = ? AND user = ?)");
				$testStmt->bind_param("iiiii", $target, $userId, $userId, $target, $userId);
				$testStmt->execute();
				$testStmt->bind_result($count);
				$testStmt->fetch();
				$testStmt->close();
				if ($count > 0) {
					$trimmedMsg = trim($_POST["msg"]);
					$addStmt = $conn->prepare("INSERT INTO chats (session, content, sender, type) VALUES (?, ?, ?, 1)");
					$addStmt->bind_param("ssi", $target, $trimmedMsg, $userId);
					$addStmt->execute();
					echo json_encode(["code" => 1, "msg" => "消息发送成功。"]);
				} else {
					http_response_code(400);
					echo json_encode(["code" => -1, "msg" => "您没有在该会话上发送消息的权限。"]);
				}
				$stmt->close();
				$conn->close();
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