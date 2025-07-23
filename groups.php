<?php
	require 'main.php';
	header("Content-Type: application/json");
	if ($_SERVER["REQUEST_METHOD"] === "POST") {
		if (isset($_COOKIE["_"]) && isset($_POST["action"]) && isset($_POST["extra"])) {
			if (trim($_POST["extra"]) == "") {
				http_response_code(400);
				echo json_encode(["code" => -1, "msg" => "群组额外信息为空。"]);
				exit;
			}
			mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
			$config = parse_ini_file("conf/settings.ini", true);
			$host = $config["database"]["host"];
			$user = $config["database"]["user"];
			$pass = $config["database"]["pass"];
			$db = $config["database"]["db"];
			try {
				$conn = new mysqli($host, $user, $pass, $db);
				$stmt = $conn->prepare("SELECT user FROM user_session WHERE token = ? AND expires >= NOW()");
				$stmt->bind_param("s", $_COOKIE["_"]);
				$stmt->execute();
				$result = $stmt->get_result();
				$data = $result->fetch_assoc();
				$userId = null;
				if ($data) {
					$userId = $data["user"];
				}
				if ($userId) {
					switch ($_POST["action"]) {
						case "create":
							$query = "SELECT id FROM friendships UNION SELECT id FROM `groups` ORDER BY id";
							$res = $conn->prepare($query);
							$res->execute();
							$resultSet = $res->get_result();
							$rows = [];
							while ($row = $resultSet->fetch_assoc()) {
								$rows[] = $row;
							}
							$res->close();
							$ids = array_column($rows, "id");
							$groupId = findMissingNumber($ids);
							$stmt2 = $conn->prepare("INSERT INTO `groups` (id, group_name, creator) VALUES (?, ?, ?)");
							$stmt2->bind_param("iss", $groupId, $_POST["extra"], $userId);
							$stmt2->execute();
							$stmt2->close();
							$stmt3 = $conn->prepare("INSERT INTO group_members (`group`, user, role) VALUES (?, ?, 'owner')");
							$stmt3->bind_param("ii", $groupId, $userId);
							$stmt3->execute();
							$stmt3->close();
							echo json_encode(["code" => 1, "msg" => "群组创建成功。", "groupId" => $groupId]);
							break;
						case "delete":
							$groupId = intval($_POST["extra"]);
							$stmtCheckMember = $conn->prepare("SELECT role FROM group_members WHERE `group` = ? AND user = ?");
							$stmtCheckMember->bind_param("ii", $groupId, $userId);
							$stmtCheckMember->execute();
							$memberResult = $stmtCheckMember->get_result();
							if ($memberResult->num_rows == 0) {
								http_response_code(404);
								echo json_encode(["code" => -1, "msg" => "你不在群组中。"]);
								$stmtCheckMember->close();
								break;
							}
							$roleData = $memberResult->fetch_assoc();
							$role = $roleData["role"];
							$stmtCheckMember->close();
							if ($role == "owner") {
								$stmtQueryGroupName = $conn->prepare("SELECT group_name FROM `groups` WHERE id = ?");
								$stmtQueryGroupName->bind_param("i", $groupId);
								$stmtQueryGroupName->execute();
								$stmtQueryGroupNameResult = $stmtQueryGroupName->get_result();
								$groupName = $stmtQueryGroupNameResult->fetch_assoc()["group_name"];
								$stmtQueryGroupName->close();
								$stmtDeleteGroup = $conn->prepare("DELETE FROM `groups` WHERE id = ?");
								$stmtDeleteGroup->bind_param("i", $groupId);
								$stmtDeleteGroup->execute();
								$stmtDeleteGroup->close();
								$stmtQueryUsers = $conn->prepare("SELECT user FROM group_members WHERE `group` = ?");
								$stmtQueryUsers->bind_param("i", $groupId);
								$stmtQueryUsers->execute();
								$stmtQueryUsersResult = $stmtQueryUsers->get_result();
								while ($row = $stmtQueryUsersResult->fetch_assoc()) {
									$targetUser = $row["user"];
									if ($targetUser == $userId) {
									    continue;
									}
									$jsonData = [
										"type" => 0,
										"title" => "群组解散通知",
										"content" => "群组“" . $groupName . "”已解散。"
									];
									$stmtInsertSystemMessage = $conn->prepare("INSERT INTO system_messages (content, target) VALUES (?, ?)");
									$stmtInsertSystemMessage->bind_param("si", json_encode($jsonData), $targetUser);
									$stmtInsertSystemMessage->execute();
									$stmtInsertSystemMessage->close();
								}
								$stmtDeleteMembers = $conn->prepare("DELETE FROM group_members WHERE `group` = ?");
								$stmtDeleteMembers->bind_param("i", $groupId);
								$stmtDeleteMembers->execute();
								$stmtDeleteMembers->close();
								$stmtQueryMulties = $conn->prepare("SELECT multi FROM chats WHERE session = ? AND type = 2");
								$stmtQueryMulties->bind_param("i", $groupId);
								$stmtQueryMulties->execute();
								$stmtQueryMultiesResult = $stmtQueryMulties->get_result();
								while ($row = $stmtQueryMultiesResult->fetch_assoc()) {
									$multi = $row["multi"];
									unlink("files/" . $multi);
								}
								$stmtQueryMulties->close();
								$stmtDeleteChats = $conn->prepare("DELETE FROM chats WHERE session = ?");
								$stmtDeleteChats->bind_param("i", $groupId);
								$stmtDeleteChats->execute();
								$stmtDeleteChats->close();
								$stmtDeleteReadStatus = $conn->prepare("DELETE FROM message_read_status WHERE session_id = ?");
								$stmtDeleteReadStatus->bind_param("i", $groupId);
								$stmtDeleteReadStatus->execute();
								$stmtDeleteReadStatus->close();
								echo json_encode(["code" => 1, "msg" => "群组解散成功。"]);
							} else {
								http_response_code(403);
								echo json_encode(["code" => -1, "msg" => "你没有权限解散群组。"]);
								break;
							}
							break;
						case "quit":
							$groupId = intval($_POST["extra"]);
							$stmtCheckMember = $conn->prepare("SELECT role FROM group_members WHERE `group` = ? AND user = ?");
							$stmtCheckMember->bind_param("ii", $groupId, $userId);
							$stmtCheckMember->execute();
							$result = $stmtCheckMember->get_result();
							if ($result->num_rows == 0) {
								http_response_code(404);
								echo json_encode(["code" => -1, "msg" => "你不在群组中。"]);
								$stmtCheckMember->close();
								break;
							}
							$roleData = $result->fetch_assoc();
							$role = $roleData["role"];
							$stmtCheckMember->close();
							$stmtDeleteMember = $conn->prepare("DELETE FROM group_members WHERE `group` = ? AND user = ?");
							$stmtDeleteMember->bind_param("ii", $groupId, $userId);
							$stmtDeleteMember->execute();
							$stmtDeleteMember->close();
							$stmtInsertMessage = $conn->prepare("INSERT INTO chats (session, content, sender, type) VALUES (?, '{\"type\": \"quit\"}', ?, 4)");
							$stmtInsertMessage->bind_param("ii", $groupId, $userId);
							$stmtInsertMessage->execute();
							$stmtInsertMessage->close();
							$stmtDeleteReadStatus = $conn->prepare("DELETE FROM message_read_status WHERE session_id = ?");
							$stmtDeleteReadStatus->bind_param("i", $groupId);
							$stmtDeleteReadStatus->execute();
							$stmtDeleteReadStatus->close();
							$stmtCount = $conn->prepare("SELECT COUNT(*) AS member_count FROM group_members WHERE `group` = ?");
							$stmtCount->bind_param("i", $groupId);
							$stmtCount->execute();
							$countResult = $stmtCount->get_result();
							$countRow = $countResult->fetch_assoc();
							$memberCount = $countRow["member_count"];
							$stmtCount->close();
							if ($memberCount > 0) {
								if ($role == "owner") {
									$stmtNewOwner = $conn->prepare("SELECT user FROM group_members WHERE `group` = ? ORDER BY id LIMIT 1");
									$stmtNewOwner->bind_param("i", $groupId);
									$stmtNewOwner->execute();
									$ownerResult = $stmtNewOwner->get_result();
									$ownerRow = $ownerResult->fetch_assoc();
									$newOwnerId = $ownerRow["user"];
									$stmtNewOwner->close();
									$stmtUpdateOwner = $conn->prepare("UPDATE group_members SET role = 'owner' WHERE `group` = ? AND user = ?");
									$stmtUpdateOwner->bind_param("ii", $groupId, $newOwnerId);
									$stmtUpdateOwner->execute();
									$stmtUpdateOwner->close();
								}
							} else {
								$stmtDeleteGroup = $conn->prepare("DELETE FROM `groups` WHERE id = ?");
								$stmtDeleteGroup->bind_param("i", $groupId);
								$stmtDeleteGroup->execute();
								$stmtDeleteGroup->close();
								$stmtQueryMulties = $conn->prepare("SELECT multi FROM chats WHERE session = ? AND type = 2");
								$stmtQueryMulties->bind_param("i", $groupId);
								$stmtQueryMulties->execute();
								$stmtQueryMultiesResult = $stmtQueryMulties->get_result();
								while ($row = $stmtQueryMultiesResult->fetch_assoc()) {
									$multi = $row["multi"];
									unlink("files/" . $multi);
								}
								$stmtQueryMulties->close();
								$stmtDeleteChats = $conn->prepare("DELETE FROM chats WHERE session = ?");
								$stmtDeleteChats->bind_param("i", $groupId);
								$stmtDeleteChats->execute();
								$stmtDeleteChats->close();
							}
							echo json_encode(["code" => 1, "msg" => "退出群组成功。"]);
							break;
						case "add":
							$userList = trim($_POST["extra"]);
							if (empty($userList)) {
								http_response_code(400);
								echo json_encode(["code" => -1, "msg" => "用户ID列表为空。"]);
								break;
							}
							$parts = explode(";", $userList, 2);
							if (count($parts) < 2) {
								http_response_code(400);
								echo json_encode(["code" => -1, "msg" => "参数格式错误，应为'群组ID;用户ID列表'。"]);
								break;
							}
							$groupId = (int)$parts[0];
							$validateStmt = $conn->prepare("SELECT 1 FROM group_members WHERE `group` = ? AND user = ?");
							$validateStmt->bind_param("ii", $groupId, $userId);
							$validateStmt->execute();
							$validateResult = $validateStmt->get_result();
							if ($validateResult->num_rows == 0) {
								http_response_code(403);
								echo json_encode(["code" => -1, "msg" => "你不在该群组中。"]);
								$validateStmt->close();
								break;
							}
							$validateStmt->close();
							$userIds = array_map("intval", explode(",", $parts[1]));
							if (empty($userIds)) {
								http_response_code(400);
								echo json_encode(["code" => -1, "msg" => "没有有效的用户ID。"]);
								break;
							}
							$stmtCheckGroup = $conn->prepare("SELECT group_name FROM `groups` WHERE id = ?");
							$stmtCheckGroup->bind_param("i", $groupId);
							$stmtCheckGroup->execute();
							$stmtCheckResult = $stmtCheckGroup->get_result();
							if ($stmtCheckResult->num_rows === 0) {
								http_response_code(404);
								echo json_encode(["code" => -1, "msg" => "群组不存在。"]);
								$stmtCheckGroup->close();
								break;
							}
							$stmtCheckData = $stmtCheckResult->fetch_assoc();
							$groupName = $stmtCheckData["group_name"];
							$stmtCheckGroup->close();
							$success = [];
							$fail = [];
							foreach ($userIds as $targetId) {
								$stmtCheckMember = $conn->prepare("SELECT 1 FROM group_members WHERE `group` = ? AND user = ?");
								$stmtCheckMember->bind_param("ii", $groupId, $targetId);
								$stmtCheckMember->execute();
								if ($stmtCheckMember->get_result()->num_rows > 0) {
									$fail[$targetId] = "用户已在群组中。";
									$stmtCheckMember->close();
									continue;
								}
								$stmtCheckMember->close();
								$stmtCheckFriend = $conn->prepare("SELECT id FROM friendships WHERE (source = ? AND target = ?) OR (source = ? AND target = ?)");
								$stmtCheckFriend->bind_param("iiii", $userId, $targetId, $targetId, $userId);
								$stmtCheckFriend->execute();
								$friendResult = $stmtCheckFriend->get_result();
								if ($friendResult->num_rows === 0) {
									$fail[$targetId] = "不是好友关系。";
									$stmtCheckFriend->close();
									continue;
								}
								$friendData = $friendResult->fetch_assoc();
								$friendshipId = $friendData["id"];
								$stmtCheckFriend->close();
								$stmtAddMember = $conn->prepare("INSERT INTO chats (session, content, sender, type) VALUES (?, ?, ?, 3)");
								$jsonData = json_encode(["session" => $groupId, "name" => $groupName]);
								$stmtAddMember->bind_param("isi", $friendshipId, $jsonData, $userId);
								$stmtAddMember->execute();
								$newMessageId = $stmtAddMember->insert_id;
								$stmtAddMember->close();
								$updateStmt = $conn->prepare("UPDATE message_read_status SET max_id = ? WHERE user_id = ? AND session_id = ?");
								$updateStmt->bind_param("iii", $newMessageId, $userId, $friendshipId);
								$updateStmt->execute();
								$updateStmt->close();
								$success[] = $targetId;
							}
							echo json_encode(["code" => 1, "msg" => "添加成员操作完成。", "success" => $success, "fail" => (object)$fail]);
							break;
						case "remove":
							if (preg_match("/^(\d+);(\d+)$/", $_POST["extra"], $matches)) {
								$groupId = (int)$matches[1];
								$targetUserId = (int)$matches[2];
								$stmtCheckPerm = $conn->prepare("SELECT role FROM group_members WHERE `group` = ? AND user = ? AND (role = 'owner' OR role = 'admin')");
								$stmtCheckPerm->bind_param("ii", $groupId, $userId);
								$stmtCheckPerm->execute();
								$permResult = $stmtCheckPerm->get_result();
								if ($permResult->num_rows > 0) {
									$currentUserRole = $permResult->fetch_assoc()["role"];
									if ($targetUserId == $userId) {
										http_response_code(400);
										echo json_encode(["code" => -1, "msg" => "不能踢出自己。"]);
										$stmtCheckPerm->close();
										break;
									}
									$stmtTarget = $conn->prepare("SELECT role FROM group_members WHERE `group` = ? AND user = ?");
									$stmtTarget->bind_param("ii", $groupId, $targetUserId);
									$stmtTarget->execute();
									$targetResult = $stmtTarget->get_result();
									if ($targetResult->num_rows > 0) {
										$targetRole = $targetResult->fetch_assoc()["role"];
										if ($currentUserRole === "owner" || ($currentUserRole === "admin" && $targetRole === "member")) {
											$stmtKick = $conn->prepare("DELETE FROM group_members WHERE `group` = ? AND user = ?");
											$stmtKick->bind_param("ii", $groupId, $targetUserId);
											$stmtKick->execute();
											if ($stmtKick->affected_rows > 0) {
												$userInfoStmt = $conn->prepare("SELECT nick, user_avatar FROM users WHERE id = ?");
												$userInfoStmt->bind_param("i", $userId);
												$userInfoStmt->execute();
												$userInfoResult = $userInfoStmt->get_result();
												$userData = $userInfoResult->fetch_assoc();
												$userInfoStmt->close();
												$groupNameStmt = $conn->prepare("SELECT group_name FROM `groups` WHERE id = ?");
												$groupNameStmt->bind_param("i", $groupId);
												$groupNameStmt->execute();
												$groupNameResult = $groupNameStmt->get_result();
												$groupNameData = $groupNameResult->fetch_assoc();
												$groupNameStmt->close();
												$contentJson = json_encode([
													"title" => "群组移出通知",
													"content" => "%n将你移出群聊“" . $groupNameData["group_name"] . "”",
													"avatar" => $userData["user_avatar"],
													"user" => [
														"nick" => $userData["nick"],
														"id" => $userId,
														"avatar" => $userData["user_avatar"]
													],
													"type" => 1
												], JSON_UNESCAPED_UNICODE);
												$messageStmt = $conn->prepare("INSERT INTO system_messages (target, content) VALUES (?, ?)");
												$messageStmt->bind_param("is", $targetUserId, $contentJson);
												$messageStmt->execute();
												$messageStmt->close();
												echo json_encode(["code" => 1, "msg" => "已踢出群成员。"]);
											} else {
												echo json_encode(["code" => -1, "msg" => "操作未生效。"]);
											}
											$stmtKick->close();
										} else {
											http_response_code(403);
											echo json_encode(["code" => -1, "msg" => "您无权踢出该成员。"]);
										}
									} else {
										http_response_code(404);
										echo json_encode(["code" => -1, "msg" => "目标用户不在群组中。"]);
									}
									$stmtTarget->close();
								} else {
									http_response_code(403);
									echo json_encode(["code" => -1, "msg" => "您不是群主或管理员。"]);
								}
								$stmtCheckPerm->close();
							} else {
								http_response_code(400);
								echo json_encode(["code" => -1, "msg" => "无效的请求格式。"]);
							}
							break;
							case "invitation":
								$messageId = (int)$_POST["extra"];
								$stmt = $conn->prepare("SELECT session, type, content FROM chats WHERE id = ?");
								$stmt->bind_param("i", $messageId);
								$stmt->execute();
								$result = $stmt->get_result();
								if ($result->num_rows == 0) {
									http_response_code(404);
									echo json_encode(["code" => -1, "msg" => "消息不存在。"]);
									$stmt->close();
									break;
								}
								$row = $result->fetch_assoc();
								$type = $row["type"];
								$sessionId = $row["session"];
								$content = $row["content"];
								$stmt->close();
								if ($type != 3) {
									http_response_code(400);
									echo json_encode(["code" => -1, "msg" => "该消息不是群组邀请消息。"]);
									break;
								}
								$stmtCheckFriendship = $conn->prepare("SELECT 1 FROM friendships WHERE id = ? AND (source = ? OR target = ?)");
								$stmtCheckFriendship->bind_param("iii", $sessionId, $userId, $userId);
								$stmtCheckFriendship->execute();
								$friendshipResult = $stmtCheckFriendship->get_result();
								if ($friendshipResult->num_rows == 0) {
									http_response_code(403);
									echo json_encode(["code" => -1, "msg" => "您不是该会话的用户。"]);
									$stmtCheckFriendship->close();
									break;
								}
								$stmtCheckFriendship->close();
								$contentData = json_decode($content, true);
								if (json_last_error() !== JSON_ERROR_NONE || !isset($contentData['session'])) {
									http_response_code(400);
									echo json_encode(["code" => -1, "msg" => "邀请消息格式错误。"]);
									break;
								}
								$groupId = $contentData['session'];
								$validateStmt = $conn->prepare("SELECT 1 FROM group_members WHERE `group` = ? AND user = ?");
								$validateStmt->bind_param("ii", $groupId, $userId);
								$validateStmt->execute();
								$validateResult = $validateStmt->get_result();
								if ($validateResult->num_rows > 0) {
									echo json_encode(["code" => -1, "msg" => "您已经是该群组成员。"]);
									$validateStmt->close();
									break;
								}
								$validateStmt->close();
								$insertionStmt = $conn->prepare("INSERT INTO group_members (`group`, user, role) VALUES (?, ?, 'member')");
								$insertionStmt->bind_param("ii", $groupId, $userId);
								$insertionStmt->execute();
								$insertionStmt->close();
								$updateStmt = $conn->prepare("UPDATE chats SET content = JSON_SET(content, '$.finish', TRUE) WHERE id = ?");
								$updateStmt->bind_param("i", $messageId);
								$updateStmt->execute();
								$updateStmt->close();
								$joinMessageContent = json_encode(["type" => "join"]);
								$joinMessageStmt = $conn->prepare("INSERT INTO chats (session, content, sender, type) VALUES (?, ?, ?, 4)");
								$joinMessageStmt->bind_param("isi", $groupId, $joinMessageContent, $userId);
								$joinMessageStmt->execute();
								$joinMessageStmt->close();
								echo json_encode(["code" => 1, "msg" => "加入群组成功。"]);
								break;
						case "adminadd":
							if (preg_match("/^(\d+);(\d+)$/", $_POST["extra"], $matches)) {
								$groupId = (int)$matches[1];
								$targetUserId = (int)$matches[2];
								$stmtCheckOwner = $conn->prepare("SELECT 1 FROM group_members WHERE `group` = ? AND user = ? AND role = 'owner'");
								$stmtCheckOwner->bind_param("ii", $groupId, $userId);
								$stmtCheckOwner->execute();
								$result = $stmtCheckOwner->get_result();
								if ($result->num_rows > 0) {
									if ($targetUserId == $userId) {
										http_response_code(400);
										echo json_encode(["code" => -1, "msg" => "不能添加自己为管理员。"]);
										$stmtCheckOwner->close();
										break;
									}
									$stmtCheckMember = $conn->prepare("SELECT role FROM group_members WHERE `group` = ? AND user = ?");
									$stmtCheckMember->bind_param("ii", $groupId, $targetUserId);
									$stmtCheckMember->execute();
									$memberResult = $stmtCheckMember->get_result();
									if ($memberResult->num_rows > 0) {
										$memberData = $memberResult->fetch_assoc();
										if ($memberData["role"] === "admin") {
											http_response_code(400);
											echo json_encode(["code" => -1, "msg" => "该用户已是管理员。"]);
										} else if ($memberData["role"] === "owner") {
											http_response_code(400);
											echo json_encode(["code" => -1, "msg" => "不能修改群主权限。"]);
										} else {
											$stmtUpdate = $conn->prepare("UPDATE group_members SET role = 'admin' WHERE `group` = ? AND user = ?");
											$stmtUpdate->bind_param("ii", $groupId, $targetUserId);
											$stmtUpdate->execute();
											if ($stmtUpdate->affected_rows > 0) {
												$contentJson = json_encode([
													"type" => "adminadd",
													"target" => $targetUserId
												]);
												$messageStmt = $conn->prepare("INSERT INTO chats (session, content, sender, type) VALUES (?, ?, ?, 4)");
												$messageStmt->bind_param("isi", $groupId, $contentJson, $userId);
												$messageStmt->execute();
												$messageStmt->close();
												echo json_encode(["code" => 1, "msg" => "已添加管理员。"]);
											} else {
												echo json_encode(["code" => 0, "msg" => "操作未生效。"]);
											}
											$stmtUpdate->close();
										}
									} else {
										http_response_code(404);
										echo json_encode(["code" => -1, "msg" => "目标用户不在群组中。"]);
									}
									$stmtCheckMember->close();
								} else {
									http_response_code(403);
									echo json_encode(["code" => -1, "msg" => "只有群主可以添加管理员。"]);
								}
								$stmtCheckOwner->close();
							} else {
								http_response_code(400);
								echo json_encode(["code" => -1, "msg" => "无效的请求格式。"]);
							}
							break;
						case "adminremove":
							if (preg_match("/^(\d+);(\d+)$/", $_POST["extra"], $matches)) {
								$groupId = (int)$matches[1];
								$targetUserId = (int)$matches[2];
								$stmtCheckOwner = $conn->prepare("SELECT 1 FROM group_members WHERE `group` = ? AND user = ? AND role = 'owner'");
								$stmtCheckOwner->bind_param("ii", $groupId, $userId);
								$stmtCheckOwner->execute();
								$result = $stmtCheckOwner->get_result();
								if ($result->num_rows > 0) {
									if ($targetUserId == $userId) {
										http_response_code(400);
										echo json_encode(["code" => -1, "msg" => "不能移除自己的管理员权限。"]);
										$stmtCheckOwner->close();
										break;
									}
									$stmtCheckMember = $conn->prepare("SELECT role FROM group_members WHERE `group` = ? AND user = ?");
									$stmtCheckMember->bind_param("ii", $groupId, $targetUserId);
									$stmtCheckMember->execute();
									$memberResult = $stmtCheckMember->get_result();
									if ($memberResult->num_rows > 0) {
										$memberData = $memberResult->fetch_assoc();
										if ($memberData["role"] === "admin") {
											$stmtUpdate = $conn->prepare("UPDATE group_members SET role = 'member' WHERE `group` = ? AND user = ?");
											$stmtUpdate->bind_param("ii", $groupId, $targetUserId);
											$stmtUpdate->execute();
											if ($stmtUpdate->affected_rows > 0) {
												$contentJson = json_encode([
													"type" => "adminremove",
													"target" => $targetUserId
												]);
												$messageStmt = $conn->prepare("INSERT INTO chats (session, content, sender, type) VALUES (?, ?, ?, 4)");
												$messageStmt->bind_param("isi", $groupId, $contentJson, $userId);
												$messageStmt->execute();
												$messageStmt->close();
												echo json_encode(["code" => 1, "msg" => "已移除管理员权限。"]);
											} else {
												echo json_encode(["code" => 0, "msg" => "操作未生效。"]);
											}
											$stmtUpdate->close();
										} else if ($memberData["role"] === "owner") {
											http_response_code(403);
											echo json_encode(["code" => -1, "msg" => "不能修改群主权限。"]);
										} else {
											http_response_code(400);
											echo json_encode(["code" => -1, "msg" => "目标用户不是管理员。"]);
										}
									} else {
										http_response_code(404);
										echo json_encode(["code" => -1, "msg" => "目标用户不在群组中。"]);
									}
									$stmtCheckMember->close();
								} else {
									http_response_code(403);
									echo json_encode(["code" => -1, "msg" => "只有群主可以移除管理员。"]);
								}
								$stmtCheckOwner->close();
							} else {
								http_response_code(400);
								echo json_encode(["code" => -1, "msg" => "无效的请求格式。"]);
							}
							break;
						case "transfer":
							if (preg_match("/^(\d+);(\d+)$/", $_POST["extra"], $matches)) {
								$groupId = (int)$matches[1];
								$targetUserId = (int)$matches[2];
								$stmtCheckOwner = $conn->prepare("SELECT 1 FROM group_members WHERE `group` = ? AND user = ? AND role = 'owner'");
								$stmtCheckOwner->bind_param("ii", $groupId, $userId);
								$stmtCheckOwner->execute();
								$result = $stmtCheckOwner->get_result();
								if ($result->num_rows > 0) {
									if ($targetUserId == $userId) {
										http_response_code(400);
										echo json_encode(["code" => -1, "msg" => "不能转让给自己。"]);
										$stmtCheckOwner->close();
										break;
									}
									$stmtCheckMember = $conn->prepare("SELECT role FROM group_members WHERE `group` = ? AND user = ?");
									$stmtCheckMember->bind_param("ii", $groupId, $targetUserId);
									$stmtCheckMember->execute();
									$memberResult = $stmtCheckMember->get_result();
									if ($memberResult->num_rows > 0) {
										$memberData = $memberResult->fetch_assoc();
										if ($memberData["role"] === "member" || $memberData["role"] === "admin") {
											$stmtUpdate = $conn->prepare("UPDATE group_members SET role = 'owner' WHERE `group` = ? AND user = ?");
											$stmtUpdate->bind_param("ii", $groupId, $targetUserId);
											$stmtUpdate->execute();
											if ($stmtUpdate->affected_rows > 0) {
												$stmtUpdateSelf = $conn->prepare("UPDATE group_members SET role = 'member' WHERE `group` = ? AND user = ?");
												$stmtUpdateSelf->bind_param("ii", $groupId, $userId);
												$stmtUpdateSelf->execute();
												if ($stmtUpdateSelf->affected_rows > 0) {
													$contentJson = json_encode([
														"type" => "transfer",
														"target" => $targetUserId
													]);
													$messageStmt = $conn->prepare("INSERT INTO chats (session, content, sender, type) VALUES (?, ?, ?, 4)");
													$messageStmt->bind_param("isi", $groupId, $contentJson, $userId);
													$messageStmt->execute();
													echo json_encode(["code" => 1, "msg" => "群主已转让。"]);
												} else {
													echo json_encode(["code" => 0, "msg" => "操作未生效。"]);
												}
											} else {
												echo json_encode(["code" => 0, "msg" => "操作未生效。"]);
											}
											$stmtUpdate->close();
										}
									} else {
										http_response_code(404);
										echo json_encode(["code" => -1, "msg" => "目标用户不在群组中。"]);
									}
								}
							}
							break;
						case "setnick":
							if (preg_match("/^(\d+);(.*)$/", $_POST["extra"], $matches)) {
								$groupId = (int)$matches[1];
								$nickname = trim($matches[2]);
								if (mb_strlen($nickname) <= 100) {
									$stmtCheck = $conn->prepare("SELECT 1 FROM group_members WHERE `group` = ? AND user = ?");
									$stmtCheck->bind_param("ii", $groupId, $userId);
									$stmtCheck->execute();
									$checkResult = $stmtCheck->get_result();
									if ($checkResult->num_rows > 0) {
										$stmtUpdate = $conn->prepare("UPDATE group_members SET group_nickname = ? WHERE `group` = ? AND user = ?");
										if ($nickname === '') {
											$nickname = null;
										}
										$stmtUpdate->bind_param("sii", $nickname, $groupId, $userId);
										$stmtUpdate->execute();
										if ($stmtUpdate->affected_rows > 0) {
											echo json_encode(["code" => 1, "msg" => "群昵称设置成功。"]);
										} else {
											echo json_encode(["code" => -1, "msg" => "群昵称未更改。"]);
										}
										$stmtUpdate->close();
									} else {
										http_response_code(403);
										echo json_encode(["code" => -1, "msg" => "您不在该群组中。"]);
									}
								} else {
									http_response_code(400);
									echo json_encode(["code" => -1, "msg" => "群昵称过长。"]);
								}
								$stmtCheck->close();
							} else {
								http_response_code(400);
								echo json_encode(["code" => -1, "msg" => "参数格式错误。"]);
							}
							break;
						case "option":
							if (preg_match("/^(\d+);p:(1|2|3)$/", $_POST["extra"], $matches)) {
								$groupId = (int)$matches[1];
								$option = $matches[2];
								$stmtCheckOwner = $conn->prepare("SELECT 1 FROM group_members WHERE `group` = ? AND user = ? AND role = 'owner'");
								$stmtCheckOwner->bind_param("ii", $groupId, $userId);
								$stmtCheckOwner->execute();
								$stmtCheckOwnerResult = $stmtCheckOwner->get_result();
								if ($stmtCheckOwnerResult->num_rows > 0) {
									$stmt = $conn->prepare("UPDATE `groups` SET group_info_permission = ? WHERE id = ?");
									$stmt->bind_param("ii", $option, $groupId);
									$stmt->execute();
									$stmt->close();
									echo json_encode(["code" => 1, "msg" => "群组设置已更新。"]);
								} else {
									http_response_code(403);
									echo json_encode(["code" => -1, "msg" => "您不是该群组的群主，无法更改设置。"]);
								}
								$stmtCheckOwner->close();
							} else if (preg_match("/^(\d+);n:(.*)$/s", $_POST["extra"], $matches)) {
								$groupId = (int)$matches[1];
								$newName = trim($matches[2]);
								if (empty($newName)) {
									http_response_code(400);
									echo json_encode(["code" => -1, "msg" => "群名不能为空。"]);
								} else {
									$stmt = $conn->prepare("SELECT g.group_info_permission, m.role FROM `groups` g JOIN group_members m ON g.id = m.`group` WHERE g.id = ? AND m.user = ?");
									$stmt->bind_param("ii", $groupId, $userId);
									$stmt->execute();
									$result = $stmt->get_result();
									if ($result->num_rows > 0) {
										$row = $result->fetch_assoc();
										$currentPerm = $row["group_info_permission"];
										$userRole = $row["role"];
										if ($currentPerm == "1" && $userRole === "owner" ||
											$currentPerm == "2" && ($userRole === "admin" || $userRole === "owner") ||
											$currentPerm == "3") {
											$stmtUpdate = $conn->prepare("UPDATE `groups` SET group_name = ? WHERE id = ?");
											$stmtUpdate->bind_param("si", $newName, $groupId);
											$stmtUpdate->execute();
											if ($stmtUpdate->affected_rows > 0) {
												echo json_encode(["code" => 1, "msg" => "群名更新成功。"]);
											} else {
												echo json_encode(["code" => -1, "msg" => "群名未更改。"]);
											}
											$stmtUpdate->close();
										} else {
											http_response_code(403);
											echo json_encode(["code" => -1, "msg" => "您没有修改群名的权限。"]);
										}
									} else {
										http_response_code(403);
										echo json_encode(["code" => -1, "msg" => "您不在该群组中。"]);
									}
									$stmt->close();
								}
							} else {
								http_response_code(400);
								echo json_encode(["code" => -1, "msg" => "无效的选项。"]);
							}
							break;
						default:
							http_response_code(400);
							echo json_encode(["code" => -1, "msg" => "无效的操作。"]);
					}
				} else {
					http_response_code(404);
					echo json_encode(["code" => -1, "msg" => "未找到有效的用户信息。"]);
				}
				$conn->close();
			} catch (mysqli_sql_exception $sqlException) {
				http_response_code(500);
			echo json_encode(["code" => -1, "msg" => "数据库错误。"]);
			}
		} else {
			http_response_code(400);
			echo json_encode(["code" => -1, "msg" => "缺少必要的参数。"]);
		}
	} else {
		http_response_code(405);
		echo json_encode(["code" => -1, "msg" => "请求方法不正确。"]);
	}
?>