<?php
	header("Content-Type: application/json");
	if ($_SERVER["REQUEST_METHOD"] === "GET") {
		if (isset($_COOKIE["_"]) && isset($_GET["source"])) {
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
					echo json_encode(["code" => 0, "msg" => "未找到有效的用户ID。"]);
					$stmt->close();
					$conn->close();
					return;
				}
				$source = $_GET["source"];
				$stmt = $conn->prepare("SELECT COUNT(*) FROM chats WHERE multi = ?");
				$stmt->bind_param("s", $source);
				$stmt->execute();
				$result = $stmt->get_result();
				$fileData = $result->fetch_assoc();
				if ($fileData) {
					$filePath = "files/" . $source;
					if (file_exists($filePath)) {
						echo json_encode(["code" => 1, "msg" => "文件内容获取成功。", "link" => $filePath]);
					} else {
						http_response_code(404);
						echo json_encode(["code" => -1, "msg" => "文件不存在。"]);
					}
				} else {
					http_response_code(404);
					echo json_encode(["code" => -1, "msg" => "文件不存在。"]);
				}
				$stmt->close();
				$conn->close();
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