<?php
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
				$conn = new mysqli($host, $user, $pass, $db);
				$stmt = $conn->prepare("SELECT user FROM user_session WHERE token = ? AND expires >= NOW()");
				$stmt->bind_param("s", $_COOKIE["_"]);
				$stmt->execute();
				$result = $stmt->get_result();
				$data = $result->fetch_assoc();
				$stmt->close();
				if ($data) {
					$userId = $data["user"];
				} else {
					http_response_code(404);
					echo json_encode(["code" => 0, "msg" => "未找到匹配的用户信息。"]);
					$stmt->close();
					$conn->close();
					exit;
				}
				switch ($_POST["action"]) {
					case "add":
						if (isset($_POST["msg"])) {
							$trimmedMsg = trim($_POST["msg"]);
							if ($trimmedMsg === "") {
								http_response_code(400);
								echo json_encode(["code" => -1, "msg" => "参数不合法。"]);
								exit;
							}
							$target = (int)$_POST["target"];
							$testStmt = $conn->prepare("SELECT (SELECT COUNT(*) FROM friendships WHERE id = ? AND (source = ? OR target = ?)) + (SELECT COUNT(*) FROM group_members WHERE `group` = ? AND user = ?)");
							$testStmt->bind_param("iiiii", $target, $userId, $userId, $target, $userId);
							$testStmt->execute();
							$testStmt->bind_result($count);
							$testStmt->fetch();
							$testStmt->close();
							if ($count > 0) {
								$addStmt = $conn->prepare("INSERT INTO chats (session, content, sender, type) VALUES (?, ?, ?, 1)");
								$addStmt->bind_param("ssi", $target, $trimmedMsg, $userId);
								$addStmt->execute();
								echo json_encode(["code" => 1, "msg" => "消息发送成功。"]);
							} else {
								http_response_code(400);
								echo json_encode(["code" => -1, "msg" => "您没有在该会话上发送消息的权限。"]);
							}
						} else {
							http_response_code(400);
							echo json_encode(["code" => -1, "msg" => "缺少必要的参数。"]);
						}
						break;
					case "edit":
						if (isset($_POST["msg"])) {
							$trimmedMsg = trim($_POST["msg"]);
							if ($trimmedMsg === "") {
								http_response_code(400);
								echo json_encode(["code" => -1, "msg" => "参数不合法。"]);
								exit;
							}
							$targetMsg = (int)$_POST["target"];
							$checkMsgStmt = $conn->prepare("SELECT session, sender, type FROM chats WHERE id = ?");
							$checkMsgStmt->bind_param("i", $targetMsg);
							$checkMsgStmt->execute();
							$msgResult = $checkMsgStmt->get_result();
							if ($msgResult->num_rows === 0) {
								http_response_code(404);
								echo json_encode(["code" => -1, "msg" => "消息不存在。"]);
								$checkMsgStmt->close();
								exit;
							}
							$msgData = $msgResult->fetch_assoc();
							$sessionId = $msgData["session"];
							$senderId = $msgData["sender"];
							$msgType = $msgData["type"];
							if ($senderId != $userId) {
								http_response_code(403);
								echo json_encode(["code" => -1, "msg" => "您无权编辑此消息。"]);
								$checkMsgStmt->close();
								exit;
							}
							if ($msgType != 1 && $msgType != 5) {
								http_response_code(400);
								echo json_encode(["code" => -1, "msg" => "该类型消息不允许编辑。"]);
								$checkMsgStmt->close();
								exit;
							}
							$checkSessionStmt = $conn->prepare("SELECT (SELECT COUNT(*) FROM friendships WHERE id = ? AND (source = ? OR target = ?)) + (SELECT COUNT(*) FROM group_members WHERE `group` = ? AND user = ?) AS total");
							$checkSessionStmt->bind_param("iiiii", $sessionId, $userId, $userId, $sessionId, $userId);
							$checkSessionStmt->execute();
							$sessionResult = $checkSessionStmt->get_result();
							$sessionData = $sessionResult->fetch_assoc();
							if ($sessionData["total"] == 0) {
								http_response_code(403);
								echo json_encode(["code" => -1, "msg" => "您已不在该会话中，无法编辑消息。"]);
								$checkSessionStmt->close();
								$checkMsgStmt->close();
								exit;
							}
							if ($msgType == 1) {
								$updateQuery = "UPDATE chats SET content = JSON_ARRAY(JSON_OBJECT('msg', content, 'sent_at', UNIX_TIMESTAMP(sent_at)), JSON_OBJECT('msg', ?, 'sent_at', UNIX_TIMESTAMP())), type = 5 WHERE id = ?";
							} elseif ($msgType == 5) {
								$updateQuery = "UPDATE chats SET content = JSON_ARRAY_APPEND(content, '$', JSON_OBJECT('msg', ?, 'sent_at', UNIX_TIMESTAMP())) WHERE id = ?";
							}
							$updateStmt = $conn->prepare($updateQuery);
							$updateStmt->bind_param("si", $trimmedMsg, $targetMsg);
							$updateStmt->execute();
							if ($updateStmt->affected_rows > 0) {
								echo json_encode(["code" => 1, "msg" => "消息编辑成功。"]);
							} else {
								echo json_encode(["code" => -1, "msg" => "消息内容未更改。"]);
							}
							$updateStmt->close();
							$checkSessionStmt->close();
							$checkMsgStmt->close();
						}
						break;
					case "recall":
						$targetMsg = (int)$_POST["target"];
						$checkStmt = $conn->prepare("SELECT session, sender, sent_at, type FROM chats WHERE id = ?");
						$checkStmt->bind_param("i", $targetMsg);
						$checkStmt->execute();
						$msgResult = $checkStmt->get_result();
						if ($msgResult->num_rows === 0) {
							http_response_code(404);
							echo json_encode(["code" => -1, "msg" => "消息不存在。"]);
							$checkStmt->close();
							exit;
						}
						$msgData = $msgResult->fetch_assoc();
						$sessionId = $msgData["session"];
						$senderId = $msgData["sender"];
						$msgType = $msgData["type"];
						$sentTime = strtotime($msgData["sent_at"]);
						$currentTime = time();
						$checkStmt->close();
						$allowedTypes = [1, 2, 5, 6];
						if (!in_array($msgType, $allowedTypes)) {
							http_response_code(400);
							echo json_encode(["code" => -1, "msg" => "该类型消息不允许撤回。"]);
							exit;
						}
						if ($senderId != $userId) {
							http_response_code(403);
							echo json_encode(["code" => -1, "msg" => "您无权撤回此消息。"]);
							exit;
						}
						if (($currentTime - $sentTime) > 120) {
							http_response_code(400);
							echo json_encode(["code" => -1, "msg" => "超过撤回时间限制（2分钟）。"]);
							exit;
						}
						$sessionCheck = $conn->prepare("SELECT (SELECT COUNT(*) FROM friendships WHERE id = ? AND (source = ? OR target = ?)) + (SELECT COUNT(*) FROM group_members WHERE `group` = ? AND user = ?) AS total");
						$sessionCheck->bind_param("iiiii", $sessionId, $userId, $userId, $sessionId, $userId);
						$sessionCheck->execute();
						$sessionResult = $sessionCheck->get_result()->fetch_assoc();
						if ($sessionResult["total"] == 0) {
							http_response_code(403);
							echo json_encode(["code" => -1, "msg" => "您已不在该会话中。"]);
							$sessionCheck->close();
							exit;
						}
						$sessionCheck->close();
						$recallStmt = $conn->prepare("UPDATE chats SET type = 4, content = '{\"type\":\"recall\"}' WHERE id = ?");
						$recallStmt->bind_param("i", $targetMsg);
						$recallStmt->execute();
						if ($recallStmt->affected_rows > 0) {
							echo json_encode(["code" => 1, "msg" => "消息撤回成功。"]);
						} else {
							echo json_encode(["code" => -1, "msg" => "撤回操作未生效，请重试。"]);
						}
						$recallStmt->close();
						break;
					case "forward":
						if (!isset($_POST['session']) || !isset($_POST['target'])) {
							http_response_code(400);
							echo json_encode(["code" => -1, "msg" => "缺少session或target参数。"]);
							break;
						}
						$sessionId = (int)$_POST['session'];
						$targetRooms = array_map('intval', explode(',', $_POST['target']));
						$hasMsg = isset($_POST['msg']);
						$hasMsgs = isset($_POST['msgs']);
						$extraMsg = isset($_POST['extra']) ? trim($_POST['extra']) : null;
						if ($hasMsg && $hasMsgs) {
							http_response_code(400);
							echo json_encode(["code" => -1, "msg" => "msg和msgs参数不能同时使用。"]);
							break;
						}
						if (!$hasMsg && !$hasMsgs) {
							http_response_code(400);
							echo json_encode(["code" => -1, "msg" => "需要msg或msgs参数。"]);
							break;
						}
						$messageIds = [];
						if ($hasMsg) {
							$messageIds = array_map('intval', explode(',', $_POST['msg']));
							$isRecordMode = false;
						} else {
							$messageIds = array_map('intval', explode(',', $_POST['msgs']));
							$isRecordMode = true;
						}
						$sessionCheck = $conn->prepare("SELECT (SELECT COUNT(*) FROM friendships WHERE id = ? AND (source = ? OR target = ?)) + (SELECT COUNT(*) FROM group_members WHERE `group` = ? AND user = ?) AS total");
						$sessionCheck->bind_param("iiiii", $sessionId, $userId, $userId, $sessionId, $userId);
						$sessionCheck->execute();
						$sessionResult = $sessionCheck->get_result()->fetch_assoc();
						$sessionCheck->close();
						if ($sessionResult['total'] == 0) {
							http_response_code(403);
							echo json_encode(["code" => -1, "msg" => "您不在源会话中。"]);
							break;
						}
						$placeholders = implode(',', array_fill(0, count($targetRooms), '?'));
						$stmt = $conn->prepare("SELECT (SELECT COUNT(*) FROM friendships WHERE id IN ($placeholders) AND (source = ? OR target = ?)) + (SELECT COUNT(*) FROM group_members WHERE `group` IN ($placeholders) AND user = ?) AS total");
						$types = str_repeat('i', count($targetRooms) * 2 + 3);
						$params = array_merge($targetRooms, [$userId, $userId], $targetRooms, [$userId]);
						$stmt->bind_param($types, ...$params);
						$stmt->execute();
						$result = $stmt->get_result();
						$row = $result->fetch_assoc();
						$stmt->close();
						if ($row['total'] != count($targetRooms)) {
							http_response_code(403);
							echo json_encode(["code" => -1, "msg" => "您不在所有目标会话中。"]);
							break;
						}
						$msgPlaceholders = implode(',', array_fill(0, count($messageIds), '?'));
						$msgStmt = $conn->prepare("SELECT id, type, content, multi, UNIX_TIMESTAMP(sent_at) AS sent_at, sender FROM chats WHERE id IN ($msgPlaceholders) AND session = ?");
						$msgParams = array_merge($messageIds, [$sessionId]);
						$msgTypes = str_repeat('i', count($messageIds)) . 'i';
						$msgStmt->bind_param($msgTypes, ...$msgParams);
						$msgStmt->execute();
						$msgResult = $msgStmt->get_result();
						$validMessages = $msgResult->fetch_all(MYSQLI_ASSOC);
						$msgStmt->close();
						if (count($validMessages) != count($messageIds)) {
							http_response_code(404);
							echo json_encode(["code" => -1, "msg" => "部分消息不存在或不属于该会话。"]);
							break;
						}
						foreach ($validMessages as &$msg) {
							if ($msg['type'] == 3 || $msg['type'] == 4) {
								http_response_code(403);
								echo json_encode(["code" => -1, "msg" => "无法转发特殊消息。"]);
								exit;
							}
						}
						unset($msg);
						$conn->begin_transaction();
						try {
							if (!$isRecordMode) {
								foreach ($validMessages as $msg) {
									foreach ($targetRooms as $room) {
										if ($msg['type'] == 1) {
											$stmt = $conn->prepare("INSERT INTO chats (session, content, sender, type) VALUES (?, ?, ?, 1)");
											$stmt->bind_param("isi", $room, $msg['content'], $userId);
											$stmt->execute();
											$stmt->close();
										} elseif ($msg['type'] == 2) {
											$stmt = $conn->prepare("INSERT INTO chats (session, content, multi, sender, type) VALUES (?, ?, ?, 2)");
											$stmt->bind_param("issi", $room, $msg['content'], $msg['multi'], $userId);
											$stmt->execute();
											$stmt->close();
										} elseif ($msg['type'] == 5) {
											$decodedContent = json_decode($msg['content'], true);
											$lastMsg = end($decodedContent)["msg"];
											$stmt = $conn->prepare("INSERT INTO chats (session, content, sender, type) VALUES (?, ?, ?, 1)");
											$stmt->bind_param("isi", $room, $lastMsg, $userId);
											$stmt->execute();
											$stmt->close();
										} elseif ($msg['type'] == 6) {
											$stmt = $conn->prepare("INSERT INTO chats (session, content, sender, type) VALUES (?, ?, ?, 6)");
											$stmt->bind_param("isi", $room, $msg['content'], $userId);
											$stmt->execute();
											$stmt->close();
										}
									}
								}
							} else {
								$recordContent = [];
								foreach ($validMessages as $msg) {
									if ($msg['type'] == 5 || $msg['type'] == 6) {
										$msg['content'] = json_decode($msg['content'], true);
									}
									$recordContent[] = [
										'sender' => $msg['sender'],
										'type' => $msg['type'],
										'content' => $msg['content'],
										'multi' => $msg['multi'],
										'sent_at' => $msg['sent_at']
									];
								}
								$jsonRecord = json_encode($recordContent);
								foreach ($targetRooms as $room) {
									$stmt = $conn->prepare("INSERT INTO chats (session, content, sender, type) VALUES (?, ?, ?, 6)");
									$stmt->bind_param("isi", $room, $jsonRecord, $userId);
									$stmt->execute();
									$stmt->close();
								}
							}
							if ($extraMsg !== null && trim($extraMsg) !== '') {
								foreach ($targetRooms as $room) {
									$stmt = $conn->prepare("INSERT INTO chats (session, content, sender, type) VALUES (?, ?, ?, 1)");
									$stmt->bind_param("isi", $room, $extraMsg, $userId);
									$stmt->execute();
									$stmt->close();
								}
							}
							$conn->commit();
							echo json_encode(["code" => 1, "msg" => "转发成功。"]);
						} catch (Exception $e) {
							$conn->rollback();
							http_response_code(500);
							echo json_encode(["code" => -1, "msg" => "转发失败。"]);
						}
						break;
					default:
						http_response_code(400);
						echo json_encode(["code" => -1, "msg" => "参数不合法。"]);
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