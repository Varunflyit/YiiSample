<?php

namespace app\behaviors;

use app\helpers\UuidHelper;
use yii\behaviors\AttributeBehavior;
use yii\db\BaseActiveRecord;

/**
 * @author Yoyon Cahyono <yoyoncahyono@gmail.com>
 */

class UuidBehavior extends AttributeBehavior
{
    /**
     * @var string the attribute that will receive uuid value
     */
    public $uuidAttribute = 'id';

    /**
     * {@inheritdoc}
     */
    public function init()
    {
        parent::init();

        if (empty($this->attributes)) {
            $this->attributes = [
                BaseActiveRecord::EVENT_AFTER_FIND => $this->uuidAttribute,
                BaseActiveRecord::EVENT_AFTER_REFRESH => $this->uuidAttribute,
                BaseActiveRecord::EVENT_AFTER_INSERT => $this->uuidAttribute,
                BaseActiveRecord::EVENT_AFTER_UPDATE => $this->uuidAttribute,
                BaseActiveRecord::EVENT_BEFORE_INSERT => $this->uuidAttribute,
                BaseActiveRecord::EVENT_BEFORE_UPDATE => $this->uuidAttribute,
            ];
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getValue($event)
    {
        $uuid = $this->owner->{$this->uuidAttribute};
        if (empty($uuid)) {
            return null;
        } elseif (in_array($event->name, [BaseActiveRecord::EVENT_BEFORE_INSERT, BaseActiveRecord::EVENT_BEFORE_UPDATE])){
            return UuidHelper::uuid2bin($event->sender->id);
        } else {
            return UuidHelper::bin2uuid($event->sender->id);
        }
    }    
}
