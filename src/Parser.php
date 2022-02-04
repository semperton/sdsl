<?php

declare(strict_types=1);

namespace Semperton\Sdsl;

use Generator;
use Iterator;
use RuntimeException;

use function abs;
use function mb_substr;
use function trim;
use function count;
use function array_pop;

final class Parser
{
	const TOKEN_EXPRESSION_START = '(';

	const TOKEN_EXPRESSION_END = ')';

	const TOKEN_INPUT_DELIMITER = ':';

	const TOKEN_VALUE_DELIMITER = ',';

	const TOKEN_ESCAPE_CHARACTER = '\\';

	const TOKEN_STRING_DELIMITER = "'";

	/** @var Iterator<int, string> */
	protected $tokens;

	/** @var Container */
	protected $data;

	/** @var int */
	protected $depth;

	/** @var string */
	protected $part = '';

	/** @var array<int, Container> */
	protected $levels = [];

	/** @var array<int, string|array<int, string>> */
	protected $inputs = [];

	/** @var array<int, string> */
	protected $values = [];

	/** @var bool */
	protected $inString = false;

	/**
	 * @param Iterator<int, string> $tokens
	 */
	protected function __construct(Iterator $tokens, int $depth)
	{
		$this->data = new Container('');
		$this->tokens = $tokens;
		$this->depth = abs($depth);
	}

	/**
	 * @return Generator<int, string>
	 */
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
		$container = $parser->process();

		return new Query($container);
	}

	protected function process(): Container
	{
		while (null !== $token = $this->tokens->current()) {

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

			// ignore whitespace
			if (trim($token) === '') {
				continue;
			}

			// TODO: validity checks from here (syntax...)

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

				$data = new Container($this->part);
				$this->part = '';

				$this->data->push($data);
				$this->levels[] = $this->data;

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
					$content = new Content($this->inputs);
					$this->data->push($content);
					$this->inputs = [];
				}

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
