<?php
	header("Content-Type: application/json");
	if ($_SERVER["REQUEST_METHOD"] === "GET") {
		if (isset($_COOKIE["_"])) {
			mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
			$config = parse_ini_file("conf/settings.ini", true);
			$host = $config["database"]["host"];
			$user = $config["database"]["user"];
			$pass = $config["database"]["pass"];
			$db = $config["database"]["db"];
			try {
				$databaseConnection = new mysqli($host, $user, $pass, $db);
				$token = $_COOKIE["_"];
				$stmt = $databaseConnection->prepare("SELECT user FROM user_session WHERE token = ? AND expires >= NOW()");
				$stmt->bind_param("s", $token);
				$stmt->execute();
				$res = $stmt->get_result();
				if ($res->num_rows > 0) {
					$row = $res->fetch_assoc();
					$userId = $row["user"];
				} else {
					http_response_code(401);
					echo json_encode(["code" => 0, "msg" => "用户不存在或token无效，请重新登录。"]);
					$stmt->close();
					$databaseConnection->close();
					return;
				}
				function getUserRemark($conn, $viewerId, $targetId) {
					$stmt = $conn->prepare("SELECT remark FROM user_remarks WHERE user_id = ? AND target_user_id = ?");
					$stmt->bind_param("ii", $viewerId, $targetId);
					$stmt->execute();
					$res = $stmt->get_result();
					return ($res->num_rows > 0) ? $res->fetch_assoc()["remark"] : null;
				}
				function getGroupNickname($conn, $groupId, $userId) {
					$stmt = $conn->prepare("SELECT group_nickname FROM group_members WHERE `group` = ? AND user = ?");
					$stmt->bind_param("ii", $groupId, $userId);
					$stmt->execute();
					$res = $stmt->get_result();
					return ($res->num_rows > 0) ? $res->fetch_assoc()["group_nickname"] : null;
				}
				function getDisplayName($conn, $viewerId, $targetId, $groupId = null) {
					$remark = getUserRemark($conn, $viewerId, $targetId);
					if ($remark) return $remark;
					if ($groupId) {
						$groupNick = getGroupNickname($conn, $groupId, $targetId);
						if ($groupNick) return $groupNick;
					}
					$stmt = $conn->prepare("SELECT nick FROM users WHERE id = ?");
					$stmt->bind_param("i", $targetId);
					$stmt->execute();
					$res = $stmt->get_result();
					return ($res->num_rows > 0) ? $res->fetch_assoc()["nick"] : "未知用户";
				}
				$friendsStmt = $databaseConnection->prepare("SELECT id, CASE WHEN source = ? THEN target ELSE source END AS friend_id, UNIX_TIMESTAMP(allowed_time) AS allowed_time FROM friendships WHERE source = ? OR target = ?");
				$friendsStmt->bind_param("iii", $userId, $userId, $userId);
				$friendsStmt->execute();
				$friendsResult = $friendsStmt->get_result();
				$friends = [];
				if ($friendsResult->num_rows > 0) {
					while ($friendRow = $friendsResult->fetch_assoc()) {
						$friendId = $friendRow["friend_id"];
						$displayName = getDisplayName($databaseConnection, $userId, $friendId);
						$avatarStmt = $databaseConnection->prepare("SELECT user_avatar AS avatar FROM users WHERE id = ?");
						$avatarStmt->bind_param("i", $friendId);
						$avatarStmt->execute();
						$avatarResult = $avatarStmt->get_result();
						$avatarRow = $avatarResult->fetch_assoc();
						$lastMsgStmt = $databaseConnection->prepare("SELECT content, UNIX_TIMESTAMP(sent_at) AS sent_at, type, sender FROM chats WHERE session = ? ORDER BY sent_at DESC LIMIT 1");
						$lastMsgStmt->bind_param("i", $friendRow["id"]);
						$lastMsgStmt->execute();
						$lastMsgResult = $lastMsgStmt->get_result();
						$lastMsgRow = $lastMsgResult->fetch_assoc();
						$unreadStmt = $databaseConnection->prepare("SELECT COUNT(*) AS count FROM chats WHERE session = ? AND id > (SELECT COALESCE((SELECT max_id FROM message_read_status WHERE session_id = ? AND user_id = ?), 0))");
						$unreadStmt->bind_param("iii", $friendRow["id"], $friendRow["id"], $userId);
						$unreadStmt->execute();
						$unreadResult = $unreadStmt->get_result();
						$unreadRow = $unreadResult->fetch_assoc();
						$friends[] = [
							"id" => $friendRow["id"],
							"avatar" => $avatarRow["avatar"],
							"nick" => $displayName,
							"time" => $lastMsgRow ? $lastMsgRow["sent_at"] : $friendRow["allowed_time"],
							"content" => $lastMsgRow ? $lastMsgRow["content"] : "",
							"msg_type" => $lastMsgRow ? $lastMsgRow["type"] : null,
							"msg_sender" => $lastMsgRow ? $lastMsgRow["sender"] : null,
							"count" => $unreadRow["count"],
							"type" => "friend",
							"friend_id" => $friendId
						];
						$avatarStmt->close();
						$lastMsgStmt->close();
						$unreadStmt->close();
					}
				}
				$friendsStmt->close();
				$groupsStmt = $databaseConnection->prepare("SELECT id, group_name, group_avatar FROM `groups` WHERE id IN (SELECT `group` FROM group_members WHERE user = ?)");
				$groupsStmt->bind_param("i", $userId);
				$groupsStmt->execute();
				$groupsResult = $groupsStmt->get_result();
				if ($groupsResult->num_rows > 0) {
					while ($groupRow = $groupsResult->fetch_assoc()) {
						$joinedAtStmt = $databaseConnection->prepare("SELECT UNIX_TIMESTAMP(joined_at) AS joined_at FROM group_members WHERE user = ? AND `group` = ?");
						$joinedAtStmt->bind_param("ii", $userId, $groupRow["id"]);
						$joinedAtStmt->execute();
						$joinedAtResult = $joinedAtStmt->get_result();
						$joinedAtRow = $joinedAtResult->fetch_assoc();
						$joinedAtStmt->close();
						$lastMessageStmt = $databaseConnection->prepare("SELECT content, UNIX_TIMESTAMP(sent_at) AS sent_at, type, sender FROM chats WHERE session = ? ORDER BY sent_at DESC LIMIT 1");
						$lastMessageStmt->bind_param("i", $groupRow["id"]);
						$lastMessageStmt->execute();
						$lastMessageResult = $lastMessageStmt->get_result();
						$lastMessageRow = $lastMessageResult->fetch_assoc();
						$lastMessageStmt->close();
						$unreadCountStmt = $databaseConnection->prepare("SELECT COUNT(*) AS count FROM chats WHERE session = ? AND id > (SELECT COALESCE((SELECT max_id FROM message_read_status WHERE session_id = ? AND user_id = ?), 0))");
						$unreadCountStmt->bind_param("iii", $groupRow["id"], $groupRow["id"], $userId);
						$unreadCountStmt->execute();
						$unreadCountResult = $unreadCountStmt->get_result();
						$unreadCountRow = $unreadCountResult->fetch_assoc();
						$unreadCountStmt->close();
						$memberCountStmt = $databaseConnection->prepare("SELECT COUNT(*) AS member_count FROM group_members WHERE `group` = ?");
						$memberCountStmt->bind_param("i", $groupRow["id"]);
						$memberCountStmt->execute();
						$memberCountResult = $memberCountStmt->get_result();
						$memberCountRow = $memberCountResult->fetch_assoc();
						$msgSenderName = null;
						if ($lastMessageRow) {
							$msgSenderName = getDisplayName($databaseConnection, $userId, $lastMessageRow["sender"], $groupRow["id"]);
						}
						$jsonDecoded = json_decode($lastMessageRow["content"], true);
						$wouldAdd = [
							"id" => $groupRow["id"],
							"nick" => $groupRow["group_name"],
							"avatar" => $groupRow["group_avatar"],
							"time" => $joinedAtRow["joined_at"],
							"content" => "",
							"count" => $unreadCountRow["count"],
							"type" => "group",
							"member_count" => $memberCountRow["member_count"]
						];
						if ($lastMessageRow) {
							$wouldAdd["time"] = $lastMessageRow["sent_at"];
							$wouldAdd["content"] = $lastMessageRow["content"];
							$wouldAdd["msg_type"] = $lastMessageRow["type"];
							$wouldAdd["msg_sender"] = $lastMessageRow["sender"];
							$wouldAdd["msg_nick"] = $msgSenderName;
						}
						if ($lastMessageRow && $lastMessageRow["type"] == 4) {
							$sysMsgContent = json_decode($lastMessageRow["content"], true);
							if ($sysMsgContent && isset($sysMsgContent["target"])) {
								$sysMsgType = $sysMsgContent["type"];
								$targetUser = $sysMsgContent["target"];
								if ($targetUser) {
									$targetName = getDisplayName($databaseConnection, $userId, $targetUser, $groupRow["id"]);
                                	$wouldAdd["inner_nick"] = $targetName;
								}
							}
						}
						$friends[] = $wouldAdd;
					}
				}
				$groupsStmt->close();
				usort($friends, function($a, $b) {
					return $b["time"] - $a["time"];
				});
				echo json_encode(["code" => 1, "msg" => "好友列表获取成功。", "data" => $friends]);
				$stmt->close();
				$databaseConnection->close();
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