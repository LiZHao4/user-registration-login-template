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