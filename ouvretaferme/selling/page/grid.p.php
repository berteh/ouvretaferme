<?php
new \selling\GridPage()
	->applyElement(function($data, \selling\Grid $e) {

		\selling\Product::model()
			->select('farm', 'status')
			->get($e['product']);

		$e['product']->validate('canWrite');

	})
	->quick(['price', 'packaging']);
?>
