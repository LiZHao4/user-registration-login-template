<?php
	require 'main.php';
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
				$databaseConnection = new mysqli($host, $user, $pass, $db);
				$token = $_COOKIE["_"];
				$stmt = $databaseConnection->prepare("SELECT user, expires FROM user_session WHERE token = ? AND expires >= NOW()");
				$stmt->bind_param("s", $token);
				$stmt->execute();
				$res = $stmt->get_result();
				$stmt->close();
				if ($res->num_rows > 0) {
					$row = $res->fetch_assoc();
					$userId = $row["user"];
					if (isset($_POST["action"])) {
						$action = $_POST["action"];
						if ($action === "value") {
							$newToken = generateRandomToken();
							$updateStatement = $databaseConnection->prepare("UPDATE user_session SET token = ? WHERE token = ?");
							$updateStatement->bind_param("ss", $newToken, $token);
							$updateStatement->execute();
							$updateStatement->close();
							setcookie("_", $newToken, [
								'expires' => $row["expires"],
								'path' => '/',
								'httponly' => true,
								'samesite' => 'Lax'
							]);
							echo json_encode(["code" => 1, "msg" => "Token值更新成功。", "token" => $newToken, "expires" => $row["expires"]]);
						} elseif ($action === "expire") {
							$seconds = isset($_POST['seconds']) ? (int)$_POST['seconds'] : 2592000;
							if ($seconds < 600 || $seconds > 7776000) {
								http_response_code(400);
								echo json_encode(["code" => 0, "msg" => "过期时间必须在600到7776000秒之间。"]);
								$databaseConnection->close();
								exit;
							}
							$newExpires = time() + $seconds;
							$updateStatement = $databaseConnection->prepare("UPDATE user_session SET expires = FROM_UNIXTIME(?) WHERE token = ?");
							$updateStatement->bind_param("is", $newExpires, $token);
							$updateStatement->execute();
							$updateStatement->close();
							setcookie("_", $token, [
								'expires' => $newExpires,
								'path' => '/',
								'httponly' => true,
								'samesite' => 'Lax'
							]);
							echo json_encode(["code" => 1, "msg" => "Token过期时间更新成功。", "expires" => $newExpires]);
						} else {
							http_response_code(400);
							echo json_encode(["code" => 0, "msg" => "无效的操作。"]);
						}
					} else {
						$newExpires = strtotime("+30 days");
						$updateStatement = $databaseConnection->prepare("UPDATE user_session SET expires = FROM_UNIXTIME(?) WHERE token = ?");
						$updateStatement->bind_param("is", $newExpires, $token);
						$updateStatement->execute();
						$updateStatement->close();
						echo json_encode(["code" => 1, "msg" => "Token过期时间已刷新。", "expires" => $newExpires]);
					}
				} else {
					http_response_code(401);
					echo json_encode(["code" => 0, "msg" => "用户不存在或token无效，请重新登录。"]);
				}
				$databaseConnection->close();
			} catch (mysqli_sql_exception $sqlException) {
				http_response_code(500);
				echo json_encode(["code" => -1, "msg" => "数据库错误。"]);
				if (isset($databaseConnection)) {
					$databaseConnection->close();
				}
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