<?php
	if (isset($_GET["lang"]) && file_exists("languages/" . $_GET["lang"] . ".json")) {
		$language = json_decode(file_get_contents("languages/" . $_GET["lang"] . ".json"), true);
	} else {
		$language = json_decode(file_get_contents("languages/zh-CN.json"), true);
	}
	header('Content-Type: application/json');
	
	if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_COOKIE['_']) && isset($_POST['target'])) {
		mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
		$config = parse_ini_file('conf/settings.ini', true);
		$host = $config['database']['host'];
		$user = $config['database']['user'];
		$pass = $config['database']['pass'];
		$db = $config['database']['db'];
		try {
			$conn = new mysqli($host, $user, $pass, $db);
			$stmt = $conn->prepare("SELECT id FROM users WHERE token = ?");
			$stmt->bind_param('s', $_COOKIE['_']);
			$stmt->execute();
			$result = $stmt->get_result();
			$data = $result->fetch_assoc();
			if ($data) {
				$chatPrepare = "SELECT id, content, sent_at, sender, multi FROM chats WHERE session = ? ORDER BY sent_at";
				$chatStmt = $conn->prepare($chatPrepare);
				$intTarget = (int)$_POST['target'];
				$chatStmt->bind_param('i', $intTarget);
				$chatStmt->execute();
				$chatResult = $chatStmt->get_result();
				$chatData = $chatResult->fetch_all(MYSQLI_ASSOC);
				$oppositeStmt = $conn->prepare("SELECT CASE WHEN source = ? THEN target ELSE source END AS other_user_id, request_time, allowed_time FROM friendships WHERE id = ?");
				$oppositeStmt->bind_param('ii', $data["id"], $_POST['target']);
				$oppositeStmt->execute();
				$oppositeStmt->bind_result($otherUserId, $requestTime, $allowedTime);
				$oppositeStmt->fetch();
				$oppositeStmt->close();
				$validateStmt = $conn->prepare("SELECT COUNT(*) FROM friendships WHERE source = ? AND target = ? OR source = ? AND target = ?");
				$validateStmt->bind_param('iiii', $data["id"], $otherUserId, $otherUserId, $data["id"]);
				$validateStmt->execute();
				$validateStmt->bind_result($validate);
				$validateStmt->fetch();
				$validateStmt->close();
				if (!$validate) {
					echo json_encode(["code" => -1, "msg" => $language["cannotViewOthersChat"]]);
					exit;
				}
				$chatContent = "";
				$nickStmt = $conn->prepare("SELECT (SELECT nick FROM users WHERE id = ?) AS name_mine, (SELECT nick FROM users WHERE id = ?) AS name_opposite");
				$nickStmt->bind_param('ii', $data["id"], $otherUserId);
				$nickStmt->execute();
				$nickStmt->bind_result($nameMine, $nameOpposite);
				$nickStmt->fetch();
				$nickStmt->close();
				foreach ($chatData as $chatRow) {
					$inputTime = $chatRow['sent_at'];
					$dateTime = new DateTime($inputTime);
					$dateTime->modify('-8 hours');
					$dateTime->setTimezone(new DateTimeZone('UTC'));
					$isoFormat = $dateTime->format(DateTime::ATOM);
					$isoFormatWithZ = str_replace('+00:00', 'Z', $isoFormat);
					if ($data["id"] == $chatRow['sender']) {
						$chatContent .= $nameMine . $language["you"] . "(id: " . $data["id"] . ")" . "(" . $isoFormatWithZ . ")" . ($chatRow['multi'] ? " *" : "") . ": \n" . $chatRow["content"] . "\n\n";
					} else {
						$chatContent .= $nameOpposite . "(id: " . $otherUserId . ")" . "(" . $isoFormatWithZ . "): \n" . $chatRow["content"] . "\n\n";
					}
				}
				echo json_encode(["code" => 1, "msg" => $language["saveChatHistorySuccess"], "data" => $chatContent]);
			} else {
				echo json_encode(["code" => 0, "msg" => $language["invalidUserOrToken"]]);
			}
			$stmt->close();
			$conn->close();
		} catch (mysqli_sql_exception $sqlException) {
			echo json_encode(["code" => -1, "msg" => $language["databaseError"]]);
		}
	} else {
		echo json_encode(["code" => -1, "msg" => $language["invalidRequest"]]);
	}
?>