<?php
namespace selling;

class CategoryUi {

	public static function text(\production\Flow|series\Task $e): string {

		if($e instanceof \production\Flow) {

			$e['variety'] = new \plant\Variety();

			$e->expects(['sequence']);

		} else {
			$e->expects(['series']);
		}

		$e->expects([
			'plant',
			'category' => ['name'],
			'variety',
			'description'
		]);

		$ePlant = $e['plant'];

		$eCategory = $e['category'];
		$eCategory->expects(['name']);

		\Asset::css('farm', 'category.css');

		$h = '<span class="category-text">';

		if($ePlant->empty()) {

			$h .= encode($eCategory['name']);

			if(
				($e instanceof series\Task and $e['series']->notEmpty()) or
				($e instanceof \production\Flow and $e['sequence']->notEmpty())
			) {
				$h .= ' <span class="category-name">'.s("PARTAGÉ").'</span>';
			}

		} else {

			$ePlant->expects(['name']);

			$plant = '<span class="category-name">'.encode($ePlant['name']).'</span>';

		}

		$h .= '</span>';

		return $h;

	}

	public function manage(\farm\Farm $eFarm, \Collection $cCategory): string {

		$h = '<div class="util-action">';

			$h .= '<h1>';
				$h .= '<a href="'.\farm\FarmUi::urlSellingProduct($eFarm).'" class="h-back">'.\Asset::icon('arrow-left').'</a>';
				$h .= s("Les catégories de produits");
			$h .= '</h1>';

			if($cCategory->notEmpty()) {

				$h .= '<div>';
					$h .= '<a href="/selling/category:create?farm='.$eFarm['id'].'" class="btn btn-primary">'.\Asset::icon('plus-circle').' '.s("Nouvelle catégorie").'</a>';
				$h .= '</div>';

			}

		$h .= '</div>';

		$h .= '<br/>';

		if($cCategory->empty()) {

			$h .= '<div class="util-block-help">';
				$h .= '<p>'.s("Vous pouvez créer des catégories de produits pour afficher vos produits de manière groupée sur votre page d'administration et sur les boutiques en ligne. À vous de définir les catégories les plus adaptées pour votre production !").'</p>';
				$h .= '<p>'.s("Par exemple, si vous cultivez des légumes et vendez des plants potagers, vous pouvez créer une catégorie <i>Légumes</i> et une catégorie <i>Plants potagers</i>.").'</p>';
				$h .= '<a href="/selling/category:create?farm='.$eFarm['id'].'" class="btn btn-secondary">'.s("Créer une première categorie").'</a>';
			$h .= '</div>';

		} else {

			$h .= '<table class="tr-bordered">';
				$h .= '<thead>';
					$h .= '<tr>';
						if($cCategory->count() >= 2 ) {
							$h .= '<th colspan="2">'.s("Position").'</th>';
						}
						$h .= '<th>'.self::p('name')->label.'</th>';
						$h .= '<th></th>';
					$h .= '</tr>';
				$h .= '</thead>';

				$h .= '<tbody>';

				foreach($cCategory as $eCategory) {

					$h .= '<tr>';

						if($cCategory->count() > 1) {

							$h .= '<td class="td-min-content">';
								$h .= '<b>'.$eCategory['position'].'.</b>';
							$h .= '</td>';
							$h .= '<td>';

								if($eCategory['position'] > 1) {
									$h .= '<a data-ajax="/selling/category:doIncrementPosition" post-id='.$eCategory['id'].'" post-increment="-1" class="btn btn-sm btn-secondary">'.\Asset::icon('arrow-up').'</a> ';
								} else {
									$h .= '<a class="btn btn-sm btn-disabled">'.\Asset::icon('arrow-up').'</a> ';
								}

								if($eCategory['position'] !== $cCategory->count()) {
									$h .= '<a data-ajax="/selling/category:doIncrementPosition" post-id='.$eCategory['id'].'" post-increment="1" class="btn btn-sm btn-secondary">'.\Asset::icon('arrow-down').'</a> ';
								} else {
									$h .= '<a class="btn btn-sm btn-disabled">'.\Asset::icon('arrow-down').'</a> ';
								}
							$h .= '</td>';

						}
						$h .= '<td>';
							$h .= encode($eCategory['name']);
						$h .= '</td>';
						$h .= '<td class="text-end" style="white-space: nowrap">';

							$h .= '<a href="/selling/category:update?id='.$eCategory['id'].'" class="btn btn-outline-secondary">';
								$h .= \Asset::icon('gear-fill');
							$h .= '</a> ';

							$h .= '<a data-ajax="/selling/category:doDelete" data-confirm="'.s("En supprimant cette catégorie, les produits qui s'y trouvent n'auront plus de catégorie. Continuer ?").'" post-id="'.$eCategory['id'].'" class="btn btn-outline-secondary">';
								$h .= \Asset::icon('trash-fill');
							$h .= '</a>';

						$h .= '</td>';
					$h .= '</tr>';
				}
				$h .= '</tbody>';
			$h .= '</table>';

		}

		return $h;

	}

	public function create(\farm\Farm $eFarm): \Panel {

		$eCategory = new Category();

		$form = new \util\FormUi();

		$h = $form->openAjax('/selling/category:doCreate');

			$h .= $form->asteriskInfo();

			$h .= $form->hidden('farm', $eFarm['id']);
			$h .= $form->dynamicGroups($eCategory, ['name*']);
			$h .= $form->group(
				content: $form->submit(s("Ajouter"))
			);

		$h .= $form->close();

		return new \Panel(
			title: s("Ajouter une nouvelle catégorie de produits"),
			body: $h,
			close: 'reload'
		);

	}

	public function update(Category $eCategory): \Panel {

		$form = new \util\FormUi();

		$h = $form->openAjax('/selling/category:doUpdate');

			$h .= $form->hidden('id', $eCategory['id']);
			$h .= $form->dynamicGroups($eCategory, ['name']);
			$h .= $form->group(
				content: $form->submit(s("Modifier"))
			);

		$h .= $form->close();

		return new \Panel(
			title: s("Modifier une catégorie de produits"),
			body: $h,
			close: 'reload'
		);

	}

	public static function p(string $property): \PropertyDescriber {

		$d = Category::model()->describer($property, [
			'name' => s("Nom de la catégorie"),
			'fqn' => s("Nom qualifié")
		]);

		switch($property) {

		}

		return $d;

	}


}
?>
