<?php

namespace app\models;

use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $short_link_id
 * @property string $ip
 * @property int $created_at
 */
class ShortLinkClick extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%short_link_click}}';
    }

    public function rules()
    {
        return [
            [['short_link_id', 'ip', 'created_at'], 'required'],
            [['short_link_id', 'created_at'], 'integer'],
            [['ip'], 'string', 'max' => 45],
        ];
    }

    public function getShortLink()
    {
        return $this->hasOne(ShortLink::class, ['id' => 'short_link_id']);
    }
}
