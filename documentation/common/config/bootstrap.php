<?php
Yii::$classMap['yii\helpers\Html'] = '@common/helpers/Html.php';

Yii::setAlias('@base', dirname(dirname(dirname(__DIR__))));
Yii::setAlias('@root', dirname(dirname(__DIR__)));
Yii::setAlias('@common', dirname(__DIR__));
Yii::setAlias('@frontend', dirname(dirname(__DIR__)) . '/frontend');
Yii::setAlias('@backend', dirname(dirname(__DIR__)) . '/backend');
Yii::setAlias('@console', dirname(dirname(__DIR__)) . '/console');
Yii::setAlias('@uploads', dirname(dirname(__DIR__)) . '/uploads');
Yii::setAlias('@backups', dirname(dirname(__DIR__)) . '/_backups');
