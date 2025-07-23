<?php
	header("Content-Type: application/json");
	if ($_SERVER["REQUEST_METHOD"] === "POST") {
		if (isset($_COOKIE["_"]) && isset($_POST["target"]) && isset($_POST["remark"])) {
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
					$stmt->close();
					http_response_code(404);
					echo json_encode(["code" => 0, "msg" => "未找到有效的用户，请重新登录。"]);
					$databaseConnection->close();
					exit;
				}
				$targetId = (int)$_POST["target"];
				if ($userId === $targetId) {
				    echo json_encode(["code" => -1, "msg" => "无法对自己设置备注。"]);
					$databaseConnection->close();
					exit;
				}
				$remark = trim($_POST["remark"]);
				$stmt = $databaseConnection->prepare("SELECT remark FROM user_remarks WHERE user_id = ? AND target_user_id = ?");
				$stmt->bind_param("ii", $userId, $targetId);
				$stmt->execute();
				$res = $stmt->get_result();
				if ($res->num_rows > 0) {
					$row = $res->fetch_assoc();
					$existingRemark = $row["remark"];
					if ($remark === $existingRemark) {
						echo json_encode(["code" => 1, "msg" => "备注未改变。"]);
					} else {
						if (empty($remark)) {
							$stmt = $databaseConnection->prepare("DELETE FROM user_remarks WHERE user_id = ? AND target_user_id = ?");
							$stmt->bind_param("ii", $userId, $targetId);
							$stmt->execute();
							if ($stmt->affected_rows > 0) {
								echo json_encode(["code" => 1, "msg" => "备注已删除。"]);
							} else {
								echo json_encode(["code" => -1, "msg" => "备注删除失败，请重试。"]);
							}
						} else {
							$stmt = $databaseConnection->prepare("UPDATE user_remarks SET remark = ? WHERE user_id = ? AND target_user_id = ?");
							$stmt->bind_param("sii", $remark, $userId, $targetId);
							$stmt->execute();
							if ($stmt->affected_rows > 0) {
								echo json_encode(["code" => 1, "msg" => "备注已更新。"]);
							} else {
								echo json_encode(["code" => -1, "msg" => "备注更新失败，请重试。"]);
							}
						}
					}
				} else {
					if (empty($remark)) {
						echo json_encode(["code" => 1, "msg" => "无备注记录。"]);
					} else {
						$stmt = $databaseConnection->prepare("INSERT INTO user_remarks (user_id, target_user_id, remark) VALUES (?, ?, ?)");
						$stmt->bind_param("iis", $userId, $targetId, $remark);
						$stmt->execute();
						if ($stmt->affected_rows > 0) {
							echo json_encode(["code" => 1, "msg" => "备注已添加。"]);
						} else {
							echo json_encode(["code" => -1, "msg" => "备注添加失败，请重试。"]);
						}
					}
				}
			} catch (mysqli_sql_exception $sqlException) {
				http_response_code(500);
				echo json_encode(["code" => -1, "msg" => "数据库错误。"]);
			} finally {
				$databaseConnection->close();
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