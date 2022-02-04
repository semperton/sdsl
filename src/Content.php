<?php

declare(strict_types=1);

namespace Semperton\Sdsl;

use function strpos;
use function strtr;
use function is_array;
use function array_map;
use function implode;

final class Content implements Stringable
{
	/** @var array<int, scalar|array<int, scalar>> */
	protected $data = [];

	/**
	 * @param array<int, scalar|array<int, scalar>> $data
	 */
	public function __construct(array $data)
	{
		$this->data = $data;
	}

	/**
	 * @param scalar $value
	 */
	protected function prepareString($value): string
	{
		$chars = [
			Parser::TOKEN_EXPRESSION_START,
			Parser::TOKEN_EXPRESSION_END,
			Parser::TOKEN_INPUT_DELIMITER,
			Parser::TOKEN_VALUE_DELIMITER,
			Parser::TOKEN_ESCAPE_CHARACTER,
			Parser::TOKEN_STRING_DELIMITER,
			' ' // space
		];

		$value = (string)$value;

		// check for special chars
		foreach ($chars as $char) {

			if (strpos($value, $char) === false) {
				continue;
			}

			$value = strtr($value, [
				Parser::TOKEN_ESCAPE_CHARACTER => Parser::TOKEN_ESCAPE_CHARACTER . Parser::TOKEN_ESCAPE_CHARACTER,
				Parser::TOKEN_STRING_DELIMITER => Parser::TOKEN_ESCAPE_CHARACTER . Parser::TOKEN_STRING_DELIMITER
			]);

			return Parser::TOKEN_STRING_DELIMITER . $value . Parser::TOKEN_STRING_DELIMITER;
		}

		return $value;
	}

	public function __toString(): string
	{
		$parts = [];

		foreach ($this->data as $entry) {
			if (is_array($entry)) {
				$entry = array_map([$this, 'prepareString'], $entry);
				$parts[] = implode(Parser::TOKEN_VALUE_DELIMITER, $entry);
			} else {
				$parts[] = $this->prepareString($entry);
			}
		}

		return implode(Parser::TOKEN_INPUT_DELIMITER, $parts);
	}
}
