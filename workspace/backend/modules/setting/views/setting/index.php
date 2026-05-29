<?php

/* @var $this yii\web\View */
/* @var $model backend\modules\setting\models\GeneralSettingForm */
/* @var $form backend\widgets\ActiveForm */

use common\models\Country;
use common\models\Currency;
use backend\widgets\ActiveForm;
use kartik\file\FileInput;
use kartik\select2\Select2;
use kartik\touchspin\TouchSpin;
use tws\widgets\datetimepicker\DateTimePicker;
use yii\bootstrap\Tabs;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use tws\helpers\Url;

$this->title = Yii::t('common', 'General Settings');
$this->params['breadcrumbs'] = [
	[
		'label' => Yii::t('common', 'Settings'),
		'url' => ['index'],
	],
	$this->title,
];
?>

<?php $form = ActiveForm::begin([
	'id' => mb_strtolower($model->formName()),
	'options' => [
		'novalidate' => true,
	],
	'validateOnType' => true,
]); ?>
	<div class="row">
		<div class="col-sm-6">
			<?= $form->field($model, 'appName')->textInput() ?>
		</div>
		<div class="col-sm-6">
			<?= $form->field($model, 'theme')->widget(Select2::class, [
				'data' => [
					'default' => Yii::t('backend', 'Default'),
					'darkblue' => Yii::t('backend', 'Dark Blue'),
					'darkorange' => Yii::t('backend', 'Dark Orange'),
					'blue' => Yii::t('backend', 'Blue'),
					'brown' => Yii::t('backend', 'Brown'),
					'green' => Yii::t('backend', 'Green'),
					'grey' => Yii::t('backend', 'Grey'),
					'light' => Yii::t('backend', 'Light'),
					'white' => Yii::t('backend', 'White'),
				],
				'pluginLoading' => false,
				'pluginOptions' => [
					'allowClear' => true,
					'placeholder' => Yii::t('common', 'Choose'),
				],
			]) ?>
		</div>
	</div>
	<div class="row">
		<div class="col-sm-12">
			<?= $form->field($model, 'appLogo')->widget(FileInput::class, [
				'options' => [
					'accept' => 'image/*',
					'data' => [
						'operation-confirm' => Yii::t('common', 'Are you sure you want to perform this operation?'),
					],
				],
				'resizeImages' => false,
				'sortThumbs' => false,
				'purifyHtml' => false,
				'pluginOptions' => [
					'allowedFileExtensions' => ['jpeg', 'jpg', 'png', 'gif'],
					'maxFileSize' => Yii::$app->settings->get('maxFileSize'),
					'dropZoneEnabled' => false,
					'showClose' => false,
					'showUpload' => false,
					'showCaption' => true,
					'showRemove' => true,
					'showPreview' => true,
					'fileActionSettings' => [
						'showDownload' => true,
						'showRemove' => true,
						'showUpload' => false,
						'showZoom' => true,
						'showDrag' => false,
					],
					'initialPreview' => $model->getAppLogoUrl() ?: false,
					'initialPreviewConfig' => [
						[
							'caption' => $model->appLogo,
							'downloadUrl' => $model->getAppLogoUrl(),
						],
					],
					'initialPreviewAsData' => true,
					'initialPreviewShowDelete' => true,
					'overwriteInitial' => true,
					'deleteUrl' => Url::to(['delete-file', 'id' => $model->id]),
					'deleteExtraData' => [
						'attribute' => 'appLogo',
					],
				],
			]) ?>
		</div>
	</div>
	<div class="panel box blue-hoki">
		<div class="panel-title">
			<div class="panel-heading"><?= Yii::t('backend', 'Date And Time') ?></div>
		</div>
		<div class="panel-body">
			<div class="text-info-icon fa-info-circle text-muted margin-bottom-10"><?= Yii::t('backend', 'Available formats can be found in the {0} manual.', ['<a href="http://userguide.icu-project.org/formatparse/datetime" target="_blank">ICU</a>']) ?></div>
			<div class="row">
				<div class="col-sm-6">
					<?= $form->field($model, 'timeZone')->widget(Select2::class, [
						'data' => array_combine(DateTimeZone::listIdentifiers(), DateTimeZone::listIdentifiers()),
						'pluginLoading' => false,
						'pluginOptions' => [
							'allowClear' => false,
							'placeholder' => Yii::t('common', 'Choose'),
						],
					])->hint(Yii::t('backend', 'The time zone to use for formatting time and date values.')) ?>
				</div>
				<div class="col-sm-6">
                    <?= $form->field($model, 'timeFormat')->widget(Select2::class, [
                        'data' => [
                            'HH:mm:ss' => 'HH:mm:ss',
                        ],
                        'pluginLoading' => false,
                        'pluginOptions' => [
	                        'allowClear' => false,
	                        'placeholder' => Yii::t('common', 'Choose'),
                        ],
                    ])->hint(Yii::t('backend', 'The default format string to be used to format a time.')) ?>
				</div>
			</div>
			<div class="row">
				<div class="col-sm-6">
                    <?= $form->field($model, 'dateFormat')->widget(Select2::class, [
                        'data' => [
                            'dd-MM-yyyy' => 'dd-MM-yyyy',
                            'dd.MM.yyyy' => 'dd.MM.yyyy',
                            'dd/MM/yyyy' => 'dd/MM/yyyy',
                            'yyyy-MM-dd' => 'yyyy-MM-dd',
                            'yyyy.MM.dd' => 'yyyy.MM.dd',
                            'yyyy/MM/dd' => 'yyyy/MM/dd',
                            'MM-dd-yyyy' => 'MM-dd-yyyy',
                            'MM/dd/yyyy' => 'MM/dd/yyyy',
                        ],
                        'pluginLoading' => false,
                        'pluginOptions' => [
                            'allowClear' => false,
                            'placeholder' => Yii::t('common', 'Choose'),
                        ],
                    ])->hint(Yii::t('backend', 'The default format string to be used to format a date.')) ?>
				</div>
				<div class="col-sm-6">
                    <?= $form->field($model, 'datetimeFormat')->widget(Select2::class, [
                        'data' => [
                            'dd-MM-yyyy HH:mm:ss' => 'dd-MM-yyyy HH:mm:ss',
                            'dd.MM.yyyy HH:mm:ss' => 'dd.MM.yyyy HH:mm:ss',
                            'dd/MM/yyyy HH:mm:ss' => 'dd/MM/yyyy HH:mm:ss',
                            'yyyy-MM-dd HH:mm:ss' => 'yyyy-MM-dd HH:mm:ss',
                            'yyyy.MM.dd HH:mm:ss' => 'yyyy.MM.dd HH:mm:ss',
                            'yyyy/MM/dd HH:mm:ss' => 'yyyy/MM/dd HH:mm:ss',
                            'MM-dd-yyyy HH:mm:ss' => 'MM-dd-yyyy HH:mm:ss',
                            'MM/dd/yyyy HH:mm:ss' => 'MM/dd/yyyy HH:mm:ss',
                        ],
                        'pluginLoading' => false,
                        'pluginOptions' => [
                            'allowClear' => false,
                            'placeholder' => Yii::t('common', 'Choose'),
                        ],
                    ])->hint(Yii::t('backend', 'The default format string to be used to format a date and time.')) ?>
				</div>
			</div>
		</div>
	</div>

	<div class="panel box blue-hoki">
		<div class="panel-title">
			<div class="panel-heading"><?= Yii::t('backend', 'Display') ?></div>
		</div>
		<div class="panel-body">
			<div class="row">
				<div class="col-sm-6">
					<?= $form->field($model, 'itemsPerPage')->widget(TouchSpin::class, [
						'pluginOptions' => [
							'min' => 1,
							'max' => PHP_INT_MAX,
							'step' => 1,
							'decimals' => 0,
							'boostat' => 5,
							'maxboostedstep' => 10,
							'verticalbuttons' => true,
						],
					])->hint(Yii::t('backend', 'The number of items listed per each page.')) ?>
				</div>
				<div class="col-sm-6">
					<?= $form->field($model, 'currencyCode')->widget(Select2::class, [
						'data' => ArrayHelper::map(Currency::findAllCurrencies(), 'iso_code', 'iso_code'),
						'pluginLoading' => false,
						'pluginOptions' => [
							'placeholder' => Yii::t('common', 'Choose'),
						],
					])->hint(Yii::t('backend', 'The 3-letter ISO 4217 currency code indicating the default currency.')) ?>
				</div>
			</div>
			<div class="row">
				<div class="col-sm-6">
					<?= $form->field($model, 'defaultCountry')->widget(Select2::class, [
						'data' => ArrayHelper::map(Country::findAllCountries(), 'iso_alpha2', 'translation.name'),
						'pluginLoading' => false,
						'pluginOptions' => [
							'placeholder' => Yii::t('common', 'Choose'),
						],
					])->hint(Yii::t('backend', 'The default value used to preselect the country in all dropdown menus.')) ?>
				</div>
				<div class="col-sm-6">
					<?= $form->field($model, 'defaultLanguage')->widget(Select2::class, [
						'data' => ArrayHelper::map(\common\models\Language::findAllLanguages(), 'language_id', 'name'),
						'pluginLoading' => false,
						'pluginOptions' => [
							'allowClear' => false,
							'placeholder' => Yii::t('common', 'Choose'),
						],
					])->hint(Yii::t('backend', 'The application default language.')) ?>
				</div>
			</div>
		</div>
	</div>

    <div class="panel blue-hoki">
        <div class="panel-title">
            <div class="panel-heading"><?= Yii::t('common', 'Other') ?></div>
        </div>
        <div class="panel-body">
	        <div class="row">
		        <div class="col-sm-12">
                    <?= $form->field($model, 'enableEventLogs')->checkbox() ?>
		        </div>
		        <div class="col-sm-12">
                    <?= $form->field($model, 'enableSoftDelete')->checkbox() ?>
		        </div>
		        <div class="col-sm-12">
                    <?= $form->field($model, 'enableScraper')->checkbox() ?>
		        </div>
            </div>
        </div>
    </div>

	<div class="form-actions floating">
		<?= Html::submitButton('<span class="fa fa-check"></span>', [
			'class' => 'btn btn-xlg btn-fab btn-success',
			'title' => Yii::t('common', 'Save'),
			'data' => [
				'toggle' => 'tooltip',
			],
		]) ?>
	</div>
<?php ActiveForm::end(); ?>
