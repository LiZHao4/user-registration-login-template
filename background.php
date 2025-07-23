<?php
	require 'main.php';
	header("Content-Type: application/json");
	error_reporting(E_ALL);
	if ($_SERVER["REQUEST_METHOD"] == "POST") {
		if (isset($_FILES["background"]) && $_FILES["background"]["error"] == UPLOAD_ERR_OK && isset($_COOKIE["_"])) {
			mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
			$config = parse_ini_file("conf/settings.ini", true);
			$host = $config["database"]["host"];
			$user = $config["database"]["user"];
			$pass = $config["database"]["pass"];
			$db = $config["database"]["db"];
			try {
				$dbConnection = new mysqli($host, $user, $pass, $db);
				$userId = null;
				$querySelectSessionToken = "SELECT user FROM user_session WHERE token = ? AND expires >= NOW()";
				$stmtSelectSessionToken = $dbConnection->prepare($querySelectSessionToken);
				$stmtSelectSessionToken->bind_param("s", $_COOKIE["_"]);
				$stmtSelectSessionToken->execute();
				$resultSessionToken = $stmtSelectSessionToken->get_result();
				$userRow = $resultSessionToken->fetch_assoc();
				if (!$userRow) {
					http_response_code(401);
					echo json_encode(["code" => 0, "msg" => "用户未认证。"]);
					exit;
				}
				$userId = isset($userRow["user"]) ? $userRow["user"] : $userRow["id"];
				$queryOldBg = "SELECT background FROM users WHERE id = ?";
				$stmtOldBg = $dbConnection->prepare($queryOldBg);
				$stmtOldBg->bind_param("i", $userId);
				$stmtOldBg->execute();
				$oldBgResult = $stmtOldBg->get_result();
				$oldBgRow = $oldBgResult->fetch_assoc();
				$oldBackgroundPath = $oldBgRow['background'] ?? null;
				$allowedTypes = ['image/jpeg', 'image/png'];
				$maxSize = 20971520;
				if (!in_array($_FILES['background']['type'], $allowedTypes)) {
					http_response_code(400);
					echo json_encode(["code" => -1, "msg" => "仅支持JPEG和PNG格式的图片。"]);
					exit;
				}
				if ($_FILES['background']['size'] > $maxSize) {
					http_response_code(400);
					echo json_encode(["code" => -1, "msg" => "图片大小不能超过20MB。"]);
					exit;
				}
				$extension = pathinfo($_FILES['background']['name'], PATHINFO_EXTENSION);
				$randomName = generateRandomFileName() . '.' . $extension;
				$relativePath = "/bg/" . $randomName;
				$absolutePath = __DIR__ . $relativePath;
				$uploadDir = dirname($absolutePath);
				if (!is_dir($uploadDir)) {
					mkdir($uploadDir, 0755, true);
				}
				if (!move_uploaded_file($_FILES['background']['tmp_name'], $absolutePath)) {
					http_response_code(500);
					echo json_encode(["code" => -1, "msg" => "文件上传失败。"]);
					exit;
				}
				function calculateDominantColor($imagePath) {
					if (!file_exists($imagePath)) {
						return null;
					}
					list($width, $height) = getimagesize($imagePath);
					$thumbSize = 100;
					$thumb = imagecreatetruecolor($thumbSize, $thumbSize);
					$extension = strtolower(pathinfo($imagePath, PATHINFO_EXTENSION));
					if ($extension == 'jpg' || $extension == 'jpeg') {
						$source = imagecreatefromjpeg($imagePath);
					} else if ($extension == 'png') {
						$source = imagecreatefrompng($imagePath);
					} else {
						return null;
					}
					imagecopyresampled($thumb, $source, 0, 0, 0, 0, $thumbSize, $thumbSize, $width, $height);
					$rTotal = $gTotal = $bTotal = 0;
					$pixelCount = $thumbSize * $thumbSize;
					for ($x = 0; $x < $thumbSize; $x++) {
						for ($y = 0; $y < $thumbSize; $y++) {
							$rgb = imagecolorat($thumb, $x, $y);
							$r = ($rgb >> 16) & 0xFF;
							$g = ($rgb >> 8) & 0xFF;
							$b = $rgb & 0xFF;
							$rTotal += $r;
							$gTotal += $g;
							$bTotal += $b;
						}
					}
					$rAvg = round($rTotal / $pixelCount);
					$gAvg = round($gTotal / $pixelCount);
					$bAvg = round($bTotal / $pixelCount);
					imagedestroy($source);
					imagedestroy($thumb);
					return pack('C3', $rAvg, $gAvg, $bAvg);
				}
				$themeColorBinary = calculateDominantColor($absolutePath);
				$queryUpdate = "UPDATE users SET background = ?, theme_color = ? WHERE id = ?";
				$stmtUpdate = $dbConnection->prepare($queryUpdate);
				$null = null;
				$stmtUpdate->bind_param("sbi", $relativePath, $null, $userId);
				$stmtUpdate->send_long_data(1, $themeColorBinary);
				if ($stmtUpdate->execute()) {
					if ($oldBackgroundPath && file_exists(__DIR__ . $oldBackgroundPath)) {
						unlink(__DIR__ . $oldBackgroundPath);
					}
					echo json_encode(["code" => 1, "msg" => "背景图片上传成功。", "background" => $relativePath]);
				} else {
					unlink($absolutePath);
					http_response_code(500);
					echo json_encode(["code" => -1, "msg" => "数据库更新失败。"]);
				}
				$dbConnection->close();
			} catch (Exception $e) {
				http_response_code(500);
				echo json_encode(["code" => -1, "msg" => "数据库错误。"]);
			}
		} else {
			$errorMsg = "文件上传错误";
			if (isset($_FILES["background"])) {
				$errorMsg = "错误代码: " . $_FILES["background"]["error"];
			}
			http_response_code(400);
			echo json_encode(["code" => -1, "msg" => "文件上传失败: " . $errorMsg]);
		}
	} else {
		http_response_code(405);
		echo json_encode(["code" => -1, "msg" => "仅支持POST请求。"]);
	}
?>