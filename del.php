<?php
	header("Content-Type: application/json");
	if ($_SERVER["REQUEST_METHOD"] === "POST") {
		if (isset($_COOKIE["_"])) {
			mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
			$config = parse_ini_file("conf/settings.ini", true);
			$host = $config["database"]["host"];
			$user = $config["database"]["user"];
			$pass = $config["database"]["pass"];
			$db = $config["database"]["db"];
			try {
				$dbConnection = new mysqli($host, $user, $pass, $db);
				$token = $_COOKIE["_"];
				$sessionStmt = $dbConnection->prepare("SELECT user FROM user_session WHERE token = ? AND expires >= NOW()");
				$sessionStmt->bind_param("s", $token);
				$sessionStmt->execute();
				$sessionRes = $sessionStmt->get_result();
				if ($sessionRes->num_rows > 0) {
					$sessionRow = $sessionRes->fetch_assoc();
					$userId = $sessionRow["user"];
				} else {
					http_response_code(404);
					echo json_encode(["code" => -1, "msg" => "未找到匹配的用户信息。"]);
					return;
				}
				$sessionStmt->close();
				if ($userId) {
					$avatarStmt = $dbConnection->prepare("SELECT nick, user_avatar FROM users WHERE id = ?");
					$avatarStmt->bind_param("i", $userId);
					$avatarStmt->execute();
					$res = $avatarStmt->get_result();
					$userNick = null;
					$avatar = null;
					if ($res->num_rows > 0) {
						$avatarRow = $res->fetch_assoc();
						$userNick = $avatarRow["nick"];
						$avatar = $avatarRow["user_avatar"];
					}
					$avatarStmt->close();
					$dbConnection->begin_transaction();
					try {
						$filesToDelete = [];
						$friendshipStmt = $dbConnection->prepare("SELECT id, source, target FROM friendships WHERE source = ? OR target = ?");
						$friendshipStmt->bind_param("ii", $userId, $userId);
						$friendshipStmt->execute();
						$friendshipRes = $friendshipStmt->get_result();
						while ($friendshipRow = $friendshipRes->fetch_assoc()) {
							$friendshipId = $friendshipRow["id"];
							$friendId = ($friendshipRow["source"] == $userId) ? $friendshipRow["target"] : $friendshipRow["source"];
							$sysMsg = json_encode([
								"type" => 0,
								"content" => "用户“" . $userNick . "”已注销，你已与该用户断开好友关系。",
								"title" => "用户注销通知",
							]);
							$sysStmt = $dbConnection->prepare("INSERT INTO system_messages (content, target) VALUES (?, ?)");
							$sysStmt->bind_param("si", $sysMsg, $friendId);
							$sysStmt->execute();
							$sysStmt->close();
							$delFriendshipStmt = $dbConnection->prepare("DELETE FROM friendships WHERE id = ?");
							$delFriendshipStmt->bind_param("i", $friendshipId);
							$delFriendshipStmt->execute();
							$delFriendshipStmt->close();
							$delChatStmt = $dbConnection->prepare("DELETE FROM chats WHERE session = ?");
							$delChatStmt->bind_param("i", $friendshipId);
							$delChatStmt->execute();
							$delChatStmt->close();
							$friendFileStmt = $dbConnection->prepare("SELECT multi FROM chats WHERE session = ? AND type = 2");
							$friendFileStmt->bind_param("i", $friendshipId);
							$friendFileStmt->execute();
							$friendFileRes = $friendFileStmt->get_result();
							while ($friendFileRow = $friendFileRes->fetch_assoc()) {
								$filesToDelete[] = "files/" . $friendFileRow['multi'];
							}
							$friendFileStmt->close();
						}
						$friendshipStmt->close();
						$groupStmt = $dbConnection->prepare("SELECT `group`, role FROM group_members WHERE user = ?");
						$groupStmt->bind_param("i", $userId);
						$groupStmt->execute();
						$groupRes = $groupStmt->get_result();
						while ($groupRow = $groupRes->fetch_assoc()) {
							$groupId = $groupRow["group"];
							$role = $groupRow["role"];
							$delMemberStmt = $dbConnection->prepare("DELETE FROM group_members WHERE `group` = ? AND user = ?");
							$delMemberStmt->bind_param("i", $groupId, $userId);
							$delMemberStmt->execute();
							$delMemberStmt->close();
							if ($role === 'owner') {
								$newOwnerStmt = $dbConnection->prepare("SELECT user FROM group_members WHERE `group` = ? AND user != ? ORDER BY user LIMIT 1");
								$newOwnerStmt->bind_param("ii", $groupId, $userId);
								$newOwnerStmt->execute();
								$newOwnerRes = $newOwnerStmt->get_result();
								if ($newOwnerRes->num_rows > 0) {
									$newOwnerRow = $newOwnerRes->fetch_assoc();
									$newOwnerId = $newOwnerRow['user'];
									$updateOwnerStmt = $dbConnection->prepare("UPDATE group_members SET role = 'owner' WHERE `group` = ? AND user = ?");
									$updateOwnerStmt->bind_param("ii", $groupId, $newOwnerId);
									$updateOwnerStmt->execute();
									$updateOwnerStmt->close();
								}
								$newOwnerStmt->close();
							}
							$countStmt = $dbConnection->prepare("SELECT COUNT(*) AS member_count FROM group_members WHERE `group` = ?");
							$countStmt->bind_param("i", $groupId);
							$countStmt->execute();
							$countRes = $countStmt->get_result();
							$countRow = $countRes->fetch_assoc();
							$countStmt->close();
							if ($countRow["member_count"] == 0) {
								$delGroupStmt = $dbConnection->prepare("DELETE FROM `groups` WHERE id = ?");
								$delGroupStmt->bind_param("i", $groupId);
								$delGroupStmt->execute();
								$delGroupStmt->close();
								$groupFileStmt = $dbConnection->prepare("SELECT multi FROM chats WHERE session = ? AND type = 2");
								$groupFileStmt->bind_param("i", $groupId);
								$groupFileStmt->execute();
								$groupFileRes = $groupFileStmt->get_result();
								while ($groupFileRow = $groupFileRes->fetch_assoc()) {
									$filesToDelete[] = "files/" . $groupFileRow['multi'];
								}
								$groupFileStmt->close();
							} else {
								$logoffMsg = json_encode([
									"type" => "logoff",
									"nick" => $userNick
								]);
								$chatStmt = $dbConnection->prepare("INSERT INTO chats (session, content, sender, type) VALUES (?, ?, ?, 4)");
								$chatStmt->bind_param("isi", $groupId, $logoffMsg, $userId);
								if (!$chatStmt->execute()) {
									throw new Exception("插入群组注销消息失败: " . $chatStmt->error);
								}
								$chatStmt->close();
							}
						}
						$groupStmt->close();
						$deleteFriendStmt = $dbConnection->prepare("DELETE FROM friend_requests WHERE source = ? OR target = ?");
						$deleteFriendStmt->bind_param("ii", $userId, $userId);
						$deleteFriendStmt->execute();
						$deleteFriendStmt->close();
						$deleteSessionStmt = $dbConnection->prepare("DELETE FROM user_session WHERE user = ?");
						$deleteSessionStmt->bind_param("i", $userId);
						$deleteSessionStmt->execute();
						$deleteSessionStmt->close();
						$deleteSysMsgStmt = $dbConnection->prepare("DELETE FROM system_messages WHERE target = ?");
						$deleteSysMsgStmt->bind_param("i", $userId);
						$deleteSysMsgStmt->execute();
						$deleteSysMsgStmt->close();
						$deleteReadStmt = $dbConnection->prepare("DELETE FROM message_read_status WHERE user_id = ?");
						$deleteReadStmt->bind_param("i", $userId);
						$deleteReadStmt->execute();
						$deleteReadStmt->close();
						$deleteRemarkStmt = $dbConnection->prepare("DELETE FROM user_remarks WHERE user_id = ? OR target_user_id = ?");
						$deleteRemarkStmt->bind_param("ii", $userId, $userId);
						$deleteRemarkStmt->execute();
						$deleteRemarkStmt->close();
						$deleteStmt = $dbConnection->prepare("DELETE FROM users WHERE id = ?");
						$deleteStmt->bind_param("i", $userId);
						$deleteStmt->execute();
						if ($deleteStmt->affected_rows > 0) {
							$dbConnection->commit();
							foreach ($filesToDelete as $filePath) {
								if (file_exists($filePath)) {
									@unlink($filePath);
								}
							}
							if (!is_null($avatar)){
								$avatarPath = "avatar/" . $avatar . ".jpg";
								if (file_exists($avatarPath)) {
									@unlink($avatarPath);
								}
							}
							echo json_encode(["code" => 1, "msg" => "账户注销成功。"]);
						} else {
							$dbConnection->rollback();
							http_response_code(500);
							echo json_encode(["code" => -1, "msg" => "账户注销失败。"]);
						}
						$deleteStmt->close();
					} catch (Exception $e) {
						$dbConnection->rollback();
						http_response_code(500);
						echo json_encode(["code" => -1, "msg" => "数据库错误。" . $e->getMessage() . "\n" . $e->getLine()]);
					}
				} else {
					http_response_code(401);
					echo json_encode(["code" => 0, "msg" => "用户不存在或token无效，请重新登录。"]);
				}
			} catch (mysqli_sql_exception $sqlException) {
				http_response_code(500);
				echo json_encode(["code" => -1, "msg" => "数据库错误。" . $sqlException->getMessage() . "\n" . $sqlException->getLine()]);
			} finally {
				if (isset($dbConnection)) {
					$dbConnection->close();
				}
			}
		} else {
			http_response_code(401);
			echo json_encode(["code" => -1, "msg" => "缺少必要的参数。"]);
		}
	} else {
		http_response_code(405);
		echo json_encode(["code" => -1, "msg" => "请求方法不正确。"]);
	}
?>