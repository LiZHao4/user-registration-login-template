<?php
	header("Content-Type: application/json");
	if ($_SERVER["REQUEST_METHOD"] === "POST"){
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
				$checkSessionQuery = "SELECT id FROM user_session WHERE token = ? AND expires >= NOW()";
				$checkSessionStmt = $databaseConnection->prepare($checkSessionQuery);
				$checkSessionStmt->bind_param("s", $token);
				$checkSessionStmt->execute();
				$sessionResult = $checkSessionStmt->get_result();
				if ($sessionResult->num_rows > 0) {
					$deleteSessionQuery = "DELETE FROM user_session WHERE token = ?";
					$deleteSessionStmt = $databaseConnection->prepare($deleteSessionQuery);
					$deleteSessionStmt->bind_param("s", $token);
					$deleteSessionStmt->execute();
					http_response_code(200);
					echo json_encode(["code" => 1, "msg" => "登出成功。"]);
				} else {
					http_response_code(404);
					echo json_encode(["code" => -1, "msg" => "未找到有效的token。"]);
				}
				$databaseConnection->close();
			} catch (mysqli_sql_exception $sqlException) {
				http_response_code(500);
				echo json_encode(["code" => -1, "msg" => "数据库错误。"]);
			}
		} else {
			http_response_code(400);
			echo json_encode(["code" => -1, "msg" => "请求方法不正确或缺少必要的参数。"]);
		}
	} else {
		http_response_code(405);
		echo json_encode(["code" => -1, "msg" => "请求方法不正确或缺少必要的参数。"]);
	}
?>