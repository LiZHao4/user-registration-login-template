<?php
	if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['id']) && isset($_POST['token'])) {
		mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
		try {
			$dbConnection = new mysqli("localhost", "yuancheng", "yc@123456", "yc");
			$userId = (int)$_POST["id"];
			$token = $_POST["token"];
			$stmt = $dbConnection->prepare("SELECT id, token FROM users WHERE id = ?");
			$stmt->bind_param("i", $userId);
			$stmt->execute();
			$res = $stmt->get_result();
			if ($res->num_rows > 0) {
				$row = $res->fetch_assoc();
				if ($row['token'] == $token) {
					$avatarStmt = $dbConnection->prepare("SELECT user_avatar FROM users WHERE id = ?");
					$avatarStmt->bind_param("i", $userId);
					$avatarStmt->execute();
					$res = $avatarStmt->get_result();
					if ($res->num_rows > 0) {
						$row = $res->fetch_assoc();
						$avatar = $row['user_avatar'];
						if ($avatar == 1) {
							$avatarPath = "avatar/" . $token . ".jpg";
							if (file_exists($avatarPath)) {
								unlink($avatarPath);
							}
						}
					}
					$avatarStmt->close();
					$deleteStmt = $dbConnection->prepare("DELETE FROM users WHERE id = ?");
					$deleteStmt->bind_param("i", $userId);
					$deleteStmt->execute();
					if ($deleteStmt->affected_rows > 0) {
						echo "success";
					} else {
						echo "fail";
					}
					$deleteStmt->close();
				} else {
					echo "fail";
				}
			} else {
				echo "fail";
			}
			$stmt->close();
		} catch (mysqli_sql_exception $sqlException) {
			echo "fail";
		} finally {
			$dbConnection->close();
		}
	}
?>