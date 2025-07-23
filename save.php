<?php
	header("Content-Type: application/json");
	if ($_SERVER["REQUEST_METHOD"] === "POST") {
		if (isset($_COOKIE["_"]) && isset($_POST["p"])) {
			mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
			$config = parse_ini_file("conf/settings.ini", true);
			$host = $config["database"]["host"];
			$user = $config["database"]["user"];
			$pass = $config["database"]["pass"];
			$db = $config["database"]["db"];
			try {
				$databaseConnection = new mysqli($host, $user, $pass, $db);
				$p = json_decode($_POST["p"]);
				if (empty($p) || !is_object($p)) {
					http_response_code(400);
					echo json_encode(["code" => -1, "msg" => "参数错误。"]);
					$databaseConnection->close();
					exit;
				}
				$token = $_COOKIE["_"];
				$stmt = $databaseConnection->prepare("SELECT user FROM user_session WHERE token = ? AND expires >= NOW()");
				$stmt->bind_param("s", $token);
				$stmt->execute();
				$res = $stmt->get_result();
				$stmt->close();
				if ($res->num_rows > 0) {
					$row = $res->fetch_assoc();
					$userId = $row["user"];
				} else {
					http_response_code(404);
					echo json_encode(["code" => 0, "msg" => "未找到有效的用户，请重新登录。"]);
					$databaseConnection->close();
					exit;
				}
				$newNickname = isset($p->nick) ? $p->nick : null;
				$newPassword = isset($p->pass) ? $p->pass : null;
				$newGender = isset($p->gender) ? $p->gender : null;
				$newBirth = isset($p->birth) ? $p->birth : null;
				$newBio = isset($p->bio) ? $p->bio : null;
				if ($newNickname) {
					if (mb_strlen($newNickname) > 100) {
						http_response_code(400);
						echo json_encode(["code" => -1, "msg" => "昵称长度不能超过100个字符。"]);
						$databaseConnection->close();
						exit;
					}
					$st = $databaseConnection->prepare("UPDATE users SET nick = ? WHERE id = ?");
					$st->bind_param("si", $newNickname, $userId);
					$st->execute();
					$st->close();
				}
				if ($newPassword) {
					if (strlen($password) > 32) {
						http_response_code(400);
						echo json_encode(["code" => -1, "msg" => "密码过长。"]);
						$databaseConnection->close();
						exit;
					}
					$hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
					$st = $databaseConnection->prepare("UPDATE users SET password = ? WHERE id = ?");
					$st->bind_param("si", $hashedPassword, $userId);
					$st->execute();
					$st->close();
				}
				if ($newGender) {
					$allowedGenders = ['M', 'W', 'N'];
					if (!in_array($newGender, $allowedGenders)) {
						http_response_code(400);
						echo json_encode(["code" => -1, "msg" => "性别参数无效。"]);
						$databaseConnection->close();
						exit;
					}
					$st = $databaseConnection->prepare("UPDATE users SET gender = ? WHERE id = ?");
					$st->bind_param("si", $newGender, $userId);
					$st->execute();
					$st->close();
				}
				if (isset($p->birth)) {
					$newBirth = $p->birth;
					if ($newBirth === "") {
						$newBirth = null;
					}
					try {
						$birthday = new DateTime($newBirth);
					} catch (Exception $e) {
						http_response_code(400);
						echo json_encode(["code" => -1, "msg" => "出生日期格式不正确。"]);
						$databaseConnection->close();
						exit;
					}
					$today = new DateTime();
					if ($birthday > $today) {
						http_response_code(400);
						echo json_encode(["code" => -1, "msg" => "出生日期不能是未来的日期。"]);
						$databaseConnection->close();
						exit;
					}
					$st = $databaseConnection->prepare("UPDATE users SET birth = ? WHERE id = ?");
					$st->bind_param("si", $newBirth, $userId);
					$st->execute();
					$st->close();
				}
				if ($newBio) {
					if (mb_strlen($newBio) > 1024) {
						http_response_code(400);
						echo json_encode(["code" => -1, "msg" => "个人简介过长。"]);
						$databaseConnection->close();
						exit;
					}
					$st = $databaseConnection->prepare("UPDATE users SET bio = ? WHERE id = ?");
					$st->bind_param("si", $newBio, $userId);
					$st->execute();
					$st->close();
				}
				echo json_encode(["code" => 1, "msg" => "更新成功！"]);
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