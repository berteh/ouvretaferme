<?php
namespace series;

class SeriesUi {

	public function __construct() {

		\Asset::css('series', 'series.css');
		\Asset::js('series', 'series.js');

	}

	public static function name(Series $eSeries): string {

		$eSeries->expects(['id', 'name', 'mode']);

		$name = encode($eSeries['name']);

		if($eSeries['mode'] === Series::GREENHOUSE) {
			$name .= \Asset::icon('greenhouse', ['style' => 'margin-left: 0.5rem']);
		} else if($eSeries['mode'] === Series::MIX) {
			$name .= \Asset::icon('mix', ['style' => 'margin-left: 0.5rem']);
		}

		return $name;

	}

	public static function link(Series $eSeries, bool $newTab = FALSE): string {

		$eSeries->expects(['id', 'name']);

		return '<a href="'.self::url($eSeries).'" '.($newTab ? 'target="_blank"' : '').'>'.self::name($eSeries).'</a>';

	}

	public static function url(Series $eSeries): string {

		$eSeries->expects(['id']);

		return '/serie/'.$eSeries['id'];

	}

	public function getDuplicateName(Series $eSeries): string {
		return $eSeries['name'].' '.s("(copie)");
	}

	public static function getPanelHeader(Series $eSeries): string {

		$eSeries->expects(['name', 'season']);

		$h = '<div class="panel-header-subtitle">';
			$h .= '<span class="hide-xs-down">'.s("Saison {season}", ['season' => $eSeries['season']]).' '.\Asset::icon('chevron-right').'</span> ';
			$h .= s("Série {name}", ['name' => self::link($eSeries)]);
		$h .= '</div>';

		return $h;

	}

	public function getComment(Series $eSeries): string {

		$h = '<div id="series-comment" class="util-block">';

		if($eSeries['comment'] !== NULL) {

			$h .= '<div class="series-comment-title">';
				$h .= '<h4>'.s("Notes").'</h4>';
				$h .= '<div>';
					$h .= '<a data-ajax="/series/series:updateComment" post-id="'.$eSeries['id'].'">'.\Asset::icon('pencil-fill').'</a>';
				$h .= '</div>';
			$h .= '</div>';

			$h .= (new \editor\EditorUi())->value($eSeries['comment']);

		}

		$h .= '</div>';

		return $h;

	}

	public function getCommentField(Series $eSeries): string {

		$form = new \util\FormUi();

		$h = '<div id="series-comment" class="util-block">';

			$h .= '<h4>'.s("Notes").'</h4>';

			$h .= $form->openAjax('/series/series:doUpdateComment');

				$h .= $form->hidden('id', $eSeries['id']);

				$h .= $form->dynamicField($eSeries, 'comment');

				$h .= '<div class="series-comment-submit">';
					$h .= $form->submit(s("Valider"), ['class' => 'btn btn-secondary']);
					$h .= $form->button(s("Annuler"), ['class' => 'btn', 'data-ajax' => '/series/series:restoreComment', 'post-id' => $eSeries['id']]);
				$h .= '</div>';

			$h .= $form->close();

		$h .= '</div>';

		return $h;

	}

	public function updatePlace(Series $eSeries, \Collection $cPlace): string {

		$h = '<div class="crop-item" id="series-soil">';

			$h .= '<div class="crop-item-header">';
				$h .= '<div class="crop-item-title">';
					$h .= \plant\PlantUi::getSoilVignette('3rem');
					$h .= '<h2 class="series-soil-title">';
						$h .= s("Assolement");
					$h .= '</h2>';
					if(
						$eSeries->canWrite() and
						$eSeries['status'] === Series::OPEN and
						$cPlace->notEmpty()
					) {
						$h .= '<div>';
							$h .= '<a href="/series/place:update?series='.$eSeries['id'].($eSeries['mode'] === Series::GREENHOUSE ? '&mode='.Series::GREENHOUSE : '').'" class="btn btn-color-primary">'.\Asset::icon('gear-fill').'</a>';
						$h .= '</div>';
					}
				$h .= '</div>';
				$h .= '<div class="crop-item-soil-infos">';
				$h .= [
					Series::BED => s("Planches {value} cm", $eSeries['bedWidth']),
					Series::BLOCK => s("Surface libre"),
				][$eSeries['use']];

				if($eSeries['alleyWidth'] !== NULL) {
					$h .= ' / '.s("Passe-pieds {value} cm", $eSeries['alleyWidth']);
				}

				switch($eSeries['use']) {

					case Series::BED;
						if($eSeries['lengthTarget']) {
							$h .= ' / '.$eSeries->quick('lengthTarget', s("Objectif de {lengthTarget} mL", $eSeries));
						}
						if($eSeries['length']) {
							$h .= ' / '.s("Actuellement {length} mL", $eSeries);
						}
						break;

					case Series::BLOCK;
						if($eSeries['areaTarget']) {
							$h .= ' / '.$eSeries->quick('areaTarget', s("Objectif de {areaTarget} m²", $eSeries));
						}
						if($eSeries['area']) {
							$h .= ' / '.s("Actuellement {area} m²", $eSeries);
						}
						break;

				}

				$h .= '</div>';
			$h .= '</div>';

			$h .= '<div class="crop-item-body">';
				if($cPlace->empty()) {

					if(
						$eSeries->canWrite() and
						$eSeries['status'] === Series::OPEN
					) {
						$h .= '<div class="series-soil-empty">';
							$h .= '<a href="/series/place:update?series='.$eSeries['id'].($eSeries['mode'] === Series::GREENHOUSE ? '&mode='.Series::GREENHOUSE : '').'" class="btn btn-outline-primary">'.\Asset::icon('plus-circle').' '.s("Définir l'assolement pour cette série").'</a>';
						$h .= '</div>';
					} else {
						$h .= '<div class="series-soil-empty">';
							$h .= s("L'assolement n'a pas été défini.");
						$h .= '</div>';
					}

				} else {
					$h .= $this->getPlace('series', $eSeries, $cPlace);
				}
			$h .= '</div>';

		$h .= '</div>';

		return $h;

	}

