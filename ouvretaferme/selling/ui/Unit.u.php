<?php
namespace selling;

class UnitUi {

	public static function getValue(?float $value, string|\selling\Unit $unit, bool $short = FALSE, bool $noWrap = TRUE, ?\Closure $callback = NULL): string {

		if($value === NULL) {
			$value = '?';
		} else {
			$value = round($value, 2);
		}

		$callback ??= fn($value, $unit) => $value.' '.$unit;

		if(is_string($unit)) {

			$text = match($unit) {
				'kg' => $callback($value, s('kg')),
				'bunch' => $short ? $callback($value, s("bte")) : p("{value} botte", "{value} bottes", $value, ['value' => $value]),
				'unit' => $short ? $callback($value, s("p.")) : ($value < 2 ? $callback($value, s("pièce")) : $callback($value, s("pièces"))),
				default => $unit
			};

		} else {

			if($unit->empty()) {
				$text = $value;
			} else {

				$text = $value.' ';
				$text .= $short ? $unit['short'] : (\L::getType(\L::getLang(), $value) === 0 ? $unit['singular'] : $unit['plural']);

			}

		}

		return $noWrap ? str_replace(' ', ' ', $text) : $text;

	}

	public static function getBy(string|\selling\Unit $unit, bool $short = FALSE): string {

		if(
			is_string($unit) or
			$unit->notEmpty()
		) {

			$text = ' / ';
			$text .= self::getSingular($unit, $short, TRUE);

		} else {
			$text = '';
		}

		return $text;

	}

	public static function getSingular(string|\selling\Unit $unit, bool $short = FALSE, bool $by = FALSE, bool $noWrap = TRUE): string {

		if(is_string($unit)) {

			$text = match($unit) {
				'kg' => s("kg"),
				'bunch' => $short ? s("bte") : s("botte"),
				'unit' => $short ? s("p.") : s("pièce"),
				default => $unit
			};

		} else {

			if($unit->empty()) {
				return '';
			} else {

				$text = $short ? $unit['short'] : $unit['singular'];

				if($unit['by'] and $by) {
					$text = substr($text, 2);
				}

			}

		}

		return $noWrap ? str_replace(' ', ' ', $text) : $text;

	}

	public static function getList(): array {

		return [
			'kg' => self::getSingular('kg'),
			'gram' => self::getSingular('gram'),
			'gram-100' => self::getSingular('gram-100'),
			'gram-250' => self::getSingular('gram-250'),
			'gram-500' => self::getSingular('gram-500'),
			'box' => self::getSingular('box'),
			'bunch' => self::getSingular('bunch'),
			'unit' => self::getSingular('unit'),
			'plant' => self::getSingular('plant'),
		];

	}

	public static function getBasicList(): array {

		return [
			'kg' => self::getSingular('kg'),
			'bunch' => self::getSingular('bunch'),
			'unit' => self::getSingular('unit'),
		];

	}

	public function getManageTitle(\farm\Farm $eFarm): string {

		$h = '<div class="util-action">';
			$h .= '<h1>';
				$h .= '<a href="'.\farm\FarmUi::urlSettings($eFarm).'"  class="h-back">'.\Asset::icon('arrow-left').'</a>';
				$h .= s("Unités de vente");
			$h .= '</h1>';
			$h .= '<div>';
				$h .= '<a href="/selling/unit:create?farm='.$eFarm['id'].'" class="btn btn-primary">'.\Asset::icon('plus-circle').' '.s("Nouvelle unité").'</a>';
			$h .= '</div>';
		$h .= '</div>';

		return $h;

	}

