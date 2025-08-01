<?php

new AdaptativeView('plant', function($data, FarmTemplate $t) {

	$t->nav = 'settings-production';

	$t->title = s("Les espèces de {value}", $data->eFarm['name']);
	$t->canonical = \plant\PlantUi::urlManage($data->eFarm);

	$h = '<div class="util-action">';
		$h .= '<h1>';
			$h .= '<a href="'.\farm\FarmUi::urlSettingsProduction($data->eFarm).'"  class="h-back">'.\Asset::icon('arrow-left').'</a>';
			$h .= s("Espèces");
		$h .= '</h1>';
		$h .=  '<div>';
			$h .= '<a '.attr('onclick', 'Lime.Search.toggle("#plant-search")').' class="btn btn-primary">'.\Asset::icon('search').'</a> ';
			if($data->eFarm->canManage()) {
				$h .= ' <a href="/plant/plant:create?farm='.$data->eFarm['id'].'" class="btn btn-primary">'.\Asset::icon('plus-circle').'<span class="hide-xs-down"> '.s("Nouvelle espèce").'</span></a>';
			}
		$h .=  '</div>';
	$h .=  '</div>';

	$t->mainTitle = $h;

	echo new \plant\PlantUi()->getSearch($data->eFarm, $data->search);
	echo new \plant\PlantUi()->getManage($data->eFarm, $data->plants, $data->cPlant, $data->search);


});

new AdaptativeView('/espece/{id@int}', function($data, PanelTemplate $t) {

	return new \plant\PlantUi()->display($data->e, $data->eFarm, $data->cItemYear, $data->cCrop, $data->cActionMain);

});

new AdaptativeView('analyzeSales', function($data, PanelTemplate $t) {
	return new \selling\AnalyzeUi()->getPlantSales($data->e, $data->switchComposition, $data->year, $data->cItemTurnover, $data->cItemYear, $data->cItemCustomer, $data->cItemType, $data->cItemMonth, $data->cItemMonthBefore, $data->cItemWeek, $data->cItemWeekBefore, $data->search);
});

new AdaptativeView('analyzeTime', function($data, PanelTemplate $t) {
	return new \series\AnalyzeUi()->getPlantTime($data->e, $data->year, $data->cPlantTimesheet, $data->cTimesheetByAction, $data->cTimesheetByUser, $data->cPlantMonth, $data->cPlantMonthBefore, $data->cPlantWeek, $data->cPlantWeekBefore);
});

new AdaptativeView('create', function($data, PanelTemplate $t) {
	return new \plant\PlantUi()->create($data->eFarm, $data->cFamily);
});

new JsonView('doCreate', function($data, AjaxTemplate $t) {

	if(Route::getRequestedOrigin() === 'panel') {
		$t->js()->moveHistory(-1);
	} else {
		$t->ajaxReloadLayer();
	}

	$t->js()->success('plant', 'Plant::created');

});

new AdaptativeView('update', function($data, PanelTemplate $t) {
	return new \plant\PlantUi()->update($data->e);
});

new JsonView('doUpdate', function($data, AjaxTemplate $t) {
	$t->js()->moveHistory(-1);
});

new JsonView('doUpdateStatus', function($data, AjaxTemplate $t) {
	$t->js()->success('plant', 'Plant::updated'.ucfirst($data->e['status']));
	$t->qs('#plant-switch-'.$data->e['id'])->toggleSwitch('post-status', [\plant\Plant::ACTIVE, \plant\Plant::INACTIVE]);
});

new JsonView('doDelete', function($data, AjaxTemplate $t) {

	$t->js()->success('plant', 'Plant::deleted');
	$t->ajaxReloadLayer();

});
?>
