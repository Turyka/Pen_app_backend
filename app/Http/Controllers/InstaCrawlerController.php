<?php

namespace App\Http\Controllers;

use App\Services\InstaGroupCrawler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InstaCrawlerController extends Controller
{
    public function __construct(
        private InstaGroupCrawler $crawler
    ) {}

    public function crawl(Request $request): JsonResponse
    {
        $username = $request->get('username', 'pannonegyetemnagykanizsa');
        $limit = $request->get('limit', 10);

        $posts = $this->crawler->crawlProfile($username, $limit);

        return response()->json([
            'success' => true,
            'username' => $username,
            'count' => count($posts),
            'posts' => $posts
        ]);
    }
}