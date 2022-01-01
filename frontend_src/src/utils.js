export function rollOverPopUpImage(el) {
	if(!el) {
		return
	}

	const emImg = el.querySelector('.pic')

	if(emImg.dataset.img !='') {
		el.addEventListener('mouseenter', (e)=> {
			emImg.classList.add('show')
			el.classList.add('show')
		})

		el.addEventListener('mouseout', (e)=> {
			emImg.classList.remove('show')
			el.classList.remove('show')
		})
	}

}


export function hideCheckBoxLabels (el) {
	if(!el) {
		return
	}

	const nextEl = el.nextElementSibling
	const parentEl = el.parentElement

	if(nextEl.classList.contains('checkbox')) {
		el.style.display = 'none'
		parentEl.classList.add('flex-end')
	}
}
