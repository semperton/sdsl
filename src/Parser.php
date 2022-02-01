<?php

declare(strict_types=1);

namespace Semperton\Sdsl;

use ArrayObject;
use Generator;
use Iterator;
use RuntimeException;

final class Parser
{
	const TOKEN_EXPRESSION_START = '(';

	const TOKEN_EXPRESSION_END = ')';

	const TOKEN_INPUT_DELIMITER = ':';

	const TOKEN_VALUE_DELIMITER = ',';

	const TOKEN_ESCAPE_CHARACTER = '\\';

	const TOKEN_STRING_DELIMITER = "'";

	/** @var Generator */
	protected $tokens;

	/** @var ArrayObject<int, mixed> */
	protected $data;

	/** @var int */
	protected $depth;

	/** @var string */
	protected $part = '';

	/** @var array<int, ArrayObject> */
	protected $levels = [];

	/** @var array */
	protected $inputs = [];

	/** @var array */
	protected $values = [];

	/** @var bool */
	protected $inString = false;

	protected function __construct(Iterator $tokens, int $depth)
	{
		/** @var ArrayObject<int, mixed> */
		$this->data = new ArrayObject();
		$this->tokens = $tokens;
		$this->depth = abs($depth);
	}

	public static function tokenize(string $str): Generator
	{
		$position = 0;
		while ('' !== $token = mb_substr($str, $position++, 1)) {
			yield $token;
		}
	}

	public static function parse(string $str, int $depth = 3): Query
	{
		$tokens = self::tokenize(trim($str));

		$parser = new self($tokens, $depth);
		$data = $parser->process();

		return new Query($data);
	}

	protected function process(): ArrayObject
	{
		while (null !== $token = $this->tokens->current()) {

			/** @var string $token */

			$this->tokens->next();

			if ($this->inString) {

				if (self::TOKEN_STRING_DELIMITER === $token) {
					$this->inString = !$this->inString;
					continue;
				}

				if (self::TOKEN_ESCAPE_CHARACTER === $token) {
					if (null !== $next = $this->tokens->current()) {
						$this->part .= $next;
						$this->tokens->next();
					}
					continue;
				}

				$this->part .= $token;
				continue;
			}

			// whitespace
			if (trim($token) === '') {
				continue;
			}

			if (self::TOKEN_STRING_DELIMITER === $token) {
				$this->inString = !$this->inString;
				continue;
			}

			if (self::TOKEN_VALUE_DELIMITER === $token) {

				if ($this->part !== '') {
					$this->values[] = $this->part;
					$this->part = '';
				}

				continue;
			}

			if (self::TOKEN_EXPRESSION_START === $token) {

				if (count($this->levels) >= $this->depth) {
					throw new RuntimeException("Maximum depth of {$this->depth} reached");
				}

				if ($this->part === '') { // implicit and
					$this->part = 'and';
				} else if ($this->part !== 'and' && $this->part !== 'or') {
					throw new RuntimeException("Invalid connection < {$this->part} > provided");
				}

				$this->data->append($this->part);
				$this->part = '';

				$this->levels[] = $this->data;

				/** @var ArrayObject<int, mixed> */
				$data = new ArrayObject();
				$this->data->append($data);
				$this->data = $data;
				continue;
			}

			if (self::TOKEN_EXPRESSION_END === $token) {

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

				if (!!$this->inputs) {
					$expression = new Expression(
						$this->inputs[0],
						$this->inputs[1],
						$this->inputs[2]
					);
					$this->data->append($expression);
					$this->inputs = [];
				}

				/** @var ArrayObject<int, mixed> */
				$this->data = array_pop($this->levels);
				continue;
			}

			if (self::TOKEN_INPUT_DELIMITER === $token) {

				$this->inputs[] = $this->part;
				$this->part = '';
				continue;
			}

			$this->part .= $token;
		}

		return $this->data;
	}
}
