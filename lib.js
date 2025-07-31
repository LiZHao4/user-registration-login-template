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
		if ([1, 2, 6].includes(item.msg_type)) {
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
			} else if (item.msg_type == 6) {
				if (isTypeGroup && item.msg_sender != id) {
					contentString += item.msg_nick + ': ';
				}
				contentString += '[聊天记录]';
				p2.text(contentString);
			}
		} else if (item.msg_type == 3 || item.msg_type == 4 || item.msg_type == 5) {
			var jsonObj = JSON.parse(item.content);
			if (item.msg_type == 3) {
				var string = '[群聊邀请] ';
				var c1 = item.msg_sender == id;
				var c2;
				if (isTypeFriend) {
					c2 = jsonObj.finish;
				} else if (isTypeGroup) {
					c2 = jsonObj.finish.includes(id);
				}
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
				} else if (jsonObj.type == 'kick') {
					p2.text('[群聊踢出] “' + item.msg_nick + '”已将“' + item.inner_nick + '”踢出群聊。');
				} else if (jsonObj.type == 'ban') {
					p2.text('[群聊禁言] “' + item.msg_nick + '”已将“' + item.inner_nick + '”禁言。');
				} else if (jsonObj.type == 'unban') {
					p2.text('[群聊解禁] “' + item.msg_nick + '”已将“' + item.inner_nick + '”解禁。');
				} else if (jsonObj.type == 'rename') {
					p2.text('[群聊重命名] “' + item.msg_nick + '”已将群聊重命名为“' + jsonObj.name + '”。');
				} else if (jsonObj.type == 'avatar') {
					p2.text('[群聊头像] “' + item.msg_nick + '”已更新群头像。');
				} else if (jsonObj.type == 'recall') {
					p2.text('[消息撤回] “' + item.msg_nick + '”已撤回一条消息。');
				}
			} else if (item.msg_type == 5) {
			    p2.text(jsonObj[jsonObj.length - 1].msg);
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
	var originTarget = data.target;
	if (data.fresh) data.target.empty();
	if (data.prepend) {
		data.target = $('<div>');
	}
	data.data.forEach(function(item) {
		var currentTime = new Date().getTime();
		var messageTime = new Date(item.sent_at * 1000).getTime();
		var canRecall = (currentTime - messageTime) < 120000;
		var condition = item.sender == userId;
		var div = $('<div>');
		if (condition) {
			div.addClass('d-flex flex-row-reverse justify-content-start align-items-sm-start my-2');
		} else {
			div.addClass('d-flex flex-row align-items-sm-start my-2');
		}
		div.css('gap', '5px');
		div.attr('data-id', item.id);
		div.attr('data-type', item.type);
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
			'white-space': 'pre-wrap',
			border: '2px solid white'
		});
		var actionBar = $('<div>');
		actionBar.css({
			display: 'flex',
			'flex-direction': 'row',
			gap: '5px'
		});
		var small = $('<small>');
		small.addClass('text-muted');
		small.text(formatDateLong(item.sent_at));
		function edit() {
			var originalMessage = p.text();
			var editContainer = $('<div>').addClass('edit-container');
			var textarea = $('<textarea>')
				.addClass('form-control mb-2')
				.val(originalMessage)
				.attr('rows', 3);
			var btnGroup = $('<div>').addClass('d-flex gap-2');
			var saveBtn = $('<button>')
				.addClass('btn btn-sm btn-success flex-fill')
				.text('保存');
			var cancelBtn = $('<button>')
				.addClass('btn btn-sm btn-outline-secondary flex-fill')
				.text('取消');
			btnGroup.append(saveBtn, cancelBtn);
			editContainer.append(textarea, btnGroup);
			p.replaceWith(editContainer);
			saveBtn.click(function() {
				fetch('messages.php', {
					method: 'POST',
					body: new URLSearchParams({
						action: 'edit',
						target: item.id,
						msg: textarea.val()
					})
				}).then(response => response.json()).then(data2 => {
					if (data2.code == 1) {
						fetch('chats.php?' + new URLSearchParams({
							target: idFromUrl,
							min: item.id,
							max: item.id
						})).then(response => response.json()).then(data3 => {
							if (data3.code == 1) {
								var tempDiv = $('<div>');
								generate3({
									target: tempDiv,
									data: data3.data,
									avatar: myAvatar,
									fresh: false,
									remark: null,
									type,
									prepend: false
								});
								div.replaceWith(tempDiv.children().first());
							}
						});
					} else {
						alert(data2.msg);
					}
				})
			});
			cancelBtn.click(function() {
				editContainer.replaceWith(p);
			});
		}
		function recallMessage() {
			fetch('messages.php', {
				method: 'POST',
				body: new URLSearchParams({
					action: 'recall',
					target: item.id
				})
			}).then(response => response.json()).then(data2 => {
				if (data2.code == 1) {
					div.remove();
					fetch('chats.php?' + new URLSearchParams({
						target: idFromUrl,
						min: item.id,
						max: item.id
					})).then(response => response.json()).then(data3 => {
						if (data3.code == 1) {
							generate3({
								target: originTarget,
								data: data3.data,
								avatar: myAvatar,
								fresh: false,
								remark: null,
								type,
								prepend: false
							});
						}
					});
				} else {
					alert(data2.msg);
				}
			});
		}
		function reply() {}
		function createMessageMenu(button) {
			if (currentMessageMenu) {
				currentMessageMenu.remove();
				currentMessageMenu = null;
			}
			var menu = $('<div>').addClass('message-menu').css({
				position: 'absolute',
				background: 'white',
				boxShadow: '0 2px 10px rgba(0,0,0,0.2)',
				borderRadius: '4px',
				zIndex: 1000,
				padding: '5px 0',
				minWidth: '100px'
			});
			var btnOffset = button.offset();
			menu.css({
				top: btnOffset.top - menu.outerHeight() - 5,
				left: btnOffset.left
			});
			var menuItemsCount = 0;
			function createMenuItem(text, action) {
				var item = $('<div>').addClass('menu-item').text(text);
				item.click(function() {
					action();
					menu.remove();
					currentMessageMenu = null;
				});
				return item;
			}
			if (item.type == 1 || item.type == 5) {
				if (condition) {
					menu.append(createMenuItem('编辑', edit));
					menuItemsCount++;
				}
			}
			if (condition && item.type != 4 && canRecall) {
				menu.append(createMenuItem('撤回', recallMessage));
				menuItemsCount++;
			}
			menu.append(createMenuItem('回复', reply));
			$('body').append(menu);
			currentMessageMenu = menu;
			setTimeout(() => {
				$(document).one('click', function(e) {
					if (!$(e.target).closest('.message-menu').length) {
						if (currentMessageMenu) {
							currentMessageMenu.remove();
							currentMessageMenu = null;
						}
					}
				});
			}, 10);
		}
		if (item.type == 2) {
			div.attr('data-attr', item.multi);
			var $a = $('<a>', { html: p.html() });
			$.each(p[0].attributes, function() {
				$a.attr(this.name, this.value);
			});
			p.remove();
			divMessageContent.append($a);
			$a.css({
				'text-decoration': 'none',
				cursor: 'default',
				color: '#000'
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
			var msgLabel, inGroupCondition;
			if (type == 'friend') {
				inGroupCondition = item.content.finish;
			} else {
				inGroupCondition = item.content.finish.includes(userId);
			}
			if (condition && !inGroupCondition) {
				msgLabel = '您已邀请对方加入“' + item.content.name + '”群聊。';
			} else if (!condition && !inGroupCondition) {
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
			} else if (condition && inGroupCondition) {
				msgLabel = '对方已加入“' + item.content.name + '”群聊。';
			} else if (!condition && inGroupCondition) {
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
			} else if (item.content.type == 'recall') {
			    tl.text('撤回了一条消息');
			} else if (item.content.type == 'kick') {
				tl.text('已将“' + item.inner_nick + '”移出群聊');
			} else if (item.content.type == 'ban') {
				tl.text('已将“' + item.inner_nick + '”禁言');
			} else if (item.content.type == 'unban') {
				tl.text('已将“' + item.inner_nick + '”解除禁言');
			} else if (item.content.type == 'rename') {
				tl.text('已将群聊名称改为“' + item.content.name + '”');
			} else if (item.content.type == 'avatar') {
				tl.text('已更改群聊头像');
			} else if (item.content.type == 'recall') {
				tl.text('撤回了一条消息');
			}
			p.append(tl);
		} else if (item.type == 5) {
			p.text(item.content[item.content.length - 1].msg);
			small.text(formatDateLong(item.content[item.content.length - 1].sent_at));
			var pagination = $('<div>').addClass('pagination-container d-flex align-items-center');
			pagination.css({
				top: '-18px',
				right: condition ? '0' : 'unset',
				left: condition ? 'unset' : '0',
				background: 'rgba(255, 255, 255, 0.9)',
				padding: '0 3px',
				'border-radius': '10px',
				'z-index': '10'
			});
			var totalPages = item.content.length;
			var currentPage = totalPages - 1;
			var prevBtn = $('<button>').addClass('btn pagination-btn p-1')
				.html('<i class="fas fa-chevron-left" style="font-size: 0.7em;"></i>')
				.prop('disabled', currentPage === 0)
				.css({
					'line-height': '1',
					'min-width': '20px'
				});
			var pageIndicator = $('<span>').addClass('page-indicator px-1')
				.text(`${currentPage + 1} / ${totalPages}`)
				.css('font-size', '0.75em');
			var nextBtn = $('<button>').addClass('btn pagination-btn p-1')
				.html('<i class="fas fa-chevron-right" style="font-size: 0.7em;"></i>')
				.prop('disabled', currentPage === totalPages - 1)
				.css({
					'line-height': '1',
					'min-width': '20px'
				});
			function updatePagination() {
				prevBtn.prop('disabled', currentPage === 0);
				nextBtn.prop('disabled', currentPage === totalPages - 1);
				pageIndicator.text(`${currentPage + 1} / ${totalPages}`);
			}
			pagination.append(prevBtn, pageIndicator, nextBtn);
			divMessageContent.append(pagination);
			prevBtn.click(function() {
				if (currentPage > 0) {
					currentPage--;
					p.text(item.content[currentPage].msg);
					small.text(formatDateLong(item.content[currentPage].sent_at));
					updatePagination();
				}
			});
			nextBtn.click(function() {
				if (currentPage < totalPages - 1) {
					currentPage++;
					p.text(item.content[currentPage].msg);
					small.text(formatDateLong(item.content[currentPage].sent_at));
					updatePagination();
				}
			});
		} else if (item.type == 6) {
			var records = item.content;
			var forwardContainer = $('<div>').addClass('forwarded-container');
			var header = $('<div>').addClass('forwarded-header').text('聊天记录 (' + records.length + '条消息)');
			var contentContainer = $('<div>').addClass('forwarded-content').css('display', 'none');
			p.remove();
			header.click(function() {
				contentContainer.toggle();
			});
			function renderRecordMessage(record) {
				var recordDiv = $('<div>').addClass('forwarded-record');
				var senderDiv = $('<div>').addClass('forwarded-sender');
				var avatar = $('<img>').attr('src', record.sender_avatar || 'default.jpg').addClass('forwarded-avatar');
				var nickname = $('<span>').addClass('forwarded-nick').text(record.sender_nick || '未知用户');
				senderDiv.append(avatar, nickname);
				var messageDiv = $('<div>').addClass('forwarded-message');
				var timeDiv = $('<div>').addClass('forwarded-time').text(formatDateLong(record.sent_at));
				if (record.type == 6) {
					var nestedRecords = record.content;
					var nestedContainer = $('<div>').addClass('forwarded-container');
					var nestedHeader = $('<div>').addClass('forwarded-header').text('聊天记录 (' + nestedRecords.length + '条消息)');
					var nestedContent = $('<div>').addClass('forwarded-content').css('display', 'none');
					nestedHeader.click(function() {
						nestedContent.toggle();
					});
					nestedRecords.forEach(function(nestedRecord) {
						nestedContent.append(renderRecordMessage(nestedRecord));
					});
					nestedContainer.append(nestedHeader, nestedContent);
					messageDiv.append(nestedContainer);
				} else if (record.type == 5) {
					var lastMsg = record.content[record.content.length - 1].msg;
					messageDiv.text(lastMsg);
				} else if (record.type == 1) {
					messageDiv.text(record.content);
				} else if (record.type == 2) {
					messageDiv.text('[文件] ' + record.content);
				}
				recordDiv.append(senderDiv, messageDiv, timeDiv);
				return recordDiv;
			}
			records.forEach(record => {
				contentContainer.append(renderRecordMessage(record));
			});
			forwardContainer.append(header, contentContainer);
			divMessageContent.append(forwardContainer);
		}
		if (item.type !== 4) {
			var moreBtn = $('<button>', {
				class: 'btn btn-sm more-btn',
				html: '<i class="fas fa-ellipsis-h"></i>'
			}).css({
				'border-radius': '3px',
				padding: '2px 8px',
				border: 'none',
				'font-size': '0.8em'
			}).hover(function() {
				$(this).css('opacity', '0.8');
			}, function() {
				$(this).css('opacity', '1');
			});
			actionBar.append(moreBtn);
			moreBtn.click(function(e) {
				e.stopPropagation();
				createMessageMenu($(this));
			});
		}
		actionBar.append(small);
		divMessageContent.append(actionBar);
	});
	if (data.prepend) {
		originTarget.prepend(data.target.children());
	}
}