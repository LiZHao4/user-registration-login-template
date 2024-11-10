<?php
	if (isset($_GET["lang"]) && file_exists("languages/" . $_GET["lang"] . ".json")) {
		$language = json_decode(file_get_contents("languages/" . $_GET["lang"] . ".json"), true);
	} else {
		$language = json_decode(file_get_contents("languages/zh-CN.json"), true);
	}
	header('Content-Type: application/json');
	if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['token']) && isset($_POST['target']) && isset($_POST['msg'])) {
		if (trim($_POST['msg']) === '') {
			echo json_encode(["code" => -1, "msg" => $language["invalidRequest"]]);
			exit;
		}
		mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
		$config = parse_ini_file('conf/settings.ini', true);
		$host = $config['database']['host'];
		$user = $config['database']['user'];
		$pass = $config['database']['pass'];
		$db = $config['database']['db'];
		try {
			$conn = new mysqli($host, $user, $pass, $db);
			$stmt = $conn->prepare("SELECT id FROM users WHERE token = ?");
			$stmt->bind_param('s', $_POST['token']);
			$stmt->execute();
			$result = $stmt->get_result();
			$data = $result->fetch_assoc();
			if ($data) {
				$userId = $data['id'];
				$target = (int)$_POST['target'];
				$testStmt = $conn->prepare("SELECT id, source, target FROM friendships WHERE id = ? AND (source = ? OR target = ?)");
				$testStmt->bind_param('iii', $target, $userId, $userId);
				$testStmt->execute();
				$testResult = $testStmt->get_result();
				$testData = $testResult->fetch_assoc();
				if ($testData) {
					$trimmedMsg = trim($_POST['msg']);
					$addStmt = $conn->prepare("INSERT INTO chats (session, content, sender) VALUES (?, ?, ?)");
					$addStmt->bind_param('ssi', $target, $trimmedMsg, $userId);
					$addStmt->execute();
					echo json_encode(["code" => 1, "msg" => $language["sendSuccessMessage"]]);
				} else {
					echo json_encode(["code" => -1, "msg" => $language["invalidRequest"]]);
				}
			} else {
				echo json_encode(["code" => 0, "msg" => $language["invalidUserOrToken"]]);
			}
			$stmt->close();
			$conn->close();
		} catch (mysqli_sql_exception $sqlException) {
			echo json_encode(["code" => -1, "msg" => $language["databaseError"]]);
		}
	} else {
		echo json_encode(["code" => -1, "msg" => $language["invalidRequest"]]);
	}
?>