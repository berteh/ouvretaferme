document.addEventListener('keydown', (e) => Merchant.pressKey(e));

class Merchant {

	static current = null;

	static selectedField = null;
	static selectedProperty = null;
	static selectedValue = 0;

	static show(item) {

		// On cache tout le monde
		qsa('.merchant:not(.hide)', entry => entry.classList.add('hide'));

		this.current = qs('#merchant-'+ item.dataset.item);
		this.current.classList.remove('hide');

		this.recalculate();

		const inputUnitPrice = this.current.qs('input[name^="unitPrice"]');
		const inputNumber = this.current.qs('input[name^="number"]');
		const inputPrice = this.current.qs('input[name^="price"]');

		let unitPrice = this.getRealValue(inputUnitPrice);
		let number = this.getRealValue(inputNumber);
		let price = this.getRealValue(inputPrice);

		const fields = (number !== null ? 1 : 0)
			+ (price !== null ? 1 : 0)
			+ (unitPrice !== null ? 1 : 0);

		if(fields <= 1) {
			this.unlockProperty(inputUnitPrice);
			this.unlockProperty(inputNumber);
			this.unlockProperty(inputPrice);
		}

		const locked = this.current.qs('input[name^="locked"]').value;

		if(locked === 'number') {
			this.keyboardOpen(this.current.qs('.merchant-field[data-property="price"]'));
		} else if(locked === 'price') {
			this.keyboardOpen(this.current.qs('.merchant-field[data-property="number"]'));
		} else {

			if(this.isUnitInteger()) {
				this.keyboardOpen(this.current.qs('.merchant-field[data-property="number"]'));
			} else {
				this.keyboardOpen(this.current.qs('.merchant-field[data-property="price"]'));
			}

		}

	}

	static hide() {

		if(Merchant.current === null) {
			return;
		}

		Merchant.current.classList.add('hide');
		Merchant.current = null;

	}

	static recalculate() {

		const inputUnitPrice = this.current.qs('input[name^="unitPrice"]');
		const inputNumber = this.current.qs('input[name^="number"]');
		const inputPrice = this.current.qs('input[name^="price"]');
		const inputLocked = this.current.qs('input[name^="locked"]');

		let unitPrice = this.getRealValue(inputUnitPrice);
		let number = this.getRealValue(inputNumber);
		let price = this.getRealValue(inputPrice);

		// Prix unitaire + Quantité non verrouillée
		const checkPrice = () => {

			if(
				unitPrice !== null &&
				number !== null &&
				this.checkPropertyDisabled(inputUnitPrice) === false &&
				this.checkPropertyDisabled(inputNumber) === false
			) {

				price = unitPrice * number;
				inputPrice.value = price;
				this.setEntryValue(this.current.dataset.item, 'price', price);

				this.unlockProperty(inputUnitPrice);
				this.unlockProperty(inputNumber);
				this.lockProperty(inputPrice);

			} else if(this.checkPropertyDisabled(inputPrice)) { // Le montant total est désactivé mais la saisie a changé

				inputPrice.value = '';
				price = null;

				this.setEntryValue(this.current.dataset.item, 'price', null);

				this.unlockProperty(inputUnitPrice);
				this.unlockProperty(inputNumber);
				this.unlockProperty(inputPrice);

			} else if(price !== null) {

				this.unlockProperty(inputPrice);

			}

		};

		// Prix unitaire + Montant total non verrouillé
		const checkNumber = () => {

			if(
				unitPrice !== null &&
				price !== null &&
				this.checkPropertyDisabled(inputUnitPrice) === false &&
				this.checkPropertyDisabled(inputPrice) === false
			) {

				number = price / unitPrice;
				inputNumber.value = number;
				this.setEntryValue(this.current.dataset.item, 'number', number);

				this.unlockProperty(inputUnitPrice);
				this.lockProperty(inputNumber);
				this.unlockProperty(inputPrice);

			} else if(this.checkPropertyDisabled(inputNumber)) { // Le montant total est désactivé mais la saisie a changé

				number = null;
				inputNumber.value = '';
				this.setEntryValue(this.current.dataset.item, 'number', null);

				this.unlockProperty(inputUnitPrice);
				this.unlockProperty(inputNumber);
				this.unlockProperty(inputPrice);

			} else if(
				unitPrice === null &&
				price === null &&
				number === null
			) {
				this.unlockProperty(inputUnitPrice);
				this.unlockProperty(inputNumber);
				this.unlockProperty(inputPrice);
			} else if(number !== null) {

				this.unlockProperty(inputNumber);

			}

		};

		// Quantité + Montant total non verrouillé
		const checkUnitPrice = () => {

			if(
				number !== null &&
				price !== null &&
				this.checkPropertyDisabled(inputNumber) === false &&
				this.checkPropertyDisabled(inputPrice) === false
			) {

				unitPrice = price / number;
				inputUnitPrice.value = unitPrice;
				this.setEntryValue(this.current.dataset.item, 'unit-price', unitPrice);

				this.lockProperty(inputUnitPrice);
				this.unlockProperty(inputNumber);
				this.unlockProperty(inputPrice);

			} else if(this.checkPropertyDisabled(inputUnitPrice)) { // Le montant total est désactivé mais la saisie a changé

				inputUnitPrice.value = '';
				unitPrice = null;

				this.setEntryValue(this.current.dataset.item, 'unit-price', null);

				this.unlockProperty(inputUnitPrice);
				this.unlockProperty(inputNumber);
				this.unlockProperty(inputPrice);

			} else if(unitPrice !== null) {

				this.unlockProperty(inputUnitPrice);

			}

		};

		if(unitPrice === 0) {

			this.setEntryValue(this.current.dataset.item, 'price', 0);

			this.unlockProperty(inputUnitPrice);
			this.unlockProperty(inputNumber);
			this.lockProperty(inputPrice);

		} else {

			switch(inputLocked.value) {

				case 'unit-price' :
					checkUnitPrice();
					checkPrice();
					checkNumber();
					break;

				case 'price' :
					checkPrice();
					checkUnitPrice();
					checkNumber();
					break;

				default :
				case 'number' :
					checkNumber();
					checkPrice();
					checkUnitPrice();
					break;

			}

		}

	}

