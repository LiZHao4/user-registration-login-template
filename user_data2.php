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
			$postedToken = $_POST["token"];
			$query = "SELECT id, user, nick, user_avatar, token, gender, birth, bio FROM users WHERE token = ?";
			$statement = $databaseConnection->prepare($query);
			$statement->bind_param("s", $postedToken);
			$statement->execute();
			$result = $statement->get_result();
			if ($result->num_rows > 0) {
				$userData = $result->fetch_assoc();
				if (isset($_POST["v"]) && $_POST["v"] == "2") {
					$data = [
						"user" => $userData["user"],
						"nick" => $userData["nick"],
						"id" => $userData["id"],
						"avatar" => (bool)$userData["user_avatar"],
						"token" => $userData["token"],
						"gender" => $userData["gender"],
						"birth" => $userData["birth"],
						"bio" => $userData["bio"]
					];
					echo json_encode(["code" => 1, "msg" => "用户信息获取成功", "data" => $data]);
				} else echo $userData["user"] . "\n" . $userData["nick"]. "\n" . $userData["id"] . "\n" . $userData["user_avatar"] . "\n" . $userData["token"] . "\n" . $userData["gender"] . "\n" . $userData["birth"] . "\n" . $userData["bio"];
			} else {
				if (isset($_POST["v"]) && $_POST["v"] == "2") echo json_encode(["code" => 0, "msg" => "用户不存在或token无效，请重新登录"]);
				else echo "fail";
			}
			$statement->close();
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