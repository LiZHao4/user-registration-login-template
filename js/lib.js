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
function logoutAccount(lang) {
	clearCookie('_');
	location.href = 'login.html?lang=' + lang;
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
function formatDateShort(date, lang) {
	var Date_ = new Date(date);
	var now = new Date();
	var languages;
	if (lang == 'zh-CN' || lang == 'zh-TW') {
		languages = {
			yesterday: '昨天',
			tomorrow: '明天',
			format: function(d, full) {
				if (full) return `${d.getFullYear()}年${d.getMonth() + 1}月${d.getDate()}日`;
				else return `${d.getMonth() + 1}月${d.getDate()}日`;
			}
		};
	} else if (lang == 'en-US') {
		languages = {
			yesterday: 'yesterday',
			tomorrow: 'tomorrow',
			format: function(d, full) {
				const months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
				if (full) return `${months[d.getMonth()]} ${d.getDate()}, ${d.getFullYear()}`;
				else return `${months[d.getMonth()]} ${d.getDate()}`;
			}
		};
	} else if (lang == 'es-ES') {
		languages = {
			yesterday: 'ayer',
			tomorrow: 'mañana',
			format: function(d, full) {
				const months = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
				if (full) return `${d.getDate()} de ${months[d.getMonth()]} de ${d.getFullYear()}`;
				else return `${d.getDate()} de ${months[d.getMonth()]}`;
			}
		};
	} else if (lang == 'fr-FR') {
		languages = {
			yesterday: 'hier',
			tomorrow: 'demain',
			format: function(d, full) {
				const months = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
				if (full) return `${d.getDate()} ${months[d.getMonth()]} ${d.getFullYear()}`;
				else return `${d.getDate()} ${months[d.getMonth()]}`;
			}
		}
	} else if (lang == 'ru-RU') {
		languages = {
			yesterday: 'вчера',
			tomorrow: 'завтра',
			format: function(d, full) {
				const months = ['января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря'];
				if (full) return `${d.getDate()} ${months[d.getMonth()]} ${d.getFullYear()}`;
				else return `${d.getDate()} ${months[d.getMonth()]}`;
			}
		}
	} else if (lang == 'ar-SA') {
		languages = {
			yesterday: 'أمس',
			tomorrow: 'غدًا',
			format: function(d, full) {
				const months = ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'];
				if (full) return `${d.getFullYear()} ${months[d.getMonth()]} ${d.getDate()}`;
				else return `${months[d.getMonth()]} ${d.getDate()}`;
			}
		}
	}
	var yesterday = new Date(now.getFullYear(), now.getMonth(), now.getDate() - 1);
	var tomorrow = new Date(now.getFullYear(), now.getMonth(), now.getDate() + 1);
	if (Date_.getFullYear() === now.getFullYear() && Date_.getMonth() === now.getMonth() && Date_.getDate() === now.getDate()) {
		return Date_.getHours().toString().padStart(2, '0') + ':' + Date_.getMinutes().toString().padStart(2, '0');
	} else if (Date_.getFullYear() === yesterday.getFullYear() && Date_.getMonth() === yesterday.getMonth() && Date_.getDate() === yesterday.getDate()) {
		return languages.yesterday;
	} else if (Date_.getFullYear() === tomorrow.getFullYear() && Date_.getMonth() === tomorrow.getMonth() && Date_.getDate() === tomorrow.getDate()) {
		return languages.tomorrow;
	} else if (Date_.getFullYear() === now.getFullYear()) {
		return languages.format(Date_, false);
	} else {
		return languages.format(Date_, true);
	}
}
function formatDateLong(date, lang) {
	var Date_ = new Date(date.replace(' ', 'T') + '+08:00');
	var now = new Date();
	var languages;
	if (lang == 'zh-CN' || lang == 'zh-TW') {
		languages = {
			yesterday: '昨天',
			tomorrow: '明天',
			format: function(d, full) {
				if (full) return `${d.getFullYear()}年${d.getMonth() + 1}月${d.getDate()}日 ${d.getHours()}:${d.getMinutes().toString().padStart(2, '0')}`;
				else return `${d.getMonth() + 1}月${d.getDate()}日 ${d.getHours()}:${d.getMinutes().toString().padStart(2, '0')}`;
			}
		};
	} else if (lang == 'en-US') {
		languages = {
			yesterday: 'yesterday',
			tomorrow: 'tomorrow',
			format: function(d, full) {
				const months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
				if (full) return `${months[d.getMonth()]} ${d.getDate()}, ${d.getFullYear()} ${d.getHours().toString().padStart(2, '0')}:${d.getMinutes().toString().padStart(2, '0')}`;
				else return `${months[d.getMonth()]} ${d.getDate()} ${d.getHours().toString().padStart(2, '0')}:${d.getMinutes().toString().padStart(2, '0')}`;
			}
		};
	} else if (lang == 'es-ES') {
		languages = {
			yesterday: 'ayer',
			tomorrow: 'mañana',
			format: function(d, full) {
				const months = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
				if (full) return `${d.getDate()} de ${months[d.getMonth()]} de ${d.getFullYear()} ${d.getHours().toString().padStart(2, '0')}:${d.getMinutes().toString().padStart(2, '0')}`;
				else return `${d.getDate()} de ${months[d.getMonth()]} ${d.getHours().toString().padStart(2, '0')}:${d.getMinutes().toString().padStart(2, '0')}`;
			}
		};
	} else if (lang == 'fr-FR') {
		languages = {
			yesterday: 'hier',
			tomorrow: 'demain',
			format: function(d, full) {
				const months = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
				if (full) return `${d.getDate()} ${months[d.getMonth()]} ${d.getFullYear()} ${d.getHours().toString().padStart(2, '0')}:${d.getMinutes().toString().padStart(2, '0')}`;
				else return `${d.getDate()} ${months[d.getMonth()]} ${d.getHours().toString().padStart(2, '0')}:${d.getMinutes().toString().padStart(2, '0')}`;
			}
		}
	} else if (lang == 'ru-RU') {
		languages = {
			yesterday: 'вчера',
			tomorrow: 'завтра',
			format: function(d, full) {
				const months = ['января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря'];
				if (full) return `${d.getDate()} ${months[d.getMonth()]} ${d.getFullYear()} ${d.getHours().toString().padStart(2, '0')}:${d.getMinutes().toString().padStart(2, '0')}`;
				else return `${d.getDate()} ${months[d.getMonth()]} ${d.getHours().toString().padStart(2, '0')}:${d.getMinutes().toString().padStart(2, '0')}`;
			}
		}
	} else if (lang == 'ar-SA') {
		languages = {
			yesterday: 'أمس',
			tomorrow: 'غدًا',
			format: function(d, full) {
				const months = ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'];
				if (full) return `${d.getFullYear()} ${months[d.getMonth()]} ${d.getDate()} ${d.getHours().toString().padStart(2, '0')}:${d.getMinutes().toString().padStart(2, '0')}`;
				else return `${months[d.getMonth()]} ${d.getDate()} ${d.getHours().toString().padStart(2, '0')}:${d.getMinutes().toString().padStart(2, '0')}`;
			}
		}
	}
	var yesterday = new Date(now.getFullYear(), now.getMonth(), now.getDate() - 1);
	var tomorrow = new Date(now.getFullYear(), now.getMonth(), now.getDate() + 1);
	if (Date_.getFullYear() === now.getFullYear() && Date_.getMonth() === now.getMonth() && Date_.getDate() === now.getDate()) {
		return Date_.getHours().toString().padStart(2, '0') + ':' + Date_.getMinutes().toString().padStart(2, '0');
	} else if (Date_.getFullYear() === yesterday.getFullYear() && Date_.getMonth() === yesterday.getMonth() && Date_.getDate() === yesterday.getDate()) {
		return languages.yesterday + ' ' + Date_.getHours().toString().padStart(2, '0') + ':' + Date_.getMinutes().toString().padStart(2, '0');
	} else if (Date_.getFullYear() === tomorrow.getFullYear() && Date_.getMonth() === tomorrow.getMonth() && Date_.getDate() === tomorrow.getDate()) {
		return languages.tomorrow + ' ' + Date_.getHours().toString().padStart(2, '0') + ':' + Date_.getMinutes().toString().padStart(2, '0');
	} else if (Date_.getFullYear() === now.getFullYear()) {
		return languages.format(Date_, false);
	} else {
		return languages.format(Date_, true);
	}
}
function generate1(data) {
	function approxy(target, buttons) {
		return function(item) {
			// <div class="friend-item" data-userid="不定" data-id="item.id">
			// 	<span class="username">item.nick</span>
			// 	<span class="timestamp">item.time</span>
			// 	<button data-key="approve"></button>
			// 	<button class="reject" data-key="deny"></button>
			// </div>
			var div = document.createElement('div');
			div.className = 'friend-item';
			if (buttons) {
				div.setAttribute('data-userid', item.source);
			} else {
				div.setAttribute('data-userid', item.target);
			}
			div.setAttribute('data-id', item.id);
			var span = document.createElement('span');
			span.className = 'username';
			span.onclick = function() {
				location.href = 'profile.html?lang=' + lang + '&id=' + div.dataset.userid;
			}
			span.innerText = item.nick;
			div.appendChild(span);
			var span2 = document.createElement('span');
			span2.className = 'timestamp';
			span2.innerText = formatDateShort(item.sent_at, lang);
			div.appendChild(span2);
			if (buttons) {
				var button = document.createElement('button');
				button.setAttribute('data-key', 'approve');
				button.onclick = function() {
					var xhr = new XMLHttpRequest();
					xhr.open('POST', 'friends.php?lang=' + lang, true);
					xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
					var ul = new URLSearchParams({
						token: data.token,
						id: data.id,
						target: div.dataset.id,
						action: 'agree'
					});
					xhr.onreadystatechange = function() {
						if (xhr.readyState === 4 && xhr.status === 200) {
							var response = JSON.parse(xhr.responseText);
							if (response.code === 1) {
								location.reload();
							} else if (response.code === 0) {
								showPop2(response.msg, [{text: language.relogin, fn: function() {
									location.href = "login.html?lang=" + lang;
								}}]);
							} else {
								showPop(response.msg);
							}
						}
					};
					xhr.send(ul.toString());
				};
				div.appendChild(button);
				var button2 = document.createElement('button');
				button2.className = 'reject';
				button2.onclick = function() {
					var xhr = new XMLHttpRequest();
					xhr.open('POST', 'friends.php?lang=' + lang, true);
					xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
					var ul = new URLSearchParams({
						token: data.token,
						id: data.id,
						target: item.source,
						action: 'refuse'
					});
					xhr.onreadystatechange = function() {
						if (xhr.readyState === 4 && xhr.status === 200) {
							var response = JSON.parse(xhr.responseText);
							if (response.code === 1) {
								location.reload();
							} else if (response.code === 0) {
								showPop2(response.msg, [{text: language.relogin, fn: function() {
									location.href = "login.html?lang=" + lang;
								}}]);
							} else {
								showPop(response.msg);
							}
						}
					};
					xhr.send(ul.toString());
				};
				button2.setAttribute('data-key', 'deny');
				div.appendChild(button2);
			}
			target.appendChild(div);
		};
	}
	var dataDesc = data.data.sort((a, b) => Date.parse(b.sent_at) - Date.parse(a.sent_at));
	var dataDesc2 = data.fData.sort((a, b) => Date.parse(b.sent_at) - Date.parse(a.sent_at));
	dataDesc.forEach(approxy(data.target, true));
	dataDesc2.forEach(approxy(data.target2));
	data.count.setAttribute('data-count', data.data.length);
}
function generate2(data) {
	var desc = data.data.sort((a, b) => Date.parse(b.time) - Date.parse(a.time)), total = 0;
	desc.forEach(function(item) {
		// <div class="chat-item" data-id="item.id">
		// 	<div>
		// 		<img class="chat-avatar" src="avatar/item.avatar.jpg">
		// 	</div>
		// 	<div class="chat-info">
		// 		<div class="chat-top">
		// 			<div class="chat-name">item.nick</div>
		// 			<div class="chat-time">item.time</div>
		// 		</div>
		// 		<div class="chat-preview badge3" data-count="item.count">item.content</div>
		// 	</div>
		// </div>
		var div = document.createElement('div');
		div.className = 'chat-item';
		div.setAttribute('data-id', item.id);
		div.onclick = function() {
			location.href = 'chat.html?lang=' + lang + '&id=' + this.dataset.id;
		};
		data.target.appendChild(div);
		var div2 = document.createElement('div');
		div.appendChild(div2);
		var img = document.createElement('img');
		img.className = 'chat-avatar';
		if (item.avatar) {
			img.src = 'avatar/' + item.avatar + '.jpg';
		} else {
			img.src = 'default.jpg';
		}
		div2.appendChild(img);
		var div3 = document.createElement('div');
		div3.className = 'chat-info';
		div.appendChild(div3);
		var div4 = document.createElement('div');
		div4.className = 'chat-top';
		div3.appendChild(div4);
		var div5 = document.createElement('div');
		div5.className = 'chat-name';
		div5.innerText = item.nick;
		div4.appendChild(div5);
		var div6 = document.createElement('div');
		div6.className = 'chat-time';
		div6.innerText = formatDateShort(item.time, lang);
		div4.appendChild(div6);
		var div7 = document.createElement('div');
		div7.className = 'chat-preview badge3';
		div7.setAttribute('data-count', item.count);
		total += item.count;
		div7.innerText = item.content;
		div3.appendChild(div7);
		data.target.appendChild(div);
	});
	data.target.previousElementSibling.setAttribute('data-count', total);
}
function generate3(data) {
	data.target.innerHTML = '';
	data.data.forEach(function(item) {
		// <div class="message (sent)">
		// 	<div><img class="avatar3"></div>
		// 	<div class="bubble">item.content</div>
		// 	<div class="time">item.time</div>
		// </div>
		var div = document.createElement('div');
		if (item.sender === id) {
			div.className = 'message sent';
		} else {
			div.className = 'message';
		}
		data.target.appendChild(div);
		var div2 = document.createElement('div');
		div.appendChild(div2);
		var img = document.createElement('img');
		img.className = 'avatar3';
		if (item.sender === id) {
			if (data.avatar) {
				img.src = 'avatar/' + data.avatar + '.jpg';
			} else {
				img.src = 'default.jpg';
			}
		} else {
			if (data.opposite) {
				img.src = 'avatar/' + data.opposite + '.jpg';
			} else {
				img.src = 'default.jpg';
			}
		}
		div2.appendChild(img);
		var div3 = document.createElement('div');
		div3.className = 'bubble';
		div3.innerText = item.content;
		div.appendChild(div3);
		var div4 = document.createElement('div');
		div4.className = 'time';
		div4.innerText = formatDateLong(item.sent_at, lang);
		div.appendChild(div4);
		data.target.appendChild(div);
	});
}