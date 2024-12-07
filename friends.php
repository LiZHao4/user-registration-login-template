<?php
	if (isset($_GET["lang"]) && file_exists("languages/" . $_GET["lang"] . ".json")) {
		$language = json_decode(file_get_contents("languages/" . $_GET["lang"] . ".json"), true);
	} else {
		$language = json_decode(file_get_contents("languages/zh-CN.json"), true);
	}
	header('Content-Type: application/json');
	if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['token']) && isset($_POST['id']) && isset($_POST['target']) && isset($_POST['action'])) {
		mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
		$config = parse_ini_file('conf/settings.ini', true);
		$host = $config['database']['host'];
		$user = $config['database']['user'];
		$pass = $config['database']['pass'];
		$db = $config['database']['db'];
		try {
			$databaseConnection = new mysqli($host, $user, $pass, $db);
			$stmt = $databaseConnection->prepare("SELECT token FROM users WHERE id = ?");
			$stmt->bind_param("i", $_POST['id']);
			$stmt->execute();
			$stmt->store_result();
			$stmt->bind_result($dbToken);
			if ($stmt->fetch() && hash_equals($_POST['token'], $dbToken)) {
				$stmt->close();
				try {
					$databaseConnection->begin_transaction();
					switch ($_POST['action']) {
						case 'add':
							if ($_POST['id'] == $_POST['target']) {
								echo json_encode(["code" => -1, "msg" => $language["cannotAddSelf"]]);
								break;
							}
							$isFriendStmt = $databaseConnection->prepare("SELECT COUNT(*) FROM friendships WHERE source = ? AND target = ? OR source = ? AND target = ?");
							$isFriendStmt->bind_param("iiii", $_POST['id'], $_POST['target'], $_POST['target'], $_POST['id']);
							$isFriendStmt->execute();
							$isFriendStmt->bind_result($isFriendCount);
							$isFriendStmt->fetch();
							$isFriendStmt->close();
							$checkStmt = $databaseConnection->prepare("SELECT COUNT(*) FROM friend_requests WHERE source = ? AND target = ? OR source = ? AND target = ?");
							$checkStmt->bind_param("iiii", $_POST['id'], $_POST['target'], $_POST['target'], $_POST['id']);
							$checkStmt->execute();
							$checkStmt->bind_result($count);
							$checkStmt->fetch();
							$checkStmt->close();
							if ($isFriendCount > 0 || $count > 0) {
								echo json_encode(["code" => -1, "msg" => $language["requestAlreadySent"]]);
								break;
							}
							$addStmt = $databaseConnection->prepare("INSERT INTO friend_requests (source, target) VALUES (?, ?)");
							$addStmt->bind_param("ii", $_POST['id'], $_POST['target']);
							$addStmt->execute();
							$addStmt->close();
							echo json_encode(["code" => 1, "msg" => $language["successMessage"]]);
							break;
						case 'delete':
							$deleteChatsStmt = $databaseConnection->prepare("DELETE FROM chats WHERE session = ?");
							$deleteChatsStmt->bind_param("i", $_POST['target']);
							$deleteChatsStmt->execute();
							$deleteChatsStmt->close();
							$deleteFriendshipStmt = $databaseConnection->prepare("DELETE FROM friendships WHERE id = ?");
							$deleteFriendshipStmt->bind_param("i", $_POST['target']);
							$deleteFriendshipStmt->execute();
							$deleteFriendshipStmt->close();
							echo json_encode(["code" => 1, "msg" => $language["deleteSuccessMessage"]]);
							break;
						case 'agree':
							$requestStmt = $databaseConnection->prepare("SELECT sent_at, source, target FROM friend_requests WHERE id = ?");
							$requestStmt->bind_param("i", $_POST['target']);
							$requestStmt->execute();
							$requestStmt->bind_result($sentAt, $source, $tagret);
							if ($requestStmt->fetch()) {
								$requestStmt->close();
								$updateStmt = $databaseConnection->prepare("UPDATE friend_requests SET received_at = NOW() WHERE id = ?");
								$updateStmt->bind_param("i", $_POST['target']);
								$updateStmt->execute();
								$updateStmt->close();
								$receivedStmt = $databaseConnection->prepare("SELECT received_at FROM friend_requests WHERE id = ?");
								$receivedStmt->bind_param("i", $_POST['target']);
								$receivedStmt->execute();
								$receivedStmt->bind_result($receivedAt);
								$receivedStmt->fetch();
								$receivedStmt->close();
								$insertStmt = $databaseConnection->prepare("INSERT INTO friendships (source, target, request_time, allowed_time) VALUES (?, ?, ?, ?)");
								$insertStmt->bind_param("iiss", $source, $tagret, $sentAt, $receivedAt);
								$insertStmt->execute();
								$insertStmt->close();
								$deleteStmt = $databaseConnection->prepare("DELETE FROM friend_requests WHERE id = ?");
								$deleteStmt->bind_param("i", $_POST['target']);
								$deleteStmt->execute();
								$deleteStmt->close();
							}
							echo json_encode(["code" => 1, "msg" => $language["acceptSuccessMessage"]]);
							break;
						case 'refuse':
							$deleteStmt = $databaseConnection->prepare("DELETE FROM friend_requests WHERE id = ?");
							$deleteStmt->bind_param("i", $_POST['target']);
							$deleteStmt->execute();
							$deleteStmt->close();
							echo json_encode(["code" => 1, "msg" => $language["refuseSuccessMessage"]]);
							break;
						default:
							echo json_encode(["code" => -1, "msg" => $language["invalidRequest"]]);
					}
					$databaseConnection->commit();
				} catch (Exception $e) {
					$databaseConnection->rollback();
					echo json_encode(["code" => -1, "msg" => $language["databaseError"] . " " . $e->getMessage()]);
				}
			} else {
				echo json_encode(["code" => 0, "msg" => $language["invalidUserOrToken"]]);
			}
		} catch (mysqli_sql_exception $sqlException) {
			echo json_encode(["code" => -1, "msg" => $language["databaseError"] . " " . $sqlException->getMessage()]);
		}
	} else {
		echo json_encode(["code" => -1, "msg" => $language["invalidRequest"]]);
	}
?>