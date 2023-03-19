<?php

namespace app\helpers;

use Yii;
use yii\helpers\BaseStringHelper;

class StringHelper extends BaseStringHelper
{
    public static function generateGuid($length)
    {
        return str_replace(
            ['_', '-'],
            rand(0, 9),
            Yii::$app->security->generateRandomString($length)
        );
    }
}
