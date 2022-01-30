<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Semperton\Sdsl\Parser;

final class ParserTest extends TestCase
{
	public function testInput(): void
	{
		$this->doesNotPerformAssertions();

		$query = "(id eq 55)";
		$data = Parser::parse($query);

		var_dump($data);
	}
}
