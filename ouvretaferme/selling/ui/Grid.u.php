<?php
namespace selling;

class GridUi {

	public function __construct() {

		\Asset::css('selling', 'customer.css');

	}

	public function getGridByProduct(Product $eProduct, \Collection $cGrid): string {

		if($eProduct['pro'] === FALSE) {
			return '';
		}

		$h = '<div class="util-title">';
			$h .= '<h3>'.s("Grille tarifaire personnalisée pour les professionnels").'</h3>';
			$h .= '<div>';
				$h .= '<a data-dropdown="bottom-end" class="dropdown-toggle btn btn-outline-primary">'.\Asset::icon('gear-fill').'</a>';
				$h .= '<div class="dropdown-list">';
					$h .= '<div class="dropdown-title">'.encode($eProduct->getName()).'</div>';
					if($cGrid->notEmpty()) {
						$h .= '<a href="/selling/product:updateGrid?id='.$eProduct['id'].'" class="dropdown-item">'.s("Modifier la grille").'</a>';
						$h .= '<a data-ajax="/selling/product:doDeleteGrid" post-id="'.$eProduct['id'].'" class="dropdown-item" data-confirm="'.s("Confirmer la suppression de l'ensemble de la grille personnalisée pour ce client ?").'">'.s("Supprimer toute la grille personnalisée").'</a>';
					} else {
						$h .= '<a href="/selling/product:updateGrid?id='.$eProduct['id'].'" class="dropdown-item">'.s("Personnaliser la grille").'</a>';
					}
				$h .= '</div>';
			$h .= '</div>';
		$h .= '</div>';

		if($cGrid->empty()) {

			$h .= '<div class="util-empty">'.s("Aucune personnalisation de prix pour ce produit.").'</div>';

		} else {

			$h .= '<table class="customer-price tr-even">';

			$h .= '<tr>';
				$h .= '<th>'.s("Client").'</th>';
				$h .= '<th>'.s("Prix").'</th>';
				$h .= '<th>'.s("Colis").'</th>';
				$h .= '<th>'.s("Depuis le...").'</th>';
			$h .= '</tr>';

			foreach($cGrid as $eGrid) {

				// Pas de changement par rapport aux prix de base
				if($eGrid['price'] === NULL and $eGrid['packaging'] === NULL) {
					continue;
				}

				$eCustomer = $eGrid['customer'];

				$taxes = $eProduct['farm']->getSelling('hasVat') ? CustomerUi::getTaxes($eCustomer['type']) : '';

				$h .= '<tr>';

					$h .= '<td>';
						$h .= CustomerUi::link($eCustomer);
						$h .= ' <span class="util-annotation">'.CustomerUi::getCategory($eCustomer).'</span>';
					$h .= '</td>';

					$h .= '<td>';
						$h .= $eGrid->quick('price', $eGrid['price'] ? \util\TextUi::money($eGrid['price']).' '.$taxes.\selling\UnitUi::getBy($eProduct['unit']) : '-');
					$h .= '</td>';

					$h .= '<td>';
						$h .= $eGrid->quick('packaging', $eGrid['packaging'] ? \selling\UnitUi::getValue($eGrid['packaging'], $eProduct['unit']) : '-');
					$h .= '</td>';

					$h .= '<td>';
						$h .= \util\DateUi::numeric($eGrid['updatedAt'], \util\DateUi::DATE);
					$h .= '</td>';

				$h .= '</tr>';

			}

			$h .= '</table>';

		}

		return $h;

	}

