<?php
/**
 * @link http://www.tintsoft.com/
 * @copyright Copyright (c) 2012 TintSoft Technology Co. Ltd.
 * @license http://www.tintsoft.com/license/
 */

namespace yuncms\attachment\models;

use Yii;
use yii\db\ActiveRecord;
use yii\db\BaseActiveRecord;
use yuncms\attachment\AttachmentTrait;
use yuncms\attachment\jobs\AttachmentDeleteJob;
use yuncms\user\models\User;

/**
 * Class Attachment
 * @property int $id
 * @property int $user_id 上传用户uID
 * @property string $filename 文件名
 * @property string $original_name 文件原始名称
 * @property string $model 上传模型
 * @property string $hash 文件哈希
 * @property int $size 文件大小
 * @property string $type 文件类型
 * @property string $mine_type 文件类型
 * @property string $ext 文件后缀
 * @property string $path 存储路径
 * @property string $ip 用户IP
 * @property int $created_at 创建时间
 *
 * @property-read string $url WEB访问路径
 * @property-read User $user
 *
 * @package yuncms\attachment\models
 */
class Attachment extends ActiveRecord
{
    use AttachmentTrait;

    /**
     * 定义数据表
     */
    public static function tableName()
    {
        return '{{%attachment}}';
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            [
                'class' => 'yii\behaviors\TimestampBehavior',
                'attributes' => [
                    BaseActiveRecord::EVENT_BEFORE_INSERT => ['created_at'],
                ],
            ],
            [
                'class' => 'yii\behaviors\AttributeBehavior',
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => 'ip'
                ],
                'value' => function ($event) {
                    return Yii::$app->request->userIP;
                }
            ],
            [
                'class' => 'yii\behaviors\AttributeBehavior',
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => 'user_id'
                ],
                'value' => function ($event) {
                    return Yii::$app->user->id;
                }
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('attachment', 'ID'),
            'user_id' => Yii::t('attachment', 'User Id'),
            'filename' => Yii::t('attachment', 'Filename'),
            'original_name' => Yii::t('attachment', 'Original FileName'),
            'size' => Yii::t('attachment', 'File Size'),
            'type' => Yii::t('attachment', 'File Type'),
            'path' => Yii::t('attachment', 'Path'),
            'ip' => Yii::t('attachment', 'User Ip'),
            'created_at' => Yii::t('attachment', 'Created At'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['filename', 'original_name'], 'required'],
        ];
    }

    /**
     * User Relation
     * @return \yii\db\ActiveQueryInterface
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'user_id']);
    }

    /**
     * 获取访问Url
     */
    public function getUrl()
    {
        return $this->getSetting('storeUrl') . '/' . $this->path;
    }

    /**
     * 附件删除
     * @return mixed
     */
    public function afterDelete()
    {
        $path = $this->getSetting('storePath') . DIRECTORY_SEPARATOR . $this->path;
        Yii::$app->queue->push(new AttachmentDeleteJob([
            'path' => $path
        ]));
        return parent::afterDelete();
    }
}