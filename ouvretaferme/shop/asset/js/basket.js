document.delegateEventListener('click', '#shop-basket-point div.point-home-wrapper input', function(e) {

	BasketManage.selectHome();

});

document.delegateEventListener('click', '#shop-basket-point div.point-place-wrapper input', function(e) {

	BasketManage.selectPlace();

});

class BasketManage {

	static prefix = 'basket-';
	static version = 'v2';

	/* Fonctions génériques pour la gestion du panier en localStorage */
	static key(dateId) {
		return this.prefix + this.version +'-'+ dateId;
	}

	static getBasket(dateId, defaultJson = null) {

		this.checkExpiration();

		const basket = localStorage.getItem(this.key(dateId));

		if(!basket) {
			return (defaultJson !== null) ? defaultJson : this.newBasket();
		}

		const basketJson = JSON.parse(basket);

		if(
			basketJson.sale === null &&
			defaultJson !== null
		) {
			return defaultJson;
		} else {
			return basketJson;
		}

	}

	static hasBasket(dateId) {
		return !!localStorage.getItem(this.key(dateId));
	}

	static setBasket(dateId, basket) {
		const basketJson = JSON.stringify(basket);
		localStorage.setItem(this.key(dateId), basketJson);
	}

	static deleteBasket(dateId) {
		localStorage.removeItem(this.key(dateId));
	}

	static modify(dateId, basket, to) {

		this.setBasket(dateId, basket);

		const pathname = location.pathname;
		const shopAndDatePathname = pathname.substring(0, pathname.lastIndexOf('/'));
		const shopPathname = shopAndDatePathname.substring(0, shopAndDatePathname.lastIndexOf('/'));

		switch(to) {

			case 'basket' :
				window.location.href = shopAndDatePathname +'/panier?modify=1';
				break;

			case 'home' :
				window.location.href = shopAndDatePathname +'?modify=1';
				break;

		}

	}

	static doUpdate(dateId) {

		const pathname = location.pathname;
		const shopAndDatePathname = pathname.substr(1, pathname.lastIndexOf('/') - 1);

		const post = this.getBasket(dateId);
		post.shopDate = dateId

		qs('#shop-basket-point input[name="shopPoint"]:checked', node => post.shopPoint = node.value);
		qs('#shop-basket-submit input[name="terms"]:checked', () => post.terms = 1);

		new Ajax.Query()
			.url('/'+ shopAndDatePathname +'/:doUpdateBasket')
			.body(post)
			.fetch();
	}

	static doCancel(saleId) {

		const pathname = location.pathname;
		const shopAndDatePathname = pathname.substr(1, pathname.lastIndexOf('/') - 1);

		new Ajax.Query()
			.url('/'+ shopAndDatePathname +'/:doCancelSale')
			.body({
				sale: saleId
			})
			.fetch()
			.then(() => {
				this.deleteBasket(dateId);
			});
	}

	static checkBasketButtons(dateId) {

		const basket = this.getBasket(dateId);

		if(basket.sale !== null) {
			ref('basket-update', node => node.removeHide());
			qs('#shop-basket-submit input[name="terms"]', node => node.checked = true);
		} else {
			ref('basket-create', node => node.removeHide());
			qs('#shop-basket-submit input[name="terms"]', node => node.checked = false);
		}

	}

	/* Fonctions de mise à jour du panier et de son affichage */
	static update(dateId, productId, step, available) {

		let initialNumber = parseFloat(qs('[data-product="' + productId + '"][data-field="number"] > span:first-child').innerHTML);

		if(isNaN(initialNumber)) {
			initialNumber = 0;
		}

		const newNumber = Math.round((initialNumber + step) * 100) / 100;

		if(newNumber < 0.0) {
			return false;
		}

		if(available > -1 && available < newNumber) {
			return false;
		}

		let basket = this.getBasket(dateId);

		const isModifying = basket.sale !== null;

		if(parseFloat(newNumber) === 0.0) {
			delete basket.products[productId];
		} else {
			basket.products[productId] = {
				number: newNumber,
				numberOrdered: newNumber
			};
		}

		this.setBasket(dateId, basket);

		// Quand on est sur le résumé du panier, on reload le panier pour tout mettre à jour.
		if(qs('#shop-basket-summary')) {

			this.loadSummary(dateId, basket.sale, isModifying);

		} else {

			qs('[data-product="' + productId + '"][data-field="number"] > span:first-child').renderInner(newNumber);
			this.updateDisplay(dateId);

		}

		return false;

	}

