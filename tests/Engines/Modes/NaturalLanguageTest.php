<?php


namespace Tests\Engines\Modes;

use Illuminate\Support\Facades\DB;
use Laravel\Scout\Builder;
use Tests\TestCase;
use Tests\TestModel;
use Yab\MySQLScout\Engines\Modes\NaturalLanguage;

class NaturalLanguageTest extends TestCase
{
    public function testNaturalLanguage()
    {
        $this->mockDb();

        $mode = new NaturalLanguage();
        $builder = new Builder(new TestModel(), __METHOD__);

        $this->assertEquals(
            '*, MATCH(first_name,last_name) AGAINST(? IN NATURAL LANGUAGE MODE) as relevance',
            $mode->buildSelectColumns($builder)
        );
        $this->assertEquals(
            'MATCH(first_name,last_name) AGAINST(? IN NATURAL LANGUAGE MODE)',
            $mode->buildWhereRawString($builder)
        );
        $this->assertEquals([__METHOD__], $mode->buildParams($builder));
    }

    public function testWithQueryExpansion()
    {
        $this->mockDb();
        config()->set('scout.mysql.query_expansion', true);

        $mode = new NaturalLanguage();
        $builder = new Builder(new TestModel(), __METHOD__);

        $this->assertEquals(
            '*, MATCH(first_name,last_name) AGAINST(? IN NATURAL LANGUAGE MODE WITH QUERY EXPANSION) as relevance',
            $mode->buildSelectColumns($builder)
        );
        $this->assertEquals(
            'MATCH(first_name,last_name) AGAINST(? IN NATURAL LANGUAGE MODE WITH QUERY EXPANSION)',
            $mode->buildWhereRawString($builder)
        );
        $this->assertEquals([__METHOD__], $mode->buildParams($builder));
    }

    private function mockDb(): void
    {
        DB::shouldReceive('connection')->andReturnSelf();
        DB::shouldReceive('getSchemaBuilder')->andReturnSelf();
        DB::shouldReceive('getColumnListing')->andReturn([
            'first_name',
            'last_name',
            'age',
        ]);
        DB::shouldReceive('select')->andReturn([
            (object)['Type' => 'VARCHAR'],
        ]);
    }
}
