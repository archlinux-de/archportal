<?php

namespace App\Tests\Service;

use App\Entity\Country;
use App\Repository\CountryRepository;
use App\Service\MirrorFetcher;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class MirrorFetcherTest extends TestCase
{
    public function testFetchMirrors()
    {
        $guzzleMock = new MockHandler([
            new Response(200, [], json_encode([
                'version' => 3,
                'urls' => [
                    [
                        'url' => 'https://127.0.0.1',
                        'protocol' => 'https',
                        'country_code' => 'DE',
                        'last_sync' => '2018-01-01',
                        'delay' => 1,
                        'duration_avg' => 2.1,
                        'score' => 3.2,
                        'completion_pct' => 4.3,
                        'duration_stddev' => 5.4
                    ]
                ]
            ]))
        ]);
        $guzzleHhandler = HandlerStack::create($guzzleMock);
        $guzzleClient = new Client(['handler' => $guzzleHhandler]);

        /** @var CountryRepository|\PHPUnit_Framework_MockObject_MockObject $countryRepository */
        $countryRepository = $this->createMock(CountryRepository::class);
        $countryRepository->method('find')->with('DE')->willReturn(new Country('DE'));

        $mirrorFetcher = new MirrorFetcher($guzzleClient, '', $countryRepository);
        $mirrors = $mirrorFetcher->fetchMirrors();

        $this->assertCount(1, $mirrors);
        $this->assertEquals('https://127.0.0.1', $mirrors[0]->getUrl());
        $this->assertEquals('https', $mirrors[0]->getProtocol());
        $this->assertEquals('DE', $mirrors[0]->getCountry()->getCode());
        $this->assertEquals(new \DateTime('2018-01-01'), $mirrors[0]->getLastSync());
        $this->assertEquals(1, $mirrors[0]->getDelay());
        $this->assertEquals(2.1, $mirrors[0]->getDurationAvg());
        $this->assertEquals(3.2, $mirrors[0]->getScore());
        $this->assertEquals(4.3, $mirrors[0]->getCompletionPct());
        $this->assertEquals(5.4, $mirrors[0]->getDurationStddev());
    }

    public function testExceptionOnEmptyResponse()
    {
        $guzzleMock = new MockHandler([
            new Response()
        ]);
        $guzzleHhandler = HandlerStack::create($guzzleMock);
        $guzzleClient = new Client(['handler' => $guzzleHhandler]);

        /** @var CountryRepository|\PHPUnit_Framework_MockObject_MockObject $countryRepository */
        $countryRepository = $this->createMock(CountryRepository::class);

        $mirrorFetcher = new MirrorFetcher($guzzleClient, '', $countryRepository);

        $this->expectException(\RuntimeException::class);
        $mirrorFetcher->fetchMirrors();
    }

    public function testExceptionOnInvalidResponse()
    {
        $guzzleMock = new MockHandler([
            new Response(200, [], 'foo')
        ]);
        $guzzleHhandler = HandlerStack::create($guzzleMock);
        $guzzleClient = new Client(['handler' => $guzzleHhandler]);

        /** @var CountryRepository|\PHPUnit_Framework_MockObject_MockObject $countryRepository */
        $countryRepository = $this->createMock(CountryRepository::class);

        $mirrorFetcher = new MirrorFetcher($guzzleClient, '', $countryRepository);

        $this->expectException(\RuntimeException::class);
        $mirrorFetcher->fetchMirrors();
    }

    public function testExceptionOnUnknownVersion()
    {
        $guzzleMock = new MockHandler([
            new Response(200, [], json_encode(['version' => 2]))
        ]);
        $guzzleHhandler = HandlerStack::create($guzzleMock);
        $guzzleClient = new Client(['handler' => $guzzleHhandler]);

        /** @var CountryRepository|\PHPUnit_Framework_MockObject_MockObject $countryRepository */
        $countryRepository = $this->createMock(CountryRepository::class);

        $mirrorFetcher = new MirrorFetcher($guzzleClient, '', $countryRepository);

        $this->expectException(\RuntimeException::class);
        $mirrorFetcher->fetchMirrors();
    }

    public function testExceptionOnEmptyMirrorList()
    {
        $guzzleMock = new MockHandler([
            new Response(200, [], json_encode(['version' => 3, 'urls' => []]))
        ]);
        $guzzleHhandler = HandlerStack::create($guzzleMock);
        $guzzleClient = new Client(['handler' => $guzzleHhandler]);

        /** @var CountryRepository|\PHPUnit_Framework_MockObject_MockObject $countryRepository */
        $countryRepository = $this->createMock(CountryRepository::class);

        $mirrorFetcher = new MirrorFetcher($guzzleClient, '', $countryRepository);

        $this->expectException(\RuntimeException::class);
        $mirrorFetcher->fetchMirrors();
    }
}