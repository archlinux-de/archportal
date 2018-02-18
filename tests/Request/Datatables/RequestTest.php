<?php

namespace App\Tests\Request\Datatables;

use App\Request\Datatables\Request;
use App\Request\Datatables\Search;
use PHPUnit\Framework\TestCase;

/**
 * @covers \App\Request\Datatables\Request
 */
class RequestTest extends TestCase
{
    /**
     * @param Search|null $search
     * @param bool $hasSearch
     * @dataProvider provideSearches
     */
    public function testHasSearch(?Search $search, bool $hasSearch)
    {
        $request = new Request(1, 2, 3);
        if (!is_null($search)) {
            $request->setSearch($search);
        }
        $this->assertEquals($hasSearch, $request->hasSearch());
    }

    /**
     * @return array
     */
    public function provideSearches(): array
    {
        /** @var Search|\PHPUnit_Framework_MockObject_MockObject $validSearch */
        $validSearch = $this->createMock(Search::class);
        $validSearch->method('isValid')->willReturn(true);

        /** @var Search|\PHPUnit_Framework_MockObject_MockObject $invalidSearch */
        $invalidSearch = $this->createMock(Search::class);
        $invalidSearch->method('isValid')->willReturn(false);

        return [
            [null, false],
            [$validSearch, true],
            [$invalidSearch, false]
        ];
    }

    public function testJsonSerialize()
    {
        $request = new Request(1, 2, 3);

        $json = json_encode($request);
        $this->assertJson($json);
        $jsonArray = json_decode($json, true);
        $this->assertEquals(
            [
                'start' => 2,
                'length' => 3,
                'search' => null,
                'order' => [],
                'columns' => []
            ],
            $jsonArray
        );
    }

    public function testGetId()
    {
        $requestA = new Request(1, 2, 3);
        $requestB = new Request(1, 2, 4);
        $this->assertNotEquals($requestA->getId(), $requestB->getId());
    }
}
