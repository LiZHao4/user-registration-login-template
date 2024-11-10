<?php
	if (isset($_GET["lang"]) && file_exists("languages/" . $_GET["lang"] . ".json")) {
		$language = json_decode(file_get_contents("languages/" . $_GET["lang"] . ".json"), true);
	} else {
		$language = json_decode(file_get_contents("languages/zh-CN.json"), true);
	}
	header('Content-Type: application/json');
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
				echo json_encode(["code" => -1, "msg" => $language["parameterError"]]);
				exit;
			}
			$userId = (int)$_POST['id'];
			$token = $_POST['token'];
			$stmt = $databaseConnection->prepare("SELECT token FROM users WHERE id = ?");
			$stmt->bind_param("i", $userId);
			$stmt->execute();
			$res = $stmt->get_result();
			if ($res->num_rows > 0) {
				$row = $res->fetch_assoc();
				if (hash_equals($row['token'], $token)) {
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
					echo json_encode(["code" => 0, "msg" => $language["invalidUserOrToken"]]);
				}
			}
			echo json_encode(["code" => 1, "msg" => $language["updateSuccess"]]);
		} catch (mysqli_sql_exception $sqlException) {
			echo json_encode(["code" => -1, "msg" => $language["databaseError"]]);
		} finally {
			$databaseConnection->close();
		}
	} else {
		echo json_encode(["code" => -1, "msg" => $language["invalidRequest"]]);
	}
?>