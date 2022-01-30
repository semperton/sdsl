<?php

declare(strict_types=1);

namespace Semperton\Sdsl;

use ArrayObject;
use Generator;
use RuntimeException;

final class Parser
{
	/** @var Generator */
	protected $tokens;

	/** @var ArrayObject<int, mixed> */
	protected $filter;

	/** @var int */
	protected $depth;

	/** @var string */
	protected $part = '';

	/** @var int */
	protected $position = 0;

	/** @var array<int, ArrayObject> */
	protected $levels = [];

	/** @var array */
	protected $inputs = [];

	/** @var array */
	protected $values = [];

	/** @var bool */
	protected $inString = false;

	protected function __construct(string $str, int $depth)
	{
		$this->tokens = $this->tokenize($str);
		/** @var ArrayObject<int, mixed> */
		$this->filter = new ArrayObject();
		$this->depth = $depth;
	}

	public static function parse(string $str, int $depth = 3): ArrayObject
	{
		$parser = new self($str, $depth);
		$result = $parser->process();

		return $result;
	}

	protected function process(): ArrayObject
	{
		while (null !== $token = $this->next()) {

			switch ($token) {
				case ',':
					if ($this->inString) {
						$this->part .= $token;
						break;
					}
					if ($this->part !== '') {
						$this->values[] = $this->part;
						$this->part = '';
					}
					break;
				case ' ':
					if ($this->inString) {
						$this->part .= $token;
					}
					break;
				case '\\':
					if ($this->inString && $next = $this->next()) {
						$this->part .= $next;
					}
					break;
				case "'":
					$this->inString = !$this->inString;
					break;
				case '(':

					if ($this->inString) {
						$this->part .= $token;
						break;
					}

					if (count($this->levels) >= $this->depth) {
						throw new RuntimeException('Maximum filter depth reached');
					}

					if ($this->part === '') { // implicit and
						$this->part = 'and';
					} else if ($this->part !== 'and' && $this->part !== 'or') {
						throw new RuntimeException('Invalid filter connection provided');
					}

					$this->filter->append($this->part);
					$this->part = '';

					$this->levels[] = $this->filter;

					/** @var ArrayObject<int, mixed> */
					$filter = new ArrayObject();
					$this->filter->append($filter);
					$this->filter = $filter;
					break;
				case ')':

					if ($this->inString) {
						$this->part .= $token;
						break;
					}

					if ($this->part !== '') {
						$this->values[] = $this->part;
						$this->part = '';
					}

					if (0 !== $count = count($this->values)) {
						if ($count > 1) {
							$this->inputs[] = $this->values;
						} else {
							$this->inputs[] = $this->values[0];
						}
						$this->values = [];
					}

					// $this->filter[] = $this->inputs;
					if (!!$this->inputs) {
						$this->filter->append([
							'field' => $this->inputs[0],
							'operator' => $this->inputs[1],
							'value' => $this->inputs[2]
						]);
						$this->inputs = [];
					}

					/** @var ArrayObject<int, mixed> */
					$this->filter = array_pop($this->levels);
					break;
				case ':':
					if ($this->inString) {
						$this->part .= $token;
						break;
					}
					$this->inputs[] = $this->part;
					$this->part = '';
					break;
				default:
					$this->part .= $token;
			}
		}

		return $this->filter;
	}

	protected function next(): ?string
	{
		/** @var null|string */
		$value = $this->tokens->current();
		$this->tokens->next();
		return $value;
	}

	protected function tokenize(string $str): Generator
	{
		while ('' !== $token = mb_substr($str, $this->position++, 1)) {
			yield $token;
		}
	}
}
