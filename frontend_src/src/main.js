import { rollOverPopUpImage, hideCheckBoxLabels } from './utils.js'

Array.from(document.querySelectorAll('.parent')).forEach(rollOverPopUpImage)

Array.from(document.querySelectorAll('.control-label')).forEach(hideCheckBoxLabels)