	public function displayImport(\farm\Farm $eFarm, int $nSeries, \Collection $cSeriesPerennial, bool $firstSeries, int $currentSeason): string {

		if($eFarm->canManage() === FALSE) {
			return '';
		}

		$previousSeason = $currentSeason - 1;

		$h = '';

		if($nSeries === 0) {

			if($firstSeries) {

				$h .= '<br/>';
				$h .= '<h2>'.s("Ajouter une première série dans le plan de culture").'</h2>';

				$h .= '<p class="util-block-help">';
					$h .= s("Vous êtes sur la page qui permet de créer des séries pour votre plan de culture. Vous êtes sur le point de créer une première série pour la saison {value}. Si vous souhaitez créer une série pour une autre année, utilisez le menu déroulant ci-dessus pour changer de saison.", $currentSeason);
				$h .= '</p>';

				$h .= '<div class="util-block-gradient">';
					$h .= $this->createFrom($eFarm, $currentSeason)->body;
				$h .= '</div>';

			} else {

				$h .= '<div class="util-block-help">';
					$h .= '<h4>'.s("Vous n'avez pas encore créé de série sur la saison {current} !", ['current' => $currentSeason]).'</h4>';
					$h .= '<p>'.s("Pour démarrer, vous pouvez <link>créer une nouvelle série</link> dès maintenant pour cette saison.", ['link' => '<a data-get="/series/series:createFrom?farm='.$eFarm['id'].'&season='.$currentSeason.'" data-ajax-class="Ajax.Query">']).'</p>';

					if($eFarm['seasonFirst'] < $currentSeason) {
						$h .= '<p>'.s("Vous pouvez également créer facilement votre plan de culture {current} en dupliquant les séries de productions annuelles qui ont bien fonctionné lors des saisons précédentes. Pour cela, retournez simplement sur la planification de la saison de votre choix, puis cochez vos séries préférées et enfin dupliquez-les !", ['current' => $currentSeason]).'</p>';
						if($previousSeason === $eFarm['seasonFirst']) {
							$h .= '<a href="'.\farm\FarmUi::urlCultivationSeries($eFarm, \farm\Farmer::SERIES, season: $previousSeason).'" class="btn btn-secondary">'.s("Revenir sur la planification {previous}",  ['previous' => $previousSeason]).'</a>';
						} else {
							$h .= '<a data-dropdown="bottom-start" class="dropdown-toggle btn btn-secondary">'.s("Revenir sur la planification d'une autre saison").'</a>';
							$h .= '<div class="dropdown-list">';
								for($season = $currentSeason - 1, $count = 1; $season >= $eFarm['seasonFirst'], $count <= 3; $season--, $count++) {
									$h .= '<a href="'.\farm\FarmUi::urlCultivationSeries($eFarm, \farm\Farmer::SERIES, season: $season).'" class="btn btn-secondary">'.s("Saison {value}", $season).'</a>';
								}
							$h .= '</div>';
						}
					}
				$h .= '</div>';

			}

		}

		if($cSeriesPerennial->notEmpty()) {

			$h .= '<div class="util-block">';

				$h .= '<h3>'.s("Que voulez-vous faire des productions pérennes de la saison {previous} ?", ['previous' => $previousSeason]).'</h3>';

				if($cSeriesPerennial->notEmpty()) {

					$h .= '<div class="util-overflow-md">';
						$h .= '<table class="tr-even">';

							$h .= '<thead>';
								$h .= '<tr>';
									$h .= '<th colspan="2">'.s("Série").'</th>';
									$h .= '<th>'.s("Démarrée").'</th>';
									$h .= '<th colspan="2">'.s("Actions").'</th>';
								$h .= '</tr>';
							$h .= '</thead>';
							$h .= '<tbody>';

								foreach($cSeriesPerennial as $eSeries) {

									$h .= '<tr>';

										$h .= '<td>'.SeriesUi::link($eSeries).'</td>';
										$h .= '<td>';
											$h .= '<div class="series-import-cultivation">';

												foreach($eSeries['cCultivation'] as $eCultivation) {

													$h .= '<div>';
														$h .= \plant\PlantUi::getVignette($eCultivation['plant'], '2rem').' '.encode($eCultivation['plant']['name']);
													$h .= '</div>';

												}

											$h .= '</div>';
										$h .= '</td>';
										$h .= '<td>';
											$h .= s("Saison {value}", $eSeries['season'] - $eSeries['perennialSeason'] + 1);
											$h .= ' &bull; ';
											$h .= s("{value} saison", \util\TextUi::th($eSeries['perennialSeason'] + 1));
										$h .= '</td>';
										$h .= '<td class="td-min-content">';
											$h .= '<a data-ajax="/series/series:perennialContinued" post-id="'.$eSeries['id'].'" class="btn btn-success">'.s("Continuer cette saison").'</a>';
										$h .= '</td>';
										$h .= '<td>';
											$h .= '<a data-ajax="/series/series:perennialFinished" post-id="'.$eSeries['id'].'" class="btn btn-danger" data-confirm="'.s("Confirmez-vous que cette série ne sera pas cultivée pour la saison {value} ?", $eSeries['season'] + 1).'">'.s("Arrêter").'</a>';
										$h .= '</td>';

									$h .= '</tr>';

								}

							$h .= '</tbody>';

						$h .= '</table>';
					$h .= '</div>';

				}

			$h .= '</div>';

		}

		return $h;

	}

