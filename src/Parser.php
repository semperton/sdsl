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
	protected $data;

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
		/** @var ArrayObject<int, mixed> */
		$this->data = new ArrayObject();
		$this->depth = $depth;
		$this->tokens = $this->tokenize($str);
	}

	public static function parse(string $str, int $depth = 3): Query
	{
		$parser = new self($str, $depth);

		$data = $parser->process();

		return new Query($data);
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
						throw new RuntimeException('Maximum depth reached');
					}

					if ($this->part === '') { // implicit and
						$this->part = 'and';
					} else if ($this->part !== 'and' && $this->part !== 'or') {
						throw new RuntimeException('Invalid connection provided');
					}

					$this->data->append($this->part);
					$this->part = '';

					$this->levels[] = $this->data;

					/** @var ArrayObject<int, mixed> */
					$data = new ArrayObject();
					$this->data->append($data);
					$this->data = $data;
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

					// $this->data[] = $this->inputs;
					if (!!$this->inputs) {
						$this->data->append((object)[
							'field' => $this->inputs[0],
							'operator' => $this->inputs[1],
							'value' => $this->inputs[2]
						]);
						$this->inputs = [];
					}

					/** @var ArrayObject<int, mixed> */
					$this->data = array_pop($this->levels);
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

		return $this->data;
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
