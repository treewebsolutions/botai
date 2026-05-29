<?php

/* @var $this yii\web\View */
/* @var $activeStep string */

use yii\helpers\Html;

if ($activeStep == 'upload') {
	$cssClassUpload = 'active';
	$cssClassSheet = '';
	$cssClassMap = '';
	$cssClassImport = '';
} elseif ($activeStep == 'sheet') {
	$cssClassUpload = 'done';
	$cssClassSheet = 'active';
	$cssClassMap = '';
	$cssClassImport = '';
} elseif ($activeStep == 'map') {
	$cssClassUpload = 'done';
	$cssClassSheet = 'done';
	$cssClassMap = 'active';
	$cssClassImport = '';
} elseif ($activeStep == 'import') {
	$cssClassUpload = 'done';
	$cssClassSheet = 'done';
	$cssClassMap = 'done';
	$cssClassImport = 'active';
}
?>

<div class="mt-element-step">
	<div class="row step-line step-line-navigable">
		<div class="col-md-3 mt-step-col first <?= $cssClassUpload ?>">
			<div class="mt-step-number bg-white" data-action="0">1</div>
			<div class="mt-step-title font-grey-cascade"><?= Yii::t('import', 'Choose / Upload a File') ?></div>
			<div class="mt-step-content font-grey-cascade"><?= Yii::t('import', 'The source file with data') ?></div>
		</div>
		<div class="col-md-3 mt-step-col <?= $cssClassSheet ?>">
			<div class="mt-step-number bg-white" data-action="1">2</div>
			<div class="mt-step-title font-grey-cascade"><?= Yii::t('import', 'Select The Sheet') ?></div>
			<div class="mt-step-content font-grey-cascade"><?= Yii::t('import', 'Set the sheet settings') ?></div>
		</div>
		<div class="col-md-3 mt-step-col <?= $cssClassMap ?>">
			<div class="mt-step-number bg-white" data-action="2">3</div>
			<div class="mt-step-title font-grey-cascade"><?= Yii::t('import', 'Map Data') ?></div>
			<div class="mt-step-content font-grey-cascade"><?= Yii::t('import', 'Map file data to the model') ?></div>
		</div>
		<div class="col-md-3 mt-step-col last <?= $cssClassImport ?>">
			<div class="mt-step-number bg-white" data-action="3">4</div>
			<div class="mt-step-title font-grey-cascade"><?= Yii::t('import', 'Import') ?></div>
			<div class="mt-step-content font-grey-cascade"><?= Yii::t('import', 'Review and import') ?></div>
		</div>
	</div>
</div>

