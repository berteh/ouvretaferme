<?php
namespace shop;

class Share extends ShareElement {

	public static function getSelection(): array {

		return parent::getSelection() + [
			'shop' => ShopElement::getSelection(),
			'farm' => \farm\FarmElement::getSelection()
		];

	}

	public function canRead(): bool {

		$this->expects(['shop']);
		return $this['shop']->canWrite();

	}

}
?>