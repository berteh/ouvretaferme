<?php
new AdaptativeView('index', function($data, FarmTemplate $t) {

	$t->nav = 'assets';
	$t->subNav = 'depreciation';

	$t->title = s("Les immobilisations de {farm}", ['farm' => encode($data->eFarm['name'])]);
	$t->canonical = \company\CompanyUi::urlAsset($data->eFarm).'/depreciation';

	$t->mainTitle = new \asset\DepreciationUi()->getTitle();
	$t->mainTitleClass = 'hide-lateral-down';

	$t->mainYear = new \account\FinancialYearUi()->getFinancialYearTabs(
		function(\account\FinancialYear $eFinancialYear) use ($data) {
			return \company\CompanyUi::urlAsset($data->eFarm).'/depreciation?financialYear='.$eFinancialYear['id'];
		},
		$data->cFinancialYear,
		$data->eFinancialYear,
	);

	if(empty($data->assetDepreciations) and empty($data->subventionDepreciations)) {

		echo '<div class="util-info">';
			echo s("Il n'y a pas d'information à afficher pour le moment.");
		echo '</div>';

	} else {

		if($data->eFinancialYear['status'] !== \account\FinancialYearElement::CLOSE) {

			echo '<div class="util-warning">';
				echo s("Vous visualisez actuellement les immobilisations d'un exercice comptable encore ouvert : il s'agit donc d'une projection à la fin de l'exercice dans le cas où les immobilisations ne changent pas dans le courant de l'exercice.");
			echo '</div>';

		}

		echo '<div class="tabs-h" id="asset-depreciation" onrender="'.encode('Lime.Tab.restore(this, "depreciation-asset")').'">';

			echo '<div class="tabs-item">';
			echo '<a class="tab-item selected" data-tab="depreciation-asset" onclick="Lime.Tab.select(this)">'.s("Immobilisations").'</a>';
			echo '<a class="tab-item" data-tab="depreciation-subvention" onclick="Lime.Tab.select(this)">'.s("Subventions").'</a>';
			echo '</div>';

			echo '<div class="tab-panel" data-tab="depreciation-asset">';
				echo \asset\DepreciationUi::getDepreciationTable($data->eFarm, $data->assetDepreciations);
			echo '</div>';

			echo '<div class="tab-panel" data-tab="depreciation-subvention">';
				echo \asset\DepreciationUi::getDepreciationTable($data->eFarm, $data->subventionDepreciations);
			echo '</div>';

		echo '</div>';

	}
});
