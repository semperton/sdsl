<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Semperton\Sdsl\Container;
use Semperton\Sdsl\Content;
use Semperton\Sdsl\Parser;

final class ParserTest extends TestCase
{
	public function testParseQuery(): void
	{
		$dsl = "(id:gt:5)or(id:in:1,'Te st',5)";

		$query = Parser::parse($dsl);
		$this->assertEquals($dsl, (string)$query);

		$dsl = "(id:eq:5)((name:like:'John Doe')or(name:like:'Jane (Doe)'))";

		$query = Parser::parse($dsl);
		$this->assertEquals($dsl, (string)$query);
	}

	public function testBuildQuery(): void
	{
		$container = new Container('and');
		$container->push(new Content(['id', 'in', [1,2,5]]));

		$dsl = "and(id:in:1,2,5)";

		$this->assertEquals($dsl, (string)$container);
	}
}
