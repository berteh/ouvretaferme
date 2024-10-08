<?php
namespace shop;

class ProductUi {

	public function __construct() {

		\Asset::css('shop', 'product.css');

	}

	public function toggle(Product $eProduct) {

		return \util\TextUi::switch([
			'id' => 'product-switch-'.$eProduct['id'],
			'data-ajax' => $eProduct->canWrite() ? '/shop/product:doUpdateStatus' : NULL,
			'post-id' => $eProduct['id'],
			'post-status' => ($eProduct['status'] === Product::ACTIVE) ? Product::INACTIVE : Product::ACTIVE
		], $eProduct['status'] === Product::ACTIVE);

	}

	public function getList(Shop $eShop, Date $eDate, \selling\Sale $eSale, \Collection $cCategory, bool $isModifying): string {

		$eDate->expects(['cProduct']);

		$h = '';

		$ccProduct = $eDate['cProduct']->reindex(['product', 'category']);

		if($ccProduct->count() === 1) {
			$h .= $this->getProducts($eShop, $eDate, $eSale, $isModifying, $ccProduct->first());
		} else {

			if($ccProduct->offsetExists('')) {
				$h .= $this->getProducts($eShop, $eDate, $eSale, $isModifying, $ccProduct['']);
			}

			foreach($cCategory as $eCategory) {

				if($ccProduct->offsetExists($eCategory['id']) === FALSE) {
					continue;
				}

				$h .= '<h3>'.encode($eCategory['name']).'</h3>';
				$h .= $this->getProducts($eShop, $eDate, $eSale, $isModifying, $ccProduct[$eCategory['id']]);

			}

		}

		$h .= '<br/><br/><br/><br/>';

		if($eDate['isOrderable'] and ($eSale->canBasket($eShop) or $isModifying)) {
			$h .= $this->getOrderedProducts($eShop, $eDate, $eSale, $isModifying);
		}

		return $h;

	}

	protected function getOrderedProducts(Shop $eShop, Date $eDate, \selling\Sale $eSale, bool $isModifying): string {

		$confirmEmpty = [
			'data-confirm-normal' => s("Voulez-vous vider votre panier ?"),
			'data-confirm-modify' => s("Votre commande n'a pas été modifiée, et votre ancienne commande reste valide. Confirmer ?"),
		];
		$labelEmpty = $isModifying ? s("Annuler") : s("Vider mon panier");

		if($eSale->notEmpty() and $eSale['paymentMethod'] === NULL) {
			$defaultJson = (new BasketUi())->getJsonBasket($eSale);
		} else {
			$defaultJson = 'null';
		}

		$h = '<div class="shop-product-ordered hide" id="shop-basket" '.attr('onrender', 'BasketManage.init('.$eDate['id'].', '.$defaultJson.')').'>';
			$h .= '<div>';
				$h .= '<div class="shop-product-ordered-icon">'.\Asset::icon('basket').'</div>';
				$h .= '<span id="shop-basket-articles"></span>';
			$h .= '</div>';
			$h .= '<div>';
				$h .= '<div class="shop-product-ordered-icon">'.\Asset::icon('currency-euro').'</div>';
				$h .= '<span id="shop-basket-price"></span>';
				$h .= ' '.$this->getTaxes($eDate);
			$h .= '</div>';
			$h .= '<div style="display: flex;">';
				$h .= '<a href="'.ShopUi::url($eShop).'/'.$eDate['id'].'/panier'.($isModifying ? '?modify=1' : '').'" class="btn btn-secondary" id="shop-basket-next">';
					$h .= '<span class="hide-sm-up">'.($isModifying ? s("Modifier") : s("Commander")).'</span>';
					$h .= '<span class="hide-xs-down">'.($isModifying ? s("Modifier la commande") : s("Poursuivre la commande")).'</span>';
				$h .= '</a>';
				$h .= '&nbsp;';
				$h .= '<a onclick="BasketManage.empty(this, '.$eDate['id'].', true)" class="shop-basket-empty btn btn-danger" '.attrs($confirmEmpty).'>';
					$h .= '<span class="hide-sm-up" title="'.$labelEmpty.'">'.\Asset::icon('trash').'</span>';
					$h .= '<span class="hide-xs-down">'.$labelEmpty.'</span>';
				$h .= '</a>';
			$h .= '</div>';
		$h .= '</div>';

		return $h;

	}

	public function getProducts(Shop $eShop, Date $eDate, \selling\Sale $eSale, bool $isModifying, \Collection $cProduct): string {

		$h = '<div class="shop-product-wrapper">';
			$h .= $cProduct->makeString(fn($eProduct) => $this->getProduct($eDate, $eProduct, $eSale->canBasket($eShop) or $isModifying));
		$h .= '</div>';

		return $h;

	}

