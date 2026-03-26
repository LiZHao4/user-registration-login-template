function generateRandomToken(length = 32) {
	const characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	let token = '';
	for (let i = 0; i < length; i++) {
		token += characters[Math.floor(Math.random() * characters.length)];
	}
	return token;
}
export async function getUniqueToken(db) {
	while (true) {
		const token = generateRandomToken();
		const result = await db.query('SELECT COUNT(*) as count FROM user_session WHERE token = ?', [token]);
		if (result[0].count === 0) {
			return token;
		}
	}
}
export function validateCredentials(username, password) {
	const usernameRegex = /^[a-zA-Z_$][a-zA-Z0-9_$]{0,31}$/;
	const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,32}$/;
	return usernameRegex.test(username) && passwordRegex.test(password);
}
export function bin2hex(buffer) {
	return Array.from(new Uint8Array(buffer))
		.map(b => b.toString(16).padStart(2, '0'))
		.join('');
}
export async function getDisplayName(db, viewerId, targetId, groupId = null) {
	const remarkResult = await db.query(
		'SELECT remark FROM user_remarks WHERE user_id = ? AND target_user_id = ?',
		[viewerId, targetId]
	);
	if (remarkResult.length > 0 && remarkResult[0].remark) {
		return remarkResult[0].remark;
	}
	if (groupId) {
		const groupNickResult = await db.query(
			'SELECT group_nickname FROM group_members WHERE `group` = ? AND user = ?',
			[groupId, targetId]
		);
		if (groupNickResult.length > 0 && groupNickResult[0].group_nickname !== null) {
			return groupNickResult[0].group_nickname;
		}
	}
	const userResult = await db.query('SELECT nick FROM users WHERE id = ?', [targetId]);
	if (userResult.length > 0 && userResult[0].nick) {
		return userResult[0].nick;
	}
	return '未知用户';
}