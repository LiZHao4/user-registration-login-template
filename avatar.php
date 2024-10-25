<?php
	error_reporting(E_ALL);
	if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES['avatar']) && $_FILES['avatar']['error'] == UPLOAD_ERR_OK && isset($_POST['id'])&& isset($_POST['token'])) {
		mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
		$fileExtension = strtolower(pathinfo($_FILES["avatar"]["name"], PATHINFO_EXTENSION));
		$userId = $_POST['id'];
		$config = parse_ini_file('conf/settings.ini', true);
		$host = $config['database']['host'];
		$user = $config['database']['user'];
		$pass = $config['database']['pass'];
		$db = $config['database']['db'];
		$table = $config['database']['table'];
		try {
			$dbConnection = new mysqli($host, $user, $pass, $db);
			$querySelectToken = "SELECT token FROM $table WHERE id = ?";
			$stmtSelectToken = $dbConnection->prepare($querySelectToken);
			$stmtSelectToken->bind_param("i", $userId);
			$stmtSelectToken->execute();
			$resultToken = $stmtSelectToken->get_result();
			$userRow = $resultToken->fetch_assoc();
			$userToken = $userRow['token'];
			$avatarFilePath = "avatar/" . $userToken . ".jpg";
			if (move_uploaded_file($_FILES["avatar"]["tmp_name"], $avatarFilePath . ".tmp")) {
				$tempFilePath = $avatarFilePath . ".tmp";
				list($imageWidth, $imageHeight) = getimagesize($tempFilePath);
				$targetWidth = 300;
				$targetHeight = 300;
				switch ($fileExtension) {
					case 'jpg':
						$sourceImage = imagecreatefromjpeg($tempFilePath);
						$resizedImage = imagecreatetruecolor($targetWidth, $targetHeight);
						imagecopyresampled($resizedImage, $sourceImage, 0, 0, 0, 0, $targetWidth, $targetHeight, $imageWidth, $imageHeight);
						imagejpeg($resizedImage, $avatarFilePath, 100);
						break;
					case 'png':
						$sourcePng = @imagecreatefrompng($tempFilePath);
						$resizedPng = imagecreatetruecolor($targetWidth, $targetHeight);
						imagealphablending($resizedPng, false);
						imagesavealpha($resizedPng, true);
						imagecopyresampled($resizedPng, $sourcePng, 0, 0, 0, 0, $targetWidth, $targetHeight, $imageWidth, $imageHeight);
						imagepng($resizedPng, $avatarFilePath, 9);
						break;
					default:
						exit("fail");
				}
				unlink($tempFilePath);
				$queryUpdateAvatar = "UPDATE $table SET user_avatar = 1 WHERE id = ?";
				$stmtUpdateAvatar = $dbConnection->prepare($queryUpdateAvatar);
				$stmtUpdateAvatar->bind_param("i", $userId);
				$stmtUpdateAvatar->execute();
				echo "success";
				$stmtSelectToken->close();
				$stmtUpdateAvatar->close();
				$dbConnection->close();
			}
		} catch (ErrorException $databaseException) {
			echo "fail";
		}
	}
?>