	static calculateTotal(dateId) {

		qsa('.shop-product', product => product.dataset.has = 0);

		let basket = this.getBasket(dateId);
		let amount = 0;
		let articles = 0;

		Object.entries(basket.products).forEach(([productId, {number}]) => {

			const product = qs('.shop-product[data-id="'+ productId +'"]');

			if(number > 0) {

				if(product.length === 0) {
					return;
				}

				const price = parseFloat(product.dataset.price);

				articles++;
				amount += number * price;

				product.qs('.shop-product-number-decrease').classList.remove('shop-product-number-decrease-disabled');

				product.dataset.has = 1;

			} else {
				product.qs('.shop-product-number-decrease').classList.add('shop-product-number-decrease-disabled');
			}

		});

		const formatter = new Intl.NumberFormat('fr-FR', {
			style: 'currency',
			currency: 'EUR'
		});

		return [
			articles,
			formatter.format(amount.toFixed(2))
		];

	}

	static init(dateId, defaultJson) {

		const basket = this.getBasket(dateId, defaultJson);
		this.setBasket(dateId, basket);

		// Met à jour toutes les quantités.
		Object.entries(basket.products).forEach(([productId, {number}]) => {

			qs(
				'[data-product="' + productId + '"][data-field="number"] > span:first-child',
				node => node.renderInner(number),
				() => this.deleteBasket(dateId, productId)
			);

		});

		// Met à jour la barre du bas.
		this.updateDisplay(dateId);
	}

	static updateDisplay(dateId) {

		let totalPrice, totalArticles;
		[totalArticles, totalPrice] = this.calculateTotal(dateId);

		qs('#shop-basket-articles').renderInner(totalArticles + (totalArticles > 1 ? ' articles' : ' article'));
		qs('#shop-basket-price').renderInner(totalPrice);
		qs('#shop-basket').removeHide();

		if(totalArticles === 0) {
			qs('#shop-basket-next').style.display = 'none';
			qsa('.shop-basket-empty', node => node.style.display = 'none');
		} else {
			qs('#shop-basket-next').style.display = 'block';
			qsa('.shop-basket-empty', node => node.style.display = 'block');
		}

	}

	/* Fonctions appelées sur la page de résumé du panier */
	static loadSummary(dateId, saleId, isModifying) {

		const pathname = location.pathname;
		const shopAndDatePathname = pathname.substring(0, pathname.lastIndexOf('/'));

		let basket = this.getBasket(dateId);

		if(saleId !== basket.sale) { // Problème de consistence
			basket = this.emptyBasket(dateId);
		}

		const modifyingArg = isModifying ? '?modify=1' : '';

		new Ajax.Query()
			.url(shopAndDatePathname +'/:getBasket'+ modifyingArg)
			.body({
				products: basket.products,
				date: dateId,
			})
			.fetch()
			.then((json) => {

				const summary = qs('#shop-basket-summary');

				summary.renderInner(json.basketSummary);
				summary.dataset.price = json.basketPrice;

				if(json.basketPrice > 0) {


					qsa('.point-list .point-element', point => {

						if(point.dataset.orderMin !== '') {

							const orderMin = parseFloat(point.dataset.orderMin);

							point.qs('.point-order-min', badge => (json.basketPrice >= orderMin) ?
								badge.classList.add('point-badge-selected') :
								badge.classList.remove('point-badge-selected'));

						}

						if(point.dataset.shipping !== '') {

							const shipping = parseFloat(point.dataset.shipping);
							let shippingActive;

							if(point.dataset.shippingUntil !== '') {

								const shippingUntil = parseFloat(point.dataset.shippingUntil);
								shippingActive = (json.basketPrice < shippingUntil);

								point.qs('.point-shipping', badge => shippingActive ?
									badge.classList.remove('point-badge-selected') :
									badge.classList.add('point-badge-selected'));

							} else {
								shippingActive = true;
							}

						}

					});

				}

				this.loadPrice();

			});

	}

