<?php
	if (isset($_POST["v"]) && $_POST["v"] == "2") header('Content-Type: application/json');
	else header('Content-Type: text/plain');
	if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['token'])) {
		mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
		$config = parse_ini_file('conf/settings.ini', true);
		$host = $config['database']['host'];
		$user = $config['database']['user'];
		$pass = $config['database']['pass'];
		$db = $config['database']['db'];
		try {
			$databaseConnection = new mysqli($host, $user, $pass, $db);
			$receivedToken = $_POST["token"];
			if ($receivedToken) {
				$query = "SELECT id, user, created_at, token, nick, user_avatar FROM users WHERE token = ?";
				$preparedStatement = $databaseConnection->prepare($query);
				$preparedStatement->bind_param("s", $receivedToken);
				$preparedStatement->execute();
				$result = $preparedStatement->get_result();
				if ($result && $userData = $result->fetch_assoc()) {
					if (isset($_POST["v"]) && $_POST["v"] == "2") {
						$data = [
							"id" => $userData["id"],
							"user" => $userData["user"],
							"time" => $userData["created_at"],
							"token" => $userData["token"],
							"nick" => $userData["nick"],
							"avatar" => (bool)$userData["user_avatar"]
						];
						echo json_encode(["code" => 1, "msg" => "用户信息获取成功", "data" => $data]);
					} else echo $userData["id"] . "\n" . $userData["user"] . "\n" . $userData["created_at"] . "\n" . $userData["token"] . "\n" . $userData["nick"] . "\n" . $userData["user_avatar"];
				} else {
					if (isset($_POST["v"]) && $_POST["v"] == "2") echo json_encode(["code" => 0, "msg" => "用户不存在或token无效，请重新登录"]);
					else echo "relogin";
				}
				$preparedStatement->close();
			} else {
				if (isset($_POST["v"]) && $_POST["v"] == "2") echo json_encode(["code" => -1, "msg" => "缺少必要的token参数"]);
				else echo "fail";
			}
			$databaseConnection->close();
		} catch (mysqli_sql_exception $sqlException) {
			if (isset($_POST["v"]) && $_POST["v"] == "2") echo json_encode(["code" => -1, "msg" => "数据库查询出错：" . $sqlException->getMessage()]);
			else echo "fail";
		}
	} else {
		if (isset($_POST["v"]) && $_POST["v"] == "2") echo json_encode(["code" => -1, "msg" => "无效的请求方法或缺少必要的token参数"]);
		else echo "fail";
	}
?>