	public function getPlace(string $source, Series|Task $e, \Collection $cPlace): string {

		$use = ($source === 'task') ? Series::BED : $e['use'];

		$h = '<div class="series-soil-grid series-soil-grid-'.$source.'">';

			$h .= '<div class="util-grid-header">'.s("Parcelle").'</div>';
			$h .= '<div class="util-grid-header">'.s("Bloc").'</div>';
			$h .= '<div class="util-grid-header">';
				if($use === Series::BED) {
					$h .= s("Planche");
				}
			$h .= '</div>';
			if($source === 'series') {
				$h .= '<div class="util-grid-header text-end">'.s("Utilisation").'</div>';
				$h .= '<div class="util-grid-header text-end" style="grid-column: span 2">'.s("Surface").'</div>';
			}
			$h .= '<div class="util-grid-header">'.\Asset::icon('greenhouse').'</div>';

			foreach($cPlace as $ePlace) {

				$h .= '<div class="series-soil-grid-zone">'.encode($ePlace['zone']['name']).'</div>';
				$h .= '<div class="series-soil-grid-plot">'.encode($ePlace['plot']['name']).'</div>';
				$h .= '<div class="series-soil-grid-bed">';
					if($use === Series::BED) {

						if($ePlace['bed']['name'] !== NULL) {
							$h .= encode($ePlace['bed']['name']);
						} else {
							$h .= s("Temporaire");
						}

						$h .= ' '.$ePlace['bed']->getGreenhouseIcon();

					}
				$h .= '</div>';

				if($source === 'series') {

					$h .= '<div class="series-soil-grid-use">';
						$h .= match($use) {
							Series::BED => ($ePlace['bed']['length'] !== NULL) ? s("{value} %", round($ePlace['length'] / $ePlace['bed']['length'] * 100)) : '',
							Series::BLOCK => s("{value} %", round($ePlace['area'] / $ePlace['bed']['area'] * 100))
						};
					$h .= '</div>';
					$h .= '<div class="series-soil-grid-area">';

						if($use === Series::BED) {
							$h .= '<b class="util-unit">'.s("{length} mL x {width} cm", $ePlace).'</b>';
						}

					$h .= '</div>';
					$h .= '<div class="series-soil-grid-size util-unit">';
						$h .= match($use) {
							Series::BED => s("({value} m²)", round($ePlace['area'])),
							Series::BLOCK => '<b>'.s("({value} m²)", round($ePlace['area'])).'</b>'
						};
					$h .= '</div>';

				}

				$h .= '<div class="series-soil-grid-greenhouse">';

					if($ePlace['bed']['greenhouse']->empty() or $ePlace['bed']['name'] === NULL) {
						$h .= '-';
					} else {
						$h .= encode($ePlace['bed']['greenhouse']['name']);
					}

				$h .= '</div>';

			}

			if(
				$source === 'series' and
				$cPlace->count() > 1
			) {

				$h .= '<div style="grid-column: span 4"></div>';

					if($use === Series::BED) {
						$h .= '<div class="series-soil-grid-area series-soil-grid-total">';
							$h .= '<b class="util-unit">'.s("{length} mL", $e).'</b>';
						$h .= '</div>';
					} else {
						$h .= '<div></div>';
					}

				$h .= '<div class="series-soil-grid-size util-unit series-soil-grid-total">';
					$h .= match($use) {
						Series::BED => s("({value} m²)", round($e['area'])),
						Series::BLOCK => '<b>'.s("({value} m²)", round($e['area'])).'</b>'
					};
				$h .= '</div>';

				switch($use) {

					case Series::BED;
						if($e['lengthTarget']) {
							$h .= '<div class="series-soil-grid-total">';
								$h .= s("{value} de l'objectif", \util\TextUi::pc($e['length'] / $e['lengthTarget'] * 100));
							$h .= '</div>';
						} else {
							$h .= '<div></div>';
						}
						break;

					case Series::BLOCK;
						if($e['areaTarget']) {
							$h .= '<div class="series-soil-grid-total">';
								$h .= s("{value} de l'objectif", \util\TextUi::pc($e['area'] / $e['areaTarget'] * 100));
							$h .= '</div>';
						} else {
							$h .= '<div></div>';
						}
						break;

				}

			}

		$h .= '</div>';

		return $h;

	}

	public function createFrom(\farm\Farm $eFarm, int $season): \Panel {

		$form = new \util\FormUi();

		$eCultivation = new Cultivation([
			'farm' => $eFarm
		]);

		$eSeries = new Series([
			'farm' => $eFarm,
			'season' => $season
		]);

		$h = '<div id="series-create-from">';

			$h .= $form->openAjax('/series/series:createFromPlant', ['method' => 'get', 'data-ajax-class' => 'Ajax.Query']);

				$h .= $form->hidden('farm', $eFarm['id']);

				$h .= $form->dynamicGroup($eSeries, 'season', function(\PropertyDescriber $d) use ($eFarm, $season) {

					$d->label = s("Pour la saison");

					if($season < date('Y')) {
						$d->after = \util\FormUi::info(s("Vous vous apprêtez à créer une série pour une saison déjà passée. Vous pouvez corriger votre choix si vous le souhaitez."), class: 'color-danger');
					}

					if($season === (int)date('Y') and date('m') >= \Setting::get('farm\newSeason')) {

						$nextSeason = $season + 1;

						$after = '<span class="color-warning">'.s("Vous vous apprêtez à créer une série pour la saison en cours alors que l'année est presque terminée.").'</span> ';

						if($nextSeason > $eFarm['seasonLast']) {

							$after .= '<span class="color-warning">'.s("Vous pouvez créer la saison {value} dès maintenant pour ajouter des séries sur la saison à venir.", $nextSeason).'</span>';

							$after .= '<br/><a data-ajax="/farm/farm:doSeasonLast" post-id="'.$eFarm['id'].'" post-increment="1" class="btn btn-secondary">';
								$after .= s("Ajouter la saison {year}", ['year' => $nextSeason]);
							$after .= '</a> ';

						} else {
							$after .= '<span class="color-warning">'.s("Vous pouvez corriger votre choix si vous le souhaitez.").'</span>';
						}

						$d->after = \util\FormUi::info($after);

					}

					$d->attributes['onchange'] = 'Series.selectCreateSeason(this)';

				});
				$h .= $form->group(content: '<h3 class="mb-0 mt-1">'.s("Créer la série").'</h3>');

				$h .= $form->group(
					s("À partir d'une espèce"),
					$form->dynamicField($eCultivation, 'plant', function($d) use ($eFarm) {
						$d->autocompleteDispatch = '#series-create-from-plant';
						$d->attributes = [
							'data-autocomplete-select' => 'submit'
						];
					})
				);

			$h .= $form->close();

			$h .= $form->openAjax('/series/series:createFromSequence', ['method' => 'get', 'data-ajax-class' => 'Ajax.Query']);

				$h .= $form->hidden('farm', $eFarm['id']);
				$h .= $form->hidden('season', $season);
				$h .= $form->group(
					s("À partir d'un itinéraire technique"),
					$form->dynamicField($eCultivation, 'sequence', function($d) use ($eFarm) {
						$d->autocompleteBody = ['farm' => $eFarm['id']];
						$d->attributes = [
							'data-autocomplete-select' => 'submit'
						];
					})
				);

			$h .= $form->close();

		$h .= '</div>';

		return new \Panel(
			id: 'panel-series-create',
			title: s("Ajouter une série"),
			body: $h,
			attributes: ['class' => 'panel-series-create']
		);

	}

