<?php
new AdaptativeView('update', function($data, FarmTemplate $t) {

	$t->title = s("Commercialisation de {value}", $data->e['name']);
	$t->tab = 'settings';
	$t->subNav = (new \farm\FarmUi())->getSettingsSubNav($data->e);

	echo '<h1>'.s("Paramétrer la commercialisation").'</h1>';
	echo '<br/>';
	echo (new \selling\ConfigurationUi())->update($data->e, $data->cCustomize, $data->eSaleExample);

});
?>
