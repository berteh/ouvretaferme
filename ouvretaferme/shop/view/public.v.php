<?php
new AdaptativeView('denied', function($data, ShopTemplate $t) {

	$t->title = s("Boutique en accès restreint");
	$t->header = '<h1>'.Asset::icon('lock-fill').' '.$t->title.'</h1>';

	if(\user\ConnectionLib::getOnline()->empty()) {

		echo '<div class="util-info">';
			echo s("Veuillez vous connecter pour accéder à cette boutique en ligne.");
		echo '</div>';

		echo new \user\UserUi()->logInBasic();

	} else {

		echo '<div class="util-info">';
			echo s("Vous n'avez pas accès à cette boutique en ligne, rapprochez-vous de la ferme pour corriger ce problème.");
		echo '</div>';

	}

});
new AdaptativeView('shop', function($data, ShopTemplate $t) {

	$t->title = encode($data->eShop['name']);
	$t->header = new \shop\ShopUi()->getHeader($data->eShop, $data->cDate, $data->eDateSelected);

	if($data->eShop['logo'] !== NULL) {
		$t->og['image'] = new \media\ShopLogoUi()->getUrlByElement($data->eShop);
	}

	Asset::js('shop', 'basket.js');

	if($data->eShop['status'] === \shop\Shop::CLOSED) {

		if($data->eShop->canWrite()) {

			echo '<div class="util-warning">';
				echo s("Cette boutique est actuellement fermée. Vos clients ne pourront pas consulter cette page tant que vous ne l'aurez pas ouverte !");
			echo '</div>';

		} else {

			echo '<div class="util-info">';
				echo s("La boutique est actuellement fermée.");
			echo '</div>';

			return;

		}

	}

	if($data->eDateSelected->notEmpty()) {

		echo '<h3>';
			echo \shop\DateUi::name($data->eDateSelected);
		echo '</h3>';

		if($data->eDateSelected['description'] !== NULL) {
			echo '<div class="util-block">';
				echo new \editor\EditorUi()->value($data->eDateSelected['description']);
			echo '</div>';
		}

		$details = [];

		if($data->eDateSelected['isOrderable']) {

			if(
				$data->eSaleExisting->canBasket($data->eShop) === FALSE and
				$data->isModifying === FALSE
			) {

				echo '<div class="util-block bg-success color-white">';
					echo '<p>';
						echo s("Merci, votre commande pour le {value} est enregistrée !", \util\DateUi::textual($data->eDateSelected['deliveryDate'], \util\DateUi::DATE_HOUR_MINUTE));
						if($data->eSaleExisting->acceptCustomerCancel()) {
							echo '<br/>'.s("Cette commande est modifiable et annulable jusqu'au {value}.", \util\DateUi::textual($data->eDateSelected['orderEndAt'], \util\DateUi::DATE_HOUR_MINUTE));
						}
					echo '</p>';
					echo '<a href="'.\shop\ShopUi::dateUrl($data->eShop, $data->eDateSelected, 'confirmation').'" class="btn btn-transparent">'.s("Consulter ma commande").'</a>';
				echo '</div>';

			}

			$orderPeriod = new \shop\DateUi()->getOrderPeriod($data->eDateSelected);
			$orderLimits = new \shop\DateUi()->getOrderLimits($data->eShop, $data->eDateSelected['ccPoint']);

			if($orderPeriod) {
				$details[] = Asset::icon('clock').'  '.$orderPeriod;
			}

			if($orderLimits) {
				$details[] = Asset::icon('cart').'  '.$orderLimits;
			}

		} else if(
			$data->eDateSelected['isDeliverable'] and
			$data->eSaleExisting->notEmpty()
		) {

			$details[] = Asset::icon('lock-fill').'  '.s("La vente est maintenant fermée, n'oubliez pas de venir chercher votre commande le {value} !", \util\DateUi::textual($data->eDateSelected['deliveryDate']));

		} else if($data->eDateSelected['isSoonOpen']) {

			$details[] = Asset::icon('clock').'  '.s("Les prises de commandes démarrent bientôt, revenez le {date} pour passer commande !", ['date' => \util\DateUi::textual($data->eDateSelected['orderStartAt'], \util\DateUi::DATE_HOUR_MINUTE)]);

		} else {

			$details[] = Asset::icon('lock-fill').'  '.s("Cette vente est désormais terminée !");

		}

		if($data->discount > 0) {
			$details[] = Asset::icon('check-lg').'  '.s("Les prix affichés incluent la remise commerciale de {value} % dont vous bénéficiez !", $data->discount);
		}

		if($details) {
			echo '<p>';
				echo implode('<br/>', $details);
			echo '</p>';
		}

		echo new \shop\ProductUi()->getList($data->eShop, $data->eDateSelected, $data->eSaleExisting, $data->cCategory, $data->isModifying);

	}

});

new AdaptativeView('/shop/public/{fqn}:conditions', function($data, PanelTemplate $t) {

	return new \shop\BasketUi()->getTerms($data->eShop);

});

