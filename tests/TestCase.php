<?php


namespace Tests;


use Illuminate\Config\Repository;
use PHPUnit\Framework\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $config = new Repository([
            'scout' => [
                'mysql' => [
                    'mode' => 'NATURAL_LANGUAGE',
                    'model_directories' => [__DIR__],
                    'min_search_length' => 0,
                    'min_fulltext_search_length' => 4,
                    'min_fulltext_search_fallback' => 'LIKE',
                    'query_expansion' => false
                ]
            ]
        ]);

        app()->instance('config', $config);
    }
}
