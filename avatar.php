<?php
	require 'main.php';
	header("Content-Type: application/json");
	error_reporting(E_ALL);
	if ($_SERVER["REQUEST_METHOD"] == "POST") {
		if (isset($_FILES["avatar"]) && $_FILES["avatar"]["error"] == UPLOAD_ERR_OK && isset($_COOKIE["_"])) {
			mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
			$config = parse_ini_file("conf/settings.ini", true);
			$host = $config["database"]["host"];
			$user = $config["database"]["user"];
			$pass = $config["database"]["pass"];
			$db = $config["database"]["db"];
			try {
				$dbConnection = new mysqli($host, $user, $pass, $db);
				$querySelectSessionToken = "SELECT user FROM user_session WHERE token = ? AND expires >= NOW()";
				$stmtSelectSessionToken = $dbConnection->prepare($querySelectSessionToken);
				$stmtSelectSessionToken->bind_param("s", $_COOKIE["_"]);
				$stmtSelectSessionToken->execute();
				$resultSessionToken = $stmtSelectSessionToken->get_result();
				$userRow = $resultSessionToken->fetch_assoc();
				if ($userRow) {
					$userId = isset($userRow["user"]) ? $userRow["user"] : $userRow["id"];
					$isGroupAvatar = false;
					$groupId = null;
					if (isset($_SERVER["HTTP_X_GROUP_ID"]) && is_numeric($_SERVER["HTTP_X_GROUP_ID"])) {
						$groupId = intval($_SERVER["HTTP_X_GROUP_ID"]);
						$queryCheckGroupPermission = "SELECT g.group_info_permission, gm.role FROM `groups` g JOIN group_members gm ON g.id = gm.`group` WHERE g.id = ? AND gm.user = ?";
						$stmtCheckPermission = $dbConnection->prepare($queryCheckGroupPermission);
						$stmtCheckPermission->bind_param("ii", $groupId, $userId);
						$stmtCheckPermission->execute();
						$permissionResult = $stmtCheckPermission->get_result();
						if ($permissionResult->num_rows === 0) {
							http_response_code(403);
							echo json_encode(["code" => -1, "msg" => "您不在该群组中或群组不存在。"]);
							exit;
						}
						$permissionData = $permissionResult->fetch_assoc();
						if (!(
							$permissionData["role"] === "owner" ||
							$permissionData["role"] === "admin" && $permissionData["group_info_permission"] == 2 ||
							$permissionData["group_info_permission"] == 3
						)) {
							http_response_code(403);
							echo json_encode(["code" => -1, "msg" => "您没有权限修改群组头像。"]);
							exit;
						}
						$isGroupAvatar = true;
					}
					$fileName = generateRandomFileName();
					$avatarFilePath = "avatar/" . $fileName . ".png";
					if (move_uploaded_file($_FILES["avatar"]["tmp_name"], $avatarFilePath . ".tmp")) {
						$tempFilePath = $avatarFilePath . ".tmp";
						list($imageWidth, $imageHeight) = getimagesize($tempFilePath);
						$targetWidth = 1000;
						$targetHeight = 1000;
						$sourceImage = null;
						$isSuccess = false;
						$imageType = exif_imagetype($tempFilePath);
						switch ($imageType) {
							case IMAGETYPE_JPEG:
								$sourceImage = imagecreatefromjpeg($tempFilePath);
								break;
							case IMAGETYPE_PNG:
								$sourceImage = @imagecreatefrompng($tempFilePath);
								break;
							case IMAGETYPE_GIF:
								$sourceImage = imagecreatefromgif($tempFilePath);
								break;
							case IMAGETYPE_WEBP:
								$sourceImage = imagecreatefromwebp($tempFilePath);
								break;
							default:
								unlink($tempFilePath);
								echo json_encode(["code" => -1, "msg" => "不支持的文件类型。"]);
								exit;
						}
						if ($sourceImage) {
							$resizedImage = imagecreatetruecolor($targetWidth, $targetHeight);
							if ($imageType == IMAGETYPE_PNG || $imageType == IMAGETYPE_GIF) {
								imagealphablending($resizedImage, false);
								imagesavealpha($resizedImage, true);
								$transparent = imagecolorallocatealpha($resizedImage, 0, 0, 0, 127);
								imagefill($resizedImage, 0, 0, $transparent);
							}
							imagecopyresampled($resizedImage, $sourceImage, 0, 0, 0, 0, $targetWidth, $targetHeight, $imageWidth, $imageHeight);
							if (imagepng($resizedImage, $avatarFilePath, 9)) {
								$isSuccess = true;
							}
							imagedestroy($sourceImage);
							imagedestroy($resizedImage);
						}
						if (!$isSuccess) {
							unlink($tempFilePath);
							http_response_code(400);
							echo json_encode(["code" => -1, "msg" => "未知的图片内容。"]);
							exit;
						}
						unlink($tempFilePath);
						if ($isGroupAvatar) {
							$querySelectGroupAvatar = "SELECT group_avatar FROM `groups` WHERE id = ?";
							$stmtSelectGroupAvatar = $dbConnection->prepare($querySelectGroupAvatar);
							$stmtSelectGroupAvatar->bind_param("i", $groupId);
							$stmtSelectGroupAvatar->execute();
							$resultGroupAvatar = $stmtSelectGroupAvatar->get_result();
							$groupAvatarRow = $resultGroupAvatar->fetch_assoc();
							if ($groupAvatarRow && $groupAvatarRow["group_avatar"]) {
								$isPublicAvatar = strpos($groupAvatarRow["group_avatar"], "/avatar/") !== false;
								$oldAvatarFilePath = ltrim($groupAvatarRow["group_avatar"], "/");
								if (!$isPublicAvatar && file_exists($oldAvatarFilePath)) {
									unlink($oldAvatarFilePath);
								}
							}
							$fullGroupAvatarPath = "/$avatarFilePath";
							$queryUpdateGroupAvatar = "UPDATE `groups` SET group_avatar = ? WHERE id = ?";
							$stmtUpdateGroupAvatar = $dbConnection->prepare($queryUpdateGroupAvatar);
							$stmtUpdateGroupAvatar->bind_param("si", $fullGroupAvatarPath, $groupId);
							$stmtUpdateGroupAvatar->execute();
							echo json_encode(["code" => 1, "msg" => "群组头像更新成功！", "avatar" => $fullGroupAvatarPath]);
						} else {
							$userAvatarStatement = "SELECT user_avatar FROM users WHERE id = ?";
							$stmtSelectAvatar = $dbConnection->prepare($userAvatarStatement);
							$stmtSelectAvatar->bind_param("i", $userId);
							$stmtSelectAvatar->execute();
							$resultAvatar = $stmtSelectAvatar->get_result();
							$userAvatarRow = $resultAvatar->fetch_assoc();
							if ($userAvatarRow && $userAvatarRow["user_avatar"]) {
								$oldAvatarFilePath = ltrim($userAvatarRow["user_avatar"], '/');
								if (file_exists($oldAvatarFilePath)) {
									unlink($oldAvatarFilePath);
								}
							}
							$fullUserAvatarPath = "/" . $avatarFilePath;
							$queryUpdateAvatar = "UPDATE users SET user_avatar = ? WHERE id = ?";
							$stmtUpdateAvatar = $dbConnection->prepare($queryUpdateAvatar);
							$stmtUpdateAvatar->bind_param("si", $fullUserAvatarPath, $userId);
							$stmtUpdateAvatar->execute();
							echo json_encode(["code" => 1, "msg" => "头像更新成功！", "avatar" => $fullUserAvatarPath]);
						}
					} else {
						http_response_code(500);
						echo json_encode(["code" => -1, "msg" => "文件上传失败。"]);
					}
				} else {
					http_response_code(404);
					echo json_encode(["code" => 0, "msg" => "未找到有效的用户 ID，请检查凭证。"]);
				}
				$stmtSelectSessionToken->close();
				if (isset($stmtSelectAdminKey)) $stmtSelectAdminKey->close();
				if (isset($stmtSelectAvatar)) $stmtSelectAvatar->close();
				if (isset($stmtUpdateAvatar)) $stmtUpdateAvatar->close();
				if (isset($stmtCheckPermission)) $stmtCheckPermission->close();
				if (isset($stmtSelectGroupAvatar)) $stmtSelectGroupAvatar->close();
				if (isset($stmtUpdateGroupAvatar)) $stmtUpdateGroupAvatar->close();
				$dbConnection->close();
			} catch (ErrorException $databaseException) {
				http_response_code(500);
				echo json_encode(["code" => -1, "msg" => "数据库错误。"]);
			}
		} else {
			http_response_code(400);
			echo json_encode(["code" => -1, "msg" => "缺少必要的参数。"]);
		}
	} else {
		http_response_code(405);
		echo json_encode(["code" => -1, "msg" => "请求方法不正确。"]);
	}
?>