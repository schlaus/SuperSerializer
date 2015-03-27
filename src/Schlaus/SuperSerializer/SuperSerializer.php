<?php

namespace Schlaus\SuperSerializer;

use SuperClosure\Serializer;
use SuperClosure\Analyzer\AstAnalyzer;
use SuperClosure\Exception\ClosureUnserializationException;

class SuperSerializer
{
	const CLOSURE     = 1;
	const ARR         = 2;
	const OBJECT      = 3;
	const SCALAR      = 4;
	const BOOLEAN     = 5;
	const NULL        = 6;
	const RESOURCE    = 7;

	protected static $serializer = null;
	protected static $key        = null;
	protected static $analyzer   = null;

	private static function initSerializer($forceReinit = false)
	{
		if (is_null(self::$analyzer)) {
			self::$analyzer = new AstAnalyzer();
		}
		if (is_null(self::$serializer) || $forceReinit) {
			self::$serializer = new Serializer(self::$analyzer, self::$key);
		}
	}

	public static function setAnalyzer($analyzer)
	{
		self::$analyzer = $analyzer;
	}

	public static function setKey($key)
	{
		self::$key = $key;
		self::initSerializer(true);
	}

	public static function serialize($data, $key = null)
	{
		$forceReinit = false;
		if (!is_null($key)) {
			self::$key = $key;
			$forceReinit = true;
		}
		self::initSerializer($forceReinit);
		return serialize(new SerializedData(self::_serialize($data)));
	}

	public static function unserialize($data, $key = null)
	{
		$forceReinit = false;
		if (!is_null($key)) {
			self::$key = $key;
			$forceReinit = true;
		}
		$data = unserialize($data);
		if (!$data instanceof SerializedData) {
			return $data;
		}
		self::initSerializer($forceReinit);
		return self::_unserialize($data->getData());
	}

	protected static function _unserialize($arr)
	{
		//var_dump($arr);
		$type = $arr['type'];
		$data = $arr['data'];
		switch ($type) {
			case SuperSerializer::CLOSURE:
				$data = self::$serializer->unserialize($data);
				break;
			case SuperSerializer::ARR:
				foreach ($data as &$value) {
					$value = self::_unserialize($data);
				}
				break;
			case SuperSerializer::OBJECT:
				$reflection = new \ReflectionObject($data);
				if (!$reflection->hasMethod('__wakeup')) {
					foreach ($reflection->getProperties() as $property) {
						//if ($property->isPrivate() || $property->isProtected()) {
							$property->setAccessible(true);
						//}
						$value = $property->getValue($data);
						$value = self::_unserialize($value);
						$property->setValue($data, $value);
					}
				}
				break;
		}
		return $data;
	}

	protected static function _serialize($data)
	{
		if ($data instanceof \Closure) {
			$data = self::$serializer->serialize($data);
			$type = SuperSerializer::CLOSURE;
		} elseif (is_array($data)) {
			$type = SuperSerializer::ARR;
			foreach ($data as &$value) {
				$value = self::_serialize($value);
			}
		} elseif (is_object($data)) {
			$reflection = new \ReflectionObject($data);
			$type = SuperSerializer::OBJECT;
			if (!$reflection->hasMethod('__sleep')) {
				$clone = clone $data;
				foreach ($reflection->getProperties() as $property) {
					$property->setAccessible(true);
					$value = $property->getValue($data);
					$value = self::_serialize($value);
					$property->setValue($clone, $value);
				}
				unset($data);
				$data = $clone;
				unset($clone);
			}
		} elseif (is_resource($data)) {
			$type = SuperSerializer::RESOURCE;
			$data = get_resource_type($data);
		} elseif (is_null($data)) {
			$type = SuperSerializer::NULL;
		} elseif (is_bool($data)) {
			$type = SuperSerializer::BOOLEAN;
		} else {
			$type = SuperSerializer::SCALAR;
		}
		return array(
			"data" => $data,
			"type" => $type
		);

	}
}