	public function getGridByCustomer(Customer $eCustomer, \Collection $cGrid): string {

		if($eCustomer['type'] === Customer::PRIVATE) {
			return '';
		}

		$h = '<div class="util-title">';

			if($cGrid->empty()) {

				$h .= '<div class="util-empty">'.s("Aucune personnalisation de prix pour ce client.").'</div>';

			} else {

				$h .= '<div class="util-info">';
					$h .= s("Uniquement les prix personnalisés pour ce client et en cours de validité.");
				$h .= '</div>';

			}
			$h .= '<div>';
				$h .= '<a data-dropdown="bottom-end" class="dropdown-toggle btn btn-outline-secondary">'.\Asset::icon('gear-fill').'</a>';
				$h .= '<div class="dropdown-list bg-secondary">';
					$h .= '<div class="dropdown-title">'.encode($eCustomer->getName()).'</div>';
					if($cGrid->notEmpty()) {
						$h .= '<a href="/selling/customer:updateGrid?id='.$eCustomer['id'].'" class="dropdown-item">'.s("Modifier la grille").'</a>';
						$h .= '<a data-ajax="/selling/customer:doDeleteGrid" post-id="'.$eCustomer['id'].'" class="dropdown-item" data-confirm="'.s("Confirmer la suppression de l'ensemble de la grille personnalisée pour ce client ?").'">'.s("Supprimer toute la grille personnalisée").'</a>';
					} else {
						$h .= '<a href="/selling/customer:updateGrid?id='.$eCustomer['id'].'" class="dropdown-item">'.s("Personnaliser la grille").'</a>';
					}
				$h .= '</div>';
			$h .= '</div>';
		$h .= '</div>';

		if($cGrid->notEmpty()) {

			$h .= '<table class="customer-price tr-even">';

			$h .= '<tr>';
				$h .= '<th class="customer-price-vignette"></th>';
				$h .= '<th>'.s("Produit").'</th>';
				$h .= '<th>'.s("Prix").'</th>';
				if($eCustomer['type'] === Customer::PRO) {
					$h .= '<th>'.s("Colis").'</th>';
				}
			$h .= '</tr>';

			foreach($cGrid as $eGrid) {

				// Pas de changement par rapport aux prix de base
				if($eGrid['price'] === NULL and $eGrid['packaging'] === NULL) {
					continue;
				}

				$eProduct = $eGrid['product'];

				$taxes = $eCustomer['farm']->getSelling('hasVat') ? CustomerUi::getTaxes($eCustomer['type']) : '';

				$h .= '<tr>';

					$h .= '<td class="customer-price-vignette">';
						$h .= ProductUi::getVignette($eProduct, '4rem');
					$h .= '</td>';

					$h .= '<td>';
						$h .= '<a href="/produit/'.$eProduct['id'].'">'.encode($eProduct->getName()).'</a>';
						if($eProduct['size']) {
							$h .= '<div><small><u>'.encode($eProduct['size']).'</u></div>';
						}
					$h .= '</td>';

					$h .= '<td>';
						$h .= '<div>';
							$h .= $eGrid->quick('price', $eGrid['price'] ? \util\TextUi::money($eGrid['price']).' '.$taxes.\selling\UnitUi::getBy($eProduct['unit']) : '-');
						$h .= '</div>';
						$defaultPrice = $eProduct[$eCustomer['type'].'Price'];
						if($defaultPrice !== NULL) {
							$h .= '<small class="color-muted">';
								$h .= s("Base : {value}", \util\TextUi::money($defaultPrice));
							$h .= '</small>';
						}
					$h .= '</td>';

					if($eCustomer['type'] === Customer::PRO) {

						$h .= '<td>';

							$h .= '<div>';
								$h .= $eGrid->quick('packaging', $eGrid['packaging'] ? \selling\UnitUi::getValue($eGrid['packaging'], $eProduct['unit']) : '-');
							$h .= '</div>';
							if($eProduct['proPackaging'] !== NULL) {
								$h .= '<small class="color-muted">';
									$h .= s("Base : {value}", \selling\UnitUi::getValue($eProduct['proPackaging'], $eProduct['unit']));
								$h .= '</small>';
							}
						$h .= '</td>';

					}

				$h .= '</tr>';

			}

			$h .= '</table>';

		}

		return $h;

	}