new AdaptativeView('/shop/public/{fqn}/{date}/panier', function($data, ShopTemplate $t) {

	$uiBasket = new \shop\BasketUi();

	$t->title = encode($data->eShop['name']);
	$t->header = $uiBasket->getHeader($data->eShop, $data->eDate);

	echo $uiBasket->getSteps($data->eShop, $data->eDate, \shop\BasketUi::STEP_SUMMARY);

	echo $uiBasket->getAccount($data->eUserOnline);

	echo '<div id="shop-basket-summary" onrender="BasketManage.loadSummary('.$data->eDate['id'].', '.($data->eSaleExisting->empty() ? 'null' : $data->eSaleExisting['id']).', '.($data->isModifying ? 'true' : 'false').');"></div>';

	if($data->eUserOnline['phone'] === NULL) {
		echo '<div id="shop-basket-phone">';
			echo new \shop\BasketUi()->getPhoneForm($data->eShop, $data->eDate, $data->eUserOnline);
		echo '</div>';
	}

	echo '<div id="shop-basket-delivery" class="'.($data->eUserOnline['phone'] === NULL ? 'hide' : '').' mb-2">';
		if($data->hasPoint) {
			echo $uiBasket->getDeliveryForm($data->eShop, $data->eDate, $data->eDate['ccPoint'], $data->eUserOnline, $data->ePointSelected);
		}
		echo $uiBasket->getSubmitBasket($data->eShop, $data->eDate, $data->eUserOnline, $data->hasPoint, $data->ePointSelected);
	echo '</div>';


});

new JsonView('/shop/public/{fqn}/{date}/:getBasket', function($data, AjaxTemplate $t) {

	$t->push('basketSummary', new \shop\BasketUi()->getSummary($data->eShop, $data->eDate, $data->eSaleExisting, $data->basket, $data->isModifying));
	$t->push('basketPrice', $data->price);

});

new AdaptativeView('/shop/public/{fqn}/{date}/livraison', function($data, ShopTemplate $t) {

	$uiBasket = new \shop\BasketUi();

	$t->title = encode($data->eShop['name']);
	$t->header = $uiBasket->getHeader($data->eShop, $data->eDate);

	echo $uiBasket->getSteps($data->eShop, $data->eDate, \shop\BasketUi::STEP_DELIVERY);

});

new AdaptativeView('authenticate', function($data, ShopTemplate $t) {

	$uiBasket = new \shop\BasketUi();

	$t->title = encode($data->eShop['name']);
	$t->header = $uiBasket->getHeader($data->eShop, $data->eDate);

	echo $uiBasket->getSteps($data->eShop, $data->eDate, \shop\BasketUi::STEP_SUMMARY);
	echo $uiBasket->getAuthenticateForm($data->eUserOnline, $data->eRole);

});

new AdaptativeView('/shop/public/{fqn}/{date}/paiement', function($data, ShopTemplate $t) {

	$uiBasket = new \shop\BasketUi();

	$t->title = encode($data->eShop['name']);
	$t->header = $uiBasket->getHeader($data->eShop, $data->eDate);

	echo $uiBasket->getSteps($data->eShop, $data->eDate, \shop\BasketUi::STEP_PAYMENT);
	echo $uiBasket->getOrder($data->eDate, $data->eSaleExisting);
	echo $uiBasket->getPayment($data->eShop, $data->eDate, $data->eCustomer, $data->eSaleExisting, $data->eStripeFarm);

});

new AdaptativeView('/shop/public/{fqn}/{date}/confirmation', function($data, ShopTemplate $t) {

	$uiBasket = new \shop\BasketUi();

	$t->title = encode($data->eShop['name']);
	$t->header = $uiBasket->getHeader($data->eShop, $data->eDate);

	echo $uiBasket->getPaymentStatus($data->eShop, $data->eDate, $data->eSaleExisting);
	echo $uiBasket->getConfirmation($data->eShop, $data->eDate, $data->eSaleExisting);

});

new AdaptativeView('/shop/public/{fqn}/{date}/:doCreatePayment', function($data, AjaxTemplate $t) {

	if(
		$data->embed !== NULL and
		$data->payment === \selling\Sale::ONLINE_CARD
	) {
		$t->js()->parentLocation($data->redirect);
	} else {
		$t->redirect($data->redirect);
	}


});

new AdaptativeView('/shop/public/{fqn}/{date}/:doCreateSale', function($data, AjaxTemplate $t) {

	if($data->created) {
		$t->js()->eval('BasketManage.deleteBasket('.$data->eSaleExisting['shopDate']['id'].')');
	}

	$t->redirect(\shop\ShopUi::dateUrl($data->eShop, $data->eDate, 'paiement'));

});

new AdaptativeView('/shop/public/{fqn}/{date}/:doCancelSale', function($data, AjaxTemplate $t) {

	$t->js()->eval('BasketManage.deleteBasket('.$data->eSaleExisting['shopDate']['id'].')');
	$t->redirect(\shop\ShopUi::dateUrl($data->eShop, $data->eDate, 'paiement'));

});

new AdaptativeView('/shop/public/{fqn}/{date}/:doUpdatePhone', function($data, AjaxTemplate $t) {

	if($data->e['phone'] !== NULL) {

		$t->js()->success('shop', 'Sale::phone');
		$t->qs('#shop-basket-phone')->remove();
		$t->qs('#shop-basket-delivery')->removeClass('hide');
		$t->qs('#shop-basket-address-phone')->innerHtml(encode($data->e['phone']));

	}

});

new AdaptativeView('/shop/public/{fqn}/{date}/:doUpdateAddress', function($data, AjaxTemplate $t) {

	// L'adresse a bien été renseignée
	if($data->e->hasAddress()) {

		$t->js()->success('shop', 'Sale::address');
		$t->qs('#shop-basket-address')->remove();
		$t->qs('#shop-basket-submit')->removeClass('hide');
		$t->qs('#shop-basket-address-wrapper')->innerHtml(new \shop\BasketUi()->getAddress($data->eUserOnline));

	}

});
?>
