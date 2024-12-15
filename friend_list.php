<?php
	if (isset($_GET["lang"]) && file_exists("languages/" . $_GET["lang"] . ".json")) {
		$language = json_decode(file_get_contents("languages/" . $_GET["lang"] . ".json"), true);
	} else {
		$language = json_decode(file_get_contents("languages/zh-CN.json"), true);
	}
	header('Content-Type: application/json');
	if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_COOKIE['_'])) {
		mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
		$config = parse_ini_file('conf/settings.ini', true);
		$host = $config['database']['host'];
		$user = $config['database']['user'];
		$pass = $config['database']['pass'];
		$db = $config['database']['db'];
		try {
			$databaseConnection = new mysqli($host, $user, $pass, $db);
			$token = $_COOKIE['_'];
			$stmt = $databaseConnection->prepare("SELECT id FROM users WHERE token = ?");
			$stmt->bind_param("s", $token);
			$stmt->execute();
			$res = $stmt->get_result();
			if ($res->num_rows > 0) {
				$row = $res->fetch_assoc();
				$userId = $row["id"];
				$friendsStmt = $databaseConnection->prepare("SELECT id, target AS friend_id, allowed_time FROM friendships WHERE source = ? UNION SELECT id, source AS friend_id, allowed_time FROM friendships WHERE target = ?");
				$friendsStmt->bind_param("ii", $userId, $userId);
				$friendsStmt->execute();
				$friendsResult = $friendsStmt->get_result();
				$friends = [];
				if ($friendsResult->num_rows > 0) {
					while ($friendRow = $friendsResult->fetch_assoc()) {
						$queryStmt = $databaseConnection->prepare("SELECT nick, user_avatar AS avatar FROM users WHERE id = ?");
						$queryStmt->bind_param("i", $friendRow['friend_id']);
						$queryStmt->execute();
						$queryResult = $queryStmt->get_result();
						$queryRow = $queryResult->fetch_assoc();
						$queryStmt2 = $databaseConnection->prepare("SELECT content, sent_at FROM chats WHERE session = ? ORDER BY sent_at DESC LIMIT 1");
						$queryStmt2->bind_param("i", $friendRow['id']);
						$queryStmt2->execute();
						$queryResult2 = $queryStmt2->get_result();
						$queryRow2 = $queryResult2->fetch_assoc();
						$queryStmt3 = $databaseConnection->prepare("SELECT COUNT(*) AS count FROM chats WHERE session = ? AND sender = ? AND is_read = 0");
						$queryStmt3->bind_param("ii", $friendRow['id'], $friendRow['friend_id']);
						$queryStmt3->execute();
						$queryResult3 = $queryStmt3->get_result();
						$queryRow3 = $queryResult3->fetch_assoc();
						$friends[] = [
							"id" => $friendRow['id'],
							"avatar" => $queryRow['avatar'],
							"nick" => $queryRow['nick'],
							"time" => $queryRow2 ? $queryRow2['sent_at'] : $friendRow['allowed_time'],
							"content" => $queryRow2 ? $queryRow2['content'] : "",
							"count" => $queryRow3['count']
						];
						$queryStmt->close();
						$queryStmt2->close();
						$queryStmt3->close();
					}
				}
				$friendsStmt->close();
				echo json_encode(["code" => 1, "msg" => $language["friendListSuccessMessage"], "data" => $friends]);
			} else {
				echo json_encode(["code" => 0, "msg" => $language["invalidUserOrToken"]]);
			}
			$stmt->close();
			$databaseConnection->close();
		} catch (mysqli_sql_exception $sqlException) {
			echo json_encode(["code" => -1, "msg" => $language["databaseError"] . $sqlException->getMessage()]);
		}
	} else {
		echo json_encode(["code" => -1, "msg" => $language["invalidRequest"]]);
	}
?>