	public function createFromPlant(\farm\Farm $eFarm, int $season, Series $eSeries, Cultivation $eCultivation, \plant\Plant $ePlant, \Collection $cAction): \Panel {

		$form = new \util\FormUi([
			'firstColumnSize' => 40
		]);

		$index = 0;

		$h = $form->openAjax('/series/series:doCreate?season='.$season.'&farm='.$eFarm['id'], ['id' => 'series-create-plant', 'data-cycle' => $eSeries['cycle']]);

		$h .= $form->hidden('farm', $eFarm['id']);
		$h .= $form->hidden('index', $index);

		$h .= '<div class="series-create-use">';

			$h .= $this->getSeasonField($form, $eSeries);
			$h .= $this->getNameField($form, $eSeries);

			$h .= $form->dynamicGroups($eSeries, ['cycle', 'perennialLifetime', 'mode', 'use']);
			$h .= $this->getBlockFields($form, $eSeries);
		$h .= '</div>';

		$h .= '<div id="series-create-plant-list">';
			$h .= $this->addFromPlant($eSeries, $eCultivation, $ePlant, $index, $cAction, $form);
		$h .= '</div>';

		$h .= '<div id="series-create-add-plant" class="util-block-gradient">';
			$h .= $form->group(
				s("Ajouter une autre production").
				'<div class="util-helper">'.s("Ajoutez une autre production à cette série si vous souhaitez associer plusieurs cultures ensemble.").'</div>',
				$form->dynamicField($eCultivation, 'plant', function($d) {
					$d->placeholder = s("Ajouter une autre plante");
					$d->name = 'newPlant';
					$d->autocompleteDispatch = '#series-create-add-plant';
				})
			);
		$h .= '</div>';

		$h .= '<div class="series-submit">';

		$h .= $form->group(
			content: $form->submit(s("Créer la série"))
		);

		$h .= '</div>';

		$h .= $form->close();

		return new \Panel(
			id: 'panel-series-create',
			title: s("Ajouter une série"),
			documentTitle: s("Ajouter une série pour {name}", ['name' => $eFarm['name']]),
			body: $h,
			attributes: ['class' => 'panel-series-create']
		);

	}

	protected function getBlockFields(\util\FormUi $form, Series $eSeries): string {

		$h = $form->dynamicGroup($eSeries, 'areaTarget', function($d) use ($eSeries) {
			$d->group = ($eSeries['use'] !== Series::BLOCK ? ['class' => 'hide'] : []);
		});

		$h .= $form->dynamicGroup($eSeries, 'lengthTarget', function($d) use ($eSeries) {
			$d->group = ($eSeries['use'] !== Series::BED ? ['class' => 'hide'] : []);
		});
		$h .= $form->dynamicGroup($eSeries, 'bedWidth', function($d) use ($eSeries) {
			$d->group = ($eSeries['use'] !== Series::BED ? ['class' => 'hide'] : []);
		});
		$h .= $form->dynamicGroup($eSeries, 'alleyWidth', function($d) use ($eSeries) {
			$d->group = ($eSeries['use'] !== Series::BED ? ['class' => 'hide'] : []);
		});

		return $h;

	}

	public function addFromPlant(Series $eSeries, Cultivation $eCultivation, \plant\Plant $ePlant, int $index, \Collection $cAction, ?\util\FormUi $form = NULL): string {

		$eSeries->expects(['use', 'cycle', 'season']);

		if($form === NULL) {

			$form = new \util\FormUi([
				'firstColumnSize' => 50
			]);

		}

		$suffix = '['.$index.']';

		$h = '<div class="series-create-plant series-write-plant">';

			$h .= $form->hidden('plant'.$suffix, $ePlant['id']);

			$h .= '<div class="util-title">';

				$h .= '<div class="series-create-plant-title" data-plant-name="'.encode($ePlant['name']).'">';
					$h .= \plant\PlantUi::getVignette($ePlant, '3rem');
					$h .= '<h4>'.\plant\PlantUi::link($ePlant).'</h4>';
				$h .= '</div>';

				$h .= '<div class="series-create-plant-delete hide">';
					$h .= '<a onclick="Series.deletePlant(this)" class="btn btn-outline-primary">'.\Asset::icon('trash').'</a>';
				$h .= '</div>';

			$h .= '</div>';

			$h .= (new CultivationUi())->getFieldsCreate($form, $eSeries['use'], $eCultivation, $cAction, $suffix);

		$h .= '</div>';

		return $h;

	}

