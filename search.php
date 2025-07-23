<?php
	header("Content-Type: application/json");
	if ($_SERVER["REQUEST_METHOD"] === "GET") {
		if (isset($_GET["q"])) {
			mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
			$config = parse_ini_file("conf/settings.ini", true);
			$host = $config["database"]["host"];
			$user = $config["database"]["user"];
			$pass = $config["database"]["pass"];
			$db = $config["database"]["db"];
			try {
				$mysqli = new mysqli($host, $user, $pass, $db);
				if (is_numeric($_GET["q"][0]) && preg_match("/^[0-9]+$/", $_GET["q"])) {
					$statement = "SELECT id, user, nick, user_avatar AS avatar FROM users WHERE id = ?";
					$intQ = $_GET["q"];
					$stmt = $mysqli->prepare($statement);
					$stmt->bind_param("s", $intQ);
					$stmt->execute();
					$result = $stmt->get_result();
					if ($result->num_rows > 0) {
						$row = $result->fetch_assoc();
						echo json_encode(["code" => 1, "msg" => "用户信息获取成功。", "data" => $row]);
					} else {
						http_response_code(404);
						echo json_encode(["code" => -1, "msg" => "用户不存在。"]);
					}
				} else if (preg_match("/^[a-zA-Z_$]/", $_GET["q"])) {
					$statement = "SELECT id, user, nick, user_avatar AS avatar FROM users WHERE user = ?";
					$strQ = $_GET["q"];
					$stmt = $mysqli->prepare($statement);
					$stmt->bind_param("s", $strQ);
					$stmt->execute();
					$result = $stmt->get_result();
					if ($result->num_rows > 0) {
						$row = $result->fetch_assoc();
						echo json_encode(["code" => 1, "msg" => "用户信息获取成功。", "data" => $row]);
					} else {
						http_response_code(404);
						echo json_encode(["code" => -1, "msg" => "用户不存在。"]);
					}
				} else {
					http_response_code(400);
					echo json_encode(["code" => -1, "msg" => "请求格式不正确。"]);
				}
				$stmt->close();
				$mysqli->close();
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