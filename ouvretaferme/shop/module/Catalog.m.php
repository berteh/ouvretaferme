<?php
namespace shop;

abstract class CatalogElement extends \Element {

	use \FilterElement;

	private static ?CatalogModel $model = NULL;

	public static function getSelection(): array {
		return Catalog::model()->getProperties();
	}

	public static function model(): CatalogModel {
		if(self::$model === NULL) {
			self::$model = new CatalogModel();
		}
		return self::$model;
	}

	public static function fail(string|\FailException $failName, array $arguments = [], ?string $wrapper = NULL): bool {
		return \Fail::log('Catalog::'.$failName, $arguments, $wrapper);
	}

}


class CatalogModel extends \ModuleModel {

	protected string $module = 'shop\Catalog';
	protected string $package = 'shop';
	protected string $table = 'shopCatalog';

	public function __construct() {

		parent::__construct();

		$this->properties = array_merge($this->properties, [
			'id' => ['serial32', 'cast' => 'int'],
			'farm' => ['element32', 'farm\Farm', 'cast' => 'element'],
			'name' => ['text8', 'min' => 1, 'max' => 50, 'cast' => 'string'],
		]);

		$this->propertiesList = array_merge($this->propertiesList, [
			'id', 'farm', 'name'
		]);

		$this->propertiesToModule += [
			'farm' => 'farm\Farm',
		];

		$this->indexConstraints = array_merge($this->indexConstraints, [
			['farm']
		]);

	}

	public function select(...$fields): CatalogModel {
		return parent::select(...$fields);
	}

	public function where(...$data): CatalogModel {
		return parent::where(...$data);
	}

	public function whereId(...$data): CatalogModel {
		return $this->where('id', ...$data);
	}

	public function whereFarm(...$data): CatalogModel {
		return $this->where('farm', ...$data);
	}

	public function whereName(...$data): CatalogModel {
		return $this->where('name', ...$data);
	}


}


abstract class CatalogCrud extends \ModuleCrud {

	public static function getById(mixed $id, array $properties = []): Catalog {

		$e = new Catalog();

		if(empty($id)) {
			Catalog::model()->reset();
			return $e;
		}

		if($properties === []) {
			$properties = Catalog::getSelection();
		}

		if(Catalog::model()
			->select($properties)
			->whereId($id)
			->get($e) === FALSE) {
				$e->setGhost($id);
		}

		return $e;

	}

	public static function getByIds(mixed $ids, array $properties = [], mixed $sort = NULL, mixed $index = NULL): \Collection {

		if(empty($ids)) {
			return new \Collection();
		}

		if($properties === []) {
			$properties = Catalog::getSelection();
		}

		if($sort !== NULL) {
			Catalog::model()->sort($sort);
		}

		return Catalog::model()
			->select($properties)
			->whereId('IN', $ids)
			->getCollection(NULL, NULL, $index);

	}

	public static function getCreateElement(): Catalog {

		return new Catalog(['id' => NULL]);

	}

	public static function create(Catalog $e): void {

		Catalog::model()->insert($e);

	}

	public static function update(Catalog $e, array $properties): void {

		$e->expects(['id']);

		Catalog::model()
			->select($properties)
			->update($e);

	}

	public static function updateCollection(\Collection $c, Catalog $e, array $properties): void {

		Catalog::model()
			->select($properties)
			->whereId('IN', $c)
			->update($e->extracts($properties));

	}

	public static function delete(Catalog $e): void {

		$e->expects(['id']);

		Catalog::model()->delete($e);

	}

}


class CatalogPage extends \ModulePage {

	protected string $module = 'shop\Catalog';

	public function __construct(
	   ?\Closure $start = NULL,
	   \Closure|array|null $propertiesCreate = NULL,
	   \Closure|array|null $propertiesUpdate = NULL
	) {
		parent::__construct(
		   $start,
		   $propertiesCreate ?? CatalogLib::getPropertiesCreate(),
		   $propertiesUpdate ?? CatalogLib::getPropertiesUpdate()
		);
	}

}
?>