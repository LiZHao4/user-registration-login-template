<?php
	if (isset($_GET["lang"]) && file_exists("languages/" . $_GET["lang"] . ".json")) {
		$language = json_decode(file_get_contents("languages/" . $_GET["lang"] . ".json"), true);
	} else {
		$language = json_decode(file_get_contents("languages/zh-CN.json"), true);
	}
	header('Content-Type: application/json');
	if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_COOKIE['_']) && isset($_FILES['file']) && isset($_POST['target'])) {
		$uploadedFile = $_FILES['file'];
		if ($uploadedFile['error'] == UPLOAD_ERR_OK) {
			$randomFileName = substr(str_shuffle(str_repeat('0123456789abcdefghijklmnopqrstuvwxyz', 34)), 0, 80);
			$filePath = 'files/' . DIRECTORY_SEPARATOR . $randomFileName;
			if (move_uploaded_file($uploadedFile['tmp_name'], $filePath)) {
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
						$userId = $data['id'];
						$target = (int)$_POST['target'];
						$sql = "INSERT INTO chats (session, multi, content, sender) VALUES (?, ?, ?, ?)";
						$prepareStmt = $conn->prepare($sql);
						$prepareStmt->bind_param("issi", $target, $randomFileName, $uploadedFile['name'], $userId);
						$prepareStmt->execute();
						echo json_encode(["code" => 1, "msg" => $language["uploadSuccess"]]);
						$prepareStmt->close();
						$conn->close();
					} else {
						echo json_encode(["code" => 0, "msg" => $language["invalidUserOrToken"]]);
					}
				} catch (mysqli_sql_exception $sqlException) {
					echo json_encode(["code" => -1, "msg" => $language["databaseError"]]);
				}
				
			} else {
				echo json_encode(["code" => -1, "msg" => $language["fileUploadFailed"]]);
			}
		} else {
			echo json_encode(["code" => -1, "msg" => $language["fileUploadFailed"]]);
		}
	} else {
		echo json_encode(["code" => -1, "msg" => $language["invalidRequest"]]);
	}
?>