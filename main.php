<?php
	function generateRandomToken() : string {
		$characters = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
		$charactersLength = strlen($characters);
		$generatedToken = "";
		for ($i = 0; $i < 32; $i++) {
			$generatedToken .= $characters[random_int(0, $charactersLength - 1)];
		}
		return $generatedToken;
	}
	function generateRandomFileName() : string {
		$characters = "0123456789abcdefghijklmnopqrstuvwxyz";
		$charactersLength = strlen($characters);
		$generatedToken = "";
		for ($i = 0; $i < 60; $i++) {
			$generatedToken .= $characters[random_int(0, $charactersLength - 1)];
		}
		return $generatedToken;
	}
	function findMissingNumber(array $nums): int {
		$left = 0;
		$right = count($nums) - 1;
		if (empty($nums) || $nums[0] != 1) {
			return 1;
		}
		if ($nums[$right] == $right + 1) {
			return $right + 2;
		}
		while ($left <= $right) {
			$mid = $left + intdiv($right - $left, 2);
			if ($nums[$mid] == $mid + 1) {
				$left = $mid + 1;
			} else {
				$right = $mid - 1;
			}
		}
		return $left + 1;
	}
?>