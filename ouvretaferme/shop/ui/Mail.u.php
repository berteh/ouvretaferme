<?php
namespace shop;

class MailUi {

	public static function getNewFarmSale(string $type, \selling\Sale $eSale, \Collection $cItem): array {

		$title = match($type) {
			'confirmed' => s("Commande de {customer} reçue pour une livraison le {date}", ['customer' => $eSale['customer']->getName(), 'date' => \util\DateUi::numeric($eSale['shopDate']['deliveryDate'])]),
			'updated' => s("Commande de {customer} modifiée pour une livraison le {date}", ['customer' => $eSale['customer']->getName(), 'date' => \util\DateUi::numeric($eSale['shopDate']['deliveryDate'])]),
		};

		$variables = \mail\CustomizeUi::getShopVariables(\mail\Customize::SHOP_CONFIRMED_NONE, $eSale, $cItem);

		$template = match($type) {
			'confirmed' => s("Vous avez reçu une commande de @customer."),
			'updated' => s("Vous avez reçu une modification de commande de @customer.")
		};
		$template .= "\n\n";
		$template .= s("- Boutique : {shop}
- Date de livraison : @delivery
- Montant de la commande : @amount

Contenu de la commande :

@products

Bonne réception,
L'équipe {siteName}", ['shop' => encode($eSale['shop']['name'])]);
		$content = \mail\CustomizeUi::convertTemplate($template, $variables);

		return \mail\DesignUi::format($eSale['farm'], $title, $content);

	}

	public static function getCancelFarmSale(\selling\Sale $eSale): array {

		$arguments = [
			'shop' => encode($eSale['shop']['name']),
			'customer' => $eSale['customer']->getName(),
			'date' => \util\DateUi::numeric($eSale['shopDate']['deliveryDate'])
		];

		$title = s("Commande annulée de {customer} pour une livraison le {date}", $arguments);

		$content = s("Votre client {customer} a annulé une commande.

- Boutique : {shop}
- Date de livraison : {date}

Bonne réception,
L'équipe {siteName}", $arguments);

		return \mail\DesignUi::format($eSale['farm'], $title, $content);

	}

	public static function getSaleUpdated(\selling\Sale $eSale, \Collection $cItem, ?string $template = NULL): array {

		$eSale->expects([
			'shopPoint',
			'shop' => ['hasPayment', 'paymentOfflineHow', 'paymentTransferHow']
		]);

		if($eSale['shopPoint']->empty()) {
			return self::getSaleNone('updated', $eSale, $cItem, $template);
		}

		return match($eSale['shopPoint']['type']) {
			Point::HOME => self::getSaleHome('updated', $eSale, $cItem, $template),
			Point::PLACE => self::getSalePlace('updated', $eSale, $cItem, $template)
		};

	}

	public static function getSaleConfirmed(\selling\Sale $eSale, \Collection $cItem, ?string $template = NULL): array {

		$eSale->expects([
			'shopPoint',
			'shop' => ['hasPayment', 'paymentOfflineHow', 'paymentTransferHow']
		]);

		if($eSale['shopPoint']->empty()) {
			return self::getSaleNone('confirmed', $eSale, $cItem, $template);
		}

		return match($eSale['shopPoint']['type']) {
			Point::HOME => self::getSaleHome('confirmed', $eSale, $cItem, $template),
			Point::PLACE => self::getSalePlace('confirmed', $eSale, $cItem, $template)
		};

	}

	protected static function getSaleNone(string $type, \selling\Sale $eSale, \Collection $cItem, ?string $template = NULL): array {

		$template ??= \mail\CustomizeUi::getDefaultTemplate(\mail\Customize::SHOP_CONFIRMED_NONE);
		$variables = \mail\CustomizeUi::getShopVariables(\mail\Customize::SHOP_CONFIRMED_NONE, $eSale, $cItem);

		$title = match($type) {
			'confirmed' => s("Commande n°{id} validée pour le {date}", ['id' => $eSale['document'], 'date' => \util\DateUi::numeric($eSale['shopDate']['deliveryDate'])]),
			'updated' => s("Commande n°{id} modifiée pour le {date}", ['id' => $eSale['document'], 'date' => \util\DateUi::numeric($eSale['shopDate']['deliveryDate'])]),
		};
		$content = \mail\CustomizeUi::convertTemplate($template, $variables);

		return \mail\DesignUi::format($eSale['farm'], $title, $content);

	}

	protected static function getSaleHome(string $type, \selling\Sale $eSale, \Collection $cItem, ?string $template = NULL): array {

		$template ??= \mail\CustomizeUi::getDefaultTemplate(\mail\Customize::SHOP_CONFIRMED_HOME);
		$variables = \mail\CustomizeUi::getShopVariables(\mail\Customize::SHOP_CONFIRMED_HOME, $eSale, $cItem);

		$title = match($type) {
			'confirmed' => s("Commande n°{id} validée pour une livraison le {date}", ['id' => $eSale['document'], 'date' => \util\DateUi::numeric($eSale['shopDate']['deliveryDate'])]),
			'updated' => s("Commande n°{id} modifiée pour une livraison le {date}", ['id' => $eSale['document'], 'date' => \util\DateUi::numeric($eSale['shopDate']['deliveryDate'])]),
		};
		$content = \mail\CustomizeUi::convertTemplate($template, $variables);

		return \mail\DesignUi::format($eSale['farm'], $title, $content);

	}

	protected static function getSalePlace(string $type, \selling\Sale $eSale, \Collection $cItem, ?string $template = NULL): array {

		$template ??= \mail\CustomizeUi::getDefaultTemplate(\mail\Customize::SHOP_CONFIRMED_PLACE);
		$variables = \mail\CustomizeUi::getShopVariables(\mail\Customize::SHOP_CONFIRMED_PLACE, $eSale, $cItem);

		$title = match($type) {
			'confirmed' => s("Commande n°{id} validée pour un retrait le {date}", ['id' => $eSale['document'], 'date' => \util\DateUi::numeric($eSale['shopDate']['deliveryDate'])]),
			'updated' => s("Commande n°{id} modifiée pour un retrait le {date}", ['id' => $eSale['document'], 'date' => \util\DateUi::numeric($eSale['shopDate']['deliveryDate'])]),
		};
		$content = \mail\CustomizeUi::convertTemplate($template, $variables);

		return \mail\DesignUi::format($eSale['farm'], $title, $content);

	}

	public static function getSaleCanceled(\selling\Sale $eSale): array {

		switch($eSale['paymentMethod']) {

			case \selling\Sale::TRANSFER :
				$payment = s("Vous ne serez donc pas facturé du montant de cette commande.")."\n";
				break;

			case \selling\Sale::OFFLINE :
				$payment = '';
				break;

			default :
				$payment = '';
				break;

		}

		$title = s("Commande n°{id} annulée", ['id' => $eSale['document']]);

		$content = s("Bonjour,

Votre commande n°{id} d'un montant de {amount} a bien été annulée.
{payment}
À bientôt,
{farm}", [
			'id' => $eSale['document'],
			'farm' => encode($eSale['farm']['name']),
			'amount' => \util\TextUi::money($eSale['priceIncludingVat']),
			'payment' => $payment,
			]);


		return \mail\DesignUi::format($eSale['farm'], $title, $content);

	}

	public static function getCardSaleFailed(\selling\Sale $eSale, bool $test = FALSE): array {

		$title = s("Le paiement de la commande n°{id} a échoué", ['id' => $eSale['document']]);

		if($test) {
			$link = LIME_URL;
		} else {
			$link = \shop\ShopUi::paymentUrl($eSale['shop'], $eSale['shopDate']);
		}

		$get = fn($payment) => s("Bonjour,

Votre paiement par carte bancaire d'un montant de {amount} pour la commande n°{id} a échoué.
Votre commande n'a pas donc pas été validée et votre compte n'a pas été débité.

{payment}

Merci et à bientôt,
{farm}", [
			'id' => $eSale['document'],
			'farm' => $eSale['farm']['name'],
			'payment' => $payment,
			'amount' => \util\TextUi::money($eSale['priceIncludingVat']),
			'link' => $link
			]);

		$text = $get(s("Vous pouvez tenter de payer à nouveau cette commande en cliquant sur ce lien :")."\n".$link);

		$html = \mail\DesignUi::getBanner($eSale['farm']);
		$html .= nl2br($get(\mail\DesignUi::getButton($link, s("Retenter un paiement")))."\n");

		return [
			$title,
			$text,
			$html
		];
	}

