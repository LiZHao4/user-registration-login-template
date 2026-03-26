export function getContrastColor(hexColor: string): string {
	let r = parseInt(hexColor[1] + hexColor[2], 16);
	let g = parseInt(hexColor[3] + hexColor[4], 16);
	let b = parseInt(hexColor[5] + hexColor[6], 16);
	const brightness = (r * 299 + g * 587 + b * 114) / 1000;
	return brightness > 128 ? 'black' : 'white';
}