<?php
namespace selling;

class StockUi {

	public function __construct() {

		\Asset::css('selling', 'stock.css');

	}

	public function getList(\Collection $cProduct, \Search $search) {

		$h = '';

		$h .= '<div class="stock-item-wrapper stick-xs">';

		$h .= '<table class="stock-item-table tr-bordered tr-even">';

			$h .= '<thead>';

				$h .= '<tr>';

					$h .= '<th class="stock-item-vignette"></th>';
					$h .= '<th>'.$search->linkSort('name', s("Produit")).'</th>';
					$h .= '<th></th>';
					$h .= '<th class="text-center" colspan="2">'.s("Stock").'</th>';
					$h .= '<th></th>';
					$h .= '<th>'.$search->linkSort('stockUpdatedAt', s("Mis à jour"), SORT_DESC).'</th>';
					$h .= '<th></th>';
					$h .= '<th></th>';
				$h .= '</tr>';

			$h .= '</thead>';

			$h .= '<tbody>';

			foreach($cProduct as $eProduct) {

				$h .= '<tr>';
				
					$h .= '<td class="stock-item-vignette td-min-content">';
						$h .= (new \media\ProductVignetteUi())->getCamera($eProduct, size: '4rem');
					$h .= '</td>';

					$h .= '<td class="stock-item-name">';
						$h .= ProductUi::getInfos($eProduct);
					$h .= '</td>';

					$h .= '<td class="td-min-content">';
						$h .= '<a href="/selling/stock:decrement?id='.$eProduct['id'].'" class="stock-item-button">-</a>';
					$h .= '</td>';

					$h .= '<td class="td-min-content stock-item-value">';
						$h .= $eProduct['stock'];
					$h .= '</td>';

					$h .= '<td class="td-min-content stock-item-unit">';
						$h .= \main\UnitUi::getSingular($eProduct['unit']);
					$h .= '</td>';

					$h .= '<td class="td-min-content">';
						$h .= '<a href="/selling/stock:increment?id='.$eProduct['id'].'" class="stock-item-button">+</a>';
					$h .= '</td>';

					$h .= '<td class="stock-item-stock-updated">';

						$date = match(substr($eProduct['stockUpdatedAt'], 0, 10)) {
							currentDate() => s("Aujourd'hui"),
							date('Y-m-d', strtotime('-1 DAY')) => s("Hier"),
							default => \util\DateUi::numeric($eProduct['stockUpdatedAt'], \util\DateUi::DATE)
						};

						$h .= '<a href="/selling/stock:history?id='.$eProduct['id'].'" class="color-text">';

						$h .= s("{date} à {time}", ['date' => $date, 'time' => \util\DateUi::numeric($eProduct['stockUpdatedAt'], \util\DateUi::TIME_HOUR_MINUTE)]);

						if($eProduct['stockDelta'] !== 0.0) {
							$h .= ', <b>'.($eProduct['stockDelta'] > 0 ? '+' : '').$eProduct['stockDelta'].'</b>';
						}

						$h .= '</a>';

						if($eProduct['stockExpired']) {
							$h .= '<div class="color-warning" style="font-size: 0.9rem">'.\Asset::icon('exclamation-triangle-fill').' '.s("Il y a plus d'une semaine").'</div>';
						}

					$h .= '</td>';

					$h .= '<td>';
						$h .= 'mouvements à intégrer ?<br/>';
					$h .= '</td>';

					$h .= '<td class="stock-item-actions">';

						if($eProduct->canWrite()) {

							$h .= '<a data-dropdown="bottom-end" class="dropdown-toggle btn btn-outline-secondary">'.\Asset::icon('gear-fill').'</a>';
							$h .= '<div class="dropdown-list">';
								$h .= '<div class="dropdown-title">'.encode($eProduct->getName()).'</div>';
								$h .= '<a href="/selling/stock:update?id='.$eProduct['id'].'" class="dropdown-item">'.s("Corriger le stock").'</a>';
								$h .= '<a href="/selling/stock:history?id='.$eProduct['id'].'" class="dropdown-item">'.s("Voir l'historique du stock").'</a>';
								$h .= '<div class="dropdown-divider"></div>';
								$h .= '<a data-ajax="selling/product:doDisableStock" post-id="'.$eProduct['id'].'" class="dropdown-item">'.\Asset::icon('box').'  '.s("Désactiver le suivi du stock").'</a>';
							$h .= '</div>';

						}

					$h .= '</td>';

				$h .= '</tr>';

			}

			$h .= '</tbody>';

		$h .= '</table>';

		$h .= '</div>';

		return $h;

	}

