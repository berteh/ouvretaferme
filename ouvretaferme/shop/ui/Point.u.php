<?php
namespace shop;

class PointUi {

	public function __construct() {

		\Asset::css('shop', 'point.css');

	}

	public function createFirst(): string {

		$h = '<div class="util-block-help">';
			$h .= '<h4>'.s("Bienvenue sur la nouvelle boutique de votre ferme !").'</h4>';
			$h .= '<p>'.s("Vous avez configuré avec succès votre boutique. Il s'agit maintenant pour vous de définir la façon dont vous souhaitez livrer vos clients. Vous pouvez définir un ou plusieurs points de retrait collectifs et des lieux pour lesquels vous voulez autoriser la livraison à domicile.").'</p>';
		$h .= '</div>';

		return $h;

	}

	public function getList(Shop $eShop, \Collection $cc, bool $inTabs): string {

		$header = $inTabs ? 'h2' : 'h3';

		$h = '';

		if($inTabs === FALSE) {
			$h .= '<h2>'.s("Modes de livraison").'</h2>';
		}

		$h .= '<div class="point-wrapper">';

			$h .= '<div>';
				$h .= '<'.$header.'>'.s("Livraison en point de retrait").'</'.$header.'>';
				$h .= '<div class="util-block">';

					if($cc->offsetExists(Point::PLACE)) {
						$h .= $this->getPoints($eShop, $cc[Point::PLACE]);
					} else {

						if($eShop->canWrite()) {
							$h .= '<div class="util-info">';
								$h .= s("La livraison en point de retrait collectif n'est pas activée sur votre boutique. Pour l'activer, ajoutez un premier point de retrait !");
							$h .= '</div>';
						} else {
							$h .= s("La livraison en point de retrait collectif n'est pas activée sur la boutique.");
						}

					}

					if($eShop->canWrite()) {
						$h .= '<a href="/shop/point:create?shop='.$eShop['id'].'&type='.Point::PLACE.'" class="btn btn-outline-primary">'.\Asset::icon('plus-circle').' '.s("Ajouter un point de retrait").'</a>';
					}

				$h .= '</div>';
			$h .= '</div>';
			$h .= '<div>';
				$h .= '<'.$header.'>'.s("Livraison à domicile").'</'.$header.'>';
				$h .= '<div class="util-block">';

					if($cc->offsetExists(Point::HOME)) {
						$h .= $this->getPoints($eShop, $cc[Point::HOME]);
					} else {

						if($eShop->canWrite()) {
							$h .= '<div class="util-info">';
								$h .= s("La livraison à domicile n'est pas activée sur votre boutique. Pour l'activer, indiquez les zones géographiques dans lesquelles vous acceptez de livrer vos clients !");
							$h .= '</div>';
						} else {
							$h .= s("La livraison à domicile n'est pas activée sur la boutique.");
						}

						if($eShop->canWrite()) {
							$h .= '<a href="/shop/point:create?shop='.$eShop['id'].'&type='.Point::HOME.'" class="btn btn-outline-primary">'.\Asset::icon('plus-circle').' '.s("Activer la livraison à domicile ").'</a>';
						}

					}

				$h .= '</div>';
			$h .= '</div>';


		$h .= '</div>';

		$h .= '<br/>';

		return $h;

	}

	public function getField(Shop $eShop, \Collection $cc, Point $ePointSelected = new Point()): string {

		$hasHome = $cc->offsetExists(Point::HOME);
		$hasPlace = $cc->offsetExists(Point::PLACE);

		if($hasHome and $hasPlace) {
			return $this->getFieldBoth($eShop, $cc, $ePointSelected);
		} else if($hasHome) {
			return $this->getFieldHome($eShop, $cc[Point::HOME], $ePointSelected);
		} else if($hasPlace) {
			return $this->getFieldPlace($eShop, $cc[Point::PLACE], $ePointSelected);
		} else {
			throw new \Exception('No point');
		}

	}

