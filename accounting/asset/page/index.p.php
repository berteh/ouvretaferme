<?php
new \asset\AssetPage(function($data) {

	\user\ConnectionLib::checkLogged();

	if(get_exists('id') === FALSE and post_exists('id') === FALSE) {
		throw new NotExpectedAction('Asset Id is required.');
	}

	$data->eFarm->validate('canManage');

	$data->eAsset = \asset\AssetLib::getWithDepreciationsById(REQUEST('id'));

})
	->get('view', function($data) {

		throw new ViewAction($data);

	});

new \asset\AssetPage(function($data) {

	\user\ConnectionLib::checkLogged();

	if(get_exists('id') === FALSE and post_exists('id') === FALSE) {
		throw new NotExpectedAction('Asset Id is required.');
	}

	$data->eFarm->validate('canManage');

	$data->eAsset = \asset\AssetLib::getWithDepreciationsById(REQUEST('id'))->validate('canView');

})
	->get('dispose', function($data) {

		$data->eFinancialYear = \account\FinancialYearLib::getDynamicFinancialYear($data->eFarm, GET('financialYear', 'int'));
		$data->cFinancialYear = \account\FinancialYearLib::getAll();

		throw new ViewAction($data);

	})
	->post('doDispose', function($data) {

		\asset\AssetLib::dispose($data->eAsset, $_POST);

		throw new ReloadAction('asset', 'Asset::disposed');

	});
