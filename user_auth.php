<?php
	if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['user']) && isset($_POST['pass']) && isset($_POST['from'])) {
		function findNextAvailableUserId() {
			global $db;
			$currentId = -1;
			do {
				$currentId++;
				$result = $db->query("SELECT * FROM users WHERE id = $currentId");
			} while ($result && $result->num_rows > 0);
			$result->free_result();
			return $currentId;
		}
		function generateRandomToken() {
			$characters = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
			$charactersLength = strlen($characters);
			$generatedToken = "";
			for ($i = 0; $i < 32; $i++) {
				$generatedToken .= $characters[random_int(0, $charactersLength - 1)];
			}
			return $generatedToken;
		}
		mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
		try {
			$db = new mysqli("localhost", "yuancheng", "yc@123456", "yc");
			$username = $_POST["user"];
			$password = $_POST["pass"];
			$action = $_POST["from"];
			if ($action == "regist") {
				$nextUserId = findNextAvailableUserId();
				$stmtCheckUsername = $db->prepare("SELECT COUNT(*) AS count FROM users WHERE user = ?");
				$stmtCheckUsername->bind_param("s", $username);
				$stmtCheckUsername->execute();
				$resultCheckUsername = $stmtCheckUsername->get_result();
				$rowCheckUsername = $resultCheckUsername->fetch_assoc();
				if ($rowCheckUsername["count"] > 0) {
					echo "exist";
					$stmtCheckUsername->close();
					$db->close();
					exit();
				}
				$tokenForNewUser = generateRandomToken();
				$hashedPassword = password_hash($password, PASSWORD_DEFAULT);
				$stmtInsertUser = $db->prepare("INSERT INTO users (id, user, password, token, nick) VALUES (?, ?, ?, ?, ?)");
				$stmtInsertUser->bind_param("issss", $nextUserId, $username, $hashedPassword, $tokenForNewUser, $username);
				$stmtInsertUser->execute();
				echo "success";
				$stmtCheckUsername->close();
				$stmtInsertUser->close();
			} else if ($action == "login") {
				$stmtLogin = $db->prepare("SELECT password, token FROM users WHERE user = ?");
				$stmtLogin->bind_param("s", $username);
				$stmtLogin->execute();
				$resultLogin = $stmtLogin->get_result();
				if ($resultLogin && $rowLogin = $resultLogin->fetch_assoc()) {
					if (password_verify($_POST["pass"], $rowLogin["password"])) {
						echo $rowLogin["token"];
					} else {
						echo "wrong";
					}
				} else {
					echo "not_exist";
				}
				$stmtLogin->close();
			}
			$db->close();
		} catch (mysqli_sql_exception $e) {
			echo "fail";
		}
	}
?>