	public function getHistory(Product $eProduct, \Collection $cStock): \Panel {

		$h = '';

		$h .= '<div class="stock-item-wrapper stick-xs">';

		$h .= '<table class="stock-item-table tr-bordered tr-even">';

			$h .= '<thead>';

				$h .= '<tr>';

					$h .= '<th class="stock-item-vignette"></th>';
					$h .= '<th>'.$search->linkSort('name', s("Produit")).'</th>';
					$h .= '<th></th>';
					$h .= '<th class="text-center" colspan="2">'.s("Stock").'</th>';
					$h .= '<th></th>';
					$h .= '<th>'.$search->linkSort('stockUpdatedAt', s("Mis à jour"), SORT_DESC).'</th>';
					$h .= '<th></th>';
					$h .= '<th></th>';
				$h .= '</tr>';

			$h .= '</thead>';

			$h .= '<tbody>';

			foreach($cStock as $eStock) {

				$h .= '<tr>';
				
					$h .= '<td class="stock-item-vignette td-min-content">';
						$h .= (new \media\StockVignetteUi())->getCamera($eStock, size: '4rem');
					$h .= '</td>';

					$h .= '<td class="stock-item-name">';
						$h .= StockUi::getInfos($eStock);
					$h .= '</td>';

					$h .= '<td class="td-min-content">';
						$h .= '<a href="/selling/stock:decrement?id='.$eStock['id'].'" class="stock-item-button">-</a>';
					$h .= '</td>';

					$h .= '<td class="td-min-content stock-item-value">';
						$h .= $eStock['stock'];
					$h .= '</td>';

					$h .= '<td class="td-min-content stock-item-unit">';
						$h .= \main\UnitUi::getSingular($eStock['unit']);
					$h .= '</td>';

					$h .= '<td class="td-min-content">';
						$h .= '<a href="/selling/stock:increment?id='.$eStock['id'].'" class="stock-item-button">+</a>';
					$h .= '</td>';

					$h .= '<td class="stock-item-stock-updated">';

						$date = match(substr($eStock['stockUpdatedAt'], 0, 10)) {
							currentDate() => s("Aujourd'hui"),
							date('Y-m-d', strtotime('-1 DAY')) => s("Hier"),
							default => \util\DateUi::numeric($eStock['stockUpdatedAt'], \util\DateUi::DATE)
						};

						$h .= '<a href="/selling/stock:history?id='.$eStock['id'].'" class="color-text">';

						$h .= s("{date} à {time}", ['date' => $date, 'time' => \util\DateUi::numeric($eStock['stockUpdatedAt'], \util\DateUi::TIME_HOUR_MINUTE)]);

						if($eStock['stockDelta'] !== 0.0) {
							$h .= ', <b>'.($eStock['stockDelta'] > 0 ? '+' : '').$eStock['stockDelta'].'</b>';
						}

						$h .= '</a>';

						if($eStock['stockExpired']) {
							$h .= '<div class="color-warning" style="font-size: 0.9rem">'.\Asset::icon('exclamation-triangle-fill').' '.s("Il y a plus d'une semaine").'</div>';
						}

					$h .= '</td>';

					$h .= '<td>';
						$h .= 'mouvements à intégrer ?<br/>';
					$h .= '</td>';

					$h .= '<td class="stock-item-actions">';

						if($eStock->canWrite()) {

							$h .= '<a data-dropdown="bottom-end" class="dropdown-toggle btn btn-outline-secondary">'.\Asset::icon('gear-fill').'</a>';
							$h .= '<div class="dropdown-list">';
								$h .= '<div class="dropdown-title">'.encode($eStock->getName()).'</div>';
								$h .= '<a href="/selling/stock:update?id='.$eStock['id'].'" class="dropdown-item">'.s("Corriger le stock").'</a>';
								$h .= '<a href="/selling/stock:history?id='.$eStock['id'].'" class="dropdown-item">'.s("Voir l'historique du stock").'</a>';
								$h .= '<div class="dropdown-divider"></div>';
								$h .= '<a data-ajax="selling/Stock:doDisableStock" post-id="'.$eStock['id'].'" class="dropdown-item">'.\Asset::icon('box').'  '.s("Désactiver le suivi du stock").'</a>';
							$h .= '</div>';

						}

					$h .= '</td>';

				$h .= '</tr>';

			}

			$h .= '</tbody>';

		$h .= '</table>';

		$h .= '</div>';

		return new \Panel(
			title: s("XXX"),
			body: $h,
			subTitle: (new ProductUi())->getPanelHeader($eProduct)
		);
	}

	public function update(Product $eProduct): \Panel {

		return self::crement(
			$eProduct,
			NULL,
			s("Nouvelle valeur"),
			s("Modifier le stock")
		);

	}

	public function increment(Product $eProduct): \Panel {

		return self::crement(
			$eProduct,
			'+',
			s("Ajouter au stock"),
			s("Augmenter le stock")
		);

	}

	public function decrement(Product $eProduct): \Panel {

		return self::crement(
			$eProduct,
			'-',
			s("Retirer du stock"),
			s("Diminuer le stock")
		);

	}

	public function crement(Product $eProduct, ?string $sign, string $label, string $header): \Panel {

		$form = new \util\FormUi();

		$h = $form->openAjax('/selling/stock:doUpdate');

			$h .= $form->hidden('id', $eProduct['id']);

			if($sign !== NULL) {
				$h .= $form->hidden('sign', $sign);
			}

			$h .= $form->group(
				$label,
				$form->inputGroup(
					($sign ? '<div class="input-group-addon">'.$sign.'</div>' : '').
					$form->dynamicField(new Stock(), 'newValue', function(\PropertyDescriber $d) use ($eProduct, $sign) {
						$d->attributes['onrender'] = 'this.focus();';
						if($sign === NULL) {
							$d->placeholder = $eProduct['stock'];
						}
					}).
					'<div class="input-group-addon">'.\main\UnitUi::getNeutral($eProduct['unit']).'</div>'
				)
			);

			$h .= $form->dynamicGroup(new Stock(), 'comment');

			$h .= $form->group(
				content: $form->submit(s("Valider"))
			);

		$h .= $form->close();

		return new \Panel(
			title: $header,
			body: $h,
			subTitle: (new ProductUi())->getPanelHeader($eProduct)
		);

	}

	public static function p(string $property): \PropertyDescriber {

		$d = Stock::model()->describer($property, [
			'comment' => s("Commentaire")
		]);

		switch($property) {

			case 'comment' :
				$d->placeholder = s("Tapez ici un commentaire facultatif sur l'évolution du stock");
				break;

		}

		return $d;

	}

}
?>
