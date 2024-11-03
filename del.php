<?php
	if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['id'])) {
		mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
		try {
			$dbConnection = new mysqli("localhost", "yuancheng", "yc@123456", "yc");
			$userId = (int)$_POST["id"];
			$deleteStmt = $dbConnection->prepare("DELETE FROM users WHERE id = ?");
			$deleteStmt->bind_param("i", $userId);
			$deleteStmt->execute();
			if ($deleteStmt->affected_rows > 0) {
				$avatarStmt = $dbConnection->prepare("SELECT avatar FROM users WHERE id = ?");
				$avatarStmt->bind_param("i", $userId);
				$avatarStmt->execute();
				$res = $avatarStmt->get_result();
				if ($res->num_rows > 0) {
					$row = $res->fetch_assoc();
					$avatar = $row['avatar'];
				}
				$avatarStmt->close();
				if ($avatar == 1) {
					$tokenStmt = $dbConnection->prepare("SELECT token FROM users WHERE id = ?");
					$tokenStmt->bind_param("i", $userId);
					$tokenStmt->execute();
					$res2 = $tokenStmt->get_result();
					if ($res2->num_rows > 0) {
						$row2 = $res2->fetch_assoc();
						$token = $row2['token'];
					}
					$tokenStmt->close();
					$avatarPath = "avatar/" . $token . ".jpg";
					if (file_exists($avatarPath)) {
						unlink($avatarPath);
					}
				}
				echo "success";
			} else {
				echo "fail";
			}
			$deleteStmt->close();
			$dbConnection->close();
		} catch (mysqli_sql_exception $sqlException) {
			echo "fail";
		}
	}
?>