<?php
	if (isset($_GET["lang"]) && file_exists("languages/" . $_GET["lang"] . ".json")) {
		$language = json_decode(file_get_contents("languages/" . $_GET["lang"] . ".json"), true);
	} else {
		$language = json_decode(file_get_contents("languages/zh-CN.json"), true);
	}
	header('Content-Type: application/json');
	error_reporting(E_ALL);
	if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['token'])) {
		mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
		$config = parse_ini_file('conf/settings.ini', true);
		$host = $config['database']['host'];
		$user = $config['database']['user'];
		$pass = $config['database']['pass'];
		$db = $config['database']['db'];
		try {
			$mysqli = new mysqli($host, $user, $pass, $db);
			$stmt = "SELECT id FROM users WHERE token = ?";
			$token = $_POST['token'];
			$stmtPrepared = $mysqli->prepare($stmt);
			$stmtPrepared->bind_param('s', $token);
			$stmtPrepared->execute();
			$result = $stmtPrepared->get_result();
			$fetchedUser = $result->fetch_assoc();
			if (!$fetchedUser) {
				echo json_encode(["code" => 0, "msg" => $language["invalidUserOrToken"]]);
			}
			$userId = $fetchedUser['id'];
			$friendRequestsStmt = "SELECT fr.id, fr.source, fr.target, fr.sent_at, u.nick FROM friend_requests fr JOIN users u ON fr.source = u.id WHERE fr.target = ? UNION SELECT fr.id, fr.source, fr.target, fr.sent_at, u.nick FROM friend_requests fr JOIN users u ON fr.target = u.id WHERE fr.source = ?;";
			$friendRequestsPrepared = $mysqli->prepare($friendRequestsStmt);
			$friendRequestsPrepared->bind_param('ii', $userId, $userId);
			$friendRequestsPrepared->execute();
			$friendRequestsResult = $friendRequestsPrepared->get_result();
			$friendRequests = [];
			$sendRequests = [];
			while ($row = $friendRequestsResult->fetch_assoc()) {
				if ($row['source'] == $userId) {
					$sendRequests[] = $row;
				} else {
					$friendRequests[] = $row;
				}
			}
			$friendRequestsPrepared->close();
			$avatarStmt = "SELECT user_avatar AS avatar FROM users WHERE id = ?";
			$avatarPrepared = $mysqli->prepare($avatarStmt);
			$avatarPrepared->bind_param('i', $userId);
			$avatarPrepared->execute();
			$avatarResult = $avatarPrepared->get_result();
			$avatarRow = $avatarResult->fetch_assoc();
			$avatar = $avatarRow['avatar'];
			$avatarPrepared->close();
			echo json_encode(["code" => 1, "msg" => $language["userInfoFetched"], "your" => $userId, "data" => $friendRequests, "fData" => $sendRequests, "avatar" => $avatar]);
			$mysqli->close();
		} catch (ErrorException $databaseException) {
			echo json_encode(["code" => -1, "msg" => $language["databaseError"]]);
		}
	} else {
		echo json_encode(["code" => -1, "msg" => $language["invalidRequest"]]);
	}
?>