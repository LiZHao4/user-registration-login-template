<?php
	if (isset($_GET["lang"]) && file_exists("languages/" . $_GET["lang"] . ".json")) {
		$language = json_decode(file_get_contents("languages/" . $_GET["lang"] . ".json"), true);
	} else {
		$language = json_decode(file_get_contents("languages/zh-CN.json"), true);
	}
	// header('Content-Type: application/json');
	if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_COOKIE['_']) && isset($_POST['source'])) {
		mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
		$config = parse_ini_file('conf/settings.ini', true);
		$host = $config['database']['host'];
		$user = $config['database']['user'];
		$pass = $config['database']['pass'];
		$db = $config['database']['db'];
		try {
			$conn = new mysqli($host, $user, $pass, $db);
			$stmt = $conn->prepare("SELECT id FROM users WHERE token = ?");
			$stmt->bind_param('s', $_COOKIE['_']);
			$stmt->execute();
			$result = $stmt->get_result();
			$data = $result->fetch_assoc();
			if ($data) {
				$source = $_POST['source'];
				$stmt = $conn->prepare("SELECT COUNT(*) FROM chats WHERE multi = ?");
				$stmt->bind_param('s', $source);
				$stmt->execute();
				$result = $stmt->get_result();
				$fileData = $result->fetch_assoc();
				if ($fileData) {
					$filePath = "files/" . $source;
					if (file_exists($filePath)) {
						echo json_encode(["code" => 1, "msg" => $language["fileContentFetched"], "content" => $filePath]);
					} else {
						echo json_encode(["code" => -1, "msg" => $language["fileNotFound"]]);
					}
				} else {
					echo json_encode(["code" => -1, "msg" => $language["fileNotFound"]]);
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