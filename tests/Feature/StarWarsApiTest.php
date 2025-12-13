<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class StarWarsApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['cache.default' => 'array']);
        Cache::clear();
    }

    public function test_search_people_returns_results(): void
    {
        Http::fake([
            'https://swapi.tech/api/people*' => Http::response([
                'message' => 'ok',
                'result' => [
                    [
                        'uid' => '9',
                        'properties' => [
                            'name' => 'Biggs Darklighter',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->getJson('/api/search?type=people&q=BI');

        $response
            ->assertStatus(200)
            ->assertJson([
                'type' => 'people',
                'query' => 'BI',
                'results' => [
                    [
                        'id' => '9',
                        'label' => 'Biggs Darklighter',
                        'type' => 'person',
                    ],
                ],
            ]);
    }

    public function test_show_person_returns_details(): void
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

        $response = $this->getJson('/api/people/45');

        $response
            ->assertStatus(200)
            ->assertJson([
                'id' => '45',
                'name' => 'Bib Fortuna',
            ])
            ->assertJsonPath('movies.0.id', '3')
            ->assertJsonPath('movies.0.title', 'Return of the Jedi');
    }

    public function test_show_person_not_found_returns_404_json(): void
    {
        Http::fake([
            'https://swapi.tech/api/people/9999' => Http::response([
                'message' => 'not found',
            ], 404),
        ]);

        $response = $this->getJson('/api/people/9999');

        $response
            ->assertStatus(404)
            ->assertJson([
                'error' => 'not_found',
                'resource' => 'person',
                'id' => '9999',
            ]);
    }

    public function test_api_route_not_found_returns_json_404(): void
    {
        $response = $this->getJson('/api/does-not-exist');

        $response
            ->assertStatus(404)
            ->assertJson([
                'error' => 'route_not_found',
            ]);
    }

    public function test_stats_returns_default_payload_when_no_snapshot(): void
    {
        Cache::forget('search_stats:latest');

        $response = $this->getJson('/api/stats');

        $response
            ->assertStatus(200)
            ->assertJson([
                'total_queries' => 0,
                'top_queries' => [],
            ]);
    }

    public function test_stats_returns_cached_snapshot(): void
    {
        $snapshot = [
            'generated_at' => '2025-12-13T22:00:00Z',
            'total_queries' => 3,
            'top_queries' => [
                [
                    'query' => 'bi',
                    'type' => 'people',
                    'count' => 2,
                    'percentage' => 2 / 3,
                ],
            ],
            'avg_duration_ms' => 123.4,
            'popular_hour' => 18,
        ];

        Cache::put('search_stats:latest', $snapshot);

        $response = $this->getJson('/api/stats');

        $response
            ->assertStatus(200)
            ->assertJson($snapshot);
    }
}
