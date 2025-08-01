<?php
new \journal\OperationPage(
	function($data) {
		\user\ConnectionLib::checkLogged();

		$data->eFarm->validate('canManage');
		\company\CompanyLib::connectSpecificDatabaseAndServer($data->eFarm);
	}
)
	->quick(['document', 'description', 'amount', 'comment'], [], ['canQuickUpdate'])
	->create(function($data) {

		if(get_exists('account') === TRUE) {
			$eAccount = \account\AccountLib::getByIdWithVatAccount(GET('account', 'int'));
		} elseif(get_exists('accountPrefix') === TRUE) {
			$eAccount = \account\AccountLib::getByPrefixWithVatAccount(GET('accountPrefix', 'int'));
		} else {
			$eAccount = new \account\Account();
		}

		if(get_exists('cashflow') === TRUE) {
			$eCashflow = \bank\CashflowLib::getById(GET('cashflow', 'int'));
		} else {
			$eCashflow = new \bank\Cashflow();
		}
		// Apply default bank account label if the class is a bank account class.
		$label = '';
		if(get_exists('accountLabel') and mb_strlen(GET('accountLabel') > 0)) {
			$label = GET('accountLabel');
		} elseif($eAccount->exists() === TRUE and $eAccount['class'] === \Setting::get('account\bankAccountClass')) {
			$eAccountBank = \bank\BankAccountLib::getDefaultAccount();
			if($eAccountBank->exists() === TRUE) {
				$label = $eAccountBank['accountLabel'];
			}
		}

		// Third party
		$thirdParty = account\ThirdPartyLib::getById(GET('thirdParty', 'int'));

		$data->e->merge([
			'farm' => $data->eFarm['id'],
			'account' => $eAccount,
			'accountLabel' => $label,
			'vatRate' => $eAccount['vatRate'] ?? 0,
			'thirdParty' => $thirdParty,
			'date' => GET('date'),
			'description' => GET('description'),
			'document' => GET('document'),
			'type' => GET('type'),
			'amount' => GET('amount', 'float'),
			'cashflow' => $eCashflow,
		]);

		$data->eFinancialYear = \account\FinancialYearLib::selectDefaultFinancialYear();

		throw new ViewAction($data);

	})
	->post('addOperation', function($data) {

		$data->index = POST('index');
		$data->eFinancialYear = \account\FinancialYearLib::selectDefaultFinancialYear();

		$eThirdParty = post_exists('thirdParty') ? \account\ThirdPartyLib::getById(POST('thirdParty')) : new \account\ThirdParty();
		$data->eOperation = new \journal\Operation(['account' => new \account\Account(), 'thirdParty' => $eThirdParty]);

		throw new ViewAction($data);

	})
	->post('doCreate', function($data) {

		$fw = new FailWatch();

		\journal\Operation::model()->beginTransaction();

		$accounts = post('account', 'array', []);

		if(count($accounts) === 0) {
			Fail::log('Operation::allocate.accountsCheck');
		}

		$cOperation = \journal\OperationLib::prepareOperations($_POST, new \journal\Operation());

		if($cOperation->empty() === TRUE) {
			\Fail::log('Operation::allocate.noOperation');
		}

		$fw->validate();

		\journal\Operation::model()->commit();

		throw new ReloadAction('journal', $cOperation->count() > 1 ? 'Operation::createdSeveral' : 'Operation::created');

	})
	->create(action: function($data) {

		// Third party
		$thirdParty = account\ThirdPartyLib::getById(GET('thirdParty', 'int'));

		$data->e->merge([
			'farm' => $data->eFarm['id'],
			'thirdParty' => $thirdParty,
			'date' => GET('date'),
			'description' => GET('description'),
			'document' => GET('document'),
			'type' => GET('type'),
			'amount' => GET('amount', 'float'),
		]);

		$data->eFinancialYear = \account\FinancialYearLib::selectDefaultFinancialYear();

		$data->cBankAccount = \bank\BankAccountLib::getAll();

		throw new ViewAction($data);
	}, page: 'createPayment')
	->post('doCreatePayment', function($data) {

		$fw = new FailWatch();

		\journal\Operation::model()->beginTransaction();

		$cOperation = \journal\OperationLib::preparePayments($_POST);

		if($cOperation === NULL or $cOperation->empty() === TRUE) {
			\Fail::log('Operation::payment.noOperation');
		}

		$fw->validate();

		\journal\Operation::model()->commit();

		throw new ReloadAction('journal', 'Operation::payment.created');

	});

new Page(
	function($data) {
		\user\ConnectionLib::checkLogged();

		$data->eFarm->validate('canManage');
		\company\CompanyLib::connectSpecificDatabaseAndServer($data->eFarm);
	})
	->post('getWaiting', function($data) {

		$data->cOperation = \journal\OperationLib::getWaiting(POST('thirdParty', 'account\ThirdParty'));

		throw new ViewAction($data);

	});

new \journal\OperationPage(
	function($data) {
		\user\ConnectionLib::checkLogged();
		$data->eFarm->validate('canManage');
		\company\CompanyLib::connectSpecificDatabaseAndServer($data->eFarm);
		$data->eOperation = \journal\OperationLib::getById(REQUEST('id', 'int'))->validate('canDelete');
	}
)
->post('doDelete', function($data) {

	\journal\OperationLib::delete($data->eOperation);

	throw new ReloadAction('journal', 'Operation::deleted');
});
?>
