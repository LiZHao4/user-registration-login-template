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
			$intTarget = (int)$_GET["target"];
			try {
				$databaseConnection = new mysqli($host, $user, $pass, $db);
				$stmt = $databaseConnection->prepare("SELECT user FROM user_session WHERE token = ? AND expires >= NOW()");
				$stmt->bind_param("s", $_COOKIE["_"]);
				$stmt->execute();
				$stmt->store_result();
				$stmt->bind_result($id);
				if ($stmt->fetch());
				else {
					$stmt->close();
					http_response_code(404);
					echo json_encode(["code" => -1, "msg" => "未找到有效的用户ID。"]);
					return;
				}
				try {
					$added = null;
					$response = ["code" => 1, "msg" => "信息获取成功。"];
					if ($id == $intTarget) {
						$added = "self";
					} else {
						$queryStmt = $databaseConnection->prepare("SELECT COUNT(*) > 0 AS is_friend FROM friendships WHERE (source = ? AND target = ?) OR (source = ? AND target = ?)");
						$queryStmt->bind_param("iiii", $id, $intTarget, $intTarget, $id);
						$queryStmt->execute();
						$queryStmt->bind_result($is_friend);
						$queryStmt->fetch();
						$queryStmt->close();
						if ($is_friend) {
							$added = "true";
						} else {
							$outgoingStmt = $databaseConnection->prepare("SELECT COUNT(*) > 0 FROM friend_requests WHERE source = ? AND target = ?");
							$outgoingStmt->bind_param("ii", $id, $intTarget);
							$outgoingStmt->execute();
							$outgoingStmt->bind_result($outgoing_request);
							$outgoingStmt->fetch();
							$outgoingStmt->close();
							if ($outgoing_request) {
								$added = "pending_out";
							} else {
								$incomingStmt = $databaseConnection->prepare("SELECT id FROM friend_requests WHERE source = ? AND target = ? ORDER BY id DESC LIMIT 1");
                                $incomingStmt->bind_param("ii", $intTarget, $id);
                                $incomingStmt->execute();
                                $incomingStmt->store_result();
                                $incomingStmt->bind_result($incoming_request_id);
                                if ($incomingStmt->fetch()) {
                                    $added = "pending_in";
                                    $response["request_id"] = $incoming_request_id;
                                } else {
                                    $added = "false";
                                }
                                $incomingStmt->close();
							}
						}
					}
					$response["added"] = $added;
					echo json_encode($response);
				} catch (Exception $e) {
					http_response_code(500);
					echo json_encode(["code" => -1, "msg" => "数据库错误。"]);
				}
				$stmt->close();
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