	public function getFieldBoth(Shop $eShop, \Collection $cc, Point $ePointSelected) {

		$h = '<h2>'.s("Mon mode de livraison").'</h2>';

		$h .= '<div class="point-wrapper">';

			$h .= '<div>';
				$h .= '<h3>'.s("Livraison en point de retrait").'</h3>';
				$h .= '<div class="util-block point-place-wrapper">';
					$h .= $this->getPoints($eShop, $cc[Point::PLACE], ePointSelected: $ePointSelected, update: TRUE);
				$h .= '</div>';
			$h .= '</div>';
			$h .= '<div>';
				$h .= '<h3>'.s("Livraison à domicile").'</h3>';
				$h .= '<div class="util-block point-home-wrapper">';

					$h .= '<p>'.s("Vous pouvez choisir la livraison à domicile si vous habitez :").'</p>';

					$h .= $this->getPoints($eShop, $cc[Point::HOME], ePointSelected: $ePointSelected, update: TRUE);

					$h .= '<p>'.s("<b>Attention !</b> Si votre adresse ne correspond pas à l'une de ces zones, votre commande pourra être annulée.").'</p>';

				$h .= '</div>';
			$h .= '</div>';


		$h .= '</div>';

		$h .= '<br/>';

		return $h;

	}

	public function getFieldHome(Shop $eShop, \Collection $c, Point $ePointSelected) {

		$h = '<h2>'.s("Mon mode de livraison").'</h2>';

		$h .= '<p class="util-info">'.s("Les commandes sont livrées uniquement à domicile.<br/>Vous êtes éligible à la livraison à domicile si vous habitez dans l'une des zones suivantes :").'</p>';

		$h .= '<div class="util-block point-home-wrapper">';
			$h .= $this->getPoints($eShop, $c, ePointSelected: $ePointSelected, update: TRUE);
			$h .= '<p>'.s("<b>Attention !</b> Si votre adresse ne correspond pas à l'une de ces zones, votre commande sera annulée.").'</p>';
		$h .= '</div>';

		$h .= '<br/>';

		return $h;

	}

	public function getFieldPlace(Shop $eShop, \Collection $c, Point $ePointSelected) {

		if($c->count() === 1) {
			$ePointSelected = $c->first();
		}

		$h = '<h2>'.s("Retrait des commandes").'</h2>';

		if($c->count() > 1) {
			$h .= '<p class="util-info">'.s("Choisissez le point de livraison auquel vous souhaitez retirer votre commande").' '.\Asset::icon('arrow-return-left', ['class' => 'asset-icon-rotate--90']).'</p>';
		}

		$h .= '<div class="util-block point-place-wrapper">';
			$h .= $this->getPoints($eShop, $c, ePointSelected: $ePointSelected, update: TRUE);
		$h .= '</div>';

		$h .= '<br/>';

		return $h;

	}

	public function getByDate(Shop $eShop, Date $eDate, \Collection $cc): string {

		$h = '<div class="point-wrapper">';

			$h .= '<div>';
				$h .= '<h2>'.s("Livraison en point de retrait").'</h2>';
				$h .= '<div class="util-block">';

					if($cc->offsetExists(Point::PLACE)) {
						$h .= $this->getPoints($eShop, $cc[Point::PLACE], write: FALSE);
					} else {
						$h .= '<div class="util-info">';
							$h .= s("La livraison en point de retrait collectif n'est pas activée pour cette date !");
						$h .= '</div>';
					}

				$h .= '</div>';
			$h .= '</div>';
			$h .= '<div>';
				$h .= '<h2>'.s("Livraison à domicile").'</h2>';
				$h .= '<div class="util-block">';

					if($cc->offsetExists(Point::HOME)) {
						$h .= '<p class="util-info">';
							$h .= s("Vous avez activé la livraison à domicile dans les zones suivantes pour le {value} :", \util\DateUi::textual($eDate['deliveryDate']));
						$h .= '</p>';
						$h .= $this->getPoints($eShop, $cc[Point::HOME], write: FALSE);
					} else {
						$h .= '<div class="util-info">';
							$h .= s("La livraison à domicile n'est pas activée pour cette date !");
						$h .= '</div>';
					}

				$h .= '</div>';
			$h .= '</div>';


		$h .= '</div>';

		$h .= '<br/>';

		return $h;

	}

