<?php
	header("Content-Type: application/json");
	if ($_SERVER["REQUEST_METHOD"] === "GET") {
		if (isset($_COOKIE["_"]) && isset($_GET["target"])) {
			mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
			$config = parse_ini_file("conf/settings.ini", true);
			$host = $config["database"]["host"];
			$user = $config["database"]["user"];
			$pass = $config["database"]["pass"];
			$db = $config["database"]["db"];
			try {
				$conn = new mysqli($host, $user, $pass, $db);
				$sessionStmt = $conn->prepare("SELECT user FROM user_session WHERE token = ? AND expires >= NOW()");
				$sessionStmt->bind_param("s", $_COOKIE["_"]);
				$sessionStmt->execute();
				$sessionResult = $sessionStmt->get_result();
				$sessionData = $sessionResult->fetch_assoc();
				if ($sessionData) {
					$userId = $sessionData["user"];
				} else {
					http_response_code(404);
					echo json_encode(["code" => -1, "msg" => "未找到有效的用户信息。"]);
					$sessionStmt->close();
					$conn->close();
					exit;
				}
				$chatPrepare = "SELECT id, content, UNIX_TIMESTAMP(sent_at) AS sent_at, sender, multi FROM chats WHERE session = ? ORDER BY sent_at";
				$chatStmt = $conn->prepare($chatPrepare);
				$intTarget = (int)$_GET["target"];
				$chatStmt->bind_param("i", $intTarget);
				$chatStmt->execute();
				$chatResult = $chatStmt->get_result();
				$chatData = $chatResult->fetch_all(MYSQLI_ASSOC);
				$oppositeStmt = $conn->prepare("SELECT request_time, allowed_time FROM friendships WHERE id = ?");
				$oppositeStmt->bind_param("i", $_GET["target"]);
				$oppositeStmt->execute();
				$oppositeStmt->bind_result($requestTime, $allowedTime);
				$oppositeStmt->fetch();
				$oppositeStmt->close();
				$validateStmt = $conn->prepare("SELECT (SELECT COUNT(*) FROM friendships WHERE id = ? AND (source = ? OR target = ?)) + (SELECT COUNT(*) FROM group_members WHERE `group` = ? AND user = ?)");
				$validateStmt->bind_param("iiiii", $_GET["target"], $userId, $userId, $_GET["target"], $userId);
				$validateStmt->execute();
				$validateStmt->bind_result($validate);
				$validateStmt->fetch();
				$validateStmt->close();
				if (!$validate) {
					http_response_code(403);
					echo json_encode(["code" => -1, "msg" => "不能偷看别人的聊天记录。"]);
					$sessionStmt->close();
					if (isset($adminStmt)) {
						$adminStmt->close();
					}
					$chatStmt->close();
					$conn->close();
					exit;
				}
				$chatContent = "";
				foreach ($chatData as $chatRow) {
					$inputTime = $chatRow["sent_at"];
					$dateTime = new DateTime('@' . $chatRow["sent_at"]);
					$isoFormat = $dateTime->format(DateTime::ATOM);
					$nickStmt = $conn->prepare("SELECT nick FROM users WHERE id = ?");
					$nickStmt->bind_param("i", $chatRow["sender"]);
					$nickStmt->execute();
					$nickStmt->bind_result($name);
					$nickStmt->fetch();
					$nickStmt->close();
					$isoFormatWithZ = str_replace("+00:00", "Z", $isoFormat);
					$chatContent .= $name . "(id: " . $chatRow["sender"] . ")" . "(" . $isoFormatWithZ . "): \n" . $chatRow["content"] . "\n\n";
				}
				echo json_encode(["code" => 1, "msg" => "聊天记录保存成功。", "data" => $chatContent]);
				if (isset($sessionStmt)) {
					$sessionStmt->close();
				}
				if (isset($adminStmt)) {
					$adminStmt->close();
				}
				$chatStmt->close();
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