	static loadPrice() {

		let price = parseFloat(qs('#shop-basket-summary').dataset.price);

		qs('.point-list [name="shopPoint"]:checked', (input) => {

			const element = input.firstParent('.point-element');

			element.qs('.point-order-min', (orderMin) => {

				if(orderMin.classList.contains('point-badge-selected')) {
					qsa('.shop-basket-submit-order-error', node => node.classList.add('hide'));
					qsa('.shop-basket-submit-order-valid', node => node.classList.remove('hide'));
				} else {
					qsa('.shop-basket-submit-order-error', node => node.classList.remove('hide'));
					qsa('.shop-basket-submit-order-valid', node => node.classList.add('hide'));
				}

			}, () => {
				qsa('.shop-basket-submit-order-error', node => node.classList.add('hide'));
				qsa('.shop-basket-submit-order-valid', node => node.classList.remove('hide'));
			})

			element.qs('.point-shipping', (shipping) => {

				if(shipping.classList.contains('point-badge-selected') === false) {
					price += parseFloat(element.dataset.shipping);
				}

			})

		})

		qs('#shop-basket-price').innerHTML = money(price);

	}

	static deleteProduct(dateId, productId) {

		this.deleteBasket(dateId, productId);

		const basket = this.newBasket();
		this.loadSummary(dateId, basket.sale);

		return false;

	}

	static deleteBasket(dateId, productId) {

		let basket = this.getBasket(dateId);
		delete basket.products[productId];
		this.setBasket(dateId, basket);

		return false;

	}

	static empty(source, dateId) {

		if(this.getBasket(dateId).sale === null) {
			if(confirm(source.dataset.confirmNormal) === false) {
				return false;
			}
		} else {
			if(confirm(source.dataset.confirmModify) === false) {
				return false;
			}
		}

		this.emptyBasket(dateId);

		const location = document.location.href.removeArgument('modify');
		window.location.href = location;

		return false;

	}

	static newBasket() {
		return {
			products: {},
			createdAt: Date.now() / 1000,
			sale: null
		};

	}

	static emptyBasket(dateId) {

		const basket = this.newBasket();
		this.setBasket(dateId, basket);

		return basket;

	}

	static updateBasketFromSummary(dateId) {

		let basket = this.getBasket(dateId);
		let products = {};

		qsa('span.shop-product-number-value', span => {

			const productId = Number.parseInt(span.dataset.product);
			const available = Number.parseFloat(span.dataset.available);
			const unitPrice = Number.parseFloat(span.dataset.price);

			products[productId] = {
				numberOrdered: basket.products[productId]['numberOrdered'],
				number: available,
				unitPrice: unitPrice
			}

			if(available < basket.products[productId]['numberOrdered']) {
				span.classList.add('shop-product-number-value-error');
			}

		});

		basket.products = products;
		this.setBasket(dateId, basket);

		this.loadSummary(dateId, basket.sale);

	}

	static showWarnings(dateId) {

		let basket = this.getBasket(dateId);
		const products = basket.products;

		Object.entries(products).forEach(([id, product]) => {
			if(product.numberOrdered > product.number) {
				qs('#number-warning').classList.remove('hide');
				qs('span.shop-product-number-value[data-field="number"][data-product="' + id + '"]').classList.add('shop-product-number-value-error');
			}
		});

	}

	static doCreate(dateId) {

		const pathname = location.pathname;
		const shopAndDatePathname = pathname.substr(1, pathname.lastIndexOf('/') - 1);

		const basket = this.getBasket(dateId);

		const post = {
			products: basket.products,
			date: dateId,
		};

		qs('#shop-basket-point input[name="shopPoint"]:checked', node => post.shopPoint = node.value);
		qs('#shop-basket-submit input[name="terms"]:checked', () => post.terms = 1);

		new Ajax.Query()
			.url('/'+ shopAndDatePathname +'/:doCreateSale')
			.body(post)
			.fetch();

	}

	static checkExpiration() {

		const time = Date.now() / 1000;

		for(let key in localStorage) {

			if(key.startsWith(this.prefix) === false) {
				continue;
			}

			const basket = JSON.parse(localStorage.getItem(key));

			if(basket['createdAt'] < time - 86400 * 15) {
				delete(localStorage.removeItem(key))
			}

		}
	}

	static selectHome() {

		qs('#shop-basket-address-wrapper', wrapper => wrapper.classList.remove('hide'));

		if(qs('#shop-basket-address-form')) {
			qs('#shop-basket-address-form').classList.remove('hide');
			qs('#shop-basket-submit').classList.add('hide');
		}

		if(qs('#shop-basket-address-show')) {
			qs('#shop-basket-address-show').classList.remove('hide');
			qs('#shop-basket-submit').classList.remove('hide');
		}

		this.loadPrice();

	}

	static selectPlace() {

		qs('#shop-basket-address-wrapper', wrapper => wrapper.classList.add('hide'));
		qs('#shop-basket-submit').classList.remove('hide');

		this.loadPrice();

	}

}