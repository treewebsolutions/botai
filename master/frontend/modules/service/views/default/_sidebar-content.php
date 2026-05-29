<?php
/* @var $this yii\web\View */

use yii\helpers\Html;
use yii\helpers\Url;
?>

<?php if ($serviceCategories = \common\models\ServiceCategory::findAllServiceCategories()): ?>
	<aside class="section section-md bg-03">
		<div class="container-fluid">
			<ul class="list-link-underline">
				<?php foreach ($serviceCategories as $serviceCategory): ?>
					<?php $serviceCategoryTranslation = $serviceCategory->getTranslation(); ?>
					<li class="<?= Yii::$app->request->get('category') == $serviceCategoryTranslation->slug ? 'active' : '' ?>">
						<a href="<?= Url::to(['index', 'category' => $serviceCategoryTranslation->slug]) ?>"><?= $serviceCategoryTranslation->title ?></a>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	</aside>
<?php endif; ?>
