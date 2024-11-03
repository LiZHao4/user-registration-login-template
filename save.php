<?php
	if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['id']) && isset($_POST['p'])) {
		mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
		try {
			$databaseConnection = new mysqli("localhost", "yuancheng", "yc@123456", "yc");
			$p = json_decode($_POST['p']);
			$userId = (int)$_POST['id'];
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
			echo "success";
			$databaseConnection->close();
		} catch (mysqli_sql_exception $sqlException) {
			echo "fail";
		}
	}
?>