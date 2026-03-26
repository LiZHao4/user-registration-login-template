export function formatDateShort(date: number): string {
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
export function formatDateLong(date: number): string {
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
export function formatRelativeTime(date: number): string {
	const now = new Date();
	const d = new Date(date);
	const diffMs = now.getTime() - d.getTime();
	const diffMins = Math.floor(diffMs / (1000 * 60));
	const diffHours = Math.floor(diffMs / (1000 * 60 * 60));
	const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));
	if (diffMins < 1) {
		return '刚刚';
	} else if (diffMins < 60) {
		return `${diffMins}分钟前`;
	} else if (diffHours < 24) {
		return `${diffHours}小时前`;
	} else if (diffDays === 1) {
		return '昨天';
	} else if (diffDays === 2) {
		return '前天';
	} else if (diffDays < 7) {
		return `${diffDays}天前`;
	} else {
		return formatDateShort(date);
	}
}