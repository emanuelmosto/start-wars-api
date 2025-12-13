<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Swapi\SwapiClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class StarWarsController extends Controller
{
    public function __construct(private readonly SwapiClient $client)
    {
    }

    /**
     * Handle search requests for people or movies.
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'sometimes|string|in:people,movies',
            'q' => 'required|string|min:1',
        ]);

        $type = $validated['type'] ?? 'people';
        $query = $validated['q'];

        try {
            $results = $type === 'movies'
                ? $this->client->searchMovies($query)
                : $this->client->searchPeople($query);
        } catch (RuntimeException $e) {
            return response()->json([
                'error' => 'upstream_unavailable',
                'message' => $e->getMessage(),
            ], 502);
        }

        return response()->json([
            'type' => $type,
            'query' => $query,
            'results' => $results,
        ]);
    }

    /**
     * Display a specific person by ID.
     *
     * @param string $id
     *
     * @return JsonResponse
     */
    public function showPerson(string $id): JsonResponse
    {
        try {
            $person = $this->client->getPerson($id);
        } catch (RuntimeException $e) {
            if ($e->getCode() === 404) {
                return response()->json([
                    'error' => 'not_found',
                    'resource' => 'person',
                    'id' => $id,
                ], 404);
            }

            return response()->json([
                'error' => 'upstream_unavailable',
                'message' => $e->getMessage(),
            ], 502);
        }

        return response()->json($person);
    }

    /**
     * Display a specific movie by ID.
     *
     * @param string $id
     *
     * @return JsonResponse
     */
    public function showMovie(string $id): JsonResponse
    {
        try {
            $movie = $this->client->getMovie($id);
        } catch (RuntimeException $e) {
            if ($e->getCode() === 404) {
                return response()->json([
                    'error' => 'not_found',
                    'resource' => 'movie',
                    'id' => $id,
                ], 404);
            }

            return response()->json([
                'error' => 'upstream_unavailable',
                'message' => $e->getMessage(),
            ], 502);
        }

        return response()->json($movie);
    }
}
