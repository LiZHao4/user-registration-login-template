<?php
	header("Content-Type: application/json");
	if ($_SERVER["REQUEST_METHOD"] == "POST") {
		if (isset($_COOKIE["_"]) && isset($_FILES["file"]) && isset($_SERVER['HTTP_X_CHAT_ROOM_ID'])) {
			$uploadedFile = $_FILES["file"];
			if ($uploadedFile["error"] == UPLOAD_ERR_OK) {
					mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
					$config = parse_ini_file("conf/settings.ini", true);
					$host = $config["database"]["host"];
					$user = $config["database"]["user"];
					$pass = $config["database"]["pass"];
					$db = $config["database"]["db"];
					try {
						$conn = new mysqli($host, $user, $pass, $db);
						$stmt = $conn->prepare("SELECT user AS id FROM user_session WHERE token = ? AND expires >= NOW()");
						$stmt->bind_param("s", $_COOKIE["_"]);
						$stmt->execute();
						$result = $stmt->get_result();
						$data = $result->fetch_assoc();
						if ($data) {
							$userId = $data["id"];
							$target = (int)$_SERVER['HTTP_X_CHAT_ROOM_ID'];
							$checkStmt = $conn->prepare("SELECT (SELECT COUNT(*) FROM friendships WHERE id = ? AND (source = ? OR target = ?)) + (SELECT COUNT(*) FROM group_members WHERE `group` = ? AND user = ?) AS in_session");
							$checkStmt->bind_param("iiiii", $target, $userId, $userId, $target, $userId);
							$checkStmt->execute();
							$checkResult = $checkStmt->get_result();
							$checkData = $checkResult->fetch_assoc();
							$checkStmt->close();
							$inSession = (int)$checkData["in_session"] > 0;
							if (!$inSession) {
								http_response_code(403);
								echo json_encode(["code" => -1, "msg" => "你不在该会话中，无法发送文件。"]);
								$conn->close();
								exit;
							}
							$randomFileName = substr(str_shuffle(str_repeat("0123456789abcdefghijklmnopqrstuvwxyz", 34)), 0, 80);
							$filePath = "files/" . $randomFileName;
							if (move_uploaded_file($uploadedFile["tmp_name"], $filePath)) {
								$sql = "INSERT INTO chats (session, multi, content, sender, type) VALUES (?, ?, ?, ?, 2)";
								$prepareStmt = $conn->prepare($sql);
								$prepareStmt->bind_param("issi", $target, $randomFileName, $uploadedFile["name"], $userId);
								$prepareStmt->execute();
								echo json_encode(["code" => 1, "msg" => "文件上传成功。"]);
								$prepareStmt->close();
							} else {
								http_response_code(500);
								echo json_encode(["code" => -1, "msg" => "文件上传失败。"]);
							}
						} else {
							http_response_code(404);
							echo json_encode(["code" => 0, "msg" => "未找到匹配的用户信息，请检查登录状态。"]);
						}
						$conn->close();
					} catch (mysqli_sql_exception $sqlException) {
						http_response_code(500);
						echo json_encode(["code" => -1, "msg" => "数据库错误。"]);
					}
				
			} else {
				http_response_code(400);
				echo json_encode(["code" => -1, "msg" => "文件上传失败。"]);
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