	public function createFromSequence(\farm\Farm $eFarm, int $season, \production\Sequence $eSequence, \Collection $cCultivation, \Collection $cFlow, array $events): \Panel {

		$eSeries = new Series([
			'season' => $season,
			'farm' => $eFarm,
			'name' => $eSequence['name'],
			'nameDefault' => $eSequence['name'],
			'nameAuto' => TRUE
		]);

		$form = new \util\FormUi([
			'firstColumnSize' => 40
		]);

		$h = '';

		$h .= $form->openAjax('/series/series:doCreate?farm='.$eFarm['id'], ['id' => 'series-create-sequence']);

		$h .= $form->hidden('farm', $eFarm['id']);
		$h .= $form->hidden('sequence', $eSequence['id']);

		$h .= '<p class="util-info">'.s("Cette série a été initialisée à partir des données de l'itinéraire technique {name}.", ['name' => \production\SequenceUi::link($eSequence)]).'</p>';

		$h .= '<div class="series-create-use">';

		$h .= $this->getSeasonField($form, $eSeries);
		$h .= $this->getNameField($form, $eSeries);

		$h .= $form->group(
			SeriesUi::p('cycle')->label,
			$form->fake(SeriesUi::p('cycle')->values[$eSequence['cycle']])
		);
		$h .= $form->dynamicGroup($eSequence, 'perennialLifetime');
		$h .= $form->dynamicGroup($eSequence, 'mode');
		$h .= $form->dynamicGroup($eSequence, 'use');

		$h .= $this->getBlockFields($form, new Series([
			'use' => $eSequence['use'],
			'bedWidth' => $eSequence['bedWidth'] ?? $eFarm['defaultBedWidth'],
			'alleyWidth' => $eSequence['alleyWidth'] ?? $eFarm['defaultAlleyWidth']
		]));

		$h .= '</div>';

		$h .= '<h3>'.p("Espèce", "Espèces", $cCultivation->count()).'</h3>';

		$h .= '<div id="series-create-plant-list">';

		$index = 0;
		$indexes = [];

		foreach($cCultivation as $eCultivation) {

			$ePlant = $eCultivation['plant'];
			$eCrop = $eCultivation['crop'];

			$indexes[$eCrop['id']] = $index;
			$suffix = '['.$index.']';

			$h .= $form->hidden('plant'.$suffix, $ePlant);
			$h .= $form->hidden('crop'.$suffix, $eCrop);

			$h .= '<div class="series-create-plant series-write-plant">';
				$h .= (new CultivationUi())->getCropTitle($eFarm, $ePlant);
				$h .= (new CultivationUi())->getFieldsCreate($form, $eSequence['use'], $eCultivation, new \Collection(), $suffix);
			$h .= '</div>';

			$index++;

		}

		$h .= '</div>';

		if($cFlow->notEmpty()) {

			$h .= '<h3>'.s("Rappel des interventions").'</h3>';

			if($eSequence['cycle'] === \production\Sequence::ANNUAL) {

				$eFlowFirst = $cFlow->first();
				$startYear = ($eFlowFirst['yearOnly'] ?? $eFlowFirst['yearStart']);
				$startWeek = ($eFlowFirst['weekOnly'] ?? $eFlowFirst['weekStart']);

				$h .= '<div id="series-create-tasks" data-farm="'.$eFarm['id'].'" data-season="'.$season.'" data-sequence="'.$eSequence['id'].'">';
					$h .= $this->getTasksFromSequence($season, $eSequence, $events, $startYear, $startWeek);
				$h .= '</div>';

			} else {
				$h .= (new \production\FlowUi())->getTimeline($eSequence, $events, FALSE);
			}

		}

		$h .= '<div class="series-submit">';
			$h .= $form->submit(s("Créer la série"));
		$h .= '</div>';

		$h .= $form->close();


		return new \Panel(
			id: 'panel-series-create',
			title: s("Ajouter une série"),
			documentTitle: s("Ajouter une série pour {name}", ['name' => $eFarm['name']]),
			body: $h,
			attributes: ['class' => 'panel-series-create']
		);

	}

	public function getTasksFromSequence(int $season, \production\Sequence $eSequence, array $events, int $startYear, int $startWeek): string {

		$form = new \util\FormUi();

		$h = '';

		if($eSequence['cycle'] === \production\Sequence::ANNUAL) {

			$h .= '<div class="task-select-start">';

				$h .= $form->group(
					s("Année de démarrage"),
					$form->select('startYear', [0 => $season, -1 => $season - 1], $startYear, ['mandatory' => TRUE])
				);

				$weeks = [];
				for($week = 1; $week <= 52; $week++) {
					$weeks[$week] = s("Semaine {value} → {interval}", ['value' => $week, 'interval' => \util\DateUi::weekToDays($season + $startYear.'-W'.sprintf('%02d', $week), withYear: FALSE)]);
				}

				$h .= $form->group(
					s("Semaine de démarrage"),
					$form->select('startWeek', $weeks, $startWeek, ['mandatory' => TRUE])
				);

			$h .= '</div>';

		}

		$h .= (new \production\FlowUi())->getTimeline($eSequence, $events, FALSE, $startYear + $season);

		return $h;

	}

