<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ItemController extends Controller
{
    /**
     * GET /api/items
     * Optional query params:
     * - state: pending|approved|rejected
     * - search: string
     * - sort: created_at|risk_score|reviewed_at|title
     * - order: asc|desc
     * - per_page: 1-100
     * - page: handled automatically by Laravel paginator
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'state' => ['nullable', Rule::in(['pending', 'approved', 'rejected'])],
            'search' => ['nullable', 'string', 'max:500'],
            'sort' => ['nullable', Rule::in(['created_at', 'risk_score', 'reviewed_at', 'title'])],
            'order' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = Item::query();

        // Filter by state if the client sent a state query param.
        if (!empty($validated['state'])) {
            $query->where('state', $validated['state']);
        }

        // Search inside title or content if the client sent a search term.
        if (!empty($validated['search'])) {
            $search = $validated['search'];

            // This creates a nested SQL condition like:
            // WHERE (title LIKE '%term%' OR content LIKE '%term%')
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%");
            });
        }

        // Safe defaults if sort/order were not provided.
        $sort = $validated['sort'] ?? 'created_at';
        $order = $validated['order'] ?? 'desc';

        $query->orderBy($sort, $order);

        // Pagination bonus:
        // default to 10 items per page if the client doesn't send per_page.
        $perPage = $validated['per_page'] ?? 10;

        // paginate() returns a paginator object, not just a plain collection.
        $items = $query->paginate($perPage);

        // Add a computed suggested_action field to each item in the current page.
        // This is derived from risk_score and is not stored in the database.
        $items->getCollection()->transform(function (Item $item) {
            $item->suggested_action = $this->suggestedAction($item->risk_score);
            return $item;
        });

        // 200 OK = the request succeeded and the list of items was returned.
        return response()->json($items, 200);
    }

    /**
     * POST /api/items
     * Body: { title: string, content: string }
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string', 'max:10000'],
        ]);

        $riskScore = $this->computeRiskScore($validated['title'], $validated['content']);

        $item = Item::create([
            'title' => $validated['title'],
            'content' => $validated['content'],
            'state' => 'pending',
            'risk_score' => $riskScore,
        ]);

        // Add a computed suggested_action field for the API response.
        $item->suggested_action = $this->suggestedAction($item->risk_score);

        // 201 Created = a new item was successfully created.
        return response()->json([
            'item' => $item,
        ], 201);
    }

    /**
     * GET /api/items/{item}
     */
    public function show(Item $item)
    {
        // Add a computed suggested_action field for the API response.
        $item->suggested_action = $this->suggestedAction($item->risk_score);

        // 200 OK = the request succeeded and a single item was returned.
        return response()->json([
            'item' => $item,
        ], 200);
    }

    /**
     * POST /api/items/{item}/review
     * Body: { action: "approve"|"reject", note?: string }
     */
    public function review(Request $request, Item $item)
    {
        // Prevent re-review:
        // only items in the pending state can be reviewed.
        if ($item->state !== 'pending') {
            // 409 Conflict = the request conflicts with the current item state.
            // This item is already approved or rejected, so it cannot be reviewed again.
            return response()->json([
                'message' => 'Item was already reviewed.',
            ], 409);
        }

        $validated = $request->validate([
            'action' => ['required', Rule::in(['approve', 'reject'])],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        $item->state = $validated['action'] === 'approve' ? 'approved' : 'rejected';
        $item->review_note = $validated['note'] ?? null;
        $item->reviewed_at = now();
        $item->save();

        // Add a computed suggested_action field for the API response.
        $item->suggested_action = $this->suggestedAction($item->risk_score);

        // 200 OK = the item was successfully reviewed and updated.
        return response()->json([
            'item' => $item,
        ], 200);
    }

    /**
     * Simple moderation heuristic.
     *
     * Rules:
     * +50 if title/content contains banned words
     * +20 if content is mostly ALL CAPS
     * +10 if content contains more than 3 links
     */
    private function computeRiskScore(string $title, string $content): int
    {
        $text = mb_strtolower($title . ' ' . $content);
        $score = 0;

        $banned = [
            'spam',
            'scam',
            'buy now',
            'free money',
        ];

        foreach ($banned as $word) {
            if (str_contains($text, $word)) {
                $score += 50;
                break;
            }
        }

        // Count all letters and uppercase letters in the original content.
        $letters = preg_match_all('/[A-Za-z]/', $content);
        $upper = preg_match_all('/[A-Z]/', $content);

        // Ignore very short text so tiny strings do not trigger the ALL CAPS rule.
        if ($letters >= 20) {
            $ratio = $upper / $letters;

            if ($ratio >= 0.8) {
                $score += 20;
            }
        }

        // Count URLs in the content.
        $linkCount = preg_match_all('/https?:\/\/\S+/i', $content);

        if ($linkCount > 3) {
            $score += 10;
        }

        // Keep the score between 0 and 100.
        return min(100, $score);
    }

    /**
     * Compute a suggested action from the risk score.
     * This value is derived and is not stored in the database.
     */
    private function suggestedAction(int $riskScore): string
    {
        return $riskScore >= 50 ? 'reject' : 'approve';
    }
}