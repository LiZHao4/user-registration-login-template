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
		$fresh = isset($_POST['getNew']);
		if ($fresh && !isset($_POST['max'])) {
			echo json_encode(["code" => -1, "msg" => $language["invalidRequest"]]);
		}
		try {
			$conn = new mysqli($host, $user, $pass, $db);
			$stmt = $conn->prepare("SELECT id FROM users WHERE token = ?");
			$stmt->bind_param('s', $_COOKIE['_']);
			$stmt->execute();
			$result = $stmt->get_result();
			$data = $result->fetch_assoc();
			if ($data) {
				if (!$fresh) {
					$chatPrepare = "SELECT id, content, sent_at, sender, multi FROM chats WHERE session = ? ORDER BY sent_at";
				} else {
					$chatPrepare = "SELECT id, content, sent_at, sender, multi FROM chats WHERE session = ? AND id > ? ORDER BY sent_at";
				}
				$chatStmt = $conn->prepare($chatPrepare);
				$intTarget = (int)$_POST['target'];
				if (!$fresh) {
					$chatStmt->bind_param('i', $intTarget);
				} else {
					$intMax = (int)$_POST['max'];
					$chatStmt->bind_param('ii', $intTarget, $intMax);
				}
				$chatStmt->execute();
				$chatResult = $chatStmt->get_result();
				$chatData = $chatResult->fetch_all(MYSQLI_ASSOC);
				$avatarStmt = $conn->prepare("SELECT user_avatar FROM users WHERE id = ?");
				$avatarStmt->bind_param('i', $data["id"]);
				$avatarStmt->execute();
				$avatarResult = $avatarStmt->get_result();
				$avatarData = $avatarResult->fetch_assoc();
				$avatarMine = $avatarData["user_avatar"];
				$avatarStmt->close();
				$oppositeStmt = $conn->prepare("SELECT CASE WHEN source = ? THEN target ELSE source END AS other_user_id, request_time, allowed_time FROM friendships WHERE id = ?");
				$oppositeStmt->bind_param('ii', $data["id"], $_POST['target']);
				$oppositeStmt->execute();
				$oppositeStmt->bind_result($otherUserId, $requestTime, $allowedTime);
				$oppositeStmt->fetch();
				$oppositeStmt->close();
				$validateStmt = $conn->prepare("SELECT COUNT(*) FROM friendships WHERE id = ? AND (source = ? OR target = ?)");
				$validateStmt->bind_param('iii', $intTarget, $data["id"], $data["id"]);
				$validateStmt->execute();
				$validateStmt->bind_result($validate);
				$validateStmt->fetch();
				$validateStmt->close();
				if (!$validate) {
					echo json_encode(["code" => -1, "msg" => $language["cannotViewOthersChat"]]);
					exit;
				}
				foreach ($chatData as $chatRow) {
					if ($data["id"] != $chatRow['sender']) {
						$updateStmt = $conn->prepare("UPDATE chats SET is_read = TRUE WHERE session = ? AND sender = ? AND is_read = FALSE");
						$updateStmt->bind_param('ii', $_POST['target'], $otherUserId);
						$updateStmt->execute();
						$updateStmt->close();
					}
				}
				$avatarUrl = "";
				$oppositeName = "";
				if ($otherUserId) {
					$avatarStmt2 = $conn->prepare("SELECT nick, user_avatar FROM users WHERE id = ?");
					$avatarStmt2->bind_param('i', $otherUserId);
					$avatarStmt2->execute();
					$avatarResult2 = $avatarStmt2->get_result();
					$avatarData2 = $avatarResult2->fetch_assoc();
					$avatarStmt2->close();
					$avatarUrl = $avatarData2['user_avatar'];
					$oppositeName = $avatarData2['nick'];
				}
				$chatStmt->close();
				if ($fresh) {
					echo json_encode(["code" => 1, "msg" => $language["chatInfoSuccessMessage"], "data" => $chatData, "avatar" => $avatarMine, "opposite" => $avatarUrl]);
				} else {
					echo json_encode(["code" => 1, "msg" => $language["chatInfoSuccessMessage"], "data" => $chatData, "id" => $data["id"], "oId" => $otherUserId, "avatar" => $avatarMine, "opposite" => $avatarUrl, "oName" => $oppositeName, "time1" => $requestTime, "time2" => $allowedTime]);
				}
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