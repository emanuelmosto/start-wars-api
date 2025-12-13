<?php

namespace App\Services\Swapi;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class SwapiClient
{
    private string $baseUri;
    private int $timeoutSeconds;
    private int $cacheTtlMinutes;

    public function __construct()
    {
        $this->baseUri = rtrim(config('services.swapi.base_uri'), '/');
        $this->timeoutSeconds = (int) config('services.swapi.timeout', 5);
        $this->cacheTtlMinutes = (int) config('services.swapi.cache_ttl_minutes', 60);
    }


    /**
     * Search for people by name.
     *
     * @param string $query
     *
     * @return array<int, array{id:string,label:string,type:string}>
     */
    public function searchPeople(string $query): array
    {
        $cacheKey = sprintf('search:people:q=%s', mb_strtolower($query));

        return Cache::remember($cacheKey, now()->addMinutes($this->cacheTtlMinutes), function () use ($query): array {
            $payload = $this->get('/people', ['name' => $query]);

            $results = Arr::get($payload, 'result', []);

            return collect($results)
                ->map(function ($item): array {
                    $uid = (string) Arr::get($item, 'uid');
                    $name = (string) Arr::get($item, 'properties.name');

                    return [
                        'id' => $uid,
                        'label' => $name,
                        'type' => 'person',
                    ];
                })
                ->values()
                ->all();
        });
    }

    /**
     * Search for movies by title.
     *
     * @param string $query
     *
     * @return array<int, array{id:string,label:string,type:string}>
     */
    public function searchMovies(string $query): array
    {
        $cacheKey = sprintf('search:movies:q=%s', mb_strtolower($query));

        return Cache::remember($cacheKey, now()->addMinutes($this->cacheTtlMinutes), function () use ($query): array {
            $payload = $this->get('/films', ['title' => $query]);

            $results = Arr::get($payload, 'result', []);

            return collect($results)
                ->map(function ($item): array {
                    $uid = (string) Arr::get($item, 'uid');
                    $title = (string) Arr::get($item, 'properties.title');

                    return [
                        'id' => $uid,
                        'label' => $title,
                        'type' => 'movie',
                    ];
                })
                ->values()
                ->all();
        });
    }

    /**
     * Get details of a person by ID, including list of movies.
     *
     * @param string $id
     *
     * @return array{id:string,name:string,gender:string|null,birth_year:string|null,eye_color:string|null,hair_color:string|null,height:string|null,mass:string|null,movies:array<int,array{id:string,title:string}>}
     */
    public function getPerson(string $id): array
    {
        $cacheKey = sprintf('people:%s', $id);

        return Cache::remember($cacheKey, now()->addMinutes($this->cacheTtlMinutes), function () use ($id): array {
            $payload = $this->get(sprintf('/people/%s', $id));

            $properties = Arr::get($payload, 'result.properties', []);

            $films = collect(Arr::get($properties, 'films', []))
                ->map(function (string $filmUrl): ?array {
                    $filmId = $this->extractIdFromUrl($filmUrl);

                    if ($filmId === null) {
                        return null;
                    }

                    $movie = $this->getMovie($filmId);

                    return [
                        'id' => $movie['id'],
                        'title' => $movie['title'],
                    ];
                })
                ->filter()
                ->values()
                ->all();

            return [
                'id' => (string) Arr::get($properties, 'uid', $id),
                'name' => (string) Arr::get($properties, 'name'),
                'gender' => Arr::get($properties, 'gender'),
                'birth_year' => Arr::get($properties, 'birth_year'),
                'eye_color' => Arr::get($properties, 'eye_color'),
                'hair_color' => Arr::get($properties, 'hair_color'),
                'height' => Arr::get($properties, 'height'),
                'mass' => Arr::get($properties, 'mass'),
                'movies' => $films,
            ];
        });
    }

    /**
     * Get details of a movie by ID, including list of characters.
     *
     * @param string $id
     *
     * @return array{id:string,title:string,opening_crawl:string|null,characters:array<int,array{id:string,name:string}>}
     */
    public function getMovie(string $id): array
    {
        $cacheKey = sprintf('movies:%s', $id);

        return Cache::remember($cacheKey, now()->addMinutes($this->cacheTtlMinutes), function () use ($id): array {
            $payload = $this->get(sprintf('/films/%s', $id));

            $properties = Arr::get($payload, 'result.properties', []);

            $characters = collect(Arr::get($properties, 'characters', []))
                ->map(function (string $personUrl): ?array {
                    $personId = $this->extractIdFromUrl($personUrl);

                    if ($personId === null) {
                        return null;
                    }

                    $person = $this->getPersonBasic($personId);

                    return [
                        'id' => $person['id'],
                        'name' => $person['name'],
                    ];
                })
                ->filter()
                ->values()
                ->all();

            return [
                'id' => (string) Arr::get($properties, 'uid', $id),
                'title' => (string) Arr::get($properties, 'title'),
                'opening_crawl' => Arr::get($properties, 'opening_crawl'),
                'characters' => $characters,
            ];
        });
    }

    /**
     * Get basic info of a person by ID (id and name only).
     *
     * @param string $id
     *
     * @return array{id:string,name:string}
     */
    private function getPersonBasic(string $id): array
    {
        $cacheKey = sprintf('people_basic:%s', $id);

        return Cache::remember($cacheKey, now()->addMinutes($this->cacheTtlMinutes), function () use ($id): array {
            $payload = $this->get(sprintf('/people/%s', $id));

            $properties = Arr::get($payload, 'result.properties', []);

            return [
                'id' => (string) Arr::get($properties, 'uid', $id),
                'name' => (string) Arr::get($properties, 'name'),
            ];
        });
    }

    /**
     * Perform a GET request to the SWAPI.
     *
     * @param string $path
     * @param array $query
     * @return array
     */
    private function get(string $path, array $query = []): array
    {
        $url = $this->baseUri . $path;

        try {
            $response = Http::timeout($this->timeoutSeconds)
                ->acceptJson()
                ->get($url, $query);
        } catch (ConnectionException $e) {
            throw new RuntimeException('SWAPI connection error', 0, $e);
        }

        if (! $response->successful()) {
            throw new RuntimeException(sprintf('SWAPI error: HTTP %d', $response->status()));
        }

        $data = $response->json();

        if (! is_array($data)) {
            throw new RuntimeException('SWAPI returned invalid JSON structure');
        }

        return $data;
    }

    /**
     * Extract the ID from a SWAPI resource URL.
     *
     * @param string $url
     *
     * @return string|null
     */
    private function extractIdFromUrl(string $url): ?string
    {
        $parts = parse_url($url);

        if (! isset($parts['path'])) {
            return null;
        }

        $segments = array_values(array_filter(explode('/', $parts['path'])));

        $last = end($segments);

        return $last !== false ? (string) $last : null;
    }
}
