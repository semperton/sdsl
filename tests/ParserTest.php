<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Semperton\Sdsl\Parser;

final class ParserTest extends TestCase
{
	public function testInput(): void
	{
		$this->doesNotPerformAssertions();

		$dsl = "(id:eq:55)and((id:eq:32)or(name:like:'John'))";
		$query = Parser::parse($dsl);
		$json = json_encode($query, JSON_PRETTY_PRINT);
		// var_dump($json);

		$dsl = "(id:gte:1)or()";
		$query = Parser::parse($dsl);
		$json = json_encode($query, JSON_PRETTY_PRINT);
		var_dump($json);

		// foreach($query as $con => $entry){
		// 	var_dump($con, $entry);
		// }
	}
}
