<?php
	if (isset($_POST["v"]) && $_POST["v"] == "2") header('Content-Type: application/json');
	else header('Content-Type: text/plain');
	if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['user']) && isset($_POST['pass']) && isset($_POST['from'])) {
		function findNextAvailableUserId() {
			global $db;
			$maxUserId = $db->query("SELECT MAX(id) AS maxId FROM users");
			$currentId = $maxUserId->fetch_assoc()["maxId"] + 1;
			return $currentId;
		}
		function generateRandomToken() {
			$characters = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
			$charactersLength = strlen($characters);
			$generatedToken = "";
			for ($i = 0; $i < 32; $i++) {
				$generatedToken .= $characters[random_int(0, $charactersLength - 1)];
			}
			return $generatedToken;
		}
		mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
		try {
			$config = parse_ini_file('conf/settings.ini', true);
			$host = $config['database']['host'];
			$user = $config['database']['user'];
			$pass = $config['database']['pass'];
			$db_name = $config['database']['db'];
			$db = new mysqli($host, $user, $pass, $db_name);
			$username = $_POST["user"];
			$password = $_POST["pass"];
			$action = $_POST["from"];
			if (!preg_match("/^[a-zA-Z_$][a-zA-Z0-9_$]{0,31}$/", $username) || strlen($password) < 8 || strlen($password) > 32 || !preg_match("/[a-z]/", $password) || !preg_match("/[A-Z]/", $password) || !preg_match("/\d/", $password)) {
				if (isset($_POST["v"]) && $_POST["v"] == "2") echo json_encode(["code" => -1, "msg" => "用户名或密码格式不正确"]);
				else echo "fail";
				$db->close();
				exit();
			}
			if ($action == "regist") {
				$nextUserId = findNextAvailableUserId();
				$stmtCheckUsername = $db->prepare("SELECT COUNT(*) AS count FROM users WHERE user = ?");
				$stmtCheckUsername->bind_param("s", $username);
				$stmtCheckUsername->execute();
				$resultCheckUsername = $stmtCheckUsername->get_result();
				$rowCheckUsername = $resultCheckUsername->fetch_assoc();
				if ($rowCheckUsername["count"] > 0) {
					if (isset($_POST["v"]) && $_POST["v"] == "2") echo json_encode(["code" => -1, "msg" => "用户名已存在"]);
					else echo "exist";
					$stmtCheckUsername->close();
					$db->close();
					exit();
				}
				$tokenForNewUser = generateRandomToken();
				$hashedPassword = password_hash($password, PASSWORD_DEFAULT);
				$stmtInsertUser = $db->prepare("INSERT INTO users (id, user, password, token, nick) VALUES (?, ?, ?, ?, ?)");
				$stmtInsertUser->bind_param("issss", $nextUserId, $username, $hashedPassword, $tokenForNewUser, $username);
				$stmtInsertUser->execute();
				if (isset($_POST["v"]) && $_POST["v"] == "2") echo json_encode(["code" => 1, "msg" => "注册成功"]);
				else echo "success";
				$stmtCheckUsername->close();
				$stmtInsertUser->close();
			} else if ($action == "login") {
				$stmtLogin = $db->prepare("SELECT password, token FROM users WHERE user = ?");
				$stmtLogin->bind_param("s", $username);
				$stmtLogin->execute();
				$resultLogin = $stmtLogin->get_result();
				if ($resultLogin && $rowLogin = $resultLogin->fetch_assoc()) {
					if (password_verify($_POST["pass"], $rowLogin["password"])) {
						if (isset($_POST["v"]) && $_POST["v"] == "2") echo json_encode(["code" => 1, "msg" => "登录成功", "token" => $rowLogin["token"]]);
						else echo $rowLogin["token"];
					} else {
						if (isset($_POST["v"]) && $_POST["v"] == "2") echo json_encode(["code" => -1, "msg" => "密码错误"]);
						else echo "wrong";
					}
				} else {
					if (isset($_POST["v"]) && $_POST["v"] == "2") echo json_encode(["code" => -1, "msg" => "用户不存在"]);
					else echo "not_exist";
				}
				$stmtLogin->close();
			}
			$db->close();
		} catch (mysqli_sql_exception $e) {
			if (isset($_POST["v"]) && $_POST["v"] == "2") echo json_encode(["code" => -1, "msg" => "数据库错误"]);
			else echo "fail";
		}
	} else {
		if (isset($_POST["v"]) && $_POST["v"] == "2") echo json_encode(["code" => -1, "msg" => "请求方法不正确或缺少必要的参数"]);
		else echo "fail";
	}
?>