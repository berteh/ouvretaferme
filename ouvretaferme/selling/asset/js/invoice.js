class Invoice {

	static toggleSelection(target) {

		CheckboxField.all(target, '[name^="batch[]"]', undefined, 'table');

		this.changeSelection(target);

	}

	static toggleDaySelection(target) {

		CheckboxField.all(target, '[name^="batch[]"]', undefined, 'tbody');

		this.changeSelection(target);

	}

	static changeSelection() {

		return Batch.changeSelection(function(selection) {

			qsa(
				'.batch-menu-send',
				selection.filter('[data-batch~="not-sent"]').length > 0 ?
					node => node.hide() :
					node => {
						node.removeHide();
					}
			);

			return 1;

		});

	}

}