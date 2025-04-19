<?php

namespace  yiitron\novakit;

use yiitron\novakit\audit\AuditBehavior;
use Yii;
use yii\helpers\Json;
use yiitron\novakit\traits\Keygen;
use yiitron\novakit\traits\Status;
use yiitron\novakit\behaviors\Delete;
use yiitron\novakit\behaviors\Creator;

class ActiveRecord extends \yii\db\ActiveRecord
{
	use Keygen;
	use Status;
	use ServiceConsumer;
	public $recordStatus;
	public function behaviors()
	{
		if ($this->tableName() == "{{%active_record}}") {
			return parent::behaviors();
		} else {
			$behaviors = [
				Delete::class,
				Creator::class,
				AuditBehavior::class,
			];
			if ($this->hasAttribute('created_at') && $this->hasAttribute('updated_at')) {
				$behaviors[] = \yii\behaviors\TimestampBehavior::class;
			}

			return array_merge(parent::behaviors(), $behaviors);
		}
	}
	public function afterFind()
	{
		if ($this->hasAttribute('status')) {
			$status = ($this->is_deleted == '1') ? $this->is_deleted : $this->status;
			$this->recordStatus = $this->loadStatus('SC'.$status);
		}
		return parent::afterFind();
	}
	public static function getCacheKey($id, $all = false)
	{
		$class = explode('\\', static::class);
		if (is_array($id)) {
			if ($all) {
				return Yii::$app->id . ':models:' .  $class[array_key_first($class)] . ':' . $class[array_key_last($class)] . ':All:' . md5(Json::encode($id));
			}
			return Yii::$app->id . ':models:' .  $class[array_key_first($class)] . ':' . $class[array_key_last($class)] . ':Condition:' . md5(Json::encode($id));
		}
		return Yii::$app->id . ':models:' . $class[array_key_first($class)] . ':' . $class[array_key_last($class)] . ':UID:' . $id;
	}
	protected static function getCacheDuration()
	{
		return isset($_SERVER['APP_CACHE_DURATION']) ? $_SERVER['APP_CACHE_DURATION'] : 1800;
	}
	public static function findOne($condition)
	{
		// Generate a unique cache key
		$cacheKey = static::getCacheKey($condition);
		// Check Redis cache
		$cachedData = Yii::$app->redis->get($cacheKey);
		if ($cachedData) {
			$cachedAttributes = Json::decode($cachedData, true);
			// Construct the model directly from cached attributes
			$model = new static();
			$model->setAttributes($cachedAttributes, false);
			$model->isNewRecord = false; // Mark as an existing record
			return $model;
		}
		// If not cached, fetch from the database
		$model = parent::findOne($condition);
		if ($model) {
			// Cache the model
			Yii::$app->redis->executeCommand('SETEX', [
				$cacheKey,
				static::getCacheDuration(), // Cache expiration time in seconds (1 hour)
				Json::encode($model->toArray())
			]);
		}
		return $model;
	}
	/* public static function findAll($conditions)
	{
		// Generate a unique cache key
		$cacheKey = static::getCacheKey($conditions,true);
		// Check Redis cache
		$cachedData = Yii::$app->redis->get($cacheKey);
		if ($cachedData) {
			// Decode and return cached data
			return Json::decode($cachedData);
		}
		// If not cached, execute the query
		$results = static::findAll($conditions);
		// Cache the results (convert objects to arrays for serialization)
		$resultsAsArray = array_map(fn($model) => $model->toArray(), $results);
		Yii::$app->redis->executeCommand('SETEX', [
			$cacheKey,
			static::getCacheDuration(), // Cache duration in seconds
			Json::encode($resultsAsArray)
		]);
		return $results;
	} */
	public function afterSave($insert, $changedAttributes)
	{
		parent::afterSave($insert, $changedAttributes);
		$cacheKey = static::getCacheKey($this->primaryKey);
		Yii::$app->redis->del($cacheKey); // Remove old cache
	}
	public function afterDelete()
	{
		parent::afterDelete();
		$cacheKey = static::getCacheKey($this->primaryKey);
		Yii::$app->redis->del($cacheKey); // Remove cache for deleted model
	}	
}
