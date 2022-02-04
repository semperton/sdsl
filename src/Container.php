<?php

declare(strict_types=1);

namespace Semperton\Sdsl;

use Generator;
use IteratorAggregate;

final class Container implements Stringable, IteratorAggregate
{
	/** @var string */
	protected $name;

	/** @var Stringable[] */
	protected $contents = [];

	public function __construct(string $name)
	{
		$this->name = $name;
	}

	public function push(Stringable $obj): self
	{
		$this->contents[] = $obj;
		return $this;
	}

	/**
	 * @return Generator<int, Stringable>
	 */
	public function getIterator(): Generator
	{
		foreach ($this->contents as $entry) {
			yield $entry;
		}
	}

	public function __toString(): string
	{
		$str = $this->name . Parser::TOKEN_EXPRESSION_START;

		foreach ($this->contents as $entry) {
			$str .= (string)$entry;
		}

		return $str . Parser::TOKEN_EXPRESSION_END;
	}
}
