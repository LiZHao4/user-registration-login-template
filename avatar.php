<?php
	if (isset($_GET["lang"]) && file_exists("languages/" . $_GET["lang"] . ".json")) {
		$language = json_decode(file_get_contents("languages/" . $_GET["lang"] . ".json"), true);
	} else {
		$language = json_decode(file_get_contents("languages/zh-CN.json"), true);
	}
	header('Content-Type: application/json');
	error_reporting(E_ALL);
	if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES['avatar']) && $_FILES['avatar']['error'] == UPLOAD_ERR_OK && isset($_COOKIE['_'])) {
		mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
		function generateRandomFileName() {
			$characters = "0123456789abcdefghijklmnopqrstuvwxyz";
			$charactersLength = strlen($characters);
			$generatedToken = "";
			for ($i = 0; $i < 60; $i++) {
				$generatedToken .= $characters[random_int(0, $charactersLength - 1)];
			}
			return $generatedToken;
		}
		$fileExtension = strtolower(pathinfo($_FILES["avatar"]["name"], PATHINFO_EXTENSION));
		$config = parse_ini_file('conf/settings.ini', true);
		$host = $config['database']['host'];
		$user = $config['database']['user'];
		$pass = $config['database']['pass'];
		$db = $config['database']['db'];
		try {
			$dbConnection = new mysqli($host, $user, $pass, $db);
			$querySelectToken = "SELECT id FROM users WHERE token = ?";
			$stmtSelectToken = $dbConnection->prepare($querySelectToken);
			$stmtSelectToken->bind_param("s", $_COOKIE['_']);
			$stmtSelectToken->execute();
			$resultToken = $stmtSelectToken->get_result();
			$userRow = $resultToken->fetch_assoc();
			if ($userRow) {
				$fileName = generateRandomFileName();
				$userId = $userRow['id'];
				$avatarFilePath = "avatar/" . $fileName . ".jpg";
				if (move_uploaded_file($_FILES["avatar"]["tmp_name"], $avatarFilePath . ".tmp")) {
					$tempFilePath = $avatarFilePath . ".tmp";
					list($imageWidth, $imageHeight) = getimagesize($tempFilePath);
					$targetWidth = 300;
					$targetHeight = 300;
					$sourceImage = null;
					$isSuccess = false;
					switch ($fileExtension) {
						case 'jpg':
							$sourceImage = imagecreatefromjpeg($tempFilePath);
							break;
						case 'png':
							$sourceImage = @imagecreatefrompng($tempFilePath);
							break;
						default:
							unlink($tempFilePath);
							echo json_encode(["code" => -1, "msg" => $language["unsupportedFileType"]]);
							exit;
					}
					if ($sourceImage) {
						$resizedImage = imagecreatetruecolor($targetWidth, $targetHeight);
						imagecopyresampled($resizedImage, $sourceImage, 0, 0, 0, 0, $targetWidth, $targetHeight, $imageWidth, $imageHeight);
						if ($fileExtension === 'png') {
							imagetruecolortopalette($resizedImage, false, 256);
						}
						if (imagejpeg($resizedImage, $avatarFilePath, 100)) {
							$isSuccess = true;
						}
						imagedestroy($sourceImage);
						imagedestroy($resizedImage);
					}
					if (!$isSuccess) {
						unlink($tempFilePath);
						echo json_encode(["code" => -1, "msg" => $language["invalidImage"]]);
						exit;
					}
					unlink($tempFilePath);
					$userAvatarStatement = "SELECT user_avatar FROM users WHERE id = ?";
					$stmtSelectAvatar = $dbConnection->prepare($userAvatarStatement);
					$stmtSelectAvatar->bind_param("i", $userId);
					$stmtSelectAvatar->execute();
					$resultAvatar = $stmtSelectAvatar->get_result();
					$userAvatarRow = $resultAvatar->fetch_assoc();
					if ($userAvatarRow) {
						$oldAvatarFilePath = "avatar/" . $userAvatarRow['user_avatar'] . ".jpg";
						if (file_exists($oldAvatarFilePath)) {
							unlink($oldAvatarFilePath);
						}
					}
					$queryUpdateAvatar = "UPDATE users SET user_avatar = ? WHERE id = ?";
					$stmtUpdateAvatar = $dbConnection->prepare($queryUpdateAvatar);
					$stmtUpdateAvatar->bind_param("si", $fileName, $userId);
					$stmtUpdateAvatar->execute();
					echo json_encode(["code" => 1, "msg" => $language["avatarUpdated"], "avatar" => $fileName]);
				} else {
					echo json_encode(["code" => -1, "msg" => $language["uploadFailed"]]);
				}
			} else {
				echo json_encode(["code" => 0, "msg" => $language["invalidUserOrToken"]]);
			}
			$stmtSelectToken->close();
			$stmtUpdateAvatar->close();
			$dbConnection->close();
		} catch (ErrorException $databaseException) {
			echo json_encode(["code" => -1, "msg" => $language["databaseError"]]);
		}
	} else {
		echo json_encode(["code" => -1, "msg" => $language["invalidRequest"]]);
	}
?>