	public static function getOrderEnd(Date $eDate, array $sales, \Collection $cItem): array {

		$hasVat = $eDate['farm']->getSelling('hasVat');

		$arguments = [
			'shop' => encode($eDate['shop']['name']),
			'date' => \util\DateUi::numeric($eDate['deliveryDate']),
		];

		if($sales['number'] === 0) {

			$text = s("Vous n'avez pas reçu de commande cette fois-ci.

Si vous avez besoin d'aide pour configurer ou déployer votre boutique, n'hésitez pas à vous rendre sur le salon de discussion Discord accessible depuis {siteName}.

Bonne réception,
{siteName}");

			return [
				s("Pas de commande pour le {date} sur {shop}", $arguments),
				$text,
				\mail\DesignUi::getBanner($eDate['farm']).nl2br($text)
			];

		}

		$price = \util\TextUi::money($eDate['type'] === Date::PRO ? $sales['priceExcludingVat'] : $sales['priceIncludingVat']);

		if($hasVat and $eDate['type'] === Date::PRO) {
			$price .= ' '.\selling\CustomerUi::getTaxes(Date::PRO);
		}

		$arguments['price'] = $price;

		$title = p("✅ {value} commande pour le {date} sur {shop}", "✅ {value} commandes pour le {date} sur {shop}", $sales['number'], $arguments);

		$items = '';

		foreach($cItem as $eItem) {


			$quantity = \selling\UnitUi::getValue($eItem['number'], $eItem['unit'], short: TRUE);

			$items .= '- '.s("{item} : {quantity}", ['item' => $eItem['name'], 'quantity' => $quantity])."\n";

		}

		$intro = p("Vous avez reçu {value} commande d'un montant total de {price} pour la livraison du {date} sur votre boutique {shop}", "Vous avez reçu {value} commandes d'un montant total de {price} pour la livraison du {date} sur votre boutique {shop}", $sales['number'], $arguments)."\n\n";
		$products = s("Vos clients ont commandé :

{items}

Bonne réception,
{siteName}", ['items' => $items]);

		$text = $intro.$products;

		$content = $intro;
		$content .= \mail\DesignUi::getButton(\Lime::getUrl().ShopUi::adminDateUrl($eDate['farm'], $eDate['shop'], $eDate).'/', s("Voir la vente"))."\n\n";
		$content .= $products;

		$html = \mail\DesignUi::getBanner($eDate['farm']).nl2br($content);

		return [
			$title,
			$text,
			$html
		];
	}

}
?>
