import { type FriendItem } from '@/types/api'
export function getDisplayNick(item: FriendItem): string {
    const isTypeFriend = item.type === 'friend';
    const isTypeGroup = item.type === 'group';
    if (isTypeFriend) {
        return item.nick;
    } else if (isTypeGroup) {
        return item.nick + ' (' + item.member_count + ')';
    }
}
export function getDisplayContent(item: FriendItem, currentUserId: number): string {
    const isTypeFriend = item.type === 'friend';
    const isTypeGroup = item.type === 'group';
    if ([1, 2, 6].includes(item.msg_type)) {
        let contentString = '';
        if (item.msg_type === 1) {
            if (isTypeGroup && item.msg_sender !== currentUserId) {
                contentString += item.msg_nick + ': ';
            }
            contentString += item.content.replace(/\n/g, ' ');
        } else if (item.msg_type === 2) {
            if (isTypeGroup && item.msg_sender !== currentUserId) {
                contentString += item.msg_nick + ': ';
            }
            contentString += '[文件] ' + item.content;
        } else if (item.msg_type === 6) {
            if (isTypeGroup && item.msg_sender !== currentUserId) {
                contentString += item.msg_nick + ': ';
            }
            contentString += '[聊天记录]';
        }
        return contentString;
    } else if (item.msg_type === 3 || item.msg_type === 4 || item.msg_type === 5) {
        try {
            const jsonObj = JSON.parse(item.content);
            if (item.msg_type === 3) {
                let string = '[群聊邀请] ';
                const c1 = item.msg_sender === currentUserId;
                let c2;
                if (isTypeFriend) {
                    c2 = jsonObj.finish;
                } else if (isTypeGroup) {
                    c2 = jsonObj.finish.includes(currentUserId);
                }
                if (c1 && c2) {
                    string += '对方已加入"' + jsonObj.name + '"群聊。';
                } else if (c1 && !c2) {
                    string += '您已邀请对方加入"' + jsonObj.name + '"群聊。';
                } else if (!c1 && c2) {
                    string += '您已加入"' + jsonObj.name + '"群聊。';
                } else if (!c1 && !c2) {
                    string += '对方已邀请您加入"' + jsonObj.name + '"群聊。';
                }
                return string;
            } else if (item.msg_type === 4) {
                if (jsonObj.type === 'quit') {
                    return '[群聊退出] "' + item.msg_nick + '"已退出群聊。';
                } else if (jsonObj.type === 'logoff') {
                    return '[群聊退出] "' + jsonObj.nick + '"因注销而退出群聊。';
                } else if (jsonObj.type === 'adminadd') {
                    return '[群聊管理员] "' + item.msg_nick + '"已将"' + item.inner_nick + '"设为群聊管理员。';
                } else if (jsonObj.type === 'adminremove') {
                    return '[群聊管理员] "' + item.msg_nick + '"已将"' + item.inner_nick + '"取消群聊管理员。';
                } else if (jsonObj.type === 'transfer') {
                    return '[群主转让] "' + item.msg_nick + '"已将群主转让给"' + item.inner_nick + '"。';
                } else if (jsonObj.type === 'join') {
                    return '[群聊加入] "' + item.msg_nick + '"已加入群聊。';
                } else if (jsonObj.type === 'kick') {
                    return '[群聊踢出] "' + item.msg_nick + '"已将"' + item.inner_nick + '"踢出群聊。';
                } else if (jsonObj.type === 'ban') {
                    return '[群聊禁言] "' + item.msg_nick + '"已将"' + item.inner_nick + '"禁言。';
                } else if (jsonObj.type === 'unban') {
                    return '[群聊解禁] "' + item.msg_nick + '"已将"' + item.inner_nick + '"解禁。';
                } else if (jsonObj.type === 'rename') {
                    return '[群聊重命名] "' + item.msg_nick + '"已将群聊重命名为"' + jsonObj.name + '"。';
                } else if (jsonObj.type === 'avatar') {
                    return '[群聊头像] "' + item.msg_nick + '"已更新群头像。';
                } else if (jsonObj.type === 'recall') {
                    return '[消息撤回] "' + item.msg_nick + '"已撤回一条消息。';
                }
            } else if (item.msg_type === 5) {
                return jsonObj[jsonObj.length - 1].msg;
            }
        } catch (e) {
            console.error('解析消息内容失败:', e);
        }
    }
    return item.content || '';
}