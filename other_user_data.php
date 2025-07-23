<?php
	header("Content-Type: application/json");
	if ($_SERVER["REQUEST_METHOD"] === "GET") {
		if (isset($_GET["id"])) {
			mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
			$config = parse_ini_file("conf/settings.ini", true);
			$host = $config["database"]["host"];
			$user = $config["database"]["user"];
			$pass = $config["database"]["pass"];
			$db = $config["database"]["db"];
			try {
				$mysqli = new mysqli($host, $user, $pass, $db);
				$stmt = $mysqli->prepare("SELECT id, user, nick, user_avatar AS avatar, gender, birth, bio FROM users WHERE id = ?");
				$stmt->bind_param("i", $_GET["id"]);
				$stmt->execute();
				$result = $stmt->get_result();
				if ($result->num_rows > 0) {
					$userData = $result->fetch_assoc();
					if (isset($_COOKIE["_"])) {
						$userId = null;
						$token = $_COOKIE["_"];
						$stmt0 = $mysqli->prepare("SELECT user FROM user_session WHERE token = ? AND expires >= NOW()");
						$stmt0->bind_param("s", $token);
						$stmt0->execute();
						$res = $stmt0->get_result();
						$stmt0->close();
						if ($res->num_rows > 0) {
							$row = $res->fetch_assoc();
							$userId = $row["user"];
						} else {
							http_response_code(404);
							echo json_encode(["code" => 0, "msg" => "未找到有效的用户，请重新登录。"]);
							$mysqli->close();
							exit;
						}
						$stmt2 = $mysqli->prepare("SELECT remark FROM user_remarks WHERE user_id = ? AND target_user_id = ?");
						$stmt2->bind_param("ii", $userId, $_GET["id"]);
						$stmt2->execute();
						$result2 = $stmt2->get_result();
						if ($result2->num_rows > 0) {
							$data = $result2->fetch_assoc();
							$userData["remark"] = $data["remark"];
						} else {
							$userData["remark"] = null;
						}
						$stmt3 = $mysqli->prepare("SELECT background, theme_color FROM users WHERE id = ?");
						$stmt3->bind_param("i", $_GET["id"]);
						$stmt3->execute();
						$result3 = $stmt3->get_result();
						if ($result3->num_rows > 0) {
							$data = $result3->fetch_assoc();
							$userData["background"] = $data["background"];
							if ($data["theme_color"] !== null) {
								$hex = bin2hex($data["theme_color"]);
								$userData["theme_color"] = '#' . strtoupper($hex);
							}
						}
						$stmt2->close();
					}
					echo json_encode(["code" => 1, "msg" => "用户信息获取成功。", "data" => $userData]);
				} else {
					http_response_code(404);
					echo json_encode(["code" => -1, "msg" => "用户不存在。"]);
				}
				$stmt->close();
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