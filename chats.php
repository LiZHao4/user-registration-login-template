<?php
	header("Content-Type: application/json");
	if ($_SERVER["REQUEST_METHOD"] === "GET") {
		if (isset($_COOKIE["_"]) && isset($_GET["target"])) {
			mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
			$config = parse_ini_file("conf/settings.ini", true);
			$host = $config["database"]["host"];
			$user = $config["database"]["user"];
			$pass = $config["database"]["pass"];
			$db = $config["database"]["db"];
			$fresh = isset($_GET["max"]);
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
					if (!$fresh) {
						$chatPrepare = "SELECT id, content, UNIX_TIMESTAMP(sent_at) AS sent_at, sender, multi, type FROM chats WHERE session = ? ORDER BY sent_at";
					} else {
						$chatPrepare = "SELECT id, content, UNIX_TIMESTAMP(sent_at) AS sent_at, sender, multi, type FROM chats WHERE session = ? AND id > ? ORDER BY sent_at";
					}
					$sessionId = $_GET["target"];
					if (!is_numeric($sessionId)) {
						http_response_code(400);
						echo json_encode(["code" => -1, "msg" => "会话ID必须是有效的数字"]);
						$conn->close();
						exit;
					}
					$sessionId = intval($sessionId);
					$stmt = $conn->prepare("SELECT id FROM friendships WHERE id = ? AND (source = ? OR target = ?)");
					$stmt->bind_param("iii", $sessionId, $userId, $userId);
					$stmt->execute();
					$res = $stmt->get_result();
					if ($res->num_rows > 0) {
						$type = "friend";
					} else {
						$stmt2 = $conn->prepare("SELECT id FROM `groups` WHERE id = ?");
						$stmt2->bind_param("i", $sessionId);
						$stmt2->execute();
						$res2 = $stmt2->get_result();
						if ($res2->num_rows == 0) {
							http_response_code(404);
							echo json_encode(["code" => 0, "msg" => "会话不存在"]);
							$stmt2->close();
							$conn->close();
							exit;
						}
						$stmt2->close();
						$stmt3 = $conn->prepare("SELECT 1 FROM group_members WHERE `group` = ? AND user = ?");
						$stmt3->bind_param("ii", $sessionId, $userId);
						$stmt3->execute();
						$res3 = $stmt3->get_result();
						if ($res3->num_rows > 0) {
							$type = "group";
						}
						$stmt3->close();
					}
					$stmt->close();
					$intTarget = (int)$sessionId;
					$validateStmt = $conn->prepare("SELECT (SELECT COUNT(*) FROM friendships WHERE id = ? AND (source = ? OR target = ?)) + (SELECT COUNT(*) FROM group_members WHERE `group` = ? AND user = ?)");
					$validateStmt->bind_param("iiiii", $intTarget, $userId, $userId, $intTarget, $userId);
					$validateStmt->execute();
					$validateStmt->bind_result($validate);
					$validateStmt->fetch();
					$validateStmt->close();
					if (!$validate) {
						http_response_code(403);
						echo json_encode(["code" => -1, "msg" => "不能偷看别人的聊天记录。"]);
						$conn->close();
						exit;
					}
					$chatStmt = $conn->prepare($chatPrepare);
					if (!$fresh) {
						$chatStmt->bind_param("i", $intTarget);
					} else {
						$intMax = (int)$_GET["max"];
						$chatStmt->bind_param("ii", $intTarget, $intMax);
					}
					$chatStmt->execute();
					$chatResult = $chatStmt->get_result();
					$chatData = $chatResult->fetch_all(MYSQLI_ASSOC);
					$chatStmt->close();
					foreach ($chatData as &$chat) {
						if ($chat["type"] == 3 || $chat["type"] == 4) {
							$chat["content"] = json_decode($chat["content"], true);
							if ($chat["type"] == 4 && isset($chat["content"]["target"])) {
								$innerUserId = $chat["content"]["target"];
								$innerStmt = $conn->prepare("SELECT nick FROM users WHERE id = ?");
								$innerStmt->bind_param("i", $innerUserId);
								$innerStmt->execute();
								$innerResult = $innerStmt->get_result();
								$innerData = $innerResult->fetch_assoc();
								$innerStmt->close();
							    $chat["inner_nick"] = $innerData["nick"];
							}
						}
					}
					unset($chat);
					switch ($type) {
						case "friend":
							$avatarStmt = $conn->prepare("SELECT user_avatar FROM users WHERE id = ?");
							$avatarStmt->bind_param("i", $userId);
							$avatarStmt->execute();
							$avatarResult = $avatarStmt->get_result();
							$avatarData = $avatarResult->fetch_assoc();
							$avatarMine = $avatarData["user_avatar"];
							$avatarStmt->close();
							$oppositeStmt = $conn->prepare("SELECT CASE WHEN source = ? THEN target ELSE source END AS other_user_id, UNIX_TIMESTAMP(request_time) AS request_time, UNIX_TIMESTAMP(allowed_time) AS allowed_time FROM friendships WHERE id = ?");
							$oppositeStmt->bind_param("ii", $userId, $_GET["target"]);
							$oppositeStmt->execute();
							$oppositeStmt->bind_result($otherUserId, $requestTime, $allowedTime);
							$oppositeStmt->fetch();
							$oppositeStmt->close();
							$avatarUrl = "";
							$oppositeName = "";
							$remark = null;
							if ($otherUserId) {
								$avatarStmt2 = $conn->prepare("SELECT nick, user_avatar FROM users WHERE id = ?");
								$avatarStmt2->bind_param("i", $otherUserId);
								$avatarStmt2->execute();
								$avatarResult2 = $avatarStmt2->get_result();
								$avatarData2 = $avatarResult2->fetch_assoc();
								$avatarStmt2->close();
								$avatarUrl = $avatarData2["user_avatar"];
								$oppositeName = $avatarData2["nick"];
								$remarkStmt = $conn->prepare("SELECT remark FROM user_remarks WHERE user_id = ? AND target_user_id = ?");
								$remarkStmt->bind_param("ii", $userId, $otherUserId);
								$remarkStmt->execute();
								$remarkResult = $remarkStmt->get_result();
								if ($remarkResult->num_rows > 0) {
									$remarkData = $remarkResult->fetch_assoc();
									$remark = $remarkData["remark"];
								}
								$remarkStmt->close();
							}
							break;
						case "group":
							$groupStmt = $conn->prepare("SELECT user, role, group_nickname FROM group_members WHERE `group` = ? ORDER BY joined_at");
							$groupStmt->bind_param("i", $sessionId);
							$groupStmt->execute();
							$groupResult = $groupStmt->get_result();
							$groupStmt->close();
							$groupMembers = [];
							$currentIndex = 0;
							while ($groupData = $groupResult->fetch_assoc()) {
								$memberUserId = $groupData["user"];
                            	$role = $groupData["role"];
								$groupNickname = $groupData["group_nickname"];
								$avatarStmt2 = $conn->prepare("SELECT nick, user_avatar FROM users WHERE id = ?");
								$avatarStmt2->bind_param("i", $memberUserId);
								$avatarStmt2->execute();
								$avatarResult2 = $avatarStmt2->get_result();
								$avatarData2 = $avatarResult2->fetch_assoc();
								$avatarStmt2->close();
								$remarkStmt = $conn->prepare("SELECT remark FROM user_remarks WHERE user_id = ? AND target_user_id = ?");
								$remarkStmt->bind_param("ii", $userId, $memberUserId);
								$remarkStmt->execute();
								$remarkResult = $remarkStmt->get_result();
								$remark = null;
								if ($remarkResult->num_rows > 0) {
									$remarkData = $remarkResult->fetch_assoc();
									$remark = $remarkData["remark"];
								}
								$remarkStmt->close();
								if ($memberUserId == $userId) {
									$currentIndex = count($groupMembers);
								}
								$groupMembers[] = ["id" => $memberUserId, "nick" => $avatarData2["nick"], "remark" => $remark, "group_nickname" => $groupNickname, "avatar" => $avatarData2["user_avatar"], "role" => $role];
							}
							$joinedAtStmt = $conn->prepare("SELECT UNIX_TIMESTAMP(joined_at) AS joined_at FROM group_members WHERE `group` = ? AND user = ?");
							$joinedAtStmt->bind_param("ii", $sessionId, $userId);
							$joinedAtStmt->execute();
							$joinedAtStmt->bind_result($joinedAt);
							$joinedAtStmt->fetch();
							$joinedAtStmt->close();
							$groupNameStmt = $conn->prepare("SELECT group_name, group_info_permission, group_avatar FROM `groups` WHERE id = ?");
							$groupNameStmt->bind_param("i", $sessionId);
							$groupNameStmt->execute();
							$groupNameStmt->bind_result($groupName, $groupInfoPermission, $groupAvatar);
							$groupNameStmt->fetch();
							$groupNameStmt->close();
							break;
						default:
							http_response_code(400);
							echo json_encode(["code" => -1, "msg" => "未知的聊天类型。"]);
							$conn->close();
							exit;
					}
					$checkStmt = $conn->prepare("SELECT 1 FROM message_read_status WHERE session_id = ? AND user_id = ?");
					$checkStmt->bind_param("ii", $_GET["target"], $userId);
					$checkStmt->execute();
					$exists = $checkStmt->fetch();
					$checkStmt->close();
					$maxIdStmt = $conn->prepare("SELECT COALESCE(MAX(id), 0) FROM chats WHERE session = ?");
					$maxIdStmt->bind_param("i", $_GET["target"]);
					$maxIdStmt->execute();
					$maxIdStmt->bind_result($currentMaxId);
					$maxIdStmt->fetch();
					$maxIdStmt->close();
					if ($exists) {
						$updateStmt = $conn->prepare("UPDATE message_read_status SET max_id = ? WHERE session_id = ? AND user_id = ?");
						$updateStmt->bind_param("iii", $currentMaxId, $_GET["target"], $userId);
						$updateStmt->execute();
						$updateStmt->close();
					} else {
						$insertStmt = $conn->prepare("INSERT INTO message_read_status (session_id, user_id, max_id) VALUES (?, ?, ?)");
						$insertStmt->bind_param("iii", $_GET["target"], $userId, $currentMaxId);
						$insertStmt->execute();
						$insertStmt->close();
					}
					if ($fresh) {
						echo json_encode(["code" => 1, "msg" => "聊天信息获取成功。", "data" => $chatData]);
					} else {
						$base = ["code" => 1, "msg" => "聊天信息获取成功。", "data" => $chatData, "type" => $type, "sessionId" => $sessionId];
						switch ($type) {
							case "friend":
								$base["id"] = $userId;
								$base["oId"] = $otherUserId;
								$base["avatar"] = $avatarMine;
								$base["opposite"] = $avatarUrl;
								$base["oName"] = $oppositeName;
								$base["remark"] = $remark;
								$base["requestTime"] = $requestTime;
								$base["allowedTime"] = $allowedTime;
								break;
							case "group":
								$base["members"] = $groupMembers;
								$base["joined_at"] = $joinedAt;
								$base["group_name"] = $groupName;
								$base["current_user_index"] = $currentIndex;
								$base["group_info_permission"] = (int)$groupInfoPermission;
								$base["group_avatar"] = $groupAvatar;
								break;
							default:
								echo json_encode(["code" => -1, "msg" => "未知类型。"]);
								exit;
						}
						echo json_encode($base);
					}
				} else {
					http_response_code(404);
					echo json_encode(["code" => -1, "msg" => "未找到有效的用户信息。"]);
				}
				$conn->close();
			} catch (mysqli_sql_exception $sqlException) {
				http_response_code(500);
				echo json_encode(["code" => -1, "msg" => "数据库错误。" . $sqlException->getMessage() . "\n" . $sqlException->getLine()]);
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