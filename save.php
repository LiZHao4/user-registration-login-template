<?php
	if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['id']) && isset($_POST['token']) && isset($_POST['p'])) {
		mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
		$config = parse_ini_file('conf/settings.ini', true);
		$host = $config['database']['host'];
		$user = $config['database']['user'];
		$pass = $config['database']['pass'];
		$db = $config['database']['db'];
		$table = $config['database']['table'];
		try {
			$databaseConnection = new mysqli($host, $user, $pass, $db);
			$p = json_decode($_POST['p']);
			if (empty($p) || !is_object($p)) {
				echo "fail";
				exit;
			}
			$userId = (int)$_POST['id'];
			$token = $_POST['token'];
			$stmt = $databaseConnection->prepare("SELECT id, token FROM $table WHERE id = ?");
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
						$st = $databaseConnection->prepare("UPDATE $table SET nick = ? WHERE id = ?");
						$st->bind_param("si", $newNickname, $userId);
						$st->execute();
						$st->close();
					}
					if ($newPassword) {
						$hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
						$st = $databaseConnection->prepare("UPDATE $table SET password = ? WHERE id = ?");
						$st->bind_param("si", $hashedPassword, $userId);
						$st->execute();
						$st->close();
					}
					if ($newGender) {
						$st = $databaseConnection->prepare("UPDATE $table SET gender = ? WHERE id = ?");
						$st->bind_param("si", $newGender, $userId);
						$st->execute();
						$st->close();
					}
					if ($newBirth) {
						$st = $databaseConnection->prepare("UPDATE $table SET birth = ? WHERE id = ?");
						$st->bind_param("si", $newBirth, $userId);
						$st->execute();
						$st->close();
					}
					if ($newBio) {
						$st = $databaseConnection->prepare("UPDATE $table SET bio = ? WHERE id = ?");
						$st->bind_param("si", $newBio, $userId);
						$st->execute();
						$st->close();
					}
				}
			}
			echo "success";
		} catch (mysqli_sql_exception $sqlException) {
			echo "fail";
		} finally {
			$databaseConnection->close();
		}
	}
?>