	static keyboardDelete(property) {

		const field = this.current.qs('.merchant-field[data-property="'+ property +'"]');
		const actions = this.current.qs('.merchant-actions[data-property="'+ property +'"]');
		const erase = actions.qs('.merchant-erase');

		// Pas la gomme
		if(erase.classList.contains('hide')) {
			return;
		}

		// On efface la valeur en cours
		field.qs('input').value = '';
		this.selectedValue = null;

		field.qs('.merchant-value').innerHTML = this.getKeyboardEmpty();

		erase.classList.add('hide');

		this.current.qs('input[name^="locked"]').value = '';

		this.recalculate();

	}

	static keyboardToggle(target) {

		const input = target.qs('input');

		// Déjà sélectionné
		if(this.selectedField === input) {
			this.keyboardClose();
		} else {
			this.keyboardOpen(target);
		}

	}

	static pressKey(e) {

		if(this.current === null) {
			return true;
		}

		let nodes;
		let selectedNode;

		switch(e.key) {

			case 'Enter' :
				this.current.qs('form').dispatchEvent(new CustomEvent("submit"));
				break;

			case 'ArrowUp' :
			case 'PageUp' :
				e.preventDefault();

				nodes = this.current.qsa('.merchant-field:not(.disabled)');

				if(this.selectedField === null) {
					selectedNode = nodes[nodes.length - 1];
				} else {
					nodes.forEach((item, key) => {
						if(item.qs('input') === this.selectedField) {
							selectedNode = nodes[(key - 1 + nodes.length) % nodes.length];
						}
					});
				}

				this.keyboardOpen(selectedNode);

				break;

			case 'ArrowDown' :
			case 'PageDown' :
				e.preventDefault();

				nodes = this.current.qsa('.merchant-field:not(.disabled)');

				if(this.selectedField === null) {
					selectedNode = nodes[0];
				} else {
					nodes.forEach((item, key) => {
						if(item.qs('input') === this.selectedField) {
							selectedNode = nodes[(key + 1) % nodes.length];
						}
					});
				}

				this.keyboardOpen(selectedNode);

				break;

			case 'Escape' :
				this.hide();
				break;

			case 'Backspace' :
				this.pressBack();
				break;

			case '1' :
			case '2' :
			case '3' :
			case '4' :
			case '5' :
			case '6' :
			case '7' :
			case '8' :
			case '9' :
			case '0' :
				this.pressDigit(parseInt(e.key));
				break;

			case 'Delete' :
				if(this.selectedProperty !== null) {
					this.keyboardDelete(this.selectedProperty);
				}
				break;

		}

	}

