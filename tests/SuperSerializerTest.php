<?php
use Schlaus\SuperSerializer\SuperSerializer;

class SuperSerializerTest extends PHPUnit_Framework_TestCase
{
	public function isSerialized($value)
	{
		return (is_string($value) && !!@unserialize($value));
	}

	public function testCanProcessScalar()
	{
		$value = "String";

		$serializedValue = SuperSerializer::serialize($value);

		$this->assertTrue($this->isSerialized($serializedValue));

		$unserializedValue = SuperSerializer::unserialize($serializedValue);

		$this->assertEquals($value, $unserializedValue);


		$value = 42;

		$serializedValue = SuperSerializer::serialize($value);

		$this->assertTrue($this->isSerialized($serializedValue));

		$unserializedValue = SuperSerializer::unserialize($serializedValue);

		$this->assertEquals($value, $unserializedValue);
	}

	public function testCanProcessClosure()
	{
		$value = function($arg1, $arg2) {
			return $arg1.$arg2;
		};

		$serializedValue = SuperSerializer::serialize($value);

		$this->assertTrue($this->isSerialized($serializedValue));

		$unserializedValue = SuperSerializer::unserialize($serializedValue);

		$this->assertEquals($value, $unserializedValue);

		$this->assertEquals("Hello, world!", $unserializedValue("Hello,", " world!"));
	}

	public function testCanProcessObject()
	{
		$value = new stdClass;
		$value->property = "Hello!";
		$value->anotherProperty = function() {
			return "Thanks for the fish!";
		};
		$serializedValue = SuperSerializer::serialize($value);

		$this->assertTrue($this->isSerialized($serializedValue));

		$unserializedValue = SuperSerializer::unserialize($serializedValue);

		$this->assertEquals($value, $unserializedValue);
		$this->assertEquals("Hello!", $unserializedValue->property);

		$fn = $unserializedValue->anotherProperty;
		$this->assertEquals("Thanks for the fish!", $fn());


	}

	public function canSerializeArray()
	{
		$value = array(
			0   => 1,
			"1" => 2,
			1   => 3.14,
			2   => new stdClass,
			3   => function() {
				$obj = new stdClass;
				$obj->property = array(1, 2, 3);
				return $obj;
			},
			4   => array(
				array(
					function() {
						return "I'm hiding here.";
					}
				)
			)
		);

		$serializedValue = SuperSerializer::serialize($value);

		$this->assertTrue($this->isSerialized($serializedValue));

		$unserializedValue = SuperSerializer::unserialize($serializedValue);

		$this->assertEquals($value, $unserializedValue);

		$this->assertEquals("I'm hiding here.", $unserializedValue[4][0]());

	}

}