	public function getPoints(Shop $eShop, \Collection $c, bool $update = FALSE, Point $ePointSelected = new Point(), bool $write = TRUE): string {

		$h = '<div class="point-list">';

			foreach($c as $e) {
				$h .= $this->getPoint($eShop, $e, $update, $ePointSelected, $write);
			}

		$h .= '</div>';

		return $h;

	}

	public function getPoint(Shop $eShop, Point $e, bool $update = FALSE, Point $ePointSelected = new Point(), bool $write = TRUE): string {

		if($update === TRUE) {
			$write = FALSE;
		}

		$tag = $update ? 'label' : 'div';

		if($update) {

			$selected = (
				$ePointSelected->notEmpty() and
				$ePointSelected['id'] === $e['id']
			);

			$icon = '<div class="point-update">';
				$icon .= '<input type="radio" name="shopPoint" value="'.$e['id'].'" '.($selected ? 'checked' : '').'/>';
				$icon .= '<div class="point-update-content">';
					$icon .= \Asset::icon('check-lg');
				$icon .= '</div>';
			$icon .= '</div>';

		} else {

			$icon = match($e['type']) {
				Point::PLACE => \Asset::icon('house-fill', ['class' => 'point-icon']),
				Point::HOME => \Asset::icon('geo-alt-fill', ['class' => 'point-icon']),
			};

		}

		$title = match($e['type']) {
			Point::PLACE => encode($e['name']),
			Point::HOME => nl2br(encode($e['zone'])),
		};

		$updateText = match($e['type']) {
			Point::PLACE => s("Modifier le point de retrait"),
			Point::HOME => s("Modifier les zones de livraison"),
		};

		$deleteText = match($e['type']) {
			Point::PLACE => s("Supprimer le point de retrait"),
			Point::HOME => s("Fermer la livraison à domicile"),
		};

		$deleteConfirm = match($e['type']) {
			Point::PLACE => s("Voulez-vous vraiment supprimer définitivement ce point de retrait ?"),
			Point::HOME => s("Voulez-vous vraiment fermer la livraison à domicile ?"),
		};

		$orderMin = $e['orderMin'] ?? $eShop['orderMin'];
		$shipping = $e['shipping'] ?? $eShop['shipping'];
		$shippingUntil = $e['shippingUntil'] ?? $eShop['shippingUntil'];

		$h = '<'.$tag.' class="point-element" data-order-min="'.$orderMin.'" data-shipping="'.$shipping.'" data-shipping-until="'.$shippingUntil.'">';

			if($write and $e['type'] === Point::HOME) {
				$h .= '<p class="util-info">'.s("Vous autorisez actuellement la livraison à domicile sur les zones suivantes :").'</p>';
			}

			$h .= '<div class="point-name">';
				$h .= $icon;
				$h .= '<div>';
					$h .= '<h4>'.$title.'</h4>';
					if($e['description'] !== NULL) {
						$h .= '<span>'.encode($e['description']).'</span>';
					}
				$h .= '</div>';

				if($write and $e->canWrite()) {

					$h .= '<div>';
						$h .= '<a data-dropdown="bottom-end" class="btn btn-sm btn-color-primary dropdown-toggle">'.\Asset::icon('gear-fill').'</a>';
						$h .= '<div class="dropdown-list">';
							$h .= match($e['type']) {
								Point::PLACE => '<a href="/shop/point:update?id='.$e['id'].'" class="dropdown-item">'.$updateText.'</a>',
								Point::HOME => $e['used'] ? '<span class="dropdown-item point-update-zone">'.s("Vous ne pouvez pas modifier un lieu déjà utilisé dans une vente. Veuillez le supprimer et en ajouter un autre si vous souhaitez le modifier.").'</span>' : '<a href="/shop/point:update?id='.$e['id'].'" class="dropdown-item">'.$updateText.'</a>',
							};
							$h .= '<div class="dropdown-divider"></div>';
							$h .= '<a data-ajax="/shop/point:doDelete" post-id="'.$e['id'].'" data-confirm="'.$deleteConfirm.'" class="dropdown-item">'.$deleteText.'</a>';
						$h .= '</div>';
					$h .= '</div>';

				}

			$h .= '</div>';

			if($e['type'] === Point::PLACE) {
				$h .= '<div class="point-address">';
					if($e['address']) {
						$h .= nl2br(encode($e['address'])).'<br/>';
					}
					$h .= encode($e['place']);
				$h .= '</div>';
			}

			if($update or $write) {

				$badges = '';

				if($orderMin > 0) {
					$badges .= '<span class="point-order-min util-badge">'.s("Minimum de commande : {value} €", $orderMin).'</span>';
				}

				if($shipping > 0) {
					if($shippingUntil > 0) {
						$badges .= ' <span class="point-shipping util-badge">';
							$badges .= s("Frais de livraison : <charged>{value} €</charged> et <free>offerts au delà de {until} € de commande</free>", ['value' => $shipping, 'until' => $shippingUntil, 'charged' => $write ? '<span>' : '<span class="point-shipping-charged">', 'free' => $write ? '<span>' : '<span class="point-shipping-free">']);
					} else {
						$badges .= ' <span class="point-shipping util-badge">';
							$badges .= s("Frais de livraison : {value} €", $shipping);
						$badges .= '</span>';
					}
				}

				if($badges) {
					$h .= '<div class="point-badge">'.$badges.'</div>';
				}

			}

		$h .= '</'.$tag.'>';

		return $h;

	}

