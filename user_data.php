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
			$receivedToken = $_COOKIE["_"];
			if ($receivedToken) {
				$query = "SELECT id, token, nick, user_avatar FROM users WHERE token = ?";
				$preparedStatement = $databaseConnection->prepare($query);
				$preparedStatement->bind_param("s", $receivedToken);
				$preparedStatement->execute();
				$result = $preparedStatement->get_result();
				if ($result && $userData = $result->fetch_assoc()) {
					$data = [
						"id" => $userData["id"],
						"token" => $userData["token"],
						"nick" => $userData["nick"],
						"avatar" => $userData["user_avatar"]
					];
					$friendRequestQuery = "SELECT COUNT(*) as request_count FROM friend_requests WHERE target = ?";
					$friendRequestPreparedStatement = $databaseConnection->prepare($friendRequestQuery);
					$friendRequestPreparedStatement->bind_param("i", $userData["id"]);
					$friendRequestPreparedStatement->execute();
					$friendRequestResult = $friendRequestPreparedStatement->get_result();
					$friendRequestRow = $friendRequestResult->fetch_assoc();
					$data["requestCount"] = $friendRequestRow["request_count"];
					$friendRequestPreparedStatement->close();
					$friendMessageQuery = "SELECT id, source, target FROM friendships WHERE source = ? OR target = ?";
					$friendMessagePreparedStatement = $databaseConnection->prepare($friendMessageQuery);
					$friendMessagePreparedStatement->bind_param("ii", $userData["id"], $userData["id"]);
					$friendMessagePreparedStatement->execute();
					$friendMessageResult = $friendMessagePreparedStatement->get_result();
					$friendMessageData = [];
					while ($friendMessageRow = $friendMessageResult->fetch_assoc()) {
						$friendMessageQuery2 = "SELECT COUNT(*) as message_count FROM chats WHERE session = ? AND is_read = 0 AND sender != ?";
						$friendMessagePreparedStatement2 = $databaseConnection->prepare($friendMessageQuery2);
						$friendMessagePreparedStatement2->bind_param("ii", $friendMessageRow["id"], $userData["id"]);
						$friendMessagePreparedStatement2->execute();
						$friendMessageResult2 = $friendMessagePreparedStatement2->get_result();
						$friendMessageRow2 = $friendMessageResult2->fetch_assoc();
						$data["requestCount"] += $friendMessageRow2["message_count"];
						$friendMessagePreparedStatement2->close();
					}
					echo json_encode(["code" => 1, "msg" => $language["userInfoFetched"], "data" => $data]);
				} else {
					echo json_encode(["code" => 0, "msg" => $language["invalidUserOrToken"]]);
				}
				$preparedStatement->close();
			} else {
				echo json_encode(["code" => -1, "msg" => $language["missingToken"]]);
			}
			$databaseConnection->close();
		} catch (mysqli_sql_exception $sqlException) {
			echo json_encode(["code" => -1, "msg" => $language["databaseError"]]);
		}
	} else {
		echo json_encode(["code" => -1, "msg" => $language["invalidRequest"]]);
	}
?>