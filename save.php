<?php
	if (isset($_POST["v"]) && $_POST["v"] == "2") header('Content-Type: application/json');
	else header('Content-Type: text/plain');
	if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['id']) && isset($_POST['token']) && isset($_POST['p'])) {
		mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
		$config = parse_ini_file('conf/settings.ini', true);
		$host = $config['database']['host'];
		$user = $config['database']['user'];
		$pass = $config['database']['pass'];
		$db = $config['database']['db'];
		try {
			$databaseConnection = new mysqli($host, $user, $pass, $db);
			$p = json_decode($_POST['p']);
			if (empty($p) || !is_object($p)) {
				if (isset($_POST["v"]) && $_POST["v"] == "2") echo json_encode(["code" => -1, "msg" => "参数错误"]);
				else echo "fail";
				exit;
			}
			$userId = (int)$_POST['id'];
			$token = $_POST['token'];
			$stmt = $databaseConnection->prepare("SELECT id, token FROM users WHERE id = ?");
			$stmt->bind_param("i", $userId);
			$stmt->execute();
			$res = $stmt->get_result();
			if ($res->num_rows > 0) {
				$row = $res->fetch_assoc();
				if ($row['token'] == $token) {
					$newNickname = isset($p->nick) ? $p->nick : null;
					$newPassword = isset($p->password) ? $p->password : null;
					$newGender = isset($p->gender) ? $p->gender : null;
					$newBirth = isset($p->birth) ? $p->birth : null;
					$newBio = isset($p->bio) ? $p->bio : null;
					if ($newNickname) {
						$st = $databaseConnection->prepare("UPDATE users SET nick = ? WHERE id = ?");
						$st->bind_param("si", $newNickname, $userId);
						$st->execute();
						$st->close();
					}
					if ($newPassword) {
						$hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
						$st = $databaseConnection->prepare("UPDATE users SET password = ? WHERE id = ?");
						$st->bind_param("si", $hashedPassword, $userId);
						$st->execute();
						$st->close();
					}
					if ($newGender) {
						$st = $databaseConnection->prepare("UPDATE users SET gender = ? WHERE id = ?");
						$st->bind_param("si", $newGender, $userId);
						$st->execute();
						$st->close();
					}
					if ($newBirth) {
						$st = $databaseConnection->prepare("UPDATE users SET birth = ? WHERE id = ?");
						$st->bind_param("si", $newBirth, $userId);
						$st->execute();
						$st->close();
					}
					if ($newBio) {
						$st = $databaseConnection->prepare("UPDATE users SET bio = ? WHERE id = ?");
						$st->bind_param("si", $newBio, $userId);
						$st->execute();
						$st->close();
					}
				} else {
					if (isset($_POST["v"]) && $_POST["v"] == "2") echo json_encode(["code" => 0, "msg" => "令牌验证失败，请重新登录"]);
					else echo "fail";
				}
			}
			if (isset($_POST["v"]) && $_POST["v"] == "2") echo json_encode(["code" => 1, "msg" => "更新成功"]);
			else echo "success";
		} catch (mysqli_sql_exception $sqlException) {
			if (isset($_POST["v"]) && $_POST["v"] == "2") echo json_encode(["code" => -1, "msg" => "数据库错误：" . $sqlException->getMessage()]);
			else echo "fail";
		} finally {
			$databaseConnection->close();
		}
	} else {
		if (isset($_POST["v"]) && $_POST["v"] == "2") echo json_encode(["code" => -1, "msg" => "无效的请求方法或缺少必要的参数"]);
		else echo "fail";
	}
?>