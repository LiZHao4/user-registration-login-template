<?php
	require 'main.php';
	header("Content-Type: application/json");
	if ($_SERVER["REQUEST_METHOD"] === "POST") {
		if (isset($_COOKIE["_"]) && isset($_POST["target"]) && isset($_POST["action"])) {
			mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
			$config = parse_ini_file("conf/settings.ini", true);
			$host = $config["database"]["host"];
			$user = $config["database"]["user"];
			$pass = $config["database"]["pass"];
			$db = $config["database"]["db"];
			try {
				$databaseConnection = new mysqli($host, $user, $pass, $db);
				$stmt = $databaseConnection->prepare("SELECT user FROM user_session WHERE token = ? AND expires >= NOW()");
				$stmt->bind_param("s", $_COOKIE["_"]);
				$stmt->execute();
				$stmt->store_result();
				$stmt->bind_result($id);
				if (!$stmt->fetch()) {
					$stmt->close();
					http_response_code(404);
					echo json_encode(["code" => -1, "msg" => "未找到匹配的用户，请检查登录信息。"]);
					return;
				}
				$stmt->close();
				try {
					$databaseConnection->begin_transaction();
					switch ($_POST["action"]) {
						case "add":
							if ($id == $_POST["target"]) {
								http_response_code(400);
								echo json_encode(["code" => -1, "msg" => "无法添加自己为好友。"]);
								break;
							}
							$targetCheckStmt = $databaseConnection->prepare("SELECT id FROM users WHERE id = ?");
							$targetCheckStmt->bind_param("i", $_POST["target"]);
							$targetCheckStmt->execute();
							$targetResult = $targetCheckStmt->get_result();
							$targetCheckStmt->close();
							if ($targetResult->num_rows == 0) {
								http_response_code(404);
								echo json_encode(["code" => -1, "msg" => "目标用户不存在。"]);
								break;
							}
							$isFriendStmt = $databaseConnection->prepare("SELECT COUNT(*) FROM friendships WHERE source = ? AND target = ? OR source = ? AND target = ?");
							$isFriendStmt->bind_param("iiii", $id, $_POST["target"], $_POST["target"], $id);
							$isFriendStmt->execute();
							$isFriendStmt->bind_result($isFriendCount);
							$isFriendStmt->fetch();
							$isFriendStmt->close();
							$checkStmt = $databaseConnection->prepare("SELECT COUNT(*) FROM friend_requests WHERE source = ? AND target = ? OR source = ? AND target = ?");
							$checkStmt->bind_param("iiii", $id, $_POST["target"], $_POST["target"], $id);
							$checkStmt->execute();
							$checkStmt->bind_result($count);
							$checkStmt->fetch();
							$checkStmt->close();
							if ($isFriendCount > 0 || $count > 0) {
								http_response_code(400);
								echo json_encode(["code" => -1, "msg" => "您已经发送了好友请求，或对方已向你发送好友请求，或已经是好友了。"]);
								break;
							}
							$addStmt = $databaseConnection->prepare("INSERT INTO friend_requests (source, target) VALUES (?, ?)");
							$addStmt->bind_param("ii", $id, $_POST["target"]);
							$addStmt->execute();
							$addStmt->close();
							echo json_encode(["code" => 1, "msg" => "发送好友请求成功。"]);
							break;
						case "delete":
							$lookupFileStmt = $databaseConnection->prepare("SELECT multi FROM chats WHERE session = ? AND multi IS NOT NULL");
							$lookupFileStmt->bind_param("i", $_POST["target"]);
							$lookupFileStmt->execute();
							$results = $lookupFileStmt->get_result()->fetch_all(MYSQLI_ASSOC);
							$lookupFileStmt->close();
							if (count($results) > 0) {
								foreach ($results as $row) {
									$fileName = $row["multi"];
									$filePath = "files/" . $fileName;
									if (file_exists($filePath)) {
										unlink($filePath);
									}
								}
							}
							$deleteChatsStmt = $databaseConnection->prepare("DELETE FROM chats WHERE session = ?");
							$deleteChatsStmt->bind_param("i", $_POST["target"]);
							$deleteChatsStmt->execute();
							$deleteChatsStmt->close();
							$friendInfoStmt = $databaseConnection->prepare("SELECT CASE WHEN source = ? THEN target ELSE source END AS friend_id FROM friendships WHERE id = ?");
							$friendInfoStmt->bind_param("ii", $id, $_POST["target"]);
							$friendInfoStmt->execute();
							$friendInfoResult = $friendInfoStmt->get_result();
							$friendData = $friendInfoResult->fetch_assoc();
							$friendInfoStmt->close();
							$friendId = $friendData["friend_id"];
							$deleteFriendshipStmt = $databaseConnection->prepare("DELETE FROM friendships WHERE id = ?");
							$deleteFriendshipStmt->bind_param("i", $_POST["target"]);
							$deleteFriendshipStmt->execute();
							$deleteFriendshipStmt->close();
							$userInfoStmt = $databaseConnection->prepare("SELECT nick, user_avatar FROM users WHERE id = ?");
							$userInfoStmt->bind_param("i", $id);
							$userInfoStmt->execute();
							$userInfoResult = $userInfoStmt->get_result();
							$userData = $userInfoResult->fetch_assoc();
							$userInfoStmt->close();
							$contentJson = json_encode([
								"title" => "好友关系变更",
								"content" => "%n已从好友列表中移除了你",
								"avatar" => $userData["user_avatar"],
								"user" => [
									"nick" => $userData["nick"],
									"id" => $id,
									"avatar" => $userData["user_avatar"]
								],
								"type" => 1
							], JSON_UNESCAPED_UNICODE);
							$messageStmt = $databaseConnection->prepare("INSERT INTO system_messages (target, content) VALUES (?, ?)");
							$messageStmt->bind_param("is", $friendId, $contentJson);
							$messageStmt->execute();
							$messageStmt->close();
							echo json_encode(["code" => 1, "msg" => "删除好友成功。"]);
							break;
						case "agree":
							$requestStmt = $databaseConnection->prepare("SELECT sent_at, source, target FROM friend_requests WHERE id = ?");
							$requestStmt->bind_param("i", $_POST["target"]);
							$requestStmt->execute();
							$requestStmt->store_result();
							$requestStmt->bind_result($sentAt, $source, $target);
							if ($requestStmt->fetch()) {
								$requestStmt->free_result();
								$query = "SELECT id FROM friendships UNION SELECT id FROM `groups` ORDER BY id";
								$res = $databaseConnection->prepare($query);
								$res->execute();
								$resultSet = $res->get_result();
								$rows = [];
								while ($row = $resultSet->fetch_assoc()) {
									$rows[] = $row;
								}
								$resultSet->free();
								$res->close();
								$ids = array_column($rows, 'id');
								$friendId = findMissingNumber($ids);
								$insertStmt = $databaseConnection->prepare("INSERT INTO friendships (id, source, target, request_time) VALUES (?, ?, ?, ?)");
								$insertStmt->bind_param("iiis", $friendId, $source, $target, $sentAt);
								$insertStmt->execute();
								$insertStmt->close();
								$deleteStmt = $databaseConnection->prepare("DELETE FROM friend_requests WHERE id = ?");
								$deleteStmt->bind_param("i", $_POST["target"]);
								$deleteStmt->execute();
								$deleteStmt->close();
								echo json_encode(["code" => 1, "msg" => "接受好友请求成功。"]);
							} else {
								echo json_encode(["code" => -1, "msg" => "好友请求不存在。"]);
							}
							$requestStmt->close();
							break;
						case "refuse":
							$checkRefuseStmt = $databaseConnection->prepare("SELECT id FROM friend_requests WHERE id = ? AND target = ?");
							$checkRefuseStmt->bind_param("ii", $_POST["target"], $id);
							$checkRefuseStmt->execute();
							$checkRefuseStmt->store_result();
							if ($checkRefuseStmt->num_rows === 0) {
								$checkRefuseStmt->close();
								http_response_code(400);
								echo json_encode(["code" => -1, "msg" => "拒绝失败：请求不存在或您无权操作。"]);
								break;
							}
							$checkRefuseStmt->close();
							$deleteStmt = $databaseConnection->prepare("DELETE FROM friend_requests WHERE id = ?");
							$deleteStmt->bind_param("i", $_POST["target"]);
							$deleteStmt->execute();
							if ($deleteStmt->affected_rows > 0) {
								echo json_encode(["code" => 1, "msg" => "拒绝好友请求成功。"]);
							} else {
								http_response_code(400);
								echo json_encode(["code" => -1, "msg" => "拒绝失败：请求可能已被处理。"]);
							}
							$deleteStmt->close();
							break;
						case "revoke":
							$checkRevokeStmt = $databaseConnection->prepare("SELECT id FROM friend_requests WHERE id = ? AND source = ?");
							$checkRevokeStmt->bind_param("ii", $_POST["target"], $id);
							$checkRevokeStmt->execute();
							$checkRevokeStmt->store_result();
							if ($checkRevokeStmt->num_rows === 0) {
								$checkRevokeStmt->close();
								http_response_code(400);
								echo json_encode(["code" => -1, "msg" => "撤回失败：请求不存在或您无权操作。"]);
								break;
							}
							$checkRevokeStmt->close();
							$deleteStmt = $databaseConnection->prepare("DELETE FROM friend_requests WHERE id = ?");
							$deleteStmt->bind_param("i", $_POST["target"]);
							$deleteStmt->execute();
							if ($deleteStmt->affected_rows > 0) {
								echo json_encode(["code" => 1, "msg" => "撤回好友请求成功。"]);
							} else {
								http_response_code(400);
								echo json_encode(["code" => -1, "msg" => "撤回失败：请求可能已被处理。"]);
							}
							$deleteStmt->close();
							break;
						default:
							http_response_code(400);
							echo json_encode(["code" => -1, "msg" => "请求方法不正确或缺少必要的参数。"]);
					}
					$databaseConnection->commit();
				} catch (Exception $e) {
					$databaseConnection->rollback();
					http_response_code(500);
					echo json_encode(["code" => -1, "msg" => "数据库错误。"]);
				}
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