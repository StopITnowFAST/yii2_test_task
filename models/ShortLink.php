<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property string $original_url
 * @property string $code
 * @property int $click_count
 * @property int $created_at
 */
class ShortLink extends ActiveRecord
{
    public static function tableName()
    {
        return '{{%short_link}}';
    }

    public function rules()
    {
        return [
            [['original_url', 'code'], 'required'],
            [['original_url'], 'string', 'max' => 2048],
            [['code'], 'string', 'max' => 16],
            [['code'], 'match', 'pattern' => '/^[A-Za-z0-9_-]+$/'],
            [['click_count', 'created_at'], 'integer'],
        ];
    }

    public function beforeValidate()
    {
        if (!parent::beforeValidate()) {
            return false;
        }
        if ($this->isNewRecord && empty($this->created_at)) {
            $this->created_at = time();
        }

        return true;
    }

    public static function findByCode(string $code): ?self
    {
        return static::find()->where(['code' => $code])->one();
    }

    public static function generateCode(int $length = 8): string
    {
        return Yii::$app->security->generateRandomString($length);
    }
}
