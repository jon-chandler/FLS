<?php
	namespace Concrete\Theme\FLS;

	use Concrete\Core\Area\Layout\Preset\Provider\ThemeProviderInterface;
	use Concrete\Core\Page\Theme\Theme;

	class PageTheme extends Theme implements ThemeProviderInterface {

		public function getThemeName() {
			return t('FLS');
		}

		public function getThemeDescription() {
			return t('FLS site theme');
		}


		public function getThemeEditorClasses() {
			return [];
		}

		public function getThemeAreaLayoutPresets() {
			$presets = [];

			return $presets;
		}

	}
