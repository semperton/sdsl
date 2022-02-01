<?php

declare(strict_types=1);

namespace Semperton\Sdsl;

use JsonSerializable;

final class Expression implements JsonSerializable
{
	/** @var string */
	protected $field;

	/** @var string */
	protected $operator;

	/** @var string|string[] */
	protected $value;

	/**
	 * @param string|string[] $value
	 */
	public function __construct(string $field, string $operator, $value)
	{
		$this->field = $field;
		$this->operator = $operator;
		$this->value = $value;
	}

	public function getField(): string
	{
		return $this->field;
	}

	public function getOperator(): string
	{
		return $this->operator;
	}

	/**
	 * @return string|string[]
	 */
	public function getValue()
	{
		return $this->value;
	}

	public function jsonSerialize()
	{
		return [
			'field' => $this->field,
			'operator' => $this->operator,
			'value' => $this->value
		];
	}
}
