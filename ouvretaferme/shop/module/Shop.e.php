<?php
namespace shop;

class Shop extends ShopElement {

	public static function isEmbed(): bool {
		return get_exists('embed');
	}

	public function validateEmbed(): void {

		$this->expects(['embedOnly', 'embedUrl']);

		if(
			$this['embedOnly'] and
			self::isEmbed() === FALSE
		) {
			throw new \RedirectAction($this['embedUrl']);
		}

	}

	public function isOpen(): bool {

		$this->expects(['status']);
		return $this['status'] === Shop::OPEN;

	}

	public function isClosed(): bool {

		$this->expects(['status']);
		return $this['status'] === Shop::CLOSED;

	}

	public function canAccess(\selling\Customer $e): bool {

		$this->expects(['limitCustomers']);

		return (
			$this['limitCustomers'] === [] or
			$this->canRead() or
			($e->notEmpty() and in_array($e['id'], $this['limitCustomers']))
		);

	}

	public function canRead(): bool {

		$this->expects(['farm']);
		return $this['farm']->canSelling();

	}

	public function canWrite(): bool {

		$this->expects(['farm']);

		return $this['farm']->canManage();

	}

	public function countPayments(): int {

		if($this['hasPayment']) {

			return (
				(int)$this['paymentCard'] +
				(int)$this['paymentTransfer'] +
				(int)$this['paymentOffline']
			);

		} else {
			return 0;
		}

	}

	public function getPayments(Point $ePoint): array {

		$payments = [];

		if($ePoint->empty()) {

			if($this['paymentCard']) {
				$payments[] = \selling\Sale::ONLINE_CARD;
			}
			if($this['paymentTransfer']) {
				$payments[] = \selling\Sale::TRANSFER;
			}
			if($this['paymentOffline']) {
				$payments[] = \selling\Sale::OFFLINE;
			}

		} else {

			if(
				$ePoint['paymentCard'] or
				($ePoint['paymentCard'] === NULL and $this['paymentCard'])
			) {
				$payments[] = \selling\Sale::ONLINE_CARD;
			}
			if(
				$ePoint['paymentTransfer'] or
				($ePoint['paymentTransfer'] === NULL and $this['paymentTransfer'])
			) {
				$payments[] = \selling\Sale::TRANSFER;
			}
			if(
				$ePoint['paymentOffline'] or
				($ePoint['paymentOffline'] === NULL and $this['paymentOffline'])
			) {
				$payments[] = \selling\Sale::OFFLINE;
			}

		}

		return $payments;

	}

	public function getSharedKey(): string {

		$this->expects(['id', 'sharedHash']);

		return $this['id'].'-'.$this['sharedHash'];

	}

	public function isSharedKeyExpired(): bool {

		if($this['sharedHashExpiresAt'] === NULL) {
			return FALSE;
		} else {
			return $this['sharedHashExpiresAt'] < currentDate();
		}

	}

	public function getNewSharedHash(): string {
		return bin2hex(random_bytes(6));
	}

	public function build(array $properties, array $input, \Properties $p = new \Properties()): void {

		if(array_intersect($properties, ['paymentCard', 'paymentOffline', 'paymentTransfer'])) {
			$properties[] = 'payment';
		}

		$p
			->setCallback('farm.prepare', function(?string $farm) use($input) {

				$this['farm'] = \farm\FarmLib::getById($farm);

			})
			->setCallback('farm.check', function() use($input) {

				return $this['farm']->notEmpty() and $this['farm']->canManage();

			})
			->setCallback('fqn.prepare', function() use($p) {

				if($p->for === 'update') {
					$this['oldFqn'] = $this['fqn'];
				}

			})
			->setCallback('terms.check', function(?string $terms) {

				return (
					$terms === NULL or
					mb_strlen(strip_tags($terms)) > 0
				);

			})
			->setCallback('commentCaption.prepare', function(?string &$caption) use($p) {

				if($p->isBuilt('comment') === FALSE) {
					return TRUE;
				}

				if($this['comment'] === FALSE) {
					$caption = NULL;
				}

			})
			->setCallback('payment.check', function() {

				$this->expects(['paymentCard', 'paymentOffline', 'paymentTransfer']);

				return (
					$this['paymentCard'] or
					$this['paymentOffline'] or
					$this['paymentTransfer']
				);

			})
			->setCallback('limitCustomers.prepare', function(mixed &$customers): bool {

				$this->expects(['farm']);

				$customers = (array)($customers ?? []);

				$customers = \selling\Customer::model()
					->select('id')
					->whereId('IN', $customers)
					->whereFarm($this['farm'])
					->getColumn('id');

				return TRUE;

			})
			->setCallback('customColor.light', function(?string $color): bool {

				if($color === NULL) {
					return TRUE;
				}

				$average = array_sum([
					hexdec(substr($color, 1, 2)),
					hexdec(substr($color, 3, 2)),
					hexdec(substr($color, 5, 2)),
				]) / 3;

				return ($average < 127);

			})
			->setCallback('customBackground.light', function(?string $color): bool {

				if($color === NULL) {
					return TRUE;
				}

				$average = array_sum([
					hexdec(substr($color, 1, 2)),
					hexdec(substr($color, 3, 2)),
					hexdec(substr($color, 5, 2)),
				]) / 3;

				return ($average > 225);

			})
			->setCallback('customFont.check', function(?string $customFont): bool {
				return $customFont === NULL or \website\DesignLib::isCustomFont($customFont, 'customFonts');
			})
			->setCallback('customTitleFont.check', function(?string $customFont): bool {
				return $customFont === NULL or \website\DesignLib::isCustomFont($customFont, 'customTitleFonts');
			});
	
		parent::build($properties, $input, $p);

	}

}
?>