<?php


namespace Schlaus\SuperSerializer;


class SerializedData implements \Serializable
{
	protected $data;

	public function __construct($data)
	{
		$this->data = $data;
	}

	public function getData()
	{
		return $this->data;
	}

	public function serialize()
	{
		return serialize($this->data);
	}

	public function unserialize($serialized)
	{
		$this->data = unserialize($serialized);
	}
}