<?php
namespace util;

class BatchUi {

	public static function one(string $menu) {

		$form = new \util\FormUi();

		$h = '<div id="batch-one" class="batch-one hide">';

			$h .= $form->open('batch-one-form');

				$h .= '<div class="batch-ids hide"></div>';
				$h .= '<div class="batch-one-menu">';
					$h .= $menu;
				$h .= '</div>';

			$h .= $form->close();

		$h .= '</div>';

		return $h;

	}

	public static function group(string $hide, string $menu, string $danger = NULL) {

		\Asset::css('util', 'batch.css');

		$form = new \util\FormUi();

		$h = '<div id="batch-group" class="hide">';

			$h .= $form->open('batch-group-form');

			$h .= '<div class="batch-ids hide"></div>';

			$h .= '<div class="batch-title">';
				$h .= '<h4>'.s("Pour la sélection").' (<span id="batch-menu-count"></span>)</h4>';
				$h .= '<a onclick="'.$hide.'" class="btn btn-transparent">'.s("Annuler").'</a>';
			$h .= '</div>';

			$h .= '<div class="batch-menu">';
				$h .= '<div class="batch-menu-main">';
					$h .= $menu;
				$h .= '</div>';
				if($danger !== NULL) {
					$h .= '<div class="batch-menu-danger">';
						$h .= $danger;
					$h .= '</div>';
				}
			$h .= '</div>';

			$h .= $form->close();

		$h .= '</div>';

		return $h;

	}

}
?>
