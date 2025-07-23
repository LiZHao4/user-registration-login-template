<?php
	header("Content-Type: application/json");
	error_reporting(E_ALL);
	if ($_SERVER["REQUEST_METHOD"] == "GET") {
		if (isset($_COOKIE["_"])) {
			mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
			$config = parse_ini_file("conf/settings.ini", true);
			$host = $config["database"]["host"];
			$user = $config["database"]["user"];
			$pass = $config["database"]["pass"];
			$db = $config["database"]["db"];
			try {
				$mysqli = new mysqli($host, $user, $pass, $db);
				$token = $_COOKIE["_"];
				$stmt = $mysqli->prepare("SELECT user FROM user_session WHERE token = ? AND expires >= NOW()");
				$stmt->bind_param("s", $token);
				$stmt->execute();
				$result = $stmt->get_result();
				if ($result->num_rows > 0) {
					$fetchedUser = $result->fetch_assoc();
					$userId = $fetchedUser["user"];
				} else {
					http_response_code(401);
					echo json_encode(["code" => 0, "msg" => "用户不存在或token无效，请重新登录。"]);
					$stmt->close();
					$mysqli->close();
					return;
				}
				$stmt_recv = $mysqli->prepare("SELECT fr.id, fr.source, fr.target, UNIX_TIMESTAMP(fr.sent_at) AS time, u.nick, ur.remark FROM friend_requests fr JOIN users u ON fr.source = u.id LEFT JOIN user_remarks ur ON u.id = ur.target_user_id AND ur.user_id = ? WHERE fr.target = ?");
				$stmt_recv->bind_param("ii", $userId, $userId);
				$stmt_recv->execute();
				$received = $stmt_recv->get_result()->fetch_all(MYSQLI_ASSOC);
				$stmt_sent = $mysqli->prepare("SELECT fr.id, fr.source, fr.target, UNIX_TIMESTAMP(fr.sent_at) AS time, u.nick, ur.remark FROM friend_requests fr JOIN users u ON fr.target = u.id LEFT JOIN user_remarks ur ON u.id = ur.target_user_id AND ur.user_id = ? WHERE fr.source = ?");
				$stmt_sent->bind_param("ii", $userId, $userId);
				$stmt_sent->execute();
				$sent = $stmt_sent->get_result()->fetch_all(MYSQLI_ASSOC);
				echo json_encode(["code" => 1, "msg" => "好友请求列表获取成功。", "data" => $received, "fData" => $sent]);
				$stmt_recv->close();
				$stmt_sent->close();
				$mysqli->close();
			} catch (ErrorException $databaseException) {
				http_response_code(500);
				echo json_encode(["code" => -1, "msg" => "数据库错误。"]);
			}
		} else {
			http_response_code(400);
			echo json_encode(["code" => 0, "msg" => "缺少必要的参数，请重新登录。"]);
		}
	} else {
		http_response_code(405);
		echo json_encode(["code" => -1, "msg" => "请求方法不正确。"]);
	}
?>