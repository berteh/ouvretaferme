<?php
new AdaptativeView('index', function($data, CompanyTemplate $t) {

	$t->title = s("Les bilans de {farm}", ['farm' => $data->eFarm['name']]);
	$t->tab = 'overview';
	$t->subNav = new \company\CompanyUi()->getOverviewSubNav($data->eFarm);
	$t->canonical = \company\CompanyUi::urlOverview($data->eFarm).'/accounting';

	$t->mainTitle = new overview\OverviewUi()->getTitle($data->eFarm, $data->eFinancialYear);

	$t->mainYear = new \account\FinancialYearUi()->getFinancialYearTabs(
		function(\account\FinancialYear $eFinancialYear) use ($data) {
			return \company\CompanyUi::urlOverview($data->eFarm).'/balance?financialYear='.$eFinancialYear['id'];
		},
		$data->cFinancialYear,
		$data->eFinancialYear,
	);

	$t->package('main')->updateNavOverview($t->canonical, 'balance');

	if(empty($data->balanceOpening) and empty($data->balanceSummarized) and empty($data->balanceDetailed)) {

		echo '<div class="util-info">';
			echo s("Il n'y a pas d'information à afficher pour le moment.");
		echo '</div>';

	} else {

		echo '<div class="tabs-h" id="overview-balance" onrender="'.encode('Lime.Tab.restore(this, "balance-summarized")').'">';

			echo '<div class="tabs-item">';
				echo '<a class="tab-item selected" data-tab="balance-opening" onclick="Lime.Tab.select(this)">'.s("Bilan d'ouverture").'</a>';
				echo '<a class="tab-item" data-tab="balance-summarized" onclick="Lime.Tab.select(this)">'.s("Bilan comptable").'</a>';
				echo '<a class="tab-item" data-tab="balance-detailed" onclick="Lime.Tab.select(this)">'.s("Bilan comptable détaillé").'</a>';
			echo '</div>';

			echo '<div class="tab-panel" data-tab="balance-opening">';
				if(empty($data->balanceOpening) === FALSE) {
					echo new \overview\BalanceUi()->displayPdfLink($data->eFarm, $data->eFinancialYear, 'opening');
					echo new \overview\BalanceUi()->displaySummarizedBalance($data->balanceOpening);
				}
			echo '</div>';

			echo '<div class="tab-panel" data-tab="balance-summarized">';
				if(empty($data->balanceSummarized) === FALSE) {
					echo new \overview\BalanceUi()->displayPdfLink($data->eFarm, $data->eFinancialYear, 'summary');
					echo new \overview\BalanceUi()->displaySummarizedBalance($data->balanceSummarized);
				}
			echo '</div>';

			echo '<div class="tab-panel" data-tab="balance-detailed">';
				echo new \overview\BalanceUi()->displayDetailedBalance($data->balanceDetailed);
			echo '</div>';

		echo '</div>';

	}

});

?>
