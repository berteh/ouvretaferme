<?php
(new Page(function($data) {

		$data->eSale = \selling\SaleLib::getById(INPUT('sale'))->validate('acceptUpdateItems', 'canWrite');

	}))
	->get('select', function($data) {

		throw new ViewAction($data);

	})
	->get('create', function($data) {

		$data->eProduct = \selling\ProductLib::getById(GET('product'));

		if($data->eProduct->notEmpty()) {

			if(\selling\ItemLib::isCompatible($data->eSale, $data->eProduct) === FALSE) {
				throw new NotExpectedAction('Sale not compatible with Product');
			}

		}

		if($data->eProduct->notEmpty() and $data->eSale['customer']->notEmpty()) {
			$eGrid = \selling\GridLib::getOne($data->eSale['customer'], $data->eProduct);
		} else {
			$eGrid = new \selling\Grid();
		}

		$data->eItem = \selling\ItemLib::getNew($data->eSale, $data->eProduct, $eGrid);

		$data->eItem['cUnit'] = $data->eProduct->empty() ?
			\selling\UnitLib::getByFarm($data->eSale['farm']) :
			new Collection();

		throw new ViewAction($data);

	})
	->get('createCollection', function($data) {

		$data->eSale['cCategory'] = \selling\CategoryLib::getByFarm($data->eSale['farm'], index: 'id');
		$data->eSale['cProduct'] = \selling\ProductLib::getForSale($data->eSale['farm'], $data->eSale['type'], excludeComposition: $data->eSale->isComposition());
		\selling\ProductLib::applyItemsForSale($data->eSale['cProduct'], $data->eSale);

		throw new ViewAction($data);

	})
	->post('doCreateCollection', function($data) {

		$fw = new FailWatch();

		$data->cItem = \selling\ItemLib::build($data->eSale, $_POST, TRUE);

		$fw->validate(onKo: fn() => $fw->has('Item::createEmpty') ? NULL : \selling\Item::fail('createCollectionError'));

		\selling\ItemLib::createCollection($data->cItem);

		throw new ReloadAction('selling', 'Item::created');

	});

(new \selling\SalePage())
	->applyElement(function($data, \selling\Sale $eSale) {
		$eSale->validate('acceptUpdateItems');
	})
	->write('doUpdateMerchant', function($data) {

		$fw = new FailWatch();

		$cItemSale = \selling\ItemLib::checkNewItems($data->e, $_POST);

		$fw->validate();

		\selling\ItemLib::updateSaleCollection($data->e, $cItemSale);

		throw new ReloadAction();


	});


(new \selling\ItemPage())
	->quick(['packaging', 'number', 'unitPrice', 'vatRate', 'price', 'description'])
	->update()
	->doUpdate(function($data) {
		throw new ReloadAction('selling', 'Item::updated');
	})
	->doDelete(function($data) {

		if($data->e['sale']['marketParent']->empty()) {
			throw new ReloadLayerAction('selling', 'Item::deleted');
		} else {

			$data->e['sale'] = \selling\SaleLib::getById($data->e['sale'], \selling\Sale::getSelection() + [
				'createdBy' => ['firstName', 'lastName', 'vignette']
			]);

			\selling\Sale::model()
				->select('preparationStatus')
				->get($data->e['sale']['marketParent']);

			$data->cItemMarket = \selling\SaleLib::getItems($data->e['sale']['marketParent']);
			$data->cItemSale = \selling\SaleLib::getItems($data->e['sale'], index: 'product');

			throw new ViewAction($data);

		}

	}, onEmpty: fn($data) => throw new ReloadLayerAction());

new Page()
	->match(['get', 'post'],'summary', function($data) {

		$data->eFarm = \farm\FarmLib::getById(GET('farm'))->validate('canSelling');

		$data->date = INPUT('date');
		$data->type = INPUT('type', [\selling\Customer::PRO, \selling\Customer::PRIVATE], NULL);
		$data->cSale = \selling\SaleLib::getByDeliveredDay($data->eFarm, $data->date, $data->type);
		$data->ccItemProduct = \selling\ItemLib::getProductsBySales($data->cSale);
		$data->ccItemSale = \selling\ItemLib::getBySales($data->cSale);

		throw new ViewAction($data);

	});
?>
