function checkPassword(password, repassword) {
	return password === repassword && !!password && !(password.length < 8 || 32 < password.length || !/[a-z]/.test(password) || !/[A-Z]/.test(password) || !/\d/.test(password));
}
function clearCookie(name) {
	var date = new Date(0);
	document.cookie = name + "=; expires=" + date.toUTCString() + "; path=/";
}
function getCookie(name) {
	var value = `; ${document.cookie}`;
	var parts = value.split(`; ${name}=`);
	if (parts.length === 2) return decodeURIComponent(parts.pop().split(';').shift());
	return null;
}
function setCookie(name, value, expires) {
	var date = new Date();
	date.setTime(expires * 1000);
	document.cookie = name + "=" + encodeURIComponent(value || "") + "; expires=" + date.toUTCString() + "; path=/";
}
function getFriendAdded(getFriendAddedId) {
	return fetch('get_friend_added.php?' + new URLSearchParams({target: getFriendAddedId})).then(response => response.json()).then(responseData => {
		if (responseData.code === 1) {
			var returns = {
				added: responseData.added
			};
			if (returns.added === 'pending_in') {
				returns.request_id = responseData.request_id;
			}
			return returns;
		}
	});
}
function logoutAccount() {
	fetch('logout.php', {method: 'POST'}).then(response => response.json()).then(responseData => {
		if (responseData.code === 1) {
			location.href = 'login.html';
		} else {
			$('#errors').text(responseData.msg).removeClass('d-none');
		}
	})
}
function testFuture(date) {
	return new Date(date) > new Date();
}
function formatDateShort(date) {
	var Date_ = new Date(date * 1000);
	var now = new Date();
	var languages = {
		yesterday: '昨天',
		tomorrow: '明天',
		format: function(d, full) {
			if (full) return `${d.getFullYear()}年${d.getMonth() + 1}月${d.getDate()}日`;
			else return `${d.getMonth() + 1}月${d.getDate()}日`;
		}
	};
	var currentYear = now.getFullYear();
	var currentMonth = now.getMonth();
	var currentDay = now.getDate();
	var dateYear = Date_.getFullYear();
	var dateMonth = Date_.getMonth();
	var dateDay = Date_.getDate();
	var yesterday = new Date(currentYear, currentMonth, currentDay - 1);
	var tomorrow = new Date(currentYear, currentMonth, currentDay + 1);
	if (dateYear === currentYear && dateMonth === currentMonth && dateDay === currentDay) {
		return Date_.getHours().toString().padStart(2, '0') + ':' + Date_.getMinutes().toString().padStart(2, '0');
	} else if (dateYear === yesterday.getFullYear() && dateMonth === yesterday.getMonth() && dateDay === yesterday.getDate()) {
		return languages.yesterday;
	} else if (dateYear === tomorrow.getFullYear() && dateMonth === tomorrow.getMonth() && dateDay === tomorrow.getDate()) {
		return languages.tomorrow;
	} else if (dateYear === currentYear) {
		return languages.format(Date_, false);
	} else {
		return languages.format(Date_, true);
	}
}
function formatDateLong(date) {
	var Date_ = new Date(date * 1000);
	var now = new Date();
	var languages = {
		yesterday: '昨天',
		tomorrow: '明天',
		format: function(d, full) {
			if (full) return `${d.getFullYear()}年${d.getMonth() + 1}月${d.getDate()}日 ${d.getHours()}:${d.getMinutes().toString().padStart(2, '0')}`;
			else return `${d.getMonth() + 1}月${d.getDate()}日 ${d.getHours()}:${d.getMinutes().toString().padStart(2, '0')}`;
		}
	};
	var currentYear = now.getFullYear();
	var currentMonth = now.getMonth();
	var currentDay = now.getDate();
	var dateYear = Date_.getFullYear();
	var dateMonth = Date_.getMonth();
	var dateDay = Date_.getDate();
	var dateHours = Date_.getHours();
	var dateHoursFormatted = dateHours.toString().padStart(2, '0');
	var dateMinutes = Date_.getMinutes();
	var dateMinutesFormatted = dateMinutes.toString().padStart(2, '0');
	var yesterday = new Date(currentYear, currentMonth, currentDay - 1);
	var tomorrow = new Date(currentYear, currentMonth, currentDay + 1);
	if (dateYear === currentYear && dateMonth === currentMonth && dateDay === currentDay) {
		return dateHoursFormatted + ':' + dateMinutesFormatted;
	} else if (dateYear === yesterday.getFullYear() && dateMonth === yesterday.getMonth() && dateDay === yesterday.getDate()) {
		return languages.yesterday + ' ' + dateHoursFormatted + ':' + dateMinutesFormatted;
	} else if (dateYear === tomorrow.getFullYear() && dateMonth === tomorrow.getMonth() && dateDay === tomorrow.getDate()) {
		return languages.tomorrow + ' ' + dateHoursFormatted + ':' + dateMinutesFormatted;
	} else if (dateYear === currentYear) {
		return languages.format(Date_, false);
	} else {
		return languages.format(Date_, true);
	}
}
function generate1(data) {
	var increase = function() {
		var total = data.data.length;
		if (total !== 0) {
			data.count.text(total);
		}
		return function() {
			total--;
			if (total !== 0) {
				data.count.text(total);
			} else {
				data.count.text('');
			}
		};
	};
	var increaseInstance = increase();
	function approxy(target, type) {
		return function(item) {
			var div = $('<div>');
			div.addClass('d-flex align-items-center flex-row justify-content-between cursor-default mt-2');
			div.css('gap', '5px');
			if (type == 1) {
				div.attr('data-userid', item.source);
			} else if (type == 2) {
				div.attr('data-userid', item.target);
			}
			div.attr('data-id', item.id);
			var div2 = $('<div>');
			div2.addClass('text-ellipsis');
			div2.css({
				'flex-shrink': 1,
				'flex-grow': 0
			});
			div2.click(function() {
				location.href = 'profile.html?id=' + div.attr('data-userid');
			});
			div2.text(item.nick);
			div.append(div2);
			var div3 = $('<div>');
			div3.addClass('d-flex align-items-center');
			div3.css({
				'flex-shrink': 0,
				'gap': '5px'
			});
			div.append(div3);
			var span = $('<span>');
			span.addClass('text-muted');
			span.text(formatDateShort(item.time));
			div3.append(span);
			if (type == 1) {
				var button = $('<button>');
				button.addClass('btn bg-success text-white btn-small');
				button.click(function() {
					fetch('friends.php', {
						method: 'POST',
						headers: {
							'Content-Type': 'application/x-www-form-urlencoded'
						},
						body: new URLSearchParams({
							target: div.attr('data-id'),
							action: 'agree',
						})
					}).then(response => {
						return response.json();
					}).then(data => {
						if (data.code === 1) {
							div.remove();
							increaseInstance();
							fetch('friend_list.php', { method: 'POST' }).then(res => res.json()).then(data => {
								if (data.code === 1) {
									var el = $('#friends-list');
									el.empty();
									generate2({
										target: el,
										data: data.data
									});
									var num = parseInt($('#friend-apply-badge').text());
									if (num - 1 > 0) {
										$('#friend-apply-badge').text(num);
									} else {
										$('#friend-apply-badge').text('');
									}
								} else {
									$('#errors').text(data.msg).removeClass('d-none');
									$('#errors').delay(3000).fadeOut(500);
								}
							});
						} else {
							$('#error').text(data.msg).removeClass('d-none');
						}
					})
				});
				button.text('同意');
				div3.append(button);
				var button2 = $('<button>');
				button2.addClass('btn bg-danger text-white btn-small');
				button2.click(function() {
					fetch('friends.php', {
						method: 'POST',
						headers: {
							'Content-Type': 'application/x-www-form-urlencoded'
						},
						body: new URLSearchParams({
							target: div.attr('data-id'),
							action: 'refuse'
						})
					}).then(response => {
						return response.json();
					}).then(data => {
						if (data.code === 1) {
							div.remove();
							increaseInstance();
						} else {
							$('#error').text(data.msg).removeClass('d-none');
							$('#error').delay(3000).fadeOut(500);
						}
					});
				});
				button2.text('拒绝');
				div3.append(button2);
			} else if (type == 2) {
				var deleteBtn = $('<button>');
				deleteBtn.addClass('btn btn-link text-primary p-0');
				deleteBtn.css({
					'background': 'none',
					'border': 'none',
					'box-shadow': 'none'
				});
				deleteBtn.text('撤回');
				deleteBtn.click(function() {
					fetch('friends.php', {
						method: 'POST',
						headers: {
							'Content-Type': 'application/x-www-form-urlencoded'
						},
						body: new URLSearchParams({
							target: div.attr('data-id'),
							action: 'revoke'
						})
					}).then(response => response.json()).then(data => {
						if (data.code === 1) {
							div.remove();
						} else {
							$('#error').text(data.msg).removeClass('d-none');
							$('#error').delay(3000).fadeOut(500);
						}
					});
				});
				div3.append(deleteBtn);
			}
			target.append(div);
		};
	}
	var dataDesc = data.data.sort((a, b) => Date.parse(b.time) - Date.parse(a.time));
	var dataDesc2 = data.fData.sort((a, b) => Date.parse(b.time) - Date.parse(a.time));
	dataDesc.forEach(approxy(data.target, 1));
	dataDesc2.forEach(approxy(data.target2, 2));
}
function generate2(data) {
	var desc = data.data.sort((a, b) => Date.parse(b.time) - Date.parse(a.time)), total = 0;
	desc.forEach(function(item) {
		var isTypeFriend = item.type == 'friend';
		var isTypeGroup = item.type == 'group';
		var li = $('<li>');
		li.addClass('list-group-item d-flex align-items-center');
		li.attr('data-id', item.id);
		li.css('cursor', 'pointer');
		li.click(function() {
			location.href = 'chat.html?id=' + $(this).attr('data-id');
		});
		data.target.append(li);
		var img = $('<img>');
		img.addClass('rounded-circle me-3 avatar');
		img.attr('src', item.avatar);
		li.append(img);
		var div = $('<div>');
		div.addClass('flex-item');
		li.append(div);
		var p1 = $('<p>');
		p1.addClass('mb-1 d-flex flex-row align-items-center justify-content-between');
		var div3 = $('<div>');
		div3.addClass('text-ellipsis');
		if (isTypeFriend) {
			div3.text(item.nick);
		} else if (isTypeGroup) {
			div3.text(item.nick + ' (' + item.member_count + ')');
		}
		p1.append(div3);
		var span = $('<span>');
		span.addClass('badge rounded-pill bg-primary');
		if (item.count !== 0) {
			span.text(item.count);
		}
		p1.append(span);
		div.append(p1);
		var p2 = $('<p>');
		p2.addClass('text-muted mb-0 text-ellipsis');
		if ([1, 2].includes(item.msg_type)) {
			var contentString = '';
			if (item.msg_type == 1) {
				if (isTypeGroup && item.msg_sender != id) {
					contentString += item.msg_nick + ': ';
				}
				contentString += item.content.replace(/\n/g, ' ');
				p2.text(contentString);
			} else if (item.msg_type == 2) {
				if (isTypeGroup && item.msg_sender != id) {
					contentString += item.msg_nick + ': ';
				}
				contentString += '[文件] ' + item.content;
				p2.text(contentString);
			}
		} else if (item.msg_type == 3 || item.msg_type == 4) {
			var jsonObj = JSON.parse(item.content);
			if (item.msg_type == 3) {
				var string = '[群聊邀请] ';
				var c1 = item.msg_sender == id;
				var c2 = jsonObj.finish;
				if (c1 && c2) {
					string += '对方已加入“' + jsonObj.name + '”群聊。';
				} else if (c1 && !c2) {
					string += '您已邀请对方加入“' + jsonObj.name + '”群聊。';
				} else if (!c1 && c2) {
					string += '您已加入“' + jsonObj.name + '”群聊。';
				} else if (!c1 && !c2) {
					string += '对方已邀请您加入“' + jsonObj.name + '”群聊。';
				}
				p2.text(string);
			} else if (item.msg_type == 4) {
				if (jsonObj.type == 'quit') {
					p2.text('[群聊退出] “' + item.msg_nick + '”已退出群聊。');
				} else if (jsonObj.type == 'logoff') {
				    p2.text('[群聊退出] “' + jsonObj.nick + '”因注销而退出群聊。');
				} else if (jsonObj.type == 'adminadd') {
					p2.text('[群聊管理员] “' + item.msg_nick + '”已将“' + item.inner_nick + '”设为群聊管理员。');
				} else if (jsonObj.type == 'adminremove') {
					p2.text('[群聊管理员] “' + item.msg_nick + '”已将“' + item.inner_nick + '”取消群聊管理员。');
				} else if (jsonObj.type == 'transfer') {
					p2.text('[群主转让] “' + item.msg_nick + '”已将群主转让给“' + item.inner_nick + '”。');
				} else if (jsonObj.type == 'join') {
					p2.text('[群聊加入] “' + item.msg_nick + '”已加入群聊。');
				}
			}
		}
		div.append(p2);
		var div2 = $('<div>');
		div2.addClass('text-muted ms-auto');
		div2.text(formatDateShort(item.time));
		div2.css('flex-shrink', 0);
		li.append(div2);
		total += item.count;
	});
}
function generate3(data) {
	if (data.fresh) data.target.empty();
	data.data.forEach(function(item) {
		var condition = item.sender == userId;
		var div = $('<div>');
		if (condition) {
			div.addClass('d-flex flex-row-reverse justify-content-start align-items-sm-start my-2');
		} else {
			div.addClass('d-flex flex-row align-items-sm-start my-2');
		}
		div.css('gap', '5px');
		div.attr('data-id', item.id);
		data.target.append(div);
		var img = $('<img>');
		img.addClass('chat-avatar');
		var small2 = $('<small>');
		if (type == 'friend') {
			if (condition) {
				img.attr('src', data.avatar);
			} else {
				img.attr('src', data.opposite);
			}
		} else if (type == 'group') {
			var user = members.find(item2 => item2.id == item.sender);
			if (user) {
				img.attr('src', user.avatar);
			} else {
				fetch('other_user_data.php?id=' + encodeURIComponent(item.sender)).then(response => response.json()).then(data => {
					if (data.code == 1) {
						img.attr('src', data.data.avatar);
						small2.text(data.data.nick);
					} else {
						img.attr('src', '/default.jpg');
						small2.text('未知用户');
					}
				});
			}
		}
		img.click(function() {
			location.href = 'profile.html?id=' + item.sender;
		});
		div.append(img);
		var divMessageContent = $('<div>');
		if (condition) {
			divMessageContent.addClass('d-flex flex-column align-items-end');
		} else {
			divMessageContent.addClass('d-flex flex-column align-items-start');
		}
		div.append(divMessageContent);
		if (!condition) {
			if (type == 'friend') {
				small2.addClass('text-muted');
				small2.text(data.remark || data.oName);
				divMessageContent.append(small2);
			} else if (type == 'group') {
				small2.addClass('text-muted');
				var user_ = members.find(i => i.id == item.sender)
				if (user_) {
					small2.text(user_.remark || user_.group_nickname || user_.nick);
				}
				divMessageContent.append(small2);
			}
		}
		var p = $('<p>');
		divMessageContent.append(p);
		if (condition) {
			p.addClass('chat-message-right');
		} else {
			p.addClass('chat-message-left');
		}
		p.css({
			'word-break': 'break-word',
			'cursor': 'default',
			'white-space': 'pre-wrap'
		});
		if (item.type == 2) {
			div.attr('data-attr', item.multi);
			var $a = $('<a>', { html: p.html() });
			$.each(p[0].attributes, function() {
				if (this.name !== 'id') {
					$a.attr(this.name, this.value);
				}
			});
			p.remove();
			divMessageContent.append($a);
			$a.css({
				'text-decoration': 'none',
				'color': '#000'
			});
			var fileExt = item.content.split('.').pop().toLowerCase();
			var iconClass = 'fas fa-file';
			if (['doc', 'docx'].includes(fileExt)) {
				iconClass = 'fas fa-file-word text-primary';
			} else if (['xls', 'xlsx', 'csv'].includes(fileExt)) {
				iconClass = 'fas fa-file-excel text-success';
			} else if (['ppt', 'pptx'].includes(fileExt)) {
				iconClass = 'fas fa-file-powerpoint text-danger';
			} else if (['pdf'].includes(fileExt)) {
				iconClass = 'fas fa-file-pdf text-danger';
			} else if (['zip', 'rar', '7z', 'tar', 'gz'].includes(fileExt)) {
				iconClass = 'fas fa-file-archive text-warning';
			} else if (['jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg'].includes(fileExt)) {
				iconClass = 'fas fa-file-image text-info';
			} else if (['mp3', 'wav', 'flac', 'aac'].includes(fileExt)) {
				iconClass = 'fas fa-file-audio text-info';
			} else if (['mp4', 'avi', 'mov', 'wmv', 'flv'].includes(fileExt)) {
				iconClass = 'fas fa-file-video text-info';
			} else if (['txt', 'log', 'ini', 'conf'].includes(fileExt)) {
				iconClass = 'fas fa-file-alt text-secondary';
			}
			var icon = $('<i>', {
				class: iconClass + ' me-2',
				css: { 'font-size': '1.2em' }
			});
			var typeLabel = $('<small>');
			typeLabel.addClass('text-muted d-block');
			typeLabel.text('文件');
			var fileNameContainer = $('<div>').addClass('d-flex align-items-center');
			fileNameContainer.append(icon);
			fileNameContainer.append(item.content);
			$a.append(typeLabel, fileNameContainer);
			fetch('download.php?' + new URLSearchParams({source: div.attr('data-attr')})).then(res => res.json()).then(data => {
				if (data.code == 1) {
					$a.attr('href', data.link);
					$a.attr('download', item.content);
				} else {
					$('#errors-body').text(data.msg);
					$('#errors').show();
					$('#errors').delay(3000).fadeOut();
				}
			});
		} else if (item.type == 1) {
			p.text(item.content);
		} else if (item.type == 3) {
			var tl = $('<small>');
			var button;
			tl.addClass('text-muted d-block');
			tl.text('群聊邀请');
			var msgLabel;
			if (condition && !item.content.finish) {
				msgLabel = '您已邀请对方加入“' + item.content.name + '”群聊。';
			} else if (!condition && !item.content.finish) {
				msgLabel = '对方邀请您加入“' + item.content.name + '”群聊。';
				button = $('<button>');
				button.addClass('btn btn-primary');
				button.text('加入');
				button.click(function() {
					fetch('groups.php', {
						method: 'POST',
						body: new URLSearchParams({
							action: 'invitation',
							extra: item.id
						})
					}).then(res => res.json()).then(data => {
						$('#errors-body').text(data.msg);
						$('#errors').show();
						$('#errors').delay(3000).fadeOut();
					})
				});
			} else if (condition && item.content.finish) {
				msgLabel = '对方已加入“' + item.content.name + '”群聊。';
			} else if (!condition && item.content.finish) {
				msgLabel = '您已加入“' + item.content.name + '”群聊。';
			}
			var textNode = document.createTextNode(msgLabel);
			p.append(tl, textNode);
			if (button) {
				p.append('<br>', button);
			}
		} else if (item.type == 4) {
			var tl = $('<small>');
			var button;
			tl.addClass('text-muted d-block');
			if (item.content.type == 'quit') {
				tl.text('退出群聊');
			} else if (item.content.type == 'logoff') {
				tl.text(item.content.nick + ' 因注销而退出群聊');
			} else if (item.content.type == 'adminadd') {
				tl.text('已添加管理员：' + item.inner_nick);
			} else if (item.content.type == 'adminremove') {
				tl.text('已移除管理员：' + item.inner_nick);
			} else if (item.content.type == 'transfer') {
				tl.text('已转让群主：' + item.inner_nick);
			} else if (item.content.type == 'join') {
			    tl.text('已加入群聊');
			}
			p.append(tl);
		}
		var small = $('<small>');
		small.addClass('text-muted');
		small.text(formatDateLong(item.sent_at));
		divMessageContent.append(small);
	});
}