	public function create(Point $e): \Panel {

		$e->expects([
			'shop' => ['name'],
			'type'
		]);

		$form = new \util\FormUi();

		$h = '';

		if($e['type'] === Point::HOME) {

			$h .= '<div class="util-block-help">';
				$h .= '<p>'.s("Indiquez les zones géographiques que vous autorisez pour la livraison à domicile. Cela peut être une liste de villes, de départements ou toutes autres localités pertinentes pour votre activité. Lorsqu'ils commandent, vos clients s'engagent à donner une adresse qui se situe dans les zones que vous avez ainsi définies.").'</p>';
			$h .= '</div>';


		}

		$h .= $form->openAjax('/shop/point:doCreate');

			$h .= $form->hidden('shop', $e['shop']['id']);
			$h .= $form->hidden('type', $e['type']);

			$h .= $form->group(
				s("Boutique"),
				'<div class="form-control disabled">'.encode($e['shop']['name']).'</div>'
			);

			$h .= match($e['type']) {
				Point::PLACE => $form->dynamicGroups($e, ['name', 'description', 'place', 'address']),
				Point::HOME => $form->dynamicGroups($e, ['zone'])
			};

			$h .= $form->group(
				content: $form->submit(s("Ajouter"))
			);

		$h .= $form->close();

		return new \Panel(
			title: match($e['type']) {
				Point::PLACE => s("Ajouter un point de retrait"),
				Point::HOME => s("Activer la livraison à domicile"),
			},
			body: $h
		);

	}

	public function update(Point $e): \Panel {

		$form = new \util\FormUi();

		$h = '';

		$h .= $form->openAjax('/shop/point:doUpdate');

			$h .= $form->hidden('id', $e['id']);

			$h .= match($e['type']) {
				Point::PLACE => $form->dynamicGroups($e, ['name', 'description', 'place', 'address']),
				Point::HOME => $form->dynamicGroups($e, ['zone'])
			};

			$h .= '<div class="util-block-gradient">';

				$title = match($e['type']) {
					Point::PLACE => s("Personnalisation du point de retrait"),
					Point::HOME => s("Personnalisation de la livraison à domicile")
				};

				$h .= $form->group(content: '<h3>'.$title.'</h3>');
				if($e['shop']->hasOnlinePayment()) {
					$h .= $form->dynamicGroups($e, ['paymentOnlineOnly']);
				}
				$h .= $form->dynamicGroups($e, ['orderMin', 'shipping', 'shippingUntil']);
			$h .= '</div>';

			$h .= $form->group(
				content: $form->submit(s("Modifier"))
			);

		$h .= $form->close();

		return new \Panel(
			title: match($e['type']) {
				Point::PLACE => s("Modifier un point de retrait"),
				Point::HOME => s("Modifier les zones de livraison à domicile"),
			},
			body: $h
		);

	}


