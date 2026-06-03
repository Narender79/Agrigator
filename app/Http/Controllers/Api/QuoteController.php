<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Interfaces\QuoteServiceInterface;
use Illuminate\Http\Request;

class QuoteController extends Controller
{
    public function __construct(private readonly QuoteServiceInterface $quoteService) {}

    public function store(Request $request)
    {
        $insurerSlug = $request->query('insurer');

        if (!$insurerSlug) {
            return response()->json([
                'success' => false,
                'message' => 'Please provide the target insurer parameter (e.g. ?insurer=tata-aig).'
            ], 400);
        }

        try {
            $result = $this->quoteService->getInsurerQuote($insurerSlug, $request->all());
            return response()->json($result, $result['success'] ? 200 : 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching quotes: ' . $e->getMessage()
            ], 500);
        }
    }
}
