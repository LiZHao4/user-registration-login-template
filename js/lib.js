function checkPassword(password, repassword) {
	return password === repassword && !!password && !(password.length < 8 || 32 < password.length || !/[a-z]/.test(password) || !/[A-Z]/.test(password) || !/\d/.test(password));
}
function clearCookie(name) {
	var date = new Date(0);
	document.cookie = name + "=1; expires=" + date.toUTCString() + "; path=/";
}
function getCookie(name) {
	var value = `; ${document.cookie}`;
	var parts = value.split(`; ${name}=`);
	if (parts.length === 2) return parts.pop().split(';').shift();
	return null;
}
function logoutAccount() {
	clearCookie('_');
	location.href = 'login.html';
}
function setCookie(name, value) {
	var date = new Date();
	date.setTime(date.getTime() + 2592e6);
	document.cookie = name + "=" + (value || "") + "; expires=" + date.toUTCString() + "; path=/";
}
function showPop(msg, seconds=5) {
	// <div class="pop"></div>
	var pop = document.createElement('div');
	pop.className = 'pop';
	pop.innerText = msg;
	pop.style.visibility = 'visible';
	document.body.appendChild(pop);
	setTimeout(function() {
		pop.remove();
	}, seconds * 1000);
}
function showPop2(msg, buttons) {
	// <div class="pop">
	// 	<div id="text"></div>
	// 	<div class="between"></div>
	// </div>
	var pop = document.createElement('div');
	pop.className = 'pop';
	pop.style.visibility = 'visible';
	var text = document.createElement('div');
	text.id = 'text';
	text.innerText = msg;
	pop.appendChild(text);
	var between = document.createElement('div');
	between.className = 'between';
	for (var i = 0; i < buttons.length; i++) {
		var button = document.createElement('button');
		button.innerText = buttons[i].text;
		button.className = 'b';
		let j = i;
		button.onclick = function() {
			if (buttons[j].fn && typeof buttons[j].fn === 'function') buttons[j].fn();
			pop.remove();
		};
		between.appendChild(button);
	}
	pop.appendChild(between);
	document.body.appendChild(pop);
}
function testFuture(date) {
	return new Date(date) > new Date();
}
function togglePopupVisibility() {
	if (isPopupVisible) {
		document.getElementById('pop2').style.visibility = 'hidden';
		isPopupVisible = false;
	} else {
		document.getElementById('pop2').style.visibility = 'visible';
		isPopupVisible = true;
	}
}