	public function update(Series $eSeries): \Panel {

		$form = new \util\FormUi();

		$h = '';

		$h .= $form->openAjax('/series/series:doUpdate', ['id' => 'series-update']);

			$h .= $form->hidden('id', $eSeries['id']);

			if($eSeries['cycle'] === Series::ANNUAL) {
				$h .= $form->dynamicGroup($eSeries, 'season');
			}

			$h .= $form->dynamicGroup($eSeries, 'name');

			if($eSeries['cSequence']->notEmpty()) {

				$h .= $form->dynamicGroup(new Cultivation([
					'sequence' => $eSeries['sequence']
				]), 'sequence', function($d) use ($eSeries) {

					$body = [
						'farm' => $eSeries['farm']['id']
					];

					foreach($eSeries['cSequence'] as $position => $eSequence) {
						$body['ids['.$position.']'] = $eSequence['id'];
					}

					$d->autocompleteBody = $body;

				});

			}

			$h .= $form->dynamicGroup($eSeries, 'mode');

			$h .= $form->dynamicGroup($eSeries, 'use', function(\PropertyDescriber $d) use ($eSeries) {

				$infos = [];

				if($eSeries['cPlace']->notEmpty()) {
					$infos[] = s("l'assolement de la série sera remis à zéro");
				}

				$reset = $eSeries['cCultivation']->reduce(fn($eCultivation, $n) => (
					(
						($eSeries['use'] === Series::BED and $eCultivation['rows'] !== NULL) or
						($eSeries['use'] === Series::BLOCK and $eCultivation['rowSpacing'] !== NULL)
					)
						? 1 : 0) + $n, 0);

				if($reset) {
					$infos[] = p("les informations concernant la densité de la culture seront réinitialisées et vous devrez les saisir à nouveau", "les informations concernant la densité des cultures seront réinitialisées et vous devrez les saisir à nouveau", $eSeries['plants']);
				}

				if($infos) {

					$text = s("Si vous changez l'utilisation du sol, {value}.", implode(', ', $infos));
					$d->after = \util\FormUi::info($text);

				}

				$d->attributes['callbackRadioAttributes'] = function() {
					return [
						'onclick' => 'Series.refreshUpdateUse(this)'
					];
				};

			});

			if($eSeries['cycle'] === Series::PERENNIAL and $eSeries['perennialStatus'] !== Series::CONTINUED) {
				$h .= $form->dynamicGroup($eSeries, 'perennialLifetime');
			}

			$h .= '<div class="series-update-block '.($eSeries['use'] === Series::BLOCK ? '' : 'hide').'">';
				$h .= $form->dynamicGroup($eSeries, 'areaTarget');
			$h .= '</div>';

			$h .= '<div class="series-update-bed '.($eSeries['use'] === Series::BED ? '' : 'hide').'">';
				$h .= $form->dynamicGroups($eSeries, ['lengthTarget', 'bedWidth', 'alleyWidth']);
			$h .= '</div>';

			$h .= $form->group(
				content: $form->submit(s("Modifier"))
			);

		$h .= $form->close();

		return new \Panel(
			id: 'panel-series-update',
			title: s("Modifier une série"),
			body: $h,
		);

	}

	public function updateSeason(\farm\Farm $eFarm, \Collection $cSeries): \Panel {

		$form = new \util\FormUi();

		$eSeriesFirst = $cSeries->first();
		$eSeriesFirst['farm'] = $eFarm; // Pour avoir la première et la dernière saison de la ferme

		$h = '';

		$h .= $form->openAjax('/series/series:doUpdateSeasonCollection');

			$h .= $this->getSeriesField($form, $cSeries);
			$h .= $form->dynamicGroup($eSeriesFirst, 'season');

			$h .= $form->group(
				content: $form->submit(s("Modifier la saison"))
			);

		$h .= $form->close();

		return new \Panel(
			id: 'panel-series-update',
			title: s("Modifier la saison"),
			body: $h,
		);

	}

	public function duplicate(\farm\Farm $eFarm, \Collection $cSeries, \Collection $cTaskMetadata, bool $hasPlaces): \Panel {

		$form = new \util\FormUi();

		$eSeriesFirst = $cSeries->first();
		$eSeriesFirst['farm'] = $eFarm; // Pour avoir la première et la dernière saison de la ferme

		$h = '';

		$h .= $form->openAjax('/series/series:doDuplicate', ['id' => 'series-duplicate']);

			$h .= $this->getSeriesField($form, $cSeries);

			$h .= $form->dynamicGroup($eSeriesFirst, 'season', function(\PropertyDescriber $d) use ($eSeriesFirst) {
				$d->label = s("Dupliquer pour la saison");
				$d->attributes['onclick'] = 'Series.changeDuplicateSeason(this, '.$eSeriesFirst['season'].')';
				$d->after = \util\FormUi::info(s("Lorsque vous copiez une série sur une saison différente, les interventions sont replacées en <i>À faire</i> et les récoltes sont remises à zéro."), class: 'series-duplicate-season hide');
			});

			if($cTaskMetadata->notEmpty() or $hasPlaces) {

				if($cSeries->count() === 1) {
					$title = s("Que souhaitez-vous conserver dans la série ?");
				} else {
					$title = s("Que souhaitez-vous conserver dans les séries ?");
				}

				$h .= $form->group(content: '<h3>'.$title.'</h3>');
			}

			$h .= '<div class="util-block bg-background-light">';

				if($cTaskMetadata->notEmpty()) {

					$h .= $form->group(
						s("Conserver les interventions"),
						$form->yesNo('copyTasks', TRUE, attributes: [
							'callbackRadioAttributes' => fn() => [
								'onchange' => 'Series.changeDuplicateTasks(this)'
							]
						])
					);

					$cAction = $cTaskMetadata
						->getColumnCollection('action')
						->sort('name');

					$h .= $form->group(
						content :
						'<h5>'.s("Choisir les interventions à conserver").'</h5>'.
						$form->checkboxes('copyActions[]', $cAction, attributes: [
							'callbackCheckboxAttributes' => fn($eAction) => [
								'data-fqn' => $eAction['fqn']
							],
							'callbackCheckboxContent' => function($eAction) {
								$action = encode($eAction['name']);
								if($eAction['fqn'] === ACTION_RECOLTE) {
									$action .= '<br/><span class="color-muted">'.s("Les quantités récoltées seront également dupliquées").'</span>';
								}
								return $action;
							}
						])
					);

					if($cTaskMetadata
						->filter(fn($eTask) => $eTask['time'] > 0)
						->notEmpty()) {

						$timesheet = $form->yesNo('copyTimesheet', FALSE);
						$timesheet .= \util\FormUi::info(s("Il n'est possible de dupliquer le temps de travail que lorsque la série est dupliquée sur la même saison."), class: 'series-duplicate-timesheet hide');

						$h .= $form->group(
							s("Conserver le temps de travail"),
							$timesheet
						);

					}

				} else {
					$h .= $form->hidden('copyTasks', FALSE);
				}

				if($hasPlaces) {

					$h .= $form->group(
						s("Conserver l'assolement"),
						$form->yesNo('copyPlaces', FALSE)
					);

				} else {
					$h .= $form->hidden('copyPlaces', FALSE);
				}

			$h .= '</div>';

			$h .= $form->group(
				content: $form->submit(s("Dupliquer"))
			);

		$h .= $form->close();

		return new \Panel(
			id: 'panel-series-duplicate',
			title: $cSeries->count() === 1 ? s("Dupliquer une série") : s("Dupliquer des séries"),
			body: $h,
		);

	}

