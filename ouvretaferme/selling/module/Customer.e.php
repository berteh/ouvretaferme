<?php
namespace selling;

class Customer extends CustomerElement {

	public static function getSelection(): array {

		return parent::getSelection() + [
			'farm' => ['name', 'vignette', 'url', 'featureDocument'],
			'user' => ['email']
		];

	}

	public function getLegalName(): string {
		return ($this['legalName'] ?? $this['name']);
	}

	public function getName(): ?string {

		if($this->empty()) {
			return s("Client anonyme");
		} else {
			return $this['name'];
		}

	}

	public function getCategory(): string {

		$this->expects(['type', 'destination']);

		if($this['destination'] === Customer::COLLECTIVE) {
			return Customer::COLLECTIVE;
		} else {
			return $this['type'];
		}

	}

	public function getTextCategory(bool $short = FALSE): string {

		return match($this->getCategory()) {
			Customer::COLLECTIVE => $short ? s("Point de vente") : s("Point de vente pour les particuliers"),
			Customer::PRIVATE => $short ? s("Particulier") : s("Client particulier"),
			Customer::PRO => $short ? s("Professionnel") : s("Client professionnel")
		};

	}

	public function canRead(): bool {
		return $this->canWrite();
	}

	public function canWrite(): bool {

		$this->expects(['farm']);
		return $this['farm']->canSelling();

	}

	public function canGrid(): bool {

		$this->expects(['type']);

		return (
			$this->canManage() and
			$this['type'] === Customer::PRO
		);

	}

	public function canManage(): bool {

		$this->expects(['farm']);
		return $this['farm']->canManage();

	}

	public function canDelete(): bool {
		return $this->canManage();
	}

	public function isPro(): bool {
		$this->expects(['type']);
		return $this['type'] === Customer::PRO;
	}

	public function isPrivate(): bool {
		$this->expects(['type']);
		return $this['type'] === Customer::PRIVATE;
	}

	public function isIndividual(): bool {
		$this->expects(['destination']);
		return $this['destination'] === Customer::INDIVIDUAL;
	}

	public function isCollective(): bool {
		$this->expects(['destination']);
		return $this['destination'] === Customer::COLLECTIVE;
	}

	public function getDeliveryAddress(): ?string {

		if($this->hasDeliveryAddress() === FALSE) {
			return NULL;
		}

		$address = $this->getDeliveryStreet()."\n";
		$address .= $this['deliveryPostcode'].' '.$this['deliveryCity'];

		return $address;

	}

	public function getDeliveryStreet(): ?string {

		$street = $this['deliveryStreet1'];
		if($this['deliveryStreet2'] !== NULL) {
			$street .= "\n".$this['deliveryStreet2'];
		}

		return $street;

	}

	public function getInvoiceAddress(): ?string {

		if($this->hasInvoiceAddress() === FALSE) {
			return NULL;
		}

		$address = $this->getInvoiceStreet()."\n";
		$address .= $this['invoicePostcode'].' '.$this['invoiceCity'];

		return $address;

	}

	public function getInvoiceStreet(): ?string {

		$street = $this['invoiceStreet1'];
		if($this['invoiceStreet2'] !== NULL) {
			$street .= "\n".$this['invoiceStreet2'];
		}

		return $street;

	}

	public function hasInvoiceAddress(): bool {
		return ($this['invoiceCity'] !== NULL);
	}

	public function getEmailOptIn() {

		$this->expects(['emailOptIn']);

		if($this['emailOptIn'] === NULL) {
			return '<span class="color-muted">'.\Asset::icon('question-circle').' '.s("Pas de consentement explicite du client").'</span>';
		} else if($this['emailOptIn'] === FALSE) {
			return '<span class="color-danger">'.\Asset::icon('x-circle').' '.s("Refus du client").'</span>';
		} else {
			return '<span class="color-success">'.\Asset::icon('check-circle').' '.s("Accord du client").'</span>';
		}

	}

	public function getOptInHash() {
		return hash('sha256', random_bytes(1024));
	}

	public function build(array $properties, array $input, array $callbacks = [], ?string $for = NULL): array {

		return parent::build($properties, $input, $callbacks + [

			'firstName.empty' => function(?string &$firstName): bool {

				$this->expects(['user']);

				if($this->getCategory() !== Customer::PRIVATE) {
					$firstName = NULL;
					return TRUE;
				}

				if($this['user']->notEmpty()) {
					return ($firstName !== NULL);
				} else {
					return TRUE;
				}


			},

			'lastName.empty' => function(?string &$lastName, \BuildProperties $p): bool {

				$this->expects(['user']);

				if($this->getCategory() !== Customer::PRIVATE) {
					$lastName = NULL;
					return TRUE;
				}

				if($this['user']->notEmpty()) {
					return ($lastName !== NULL);
				} else {

					if($p->isBuilt('firstName')) {
						return TRUE;
					} else {
						return ($lastName !== NULL or $this['firstName'] !== NULL);
					}

				}

			},

			'name.empty' => function(?string &$name): bool {

				$this->expects(['user']);

				if($this->getCategory() === Customer::PRIVATE) {
					$name = NULL;
					return TRUE;
				}

				return ($name !== NULL);

			},

			'name.set' => function(?string $name, \BuildProperties $p): bool {

				if($this->getCategory() === Customer::PRIVATE) {

					if($p->isBuilt(['firstName', 'lastName'])) {

						if($this['firstName'] !== NULL and $this['lastName'] !== NULL) {
							$this['name'] = $this['firstName'].' '.mb_strtoupper($this['lastName']);
						} else if($this['lastName'] !== NULL) {
							$this['name'] = mb_strtoupper($this['lastName']);
						} else {
							$this['name'] = $this['firstName'];
						}

					} else {
						return FALSE;
					}

				} else {
					$this['name'] = $name;
				}

				return TRUE;

			},

			'category.user' => function(?string $category) use ($for): bool {

				return match($for) {
					'create' => TRUE,
					'update' => ($category !== Customer::COLLECTIVE)
				};

			},

			'phone.check' => function(?string $phone): bool {

				return (
					$phone === NULL or
					preg_match('/^[0-9 \+]+$/si', $phone) > 0
				);

			},

			'category.set' => function(?string $category) use ($for): bool {

				$this['category'] = $category;

				switch($category) {

					case Customer::PRIVATE :
						$this['type'] = Customer::PRIVATE;
						$this['destination'] = Customer::INDIVIDUAL;
						return TRUE;

					case Customer::COLLECTIVE :
						$this['type'] = Customer::PRIVATE;
						$this['destination'] = Customer::COLLECTIVE;
						return TRUE;

					case Customer::PRO :
						$this['type'] = Customer::PRO;
						$this['destination'] = NULL;
						return TRUE;

					default :
						return FALSE;

				};

			},

		]);

	}

}
?>