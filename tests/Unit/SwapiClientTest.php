<?php

namespace Tests\Unit;

use App\Services\Swapi\SwapiClient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class SwapiClientTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['cache.default' => 'array']);
        Cache::clear();
    }

    public function test_get_person_happy_path(): void
    {
        Http::fake([
            'https://swapi.tech/api/people/45' => Http::response([
                'result' => [
                    'properties' => [
                        'uid' => '45',
                        'name' => 'Bib Fortuna',
                        'gender' => 'male',
                        'birth_year' => 'unknown',
                        'eye_color' => 'pink',
                        'hair_color' => 'none',
                        'height' => '180',
                        'mass' => 'unknown',
                        'films' => ['https://swapi.tech/api/films/3'],
                    ],
                ],
            ], 200),
            'https://swapi.tech/api/films/3' => Http::response([
                'result' => [
                    'properties' => [
                        'uid' => '3',
                        'title' => 'Return of the Jedi',
                    ],
                ],
            ], 200),
        ]);

        /** @var SwapiClient $client */
        $client = $this->app->make(SwapiClient::class);

        $person = $client->getPerson('45');

        $this->assertSame('45', $person['id']);
        $this->assertSame('Bib Fortuna', $person['name']);
        $this->assertCount(1, $person['movies']);
        $this->assertSame('3', $person['movies'][0]['id']);
        $this->assertSame('Return of the Jedi', $person['movies'][0]['title']);
    }

    public function test_get_person_not_found_throws_runtime_exception_with_404_code(): void
    {
        Http::fake([
            'https://swapi.tech/api/people/9999' => Http::response([
                'message' => 'not found',
            ], 404),
        ]);

        /** @var SwapiClient $client */
        $client = $this->app->make(SwapiClient::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionCode(404);

        $client->getPerson('9999');
    }

    public function test_get_movie_happy_path(): void
    {
        Http::fake([
            'https://swapi.tech/api/films/1' => Http::response([
                'result' => [
                    'properties' => [
                        'uid' => '1',
                        'title' => 'A New Hope',
                        'opening_crawl' => 'It is a period of civil war...',
                        'characters' => [
                            'https://swapi.tech/api/people/1',
                        ],
                    ],
                ],
            ], 200),
            'https://swapi.tech/api/people/1' => Http::response([
                'result' => [
                    'properties' => [
                        'uid' => '1',
                        'name' => 'Luke Skywalker',
                    ],
                ],
            ], 200),
        ]);

        /** @var SwapiClient $client */
        $client = $this->app->make(SwapiClient::class);

        $movie = $client->getMovie('1');

        $this->assertSame('1', $movie['id']);
        $this->assertSame('A New Hope', $movie['title']);
        $this->assertSame('It is a period of civil war...', $movie['opening_crawl']);
        $this->assertCount(1, $movie['characters']);
        $this->assertSame('1', $movie['characters'][0]['id']);
        $this->assertSame('Luke Skywalker', $movie['characters'][0]['name']);
    }

    public function test_get_movie_not_found_throws_runtime_exception_with_404_code(): void
    {
        Http::fake([
            'https://swapi.tech/api/films/9999' => Http::response([
                'message' => 'not found',
            ], 404),
        ]);

        /** @var SwapiClient $client */
        $client = $this->app->make(SwapiClient::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionCode(404);

        $client->getMovie('9999');
    }
}
