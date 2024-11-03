<?php
	if (isset($_GET["lang"]) && file_exists("languages/" . $_GET["lang"] . ".json")) {
		$language = json_decode(file_get_contents("languages/" . $_GET["lang"] . ".json"), true);
	} else {
		$language = json_decode(file_get_contents("languages/zh-CN.json"), true);
	}
	if (isset($_POST["v"]) && $_POST["v"] == "2") header('Content-Type: application/json');
	else header('Content-Type: text/plain');
	error_reporting(E_ALL);
	if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES['avatar']) && $_FILES['avatar']['error'] == UPLOAD_ERR_OK && isset($_POST['id']) && isset($_POST['token'])) {
		mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
		$fileExtension = strtolower(pathinfo($_FILES["avatar"]["name"], PATHINFO_EXTENSION));
		$userId = $_POST['id'];
		$config = parse_ini_file('conf/settings.ini', true);
		$host = $config['database']['host'];
		$user = $config['database']['user'];
		$pass = $config['database']['pass'];
		$db = $config['database']['db'];
		try {
			$dbConnection = new mysqli($host, $user, $pass, $db);
			$querySelectToken = "SELECT token FROM users WHERE id = ?";
			$stmtSelectToken = $dbConnection->prepare($querySelectToken);
			$stmtSelectToken->bind_param("i", $userId);
			$stmtSelectToken->execute();
			$resultToken = $stmtSelectToken->get_result();
			$userRow = $resultToken->fetch_assoc();
			if ($userRow && hash_equals($userRow['token'], $_POST['token'])) {
				$avatarFilePath = "avatar/" . $_POST['token'] . ".jpg";
				if (move_uploaded_file($_FILES["avatar"]["tmp_name"], $avatarFilePath . ".tmp")) {
					$tempFilePath = $avatarFilePath . ".tmp";
					list($imageWidth, $imageHeight) = getimagesize($tempFilePath);
					$targetWidth = 300;
					$targetHeight = 300;
					switch ($fileExtension) {
						case 'jpg':
							$sourceImage = imagecreatefromjpeg($tempFilePath);
							if ($sourceImage === false) {
								unlink($tempFilePath);
								if (isset($_POST["v"]) && $_POST["v"] == "2") echo json_encode(["code" => -1, "msg" => $language["invalidImage"]]);
								else echo "fail";
								exit;
							}
							$resizedImage = imagecreatetruecolor($targetWidth, $targetHeight);
							imagecopyresampled($resizedImage, $sourceImage, 0, 0, 0, 0, $targetWidth, $targetHeight, $imageWidth, $imageHeight);
							imagejpeg($resizedImage, $avatarFilePath, 100);
							unlink($tempFilePath);
							$isSuccess = true;
							break;
						case 'png':
							$sourcePng = @imagecreatefrompng($tempFilePath);
							if ($sourcePng === false) {
								unlink($tempFilePath);
								if (isset($_POST["v"]) && $_POST["v"] == "2") echo json_encode(["code" => -1, "msg" => $language["invalidImage"]]);
								else echo "fail";
								exit;
							}
							$resizedPng = imagecreatetruecolor($targetWidth, $targetHeight);
							imagealphablending($resizedPng, false);
							imagesavealpha($resizedPng, true);
							imagecopyresampled($resizedPng, $sourcePng, 0, 0, 0, 0, $targetWidth, $targetHeight, $imageWidth, $imageHeight);
							imagepng($resizedPng, $avatarFilePath, 9);
							unlink($tempFilePath);
							$isSuccess = true;
							break;
						default:
							unlink($tempFilePath);
							if (isset($_POST["v"]) && $_POST["v"] == "2") echo json_encode(["code" => -1, "msg" => $language["unsupportedFileType"]]);
							else echo "fail";
							exit;
					}
					if ($isSuccess) {
						$queryUpdateAvatar = "UPDATE users SET user_avatar = 1 WHERE id = ?";
						$stmtUpdateAvatar = $dbConnection->prepare($queryUpdateAvatar);
						$stmtUpdateAvatar->bind_param("i", $userId);
						$stmtUpdateAvatar->execute();
						if (isset($_POST["v"]) && $_POST["v"] == "2") echo json_encode(["code" => 1, "msg" => $language["avatarUpdated"]]);
						else echo "success";
					}
					$stmtSelectToken->close();
					$stmtUpdateAvatar->close();
				} else {
					if (isset($_POST["v"]) && $_POST["v"] == "2") echo json_encode(["code" => -1, "msg" => $language["uploadFailed"]]);
					else echo "fail";
				}
			} else {
				if (isset($_POST["v"]) && $_POST["v"] == "2") echo json_encode(["code" => 0, "msg" => $language["invalidUserOrToken"]]);
				else echo "fail";
			}
			$dbConnection->close();
		} catch (ErrorException $databaseException) {
			if (isset($_POST["v"]) && $_POST["v"] == "2") echo json_encode(["code" => -1, "msg" => $language["databaseError"]]);
			else echo "fail";
		}
	} else {
		if (isset($_POST["v"]) && $_POST["v"] == "2") echo json_encode(["code" => -1, "msg" => $language["invalidRequest"]]);
		else echo "fail";
	}
?>