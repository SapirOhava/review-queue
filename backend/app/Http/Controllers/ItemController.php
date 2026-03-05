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
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'state' => ['nullable', Rule::in(['pending', 'approved', 'rejected'])],
            'search' => ['nullable', 'string', 'max:500'],
            'sort' => ['nullable', Rule::in(['created_at', 'risk_score', 'reviewed_at', 'title'])],
            'order' => ['nullable', Rule::in(['asc', 'desc'])],
        ]);

        $query = Item::query();

        // Filter by state
        if (!empty($validated['state'])) {
            $query->where('state', $validated['state']);
        }

        // Search in title/content
        if (!empty($validated['search'])) {
            $search = $validated['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });
        }

        // Sorting (safe allowlist)
        $sort = $validated['sort'] ?? 'created_at';
        $order = $validated['order'] ?? 'desc';

        // If sorting by reviewed_at, you might want reviewed items first
        // but keep it simple and just orderBy.
        $query->orderBy($sort, $order);

        // Keep it simple (no pagination requirement).
        $items = $query->get();

        // Add a computed "suggested_action" for the heuristic (not stored in DB)
        $items->transform(function (Item $item) {
            $item->suggested_action = $this->suggestedAction($item->risk_score);
            return $item;
        });

        return response()->json([
            'items' => $items,
        ]);
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

        $item->suggested_action = $this->suggestedAction($item->risk_score);

        return response()->json([
            'item' => $item,
        ], 201);
    }

    /**
     * GET /api/items/{item}
     */
    public function show(Item $item)
    {
        $item->suggested_action = $this->suggestedAction($item->risk_score);

        return response()->json([
            'item' => $item,
        ]);
    }

    /**
     * POST /api/items/{item}/review
     * Body: { action: "approve"|"reject", note?: string }
     */
    public function review(Request $request, Item $item)
    {
        // Optional: prevent re-review (simple rule). If you want to allow re-review, remove this.
        if ($item->state !== 'pending') {
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

        $item->suggested_action = $this->suggestedAction($item->risk_score);

        return response()->json([
            'item' => $item,
        ]);
    }

    /**
     * Moderation heuristic:
     * Keep it simple and discussable.
     *
     * Rules:
     * +50 if contains banned words (in title or content)
     * +20 if content looks like ALL CAPS (mostly letters are uppercase)
     * +10 if more than 3 links
     */
    private function computeRiskScore(string $title, string $content): int
    {
        $text = mb_strtolower($title . ' ' . $content);

        $score = 0;

        // 1) Banned words rule
        $banned = [
            'spam',
            'scam',
            'buy now',
            'free money',
        ];

        foreach ($banned as $word) {
            if (str_contains($text, $word)) {
                $score += 50;
                break; // only add once
            }
        }

        // 2) ALL CAPS-ish rule (simple approximation)
        // Count letters and how many are uppercase in the original content.
        $letters = preg_match_all('/[A-Za-z]/', $content, $m1);
        $upper = preg_match_all('/[A-Z]/', $content, $m2);

        if ($letters >= 20) { // ignore tiny strings
            $ratio = $upper / max(1, $letters);
            if ($ratio >= 0.8) {
                $score += 20;
            }
        }

        // 3) Too many links rule
        $linkCount = preg_match_all('/https?:\/\/\S+/i', $content, $m3);
        if ($linkCount > 3) {
            $score += 10;
        }

        // Keep it bounded (nice for UI)
        return min(100, $score);
    }

    /**
     * Suggested action derived from risk_score (not stored).
     */
    private function suggestedAction(int $riskScore): string
    {
        return $riskScore >= 50 ? 'reject' : 'approve';
    }
}