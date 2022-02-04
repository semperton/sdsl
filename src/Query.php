<?php

declare(strict_types=1);

namespace Semperton\Sdsl;

use Generator;
use IteratorAggregate;

final class Query implements IteratorAggregate
{
	/** @var Container */
	protected $container;

	public function __construct(Container $container)
	{
		$this->container = $container;
	}

	/**
	 * @return Generator<int, Stringable>
	 */
	public function getIterator(): Generator
	{
		return $this->container->getIterator();
	}

	public function __toString(): string
	{
		$str = '';

		foreach ($this->container as $entry) {
			$str .= (string)$entry;
		}

		return $str;
	}
}
