<?php
/**
 * Pour les boutiques
 */
class ShopTemplate extends MainTemplate {

	public string $template = 'default shop';

	public function __construct() {

		parent::__construct();

		Asset::css('shop', 'shop.css');

	}

	protected function getHead(): string {

		$h = parent::getHead();
		$h .= $this->getStyles();

		return $h;

	}

	public function getStyles(): string {

		$fontLabel = ($this->data->eShop->canWrite() and get_exists('customFont')) ? GET('customFont') : $this->data->eShop['customFont'];
		$titleFontLabel = ($this->data->eShop->canWrite() and get_exists('customTitleFont')) ? GET('customTitleFont') : $this->data->eShop['customTitleFont'];

		$font = \website\DesignUi::getFont($fontLabel);
		$titleFont = \website\DesignUi::getTitleFont($titleFontLabel);

		$families = [];

		if($font !== NULL) {
			$families[] = 'family='.$font['label'];
		}

		if($titleFont !== NULL) {
			$families[] = 'family='.$titleFont['label'];
		}

		if($families) {
			$h = '<link rel="preconnect" href="https://fonts.googleapis.com">';
			$h .= '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
			$h .= '<link href="https://fonts.googleapis.com/css2?'.implode('&', $families).'" rel="stylesheet">';
		}

		$color = ($this->data->eShop->canWrite() and get_exists('customColor')) ? (GET('customColor') ?: NULL) : $this->data->eShop['customColor'];
		$background = ($this->data->eShop->canWrite() and get_exists('customBackground')) ? (GET('customBackground') ?: NULL) : $this->data->eShop['customBackground'];

		$h .= '<style>';
			$h .= ':root {';
				if($color !== NULL) {
					$h .= '--secondary: '.encode($color).' !important;';
				}
				if($background !== NULL) {
					$h .= '--background: '.encode($background).' !important;';
				}
			$h .= '}';
			if($font !== NULL) {
				$h .= 'body[data-template~="shop"] header,';
				$h .= 'body[data-template~="shop"] main {
					font-family: '.$font['value'].';
				}';
			}
			if($titleFont !== NULL) {
				$h .= 'body[data-template~="shop"] header h1,';
				$h .= 'body[data-template~="shop"] main h1 {
					font-family: '.$titleFont['value'].';
				}';
			}
		$h .= '</style>';

		return $h;
	}

}
?>
