<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use brunaobh\Search\Search\Search;
use Illuminate\Http\Request;

class PackageTest extends TestCase
{
    /**
     * @test
     */
    public function try_to_parse_fields()
    {
        $request = new Request();
        $request->replace(['fields' => 'email,name']);

        $search = new Search();
        $search->handleRequest($request);

        $this->assertCount(2, $search->getFields());
        $this->assertContains('email', $search->getFields());
        $this->assertContains('name', $search->getFields());
    }

    /**
     * @test
     */
    public function try_to_parse_filters()
    {
        $request = new Request();
        $request->replace(['filters' => 'email=teste@email.com']);

        $search = new Search();
        $search->handleRequest($request);

        $this->assertContains('email=teste@email.com', $search->getFilters());
    }

    /**
     * @test
     */
    public function try_to_parse_relations()
    {
        $request = new Request();
        $request->replace(['fields' => 'email,name,address(id,street)']);

        $search = new Search();
        $search->handleRequest($request);

        $this->assertArrayHasKey('address', $search->getRelations());
    }

    /**
     * @test
     */
    public function try_to_parse_relations_filters()
    {
        $request = new Request();
        $request->replace(['filters' => 'address(id=1)']);

        $search = new Search();
        $search->handleRequest($request);

        $this->assertArrayHasKey('address', $search->getRelationsFilters());
        $this->assertContains('id=1', $search->getRelationsFilters());
    }

    /**
     * @test
     */
    public function method_to_return_query_condition()
    {
        $search = new Search();
        $condition = $search->str_array_pos('id=1', ['!=', '>=', '<=', '=', '>', '<', '%']);

        $this->assertCount(3, $condition);
        $this->assertArrayHasKey('attribute', $condition);
        $this->assertArrayHasKey('operator', $condition);
        $this->assertArrayHasKey('value', $condition);
        $this->assertContains('id', $condition);
        $this->assertContains('=', $condition);
        $this->assertContains('1', $condition);
    }
}
