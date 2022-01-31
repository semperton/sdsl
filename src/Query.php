<?php

declare(strict_types=1);

namespace Semperton\Sdsl;

use ArrayObject;
use IteratorAggregate;
use JsonSerializable;
use Generator;

final class Query implements IteratorAggregate, JsonSerializable
{
	/** @var ArrayObject */
	protected $data;

	public function __construct(ArrayObject $data)
	{
		$this->data = $data;
	}

	public function getIterator(): Generator
	{
		$connection = '';
		foreach ($this->data as $entry) {

			if (is_string($entry)) {
				$connection = $entry;
				continue;
			}

			yield $connection => $entry;
		}
	}

	protected function unfold(ArrayObject $data)
	{
		$result = [];

		foreach ($data as $entry) {

			if ($entry instanceof ArrayObject) {
				if(count($entry)){
					$result[] = $this->unfold($entry);
				}
			} else {
				$result[] = $entry;
			}
		}

		return count($result) === 1 ? $result[0] : $result;
	}

	public function toArray(): array
	{
		return $this->unfold($this->data);
	}

	public function jsonSerialize()
	{
		return $this->toArray();
	}
}
