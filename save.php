<?php
	if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['id']) && isset($_POST['nick']) && isset($_POST['pass'])) {
		mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
		try {
			$databaseConnection = new mysqli("localhost", "yuancheng", "yc@123456", "yc");
			$userId = (int)$_POST["id"];
			$newNickname = $_POST["nick"];
			$newPassword = $_POST["pass"];
			$updateNicknameStmt = $databaseConnection->prepare("UPDATE users SET nick = ? WHERE id = ?");
			$updateNicknameStmt->bind_param("si", $newNickname, $userId);
			$updateNicknameStmt->execute();
			if ($newPassword) {
				$hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
				$updatePasswordStmt = $databaseConnection->prepare("UPDATE users SET password = ? WHERE id = ?");
				$updatePasswordStmt->bind_param("si", $hashedPassword, $userId);
				$updatePasswordStmt->execute();
			}
			if ($updateNicknameStmt->affected_rows > 0 || $newPassword && $updatePasswordStmt->affected_rows > 0) {
				echo "success";
			} else {
				echo "fail";
			}
			$updateNicknameStmt->close();
			if (isset($updatePasswordStmt)) {
				$updatePasswordStmt->close();
			}
			$databaseConnection->close();
		} catch (mysqli_sql_exception $sqlException) {
			echo "fail";
		}
	}
?>