	public function getProduct(Date $eDate, Product $eProduct, bool $canOrder): string {

		$eProductSelling = $eProduct['product'];

		if($eProduct['packaging'] === NULL) {
			$price = $eProduct['price'];
		} else {
			$price = $eProduct['price'] * $eProduct['packaging'];
		}

		$h = '<div class="shop-product" data-id="'.$eProductSelling['id'].'" data-price="'.$price.'" data-has="0">';

			if($eProductSelling['vignette'] !== NULL) {
				$url = (new \media\ProductVignetteUi())->getUrlByElement($eProductSelling, 'l');
			} else if($eProductSelling['plant']->notEmpty()) {
				$url = (new \media\PlantVignetteUi())->getUrlByElement($eProductSelling['plant'], 'l');
			} else {
				$url = NULL;
			}

			$h .= '<div ';
			if($url !== NULL) {
				$h .= 'class="shop-product-image" style="background-image: url('.$url.')"';
			} else {
				$h .= 'class="shop-product-image shop-product-image-empty"';
			}
			$h .= '>';
				if($url === NULL) {
					if($eProductSelling['plant']->notEmpty()) {
						$h .= \plant\PlantUi::getVignette($eProductSelling['plant'], '8rem');
					} else {
						$h .= \Asset::icon('camera', ['class' => 'shop-product-image-placeholder']);
					}
				}
				if($eProductSelling['quality']) {
					$h .= '<div class="shop-header-image-quality">'.\farm\FarmUi::getQualityLogo($eProductSelling['quality'], '2.5rem').'</div>';
				}
			$h .= '</div>';

			$h .= '<div class="shop-product-text">';
				$h .= '<div class="shop-product-content">';

					$h .= '<h4>';
						$h .= $eProductSelling->getName('html');
					$h .= '</h4>';

					if($eDate['type'] === Date::PRO and $eProductSelling['size'] !== NULL) {
						$h .= '<div class="shop-product-size">';
							$h .= encode($eProductSelling['size']);
						$h .= '</div>';
					}

					if($eProductSelling['description'] !== NULL) {
						$h .= '<div class="shop-product-description">';
							$h .= (new \editor\EditorUi())->value($eProductSelling['description']);
						$h .= '</div>';
					}

				$h .= '</div>';

				$h .= '<div class="shop-product-buy">';

					$h .= '<div class="shop-product-buy-price">';

						$h .= '<span style="white-space: nowrap">'.\util\TextUi::money($eProduct['price']).' '.$this->getTaxes($eDate).' / '.\main\UnitUi::getSingular($eProductSelling['unit'], by: TRUE).'</span>';

						$h .= '<div class="shop-product-buy-infos">';

							if($eProduct['packaging'] !== NULL) {
								$h.= '<div class="shop-product-buy-info">';
									$h .= s("Colis de {value}", \main\UnitUi::getValue($eProduct['packaging'], $eProductSelling['unit'], TRUE));
								$h .= '</div>';
							}

							if($eProduct['stock'] !== NULL) {
								$h.= '<div class="shop-product-buy-info">';
								if($eProduct->isInStock() === FALSE) {
									$h .= s("Rupture de stock");
								} else {
									$h .= s("Stock : {value}", $eProduct->getRemainingStock());
								}
								$h .= '</div>';
							}

						$h .= '</div>';
					$h .= '</div>';

					if($canOrder and $eProduct->isInStock()) {
						$h .= self::quantityOrder($eDate, $eProductSelling, $eProduct);
					}

				$h .= '</div>';
			$h .= '</div>';

		$h .= '</div>';

		return $h;

	}

	public static function getTaxes(Date $eDate): string {

		if(
			$eDate['type'] === Shop::PRO and
			$eDate['farm']->getSelling('hasVat')
		) {
			return $eDate->getTaxes();
		} else {
			return '';
		}

	}