	public function getManage(\Collection $cUnit): string {

		$h = '';

		if($cUnit->contains(fn($eUnit) => $eUnit['farm']->notEmpty()) === FALSE) {

			$h .= '<div class="util-block-help"">';
				$h .= s("Des unités de vente par défaut sont déjà fournies avec {siteName} et ne peuvent pas être modifiées. Cependant, vous avez la possibilité de créer de nouvelles unités adaptées à votre contexte de production.");
			$h .= '</div>';

		}

		$h .= '<div class="util-overflow-sm">';

			$h .= '<table class="tr-even">';
				$h .= '<thead>';
					$h .= '<tr>';
						$h .= '<th></th>';
						$h .= '<th>'.s("Singulier").'</th>';
						$h .= '<th>'.s("Pluriel").'</th>';
						$h .= '<th>'.s("Version courte").'</th>';
						$h .= '<th></th>';
					$h .= '</tr>';
				$h .= '</thead>';

				$h .= '<tbody>';

				foreach($cUnit as $eUnit) {

					$h .= '<tr class="'.($eUnit['farm']->empty() ? 'color-muted' : '').'">';
						$h .= '<td>';

							if($eUnit['farm']->empty()) {
								$h .= s("Fournie par défaut");
							} else {
								$h .= s("Personnalisée");
							}

						$h .= '</td>';
						$h .= '<td>';
							$h .= $eUnit['farm']->empty() ? encode($eUnit['singular']) : $eUnit->quick('singular', encode($eUnit['singular']));
						$h .= '</td>';
						$h .= '<td>';
							$h .= $eUnit['farm']->empty() ? encode($eUnit['plural']) : $eUnit->quick('plural', encode($eUnit['plural']));
						$h .= '</td>';
						$h .= '<td>';
							$h .= $eUnit['farm']->empty() ? encode($eUnit['short']) : $eUnit->quick('short', encode($eUnit['short']));
						$h .= '</td>';
						$h .= '<td class="text-end">';

							if($eUnit['farm']->notEmpty()) {

								$h .= '<a href="/selling/unit:update?id='.$eUnit['id'].'" class="btn btn-outline-secondary">';
									$h .= \Asset::icon('gear-fill');
								$h .= '</a> ';

								$h .= '<a data-ajax="/selling/unit:doDelete" data-confirm="'.s("Supprimer ce matériel ?").'" post-id="'.$eUnit['id'].'" class="btn btn-outline-secondary">';
									$h .= \Asset::icon('trash-fill');
								$h .= '</a>';

							}

						$h .= '</td>';
					$h .= '</tr>';
				}
				$h .= '</tbody>';
			$h .= '</table>';

		$h .= '</div>';

		return $h;

	}

	public function create(Unit $eUnit): \Panel {

		$eUnit->expects(['farm']);

		$form = new \util\FormUi();

		$h = $form->openAjax('/selling/unit:doCreate');

			$h .= $form->asteriskInfo();

			$h .= $form->hidden('farm', $eUnit['farm']['id']);
			$h .= $form->dynamicGroups($eUnit, ['singular*', 'plural*', 'short*', 'type*']);
			$h .= $form->group(
				content: $form->submit(s("Ajouter"))
			);

		$h .= $form->close();

		return new \Panel(
			title: s("Ajouter une nouvelle unité"),
			body: $h
		);

	}

	public function update(Unit $eUnit): \Panel {

		$form = new \util\FormUi();

		$h = $form->openAjax('/selling/unit:doUpdate');

			$h .= $form->hidden('id', $eUnit['id']);
			$h .= $form->dynamicGroups($eUnit, ['singular', 'plural', 'short', 'type']);
			$h .= $form->group(
				content: $form->submit(s("Modifier"))
			);

		$h .= $form->close();

		return new \Panel(
			title: s("Modifier l'unité"),
			body: $h
		);

	}

	public static function p(string $property): \PropertyDescriber {

		$d = Unit::model()->describer($property, [
			'singular' => s("Nom de l'unité au singulier"),
			'plural' => s("Nom de l'unité au pluriel"),
			'short' => s("Version courte"),
			'type' => s("Utilisation avec"),
		]);

		switch($property) {

			case 'singular' :
				$d->placeholder = s("bouquet");
				break;

			case 'plural' :
				$d->placeholder = s("bouquets");
				break;

			case 'short' :
				$d->placeholder = s("b.");
				$d->after = \util\FormUi::info(s("Maximum {value} caractères", Unit::model()->getPropertyRange('short')[1]));
				break;

			case 'type' :
				$d->values = [
					Unit::DECIMAL => s("Valeurs décimales (0.00)"),
					Unit::INT => s("Valeurs entières (0)")
				];
				$d->default = Unit::INT;
				$d->after = \util\FormUi::info(s("Par exemple, l'unité <i>Pot</i> sera utilisée avec des valeurs entières uniquement, l'unité <i>Litre</i> sera sûrement utilisée avec des valeurs décimales."));
				break;

		}

		return $d;

	}


}
?>