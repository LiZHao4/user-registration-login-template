<?php
	header("Content-Type: application/json");
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
			$stmt = $databaseConnection->prepare("SELECT id, user, CASE WHEN creation IS NOT NULL THEN UNIX_TIMESTAMP(creation) ELSE NULL END AS creation, UNIX_TIMESTAMP(expires) AS expire FROM user_session WHERE token = ? AND expires >= NOW()");
			$stmt->bind_param("s", $token);
			$stmt->execute();
			$res = $stmt->get_result();
			$stmt->close();
			if ($res->num_rows === 0) {
				http_response_code(401);
				echo json_encode(["code" => 0, "msg" => "用户不存在或token无效，请重新登录。"]);
				exit;
			}
			$row = $res->fetch_assoc();
			$userId = $row["user"];
			$currentSessionId = $row["id"];
			if ($_SERVER["REQUEST_METHOD"] === "GET") {
				$currentToken = [
					"id" => $row["id"],
					"creation" => $row["creation"],
					"expire" => $row["expire"]
				];
				$stmt = $databaseConnection->prepare("SELECT id, CASE WHEN creation IS NOT NULL THEN UNIX_TIMESTAMP(creation) ELSE NULL END AS creation, UNIX_TIMESTAMP(expires) AS expire FROM user_session WHERE user = ? AND token != ?");
				$stmt->bind_param("is", $userId, $token);
				$stmt->execute();
				$res = $stmt->get_result();
				$stmt->close();
				$tokens = $res->fetch_all(MYSQLI_ASSOC);
				echo json_encode(["code" => 1, "msg" => "Token查询成功", "current" => $currentToken, "tokens" => $tokens]);
			} elseif ($_SERVER["REQUEST_METHOD"] === "POST") {
				if (!isset($_POST["id"])) {
					http_response_code(400);
					echo json_encode(["code" => -1, "msg" => "缺少token ID参数。"]);
					exit;
				}
				$tokenId = (int)$_POST["id"];
				$stmt = $databaseConnection->prepare("SELECT id FROM user_session WHERE id = ? AND user = ?");
				$stmt->bind_param("ii", $tokenId, $userId);
				$stmt->execute();
				$res = $stmt->get_result();
				$stmt->close();
				if ($res->num_rows === 0) {
					http_response_code(403);
					echo json_encode(["code" => -1, "msg" => "无权操作此token。"]);
					exit;
				}
				$stmt = $databaseConnection->prepare("DELETE FROM user_session WHERE id = ?");
				$stmt->bind_param("i", $tokenId);
				$stmt->execute();
				$affectedRows = $stmt->affected_rows;
				$stmt->close();
				if ($affectedRows > 0) {
					echo json_encode(["code" => 1, "msg" => "Token已成功下线。"]);
				} else {
					echo json_encode(["code" => -1, "msg" => "操作失败，可能token不存在或已过期。"]);
				}
			} elseif ($_SERVER["REQUEST_METHOD"] === "DELETE") {
				$stmt = $databaseConnection->prepare("DELETE FROM user_session WHERE user = ? AND token != ?");
				$stmt->bind_param("is", $userId, $token);
				$stmt->execute();
				$affectedRows = $stmt->affected_rows;
				$stmt->close();
				echo json_encode(["code" => 1, "msg" => "已下线所有其他设备的登录凭证", "count" => $affectedRows]);
			} else {
				http_response_code(405);
				echo json_encode(["code" => -1, "msg" => "请求方法不正确。"]);
			}
			$databaseConnection->close();
		} catch (mysqli_sql_exception $sqlException) {
			http_response_code(500);
			echo json_encode(["code" => -1, "msg" => "数据库错误: "]);
			if (isset($databaseConnection)) {
				$databaseConnection->close();
			}
		}
	} else {
		http_response_code(400);
		echo json_encode(["code" => -1, "msg" => "缺少必要的参数。"]);
	}
?>