	protected function getSeriesField(\util\FormUi $form, \Collection $cSeries): string {

		$h = '';

		if($cSeries->count() === 1) {

			$eSeries = $cSeries->first();

			$h .= $form->hidden('ids[]', $eSeries['id']);

			$h .= $form->group(
				s("Nom"),
				SeriesUi::link($eSeries)
			);

		} else {

			$h .= $form->group(
				s("Séries"),
				$form->checkboxes('ids[]', $cSeries, $cSeries, [
					'all' => FALSE,
					'callbackCheckboxContent' => function($eSeries) {
						return SeriesUi::name($eSeries);
					}
				])
			);

		}

		return $h;

	}

	protected function getSeasonField(\util\FormUi $form, Series $eSeries): string {

		$h = $form->hidden('season', $eSeries['season']);
		$h .= $form->group(
			self::p('season')->label,
			$form->fake($eSeries['season'])
		);

		return $h;

	}

	protected function getNameField(\util\FormUi $form, Series $eSeries): string {

		$eSeries->expects(['nameAuto', 'nameDefault']);

		return $form->group(
			self::p('name')->label,
			$form->inputGroup(
				$form->dynamicField($eSeries, 'name', function($d) use ($eSeries) {
					$d->attributes['data-auto'] = 'true';
					$d->attributes['oninput'] = 'Series.changeNameAuto(this)';
				})
			),
			['wrapper' => 'name']
		);

	}

	public function deleteSeason(\farm\Farm $eFarm, int $season): string {

		$eFarm->expects(['createdAt', 'seasonFirst', 'seasonLast']);

		if(
			$eFarm['seasonFirst'] === $eFarm['seasonLast'] or
			(
				// Impossible de supprimer des saisons de l'année N et N + 1 si ferme récente
				$eFarm['createdAt'] > date('Y-m-d H:i:s', strtotime('1 year ago')) and
				($season === currentYear() or $season === currentYear() + 1)
			)
		) {
			return '';
		}

		// Il n'est possible de supprimer que la première ou la dernière saison
		if($eFarm['seasonFirst'] === $season) {
			$link = 'data-ajax="/farm/farm:doSeasonFirst" post-id="'.$eFarm['id'].'" post-increment="1"';
		} else if($eFarm['seasonLast'] === $season) {
			$link = 'data-ajax="/farm/farm:doSeasonLast" post-id="'.$eFarm['id'].'" post-increment="-1"';
		} else {
			return '';
		}

		$h = '<div class="util-block">';

			$h .= '<h4>'.s("Supprimer cette saison").'</h4>';

			$h .= '<p>'.s("Vous n'avez pas encore ajouté de série sur cette saison. Il est toujours possible de supprimer cette saison {value} si vous ne comptez pas travailler dessus pour le moment et de la recréer plus tard !", $season).'</p>';
			$h .= '<a '.$link.' class="btn btn-danger">'.s("Supprimer la saison {value}", $season).'</a>';

		$h .= '</div>';

		return $h;

	}

	public function getWorkingTime(Series $eSeries, \Collection $cCultivation, \Collection $ccTask, \Collection $ccTaskHarvested): string {

		if($ccTask->empty()) {
			return '';
		}

		$seriesTime = 0;

		$h = '<div id="series-timesheet-wrapper">';

			$h .= '<h3>'.s("Temps de travail").'</h3>';

			$h .= '<div class="series-timesheet">';

			foreach($ccTask as $cultivation => $cTask) {

				if($cultivation) {
					$eCultivation = $cCultivation[$cultivation];
				} else {
					$eCultivation = new Cultivation();
				}

				$cTaskHarvested = $ccTaskHarvested[$cultivation] ?? new \Collection();

				$cultivationTime = $cTask->sum('totalTime');
				$seriesTime += $cultivationTime;

				$h .= '<div class="series-timesheet-image">';
					if($cultivation) {
						$h .= \plant\PlantUi::getVignette($eCultivation['plant'], '3rem').' ';
					}
				$h .= '</div>';

				$h .= '<div class="series-timesheet-cultivation">';
					if($cultivation) {
						$ePlant = $eCultivation['plant'];
						$h .= '<h4>';
							$h .= encode($ePlant['name']);
						$h .= '</h4>';
					} else {
						$h .= '<h4>'.s("Partagé").'</h4>';
					}
				$h .= '</div>';

				$h .= '<div class="series-timesheet-total">';
					$h .= TaskUi::convertTime($cultivationTime);
				$h .= '</div>';

				$h .= '<div class="series-timesheet-content">';

					foreach($cTask as $eTask) {

						$eAction = $eTask['action'];

						$h .= $this->getWorkingTimeBox($eSeries, $eCultivation, $eAction, $eTask['totalTime'], $cTaskHarvested);

					}

				$h .= '</div>';

			}

			if($ccTask->count() > 1) {

				$h .= '<div class="series-timesheet-sum" style="grid-column: span 2">';
					$h .= '<span class="btn btn-readonly btn-outline-secondary">'.TaskUi::convertTime($seriesTime).'</span>';
				$h .= '</div>';

				$h .= '<div>';
				$h .= '</div>';

			}

			$h .= '</div>';

		$h .= '</div>';

		return $h;

	}

