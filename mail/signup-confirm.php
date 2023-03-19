<?php

use app\models\User;
use yii\base\View;
use yii\helpers\Html;

/**
 * @var View $this
 * @var User $user
 * @var string $confirmCode
 * @var string $confirmUrl
 */
?>
<p>Hi <?= $user->name ?>, </p>
<p>
    Please go to this url to confirm your account: <?= Html::a($confirmUrl, $confirmUrl); ?><br>
    Or use this code to confirm: <?= $confirmCode ?>
</p>
<p><?= Yii::$app->name ?></p>