	public static function quantityOrder(Date $eDate, \selling\Product $eProductSelling, Product $eProduct, float $quantity = 0): string {

		if($eDate['isOrderable'] === FALSE) {
			return '';
		}

		$attributesDecrease = 'BasketManage.update('.$eDate['id'].', '.$eProductSelling['id'].', -'.self::getStep($eDate, $eProductSelling).', '.($eProduct['stock'] !== NULL ? $eProduct->getRemainingStock() : -1).')';
		$attributesIncrease = 'BasketManage.update('.$eDate['id'].', '.$eProductSelling['id'].', '.self::getStep($eDate, $eProductSelling).', '.($eProduct['stock'] !== NULL ? $eProduct->getRemainingStock() : -1).')';

		if($eProduct['packaging'] === NULL) {
			$price = $eProduct['price'];
		} else {
			$price = $eProduct['price'] * $eProduct['packaging'];
		}

		$h = '<div class="shop-product-quantity">';
			$h .= '<a class="btn btn-outline-primary btn-sm shop-product-quantity-decrease" onclick="'.$attributesDecrease.'">-</a>';
			$h .= '<span class="shop-product-quantity-value" data-price="'.$price.'" data-remaining-stock="'.$eProduct->getRemainingStock().'" data-product="'.$eProductSelling['id'].'" data-field="quantity">';
				$h .= '<span>'.$quantity.'</span> ';

				if($eProduct['packaging'] === NULL) {
					$h .= \main\UnitUi::getSingular($eProductSelling['unit'], short: TRUE);
				} else {
					$h .= s("colis");
				}

			$h .= '</span>';
			$h .= '<a class="btn btn-outline-primary btn-sm shop-product-quantity-increase" onclick="'.$attributesIncrease.'">+</a>';
		$h .= '</div>';

		return $h;

	}

	public static function getStep(\shop\Date $eDate, \selling\Product $eProduct): float {

		return match($eDate['type']) {
			Date::PRIVATE => $eProduct['privateStep'] ?? self::getDefaultPrivateStep($eProduct),
			Date::PRO => $eProduct['proStep'] ?? self::getDefaultProStep($eProduct),
		};

	}

	public static function getDefaultPrivateStep(\selling\Product $eProduct): float {

		return match($eProduct['unit']) {

			\selling\Product::GRAM => 100,
			\selling\Product::KG => 0.5,
			default => 1,

		};

	}

	public static function getDefaultProStep(\selling\Product $eProduct): float {

		return 1;

	}

	// Modifier (quick) le stock
	public function getUpdateList(Date $eDate, \Collection $cProduct, \Collection $cCategory): string {

		if($cProduct->empty()) {
			return '<div class="util-info">'.s("Vous ne vendez encore aucun produit à cette date !").'</div>';
		}

		$ccProduct = $cProduct->reindex(['product', 'category']);

		if($ccProduct->count() === 1) {
			return $this->getUpdateProducts($eDate, $ccProduct->first());
		} else {

			$h = '';

			if($ccProduct->offsetExists('')) {
				$h .= $this->getUpdateProducts($eDate, $ccProduct['']);
			}

			foreach($cCategory as $eCategory) {

				if($ccProduct->offsetExists($eCategory['id']) === FALSE) {
					continue;
				}

				$h .= '<h3>'.encode($eCategory['name']).'</h3>';
				$h .= $this->getUpdateProducts($eDate, $ccProduct[$eCategory['id']]);

			}

			return $h;

		}

	}

	public function getUpdateProducts(Date $eDate, \Collection $cProduct): string {

		$taxes = $eDate['farm']->getSelling('hasVat') ? '<span class="util-annotation">'.$eDate->getTaxes().'</span>' : '';

		$h = '<div class="'.($eDate['type'] === Date::PRIVATE ? 'util-overflow-xs' : 'util-overflow-sm').' stick-xs">';
			$h .= '<table class="tr-even">';
				$h .= '<thead>';
					$h .= '<tr>';
						$h .= '<th colspan="2">'.s("Produit").'</th>';
						if($eDate['type'] === Date::PRO) {
							$h .= '<td></td>';
						}
						$h .= '<th class="text-end highlight">'.s("Prix").' '.$taxes.'</th>';
						$h .= '<th class="text-end">'.s("Limite").'</th>';
						$h .= '<th class="text-end highlight">'.s("Vendu").'</th>';
						$h .= '<th class="text-end">';
							$h .= '<span class="hide-md-down">'.s("Vente en cours").'</span>';
							$h .= '<span class="hide-lg-up">'.s("Vente").'</span>';
						$h .= '</th>';
						$h .= '<th></th>';
					$h .= '</tr>';
				$h .= '</theaf>';
				$h .= '<tbody>';

					foreach($cProduct as $eProduct) {
						$h .= $this->getUpdateProduct($eDate, $eProduct);
					}

				$h .= '</tbody>';
			$h .= '</table>';
		$h .= '</div>';

		return $h;

	}