	public function getWorkingTimeBox(Series $eSeries, Cultivation $eCultivation, \farm\Action $eAction, float $time, \Collection $cTaskHarvested): string {

		$h = '<div class="series-item-working-time-task">';
			$h .= '<div class="series-item-working-time-task-content" style="background-color: '.$eAction['color'].'">';
				$h .= '<h5>'.encode($eAction['name']).'</h5>';
				$h .= '<span class="series-item-working-time-task-value">'.TaskUi::convertTime($time).'</span>';
			$h .= '</div>';

			if($eAction['pace'] and $time > 0) {

				$h .= '<div class="series-item-working-time-task-pace" style="color: '.$eAction['color'].'">';
					$h .= $this->getPace($eSeries['area'], $eCultivation->empty() ? NULL : $eCultivation['density'] * $eSeries['area'], $eAction, $time, $cTaskHarvested);
				$h .= '</div>';

			}
		$h .= '</div>';

		return $h;

	}

	public function getPace(?int $area, ?float $plants, \farm\Action $eAction, float $time, \Collection $cTaskHarvested): string {

		if($area === NULL) {
			return '';
		}

		$pace = '';

		switch($eAction['pace']) {

			case \farm\Action::BY_HARVEST :
				foreach($cTaskHarvested as $eTaskHarvested) {
					if($eTaskHarvested['totalTime'] > 0 and $eTaskHarvested['totalHarvested'] > 0) {
						$pace .= s("{value} / h", \selling\UnitUi::getValue(round($eTaskHarvested['totalHarvested'] / $eTaskHarvested['totalTime'], 1), $eTaskHarvested['harvestUnit'], short: TRUE)).'<br/>';
					}
				}
				break;

			case \farm\Action::BY_AREA :
				$pace .= s("{value} m² / h", round($area / $time));
				break;

			case \farm\Action::BY_PLANT :
				$pace .= s("{value} plants / h", round($plants / $time));
				break;

		}

		return $pace;

	}

	public function getPhotos(Series $eSeries, \Collection $cPhoto): string {

		$h = '';

		$h .= '<div class="util-title">';
			$h .= '<h3 id="scroll-photos">'.s("Photos").'</h3>';
			if($eSeries->canWrite()) {
				$h .= '<div data-media="gallery" post-series="'.$eSeries['id'].'">';
					$h .= (new \media\GalleryUi())->getDropdownLinks(
						\Asset::icon('plus-circle').' <span>'.s("Ajouter une photo").'</span>',
						'btn-outline-primary',
						uploadInputAttributes: ['multiple' => 'multiple']
					);
				$h .= '</div>';
			}
		$h .= '</div>';

		if($cPhoto->notEmpty()) {
			$h .= (new \gallery\PhotoUi())->getList($cPhoto, NULL, 4);
		}

		return $h;

	}

	public static function p(string $property): \PropertyDescriber {

		$d = Series::model()->describer($property, [
			'season' => s("Saison"),
			'sequence' => s("Itinéraire technique"),
			'name' => s("Nom de la série"),
			'use' => s("Utilisation du sol"),
			'mode' => s("Mode de culture"),
			'cycle' => s("Cycle de culture"),
			'areaTarget' => s("Objectif de surface"),
			'lengthTarget' => s("Objectif de surface"),
			'bedWidth' => s("Largeur travaillée de planche"),
			'alleyWidth' => s("Largeur de passe-pieds")
		]);

		switch($property) {

			case 'season' :
				$d->field = function(\util\FormUi $form, Series $e, string $property, \PropertyDescriber $d) {

					$e->expects([
						'farm' => ['seasonFirst', 'seasonLast']
					]);

					return $form->rangeSelect('season', $e['farm']['seasonLast'], $e['farm']['seasonFirst'], -1, $e['season'], ['mandatory' => TRUE] + $d->attributes);

				};
				break;

			case 'use' :
				$d->values = [
					Series::BED => s("Culture sur planches"),
					Series::BLOCK => s("Culture sur surface libre"),
				];
				$d->attributes = [
					'columns' => 2,
					'mandatory' => TRUE,
					'onchange' => 'Series.updateArea(this)'
				];
				break;

			case 'mode' :
				$d->values = [
					Series::GREENHOUSE => s("Sous abri"),
					Series::OPEN_FIELD => s("Plein champ"),
					Series::MIX => s("Mixte"),
				];
				$d->attributes = [
					'columns' => 3,
					'mandatory' => TRUE
				];
				break;

			case 'cycle' :
				$d->values = [
					Series::ANNUAL => s("Culture annuelle"),
					Series::PERENNIAL => s("Culture pérenne"),
				];
				$d->attributes = [
					'data-action' => 'series-cycle-change',
					'columns' => 2,
					'mandatory' => TRUE
				];
				break;

			case 'perennialLifetime' :
				$d->groupLabel = FALSE;
				$d->prepend = s("Durée de vie de la culture").'&nbsp;&nbsp;'.\Asset::icon('arrow-right');
				$d->append = s("saison(s)");
				$d->after = '<small>'.s("Vous pouvez laisser vide si la durée de vie n'est pas connue à ce jour.").'</small>';
				$d->group = function(Series $e) {

					$e->expects(['cycle']);

					return [
						'id' => 'series-write-perennial-lifetime',
						'style' => ($e['cycle'] === \production\Sequence::PERENNIAL) ? '' : 'display: none'
					];

				};
				break;

			case 'lengthTarget' :
				$d->append = s("mL de planches");
				$d->attributes['oninput'] = 'Series.updateArea(this)';
				break;

			case 'bedWidth' :
				$d->append = s("cm");
				$d->attributes['oninput'] = 'Series.updateArea(this)';
				break;

			case 'alleyWidth' :
				$d->after = \util\FormUi::info(s("Les rendements et la fertilisation sont calculés en intégrant la largeur du passe-pied.").'</small>');
				$d->append = s("cm");
				$d->attributes['oninput'] = 'Series.updateArea(this)';
				break;

			case 'areaTarget' :
				$d->append = s("m²");
				$d->attributes['oninput'] = 'Series.updateArea(this)';
				break;
		}

		return $d;

	}

}
?>
