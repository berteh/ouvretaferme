<?php
(new shop\ShopPage())
	->getCreateElement(function($data) {

		$data->eFarm = \farm\FarmLib::getById(INPUT('farm'));

		return new \shop\Shop([
			'farm' => $data->eFarm
		]);

	})
	->create(function($data) {

		$data->eFarm['selling'] = \selling\ConfigurationLib::getByFarm($data->eFarm);

		throw new ViewAction($data);

	})
	->doCreate(function($data) {
		throw new ReloadAction('shop', 'Shop::created');
	})
	->read('website', function($data) {

		$data->eFarm = \farm\FarmLib::getById(INPUT('farm'));

		$data->eWebsite = \website\WebsiteLib::getByFarm($data->eFarm);
		$data->eDate = \shop\DateLib::getMostRelevantByShop($data->e, one: TRUE);

		throw new \ViewAction($data);

	}, validate: ['canWrite'])
	->read('emails', function($data) {

		$data->emails = \shop\ShopLib::getEmails($data->e);

		throw new \ViewAction($data);

	}, validate: ['canWrite'])
	->doUpdateProperties('doUpdateStatus', ['status'], function($data) {
		throw new ReloadAction('shop', $data->e['status'] === \shop\Shop::OPEN ? 'Shop::opened' : 'Shop::closed');
	})
	->doUpdateProperties('doUpdatePayment', ['hasPayment'], function($data) {
		throw new ReloadAction('shop', $data->e['hasPayment'] === \shop\Shop::OPEN ? 'Shop::paymentOn' : 'Shop::paymentOff');
	})
	->doDelete(function() {
		throw new ReloadAction('shop', 'Shop::deleted');
	});
?>