	public static function p(string $property): \PropertyDescriber {

		$d = Point::model()->describer($property, [
			'zone' => s("Zones de livraison"),
			'name' => s("Nom du point de retrait"),
			'description' => s("Informations sur le point de retrait"),
			'place' => s("Ville du point de retrait"),
			'address' => s("Adresse du point de retrait"),
			'paymentOnlineOnly' => s("Rendre le paiement en ligne obligatoire"),
			'orderMin' => s("Montant minimal de commande"),
			'shipping' => s("Frais de livraison par commande"),
			'shippingUntil' => s("Montant minimal de commande au delà duquel les frais de livraison sont offerts"),
		]);

		switch($property) {

			case 'type' :
				$d->values = [
					Point::HOME => s("Livraison à domicile"),
					Point::PLACE => s("Livraison en point de retrait")
				];
				break;

			case 'name' :
				$d->placeholder = s("Exemple : Maison des Citoyens, À la ferme, ...");
				break;

			case 'description' :
				$d->placeholder = s("Exemple : Retrait des commandes de HH:MM à HH:MM");
				$d->after = \util\FormUi::info(s("Vous pouvez indiquer ici toute information utile spécifique à ce point de retrait, pour être communiquée à vos clients."));
				break;

			case 'place' :
				$d->placeholder = s("Exemple : Saint-Alban");
				break;

			case 'address' :
				$d->placeholder = s("Exemple : 12 rue sous les Augustins");
				break;

			case 'zone' :
				$d->placeholder = s("Exemple :\n- Paris\n- New York\n- Ile de Pâques\n\nNous assurons la livraison en voilier.");
				$d->after = \util\FormUi::info(s("Indiquez une zone géographique par ligne. Vous pouvez également ajouter du texte si vous souhaitez apporter des précisions supplémentaires à vos clients."));
				break;

			case 'paymentOnlineOnly' :
				$d->field = 'yesNo';
				$d->attributes = fn(\util\FormUi $form, Point $e) => [
					'no' => s("la valeur  choisie pour la boutique ({value})", $e['shop']['paymentOnlineOnly'] ? s("oui") : s("non"))
				];
				break;

			case 'orderMin' :
				$d->append = '€ '.\selling\CustomerUi::getTaxes(\selling\Customer::PRIVATE);
				$d->after = fn(\util\FormUi $form, Point $e) => \util\FormUi::info($e['shop']['orderMin'] !== NULL ?
					s("Si ce champ est laissé vide, le montant minimal de commande choisi pour la boutique ({value}) s'applique.", \util\TextUi::money($e['shop']['orderMin'], precision: 0)) :
					s("Si ce champ est laissé vide, le montant minimal de commande choisi pour la boutique s'applique."));
				break;

			case 'shipping' :
				$d->append = '€ '.\selling\CustomerUi::getTaxes(\selling\Customer::PRIVATE);
				$d->after = fn(\util\FormUi $form, Point $e) => \util\FormUi::info($e['shop']['shipping'] !== NULL ?
					s("Si ce champ est laissé vide, les frais de livraison choisis pour la boutique ({value}) s'appliquent.", \util\TextUi::money($e['shop']['shipping'], precision: 0)) :
					s("Si ce champ est laissé vide, les frais de livraison choisis pour la boutique s'appliquent."));
				break;

			case 'shippingUntil' :
				$d->append = '€ '.\selling\CustomerUi::getTaxes(\selling\Customer::PRIVATE);
				$d->after = \util\FormUi::info(s("Les frais de livraison ne sont pas facturés si la commande des clients excède ce montant."));
				$d->after = fn(\util\FormUi $form, Point $e) => \util\FormUi::info($e['shop']['shippingUntil'] !== NULL ?
					s("Si ce champ est laissé vide, le montant minimal choisi pour la boutique ({value}) s'applique.", \util\TextUi::money($e['shop']['shippingUntil'], precision: 0)) :
					s("Si ce champ est laissé vide, le montant minimal choisi pour la boutique s'applique."));
				break;

		}

		return $d;

	}
}
?>
