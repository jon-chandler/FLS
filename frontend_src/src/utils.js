export function rollOverPopUpImage(el) {
	if(!el) {
		return
	}

	const emImg = el.querySelector('.pic')

	if(emImg.getAttribute('src').length) {
		el.addEventListener('mouseenter', (e)=> {
			emImg.classList.add('show')
		})

		el.addEventListener('mouseout', (e)=> {
			emImg.classList.remove('show')
		})
	}

}


export function hideCheckBoxLabels (el) {
	if(!el) {
		return
	}

	const nextEl = el.nextElementSibling
	
	if(nextEl.classList.contains('checkbox')) {
		el.style.display = 'none'
	}
}