	public function getUpdateProduct(Date $eDate, Product $eProduct): string {

		$eProductSelling = $eProduct['product'];
		$uiProductSelling = new \selling\ProductUi();

		$h = '<tr>';

			$h .= '<td class="td-min-content">';
				if($eProductSelling['vignette'] !== NULL) {
					$h .= \selling\ProductUi::getVignette($eProductSelling, '3rem');
				} else if($eProductSelling['plant']->notEmpty()) {
					$h .= \plant\PlantUi::getVignette($eProductSelling['plant'], '3rem');
				}
			$h .= '</td>';

			$h .= '<td>';
				$h .= $uiProductSelling->getInfos($eProductSelling, includeStock: TRUE);
			$h .= '</td>';

			if($eDate['type'] === Date::PRO) {
				$h .= '<td class="td-min-content">';
					if($eProduct['packaging'] !== NULL) {
						$h .= s("Colis de {value}", \main\UnitUi::getValue($eProduct['packaging'], $eProductSelling['unit'], TRUE));
					}
				$h .= '</td>';
			}

			$h .= '<td class="text-end highlight" style="white-space: nowrap">';
				$h .= $eProduct->quick('price', \util\TextUi::money($eProduct['price']).' / '.\main\UnitUi::getSingular($eProductSelling['unit'], short: TRUE, by: TRUE));
			$h .= '</td>';
			$h .= '<td class="text-end">';
				if($eProduct['stock'] === NULL) {
					$stock = s("illimité");
				} else {
					$stock = $eProduct['stock'];
				}
				$h .= $eProduct->quick('stock', $stock);
			$h .= '</td>';
			$h .= '<td class="text-end highlight">';
				$h .= $eProduct['sold'] ?? 0;
			$h .= '</td>';
			$h .= '<td class="text-end">';
				$h .= $this->toggle($eProduct);
			$h .= '</td>';
			$h .= '<td class="td-min-content">';

				if($eProduct['sold'] === 0.0) {
					$h .= '<a data-ajax="/shop/product:doDelete" class="btn btn-danger" data-confirm="'.s("Voulez-vous vraiment supprimer ce produit de cette vente ?").'" post-id="'.$eProduct['id'].'">'.\Asset::icon('trash-fill').'</a>';
				} else {
					$h .= '<a class="btn btn-readonly btn-secondary disabled" disabled title="'.s("Vous ne pouvez pas supprimer ce produit car des ventes ont déjà été réalisées.").'">'.\Asset::icon('trash-fill').'</a>';

				}

			$h .= '</div>';
			$h .= '</td>';

		$h .= '</tr>';

		return $h;
	}

	public function create(\farm\Farm $eFarm, Date $eDate, \Collection $cProduct): \Panel {

		$eDate['cProduct'] = $cProduct;
		$eDate['farm'] = $eFarm;

		$form = new \util\FormUi([
			'columnBreak' => 'sm'
		]);

		$h = $form->openAjax('/shop/date:doCreateProducts');

			$h .= $form->hidden('id', $eDate['id']);
			$h .= $form->hidden('farm', $eFarm['id']);

			$h .= (new DateUi())->getProducts($form, $eDate);
			$h .= '<br/>';
			$h .= $form->submit(s("Ajouter"), ['class' => 'btn btn-primary']);

		$h .= $form->close();

		return new \Panel(
			title: s("Ajouter des produits à la vente"),
			body: $h
		);
	}

	public static function p(string $property): \PropertyDescriber {

		$d = Product::model()->describer($property, [
			'product' => s("Produit"),
			'stock' => s("Limite"),
			'price' => s("Prix unitaire"),
			'date' => s("Vente"),
		]);

		switch($property) {

			case 'stock' :
				$d->field = function(\util\FormUi $form, Product $e) use($d) {

					$e->expects([
						'packaging',
						'date' => ['type']
					]);

					$step = (
						$e['date']['type'] === Date::PRO or
						in_array($e['product']['unit'], [\selling\Product::UNIT, \selling\Product::BUNCH])
					) ? 1 : 0.1;

					$h = '<div class="input-group" data-product="'.$e['product']['id'].'" data-element="input-group-stock">';
						$h .= $form->number($d->name, $e['stock'] ?? NULL, [
							'data-product' => $e['product']['id'],
							'onfocusin' => 'DateManage.checkStockFocusIn(this)',
							'onfocusout' => 'DateManage.checkStockFocusOut(this)',
							'placeholder' => s("Illimité"),
							'data-placeholder' => s("Illimité"),
							'min' => $step,
							'step' => $step,
						]);

						if(
							$e['date']['type'] === Date::PRIVATE or
							$e['packaging'] === NULL
						) {
							$unit = \main\UnitUi::getNeutral($e['product']['unit'], TRUE);
						} else {
							$unit = s("colis");
						}

						$h .= $form->addon($unit);

					$h .= '</div>';

					return $h;

				};
				break;

			case 'price' :
				$d->append = function(\util\FormUi $form, Product $e) {

					return $form->addon(s('€ {taxes} / {unit}', [
						'taxes' => $e['date']['farm']->getSelling('hasVat') ? $e['date']->getTaxes() : '',
						'unit' => \main\UnitUi::getSingular($e['product']['unit'], short: TRUE)
					]));

				};
				break;


		}

		return $d;

	}

}