	public function updateByCustomer(Customer $eCustomer, \Collection $cProduct): \Panel {

		$form = new \util\FormUi();

		$h = $form->hidden('id', $eCustomer['id']);

		$h .= '<div class="util-block-help">'.s("Vous pouvez personnaliser ici les prix et les conditionnements pour votre client {value}. Les informations personnalisées ont la priorité sur les valeurs de base, mais ne s'appliquent pas dans les boutiques en ligne.", encode($eCustomer->getName())).'</div>';

		$h .= '<table class="stick-xs">';

		$h .= '<tr>';
			$h .= '<th class="customer-price-vignette"></th>';
			$h .= '<th>'.s("Produit").'</th>';
			$h .= '<th>'.s("Prix").'</th>';
			if($eCustomer['type'] === Customer::PRO) {
				$h .= '<th>'.s("Colis").'</th>';
			}
		$h .= '</tr>';

		foreach($cProduct as $eProduct) {

			$eGrid = $eProduct['eGrid'];

			$taxes = $eCustomer['farm']->getSelling('hasVat') ? CustomerUi::getTaxes($eCustomer['type']) : '';

			$h .= '<tr>';

				$h .= '<td class="customer-price-vignette">';
					$h .= ProductUi::getVignette($eProduct, '4rem');
				$h .= '</td>';

				$h .= '<td>';
					$h .= '<a href="/produit/'.$eProduct['id'].'">'.encode($eProduct->getName()).'</a>';
					if($eProduct['size']) {
						$h .= '<div><small><u>'.encode($eProduct['size']).'</u></small></div>';
					}
				$h .= '</td>';

				$defaultPrice = $eProduct[$eCustomer['type'].'Price'];

				$h .= '<td data-wrapper="price['.$eProduct['id'].']">';
					$h .= '<div>';
						$h .= $form->inputGroup(
							$form->number('price['.$eProduct['id'].']', $eGrid['price'] ?? '', ['step' => 0.01]).
							$form->addon('€ '.$taxes.\selling\UnitUi::getBy($eProduct['unit']))
						);
					$h .= '</div>';
					if($defaultPrice !== NULL) {
						$h .= '<small class="color-muted">';
							$h .= s("Base : {value}", \util\TextUi::money($defaultPrice));
						$h .= '</small>';
					}
				$h .= '</td>';

				if($eCustomer['type'] === Customer::PRO) {

					$h .= '<td data-wrapper="packaging['.$eProduct['id'].']">';
						$h .= '<div>';
							$h .= $form->inputGroup(
								$form->number('packaging['.$eProduct['id'].']', $eGrid['packaging'] ?? '', ['step' => 0.01]).
								($eProduct['unit']->notEmpty() ? $form->addon(\selling\UnitUi::getSingular($eProduct['unit'])) : '')
							);
						$h .= '</div>';
						if($eProduct['proPackaging'] !== NULL) {
							$h .= '<small class="color-muted">';
								$h .= s("Base : {value}", \selling\UnitUi::getValue($eProduct['proPackaging'], $eProduct['unit']));
							$h .= '</small>';
						}
					$h .= '</td>';

				}

			$h .= '</tr>';

		}

		$h .= '</table>';

		return new \Panel(
			id: 'panel-grid-customer',
			title: s("Personnaliser la grille tarifaire"),
			subTitle: CustomerUi::getPanelHeader($eCustomer),
			dialogOpen: $form->openAjax('/selling/customer:doUpdateGrid', ['class' => 'panel-dialog container']),
			dialogClose: $form->close(),
			body: $h,
			footer: $form->submit(s("Enregistrer"), ['class' => 'btn btn-primary btn-lg']),
			close: 'reload'
		);

	}

	public function updateByProduct(Product $eProduct, \Collection $cCustomer): \Panel {

		$form = new \util\FormUi();

		$h = $form->hidden('id', $eProduct['id']);

		$h .= '<table class="stick-xs">';

		$h .= '<tr>';
			$h .= '<th>'.s("Client").'</th>';
			$h .= '<th>'.s("Prix").'</th>';
			$h .= '<th>'.s("Colis").'</th>';
		$h .= '</tr>';

		foreach($cCustomer as $eCustomer) {

			$eGrid = $eCustomer['eGrid'];

			$taxes = $eProduct['farm']->getSelling('hasVat') ? CustomerUi::getTaxes($eCustomer['type']) : '';

			$h .= '<tr>';

				$h .= '<td>';
					$h .= CustomerUi::link($eCustomer);
				$h .= '</td>';

				$h .= '<td data-wrapper="price['.$eCustomer['id'].']">';
					$h .= $form->inputGroup(
						$form->number('price['.$eCustomer['id'].']', $eGrid['price'] ?? '', ['step' => 0.01]).
						$form->addon('€ '.$taxes.\selling\UnitUi::getBy($eProduct['unit']))
					);
				$h .= '</td>';

				$h .= '<td data-wrapper="packaging['.$eCustomer['id'].']">';

					if($eCustomer['type'] === Customer::PRO) {
						$h .= $form->inputGroup(
							$form->number('packaging['.$eCustomer['id'].']', $eGrid['packaging'] ?? '', ['step' => 0.01]).
							($eProduct['unit']->notEmpty() ? $form->addon(\selling\UnitUi::getSingular($eProduct['unit'])) : '')
						);
					} else {
						$h .= '-';
					}

				$h .= '</td>';

			$h .= '</tr>';

		}

		$h .= '</table>';

		return new \Panel(
			id: 'panel-grid-product',
			title: s("Personnaliser la grille tarifaire"),
			subTitle: ProductUi::getPanelHeader($eProduct),
			dialogOpen: $form->openAjax('/selling/product:doUpdateGrid', ['class' => 'panel-dialog container']),
			dialogClose: $form->close(),
			body: $h,
			footer: $form->submit(s("Enregistrer"), ['class' => 'btn btn-primary btn-lg']),
			close: 'reload'
		);

	}

	public static function p(string $property): \PropertyDescriber {

		$d = Grid::model()->describer($property);

		return $d;

	}

}
?>