	static keyboardOpen(target) {

		this.selectedProperty = target.dataset.property;
		this.selectedField = this.current.qs('.merchant-field[data-property="'+ this.selectedProperty +'"] input');
		this.selectedValue = 0;//this.getRealValue(this.selectedField);

		this.current.qs('.merchant-field.selected', field => field.classList.remove('selected'));
		target.classList.add('selected');
		this.current.qs('.merchant-keyboard').classList.remove('disabled');

	}

	static keyboardClose() {

		this.current.qs('.merchant-field.selected', field => field.classList.remove('selected'));
		this.current.qs('.merchant-keyboard').classList.add('disabled');

		this.selectedProperty = null;
		this.selectedField = null;
		this.selectedValue = null;

	}

	static pressDigit(digit) {

		if(this.selectedField === null) {
			return;
		}

		this.selectedValue *= (digit === '00') ? 100 : 10;
		this.selectedValue += (digit === '00') ? 0 : digit;

		const value = this.selectedValue / (this.isPropertyInteger(this.selectedProperty) ? 1 : 100);
		const isNull = (value === 0 && this.selectedProperty !== 'unit-price');
		this.selectedField.value = isNull ? '' : value;

		if(digit !== '00') {

			const digitElement = this.current.qs('[data-digit="'+ digit +'"]');

			digitElement.classList.add('merchant-digit-animation');
			setTimeout(() => digitElement.classList.remove('merchant-digit-animation'), 100);

		}

		this.recalculate();

		this.setEntryValue(this.current.dataset.item, this.selectedProperty, isNull ? null : value);

	}

	static pressBack() {

		if(this.selectedField === null) {
			return;
		}

		this.selectedValue /= 10;
		this.selectedValue = Math.floor(this.selectedValue);

		const value = this.selectedValue / (this.isPropertyInteger(this.selectedProperty) ? 1 : 100);
		const isNull = (value === 0 && this.selectedProperty !== 'unit-price');
		this.selectedField.value = isNull ? '' : value;

		this.recalculate();
		
		this.setEntryValue(this.current.dataset.item, this.selectedProperty, isNull ? null : value);

	}

	static isUnitInteger() {
		
		const unit = this.current.dataset.unit;
		
		return (
			unit === '' ||
			unit === 'bunch' ||
			unit === 'unit' ||
			unit === 'box' ||
			unit === 'gram-250' ||
			unit === 'gram-500'
		);
		
	}

	static setEntryValue(item, property, value) {

		let text;

		const node = qs('#merchant-'+ item +'-'+ property);

		if(value === null) {
			text = this.getKeyboardEmpty();
		} else {

			if(this.isPropertyInteger(property)) {
				text = value;
			} else {
				const textValue = Math.round(value * 100).toString().padStart(3, '0');
				text = textValue.substring(0, textValue.length - 2) +','+ textValue.substring(textValue.length - 2, textValue.length);
			}

		}

		node.innerHTML = text;

	}

	static getKeyboardEmpty() {
		return this.isPropertyInteger() ? '-' : '-,--';
	}

	static checkPropertyDisabled(input) {
		return input.parentElement.classList.contains('disabled');
	}

	static isPropertyInteger(property) {
		return (this.isUnitInteger() && property === 'number');
	}

	static getRealValue(input) {

		if(
			input.value === '' ||
			(input.parentElement.dataset.property !== 'unit-price' && input.value === '0')
		) {
			return null;
		} else {
			return parseFloat(input.value);
		}

	}

	static unlockProperty(input) {

		const item = input.firstParent('.merchant');
		const property = input.firstParent('.merchant-field').dataset.property;

		item.qs('.merchant-field[data-property="'+ property +'"].disabled', field => field.classList.remove('disabled'));

		const actions = item.qs('.merchant-actions[data-property="'+ property +'"]');

		actions.qs('.merchant-lock', action => action.classList.add('hide'));
		actions.qs('.merchant-erase', action => {
			(input.value === '') ? action.classList.add('hide') : action.classList.remove('hide')
		});

	}

	static lockProperty(input) {

		const entry = input.firstParent('.merchant');
		const field = input.firstParent('.merchant-field');
		const property = field.dataset.property;

		entry.qs('.merchant-field[data-property="'+ property +'"]').classList.add('disabled');

		const actions = entry.qs('.merchant-actions[data-property="'+ property +'"]');
		actions.qs('.merchant-lock', action => action.classList.remove('hide'));
		actions.qs('.merchant-erase', action => action.classList.add('hide'));

		entry.qs('input[name^="locked"]').value = property;

	}

}