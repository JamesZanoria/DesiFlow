<?php

namespace App\Http\Controllers\Admin\Concerns;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Fixes: delete the last row on page 2 (or any page beyond the first) and
 * the table would otherwise keep showing page 2 -- now empty -- with
 * pagination controls still pointing at a page that no longer has anything
 * on it, rather than falling back to the new actual last page.
 
 * $queryFactory is a Closure that BUILDS a fresh query each time it's
 * called, rather than a pre-built Builder instance. This is required, not
 * a style choice: paginate() applies its own LIMIT/OFFSET directly onto the
 * builder it's given, so calling ->paginate() twice on the same instance
 * (once for the requested page, once for the corrected page) would stack a
 * second LIMIT/OFFSET on top of the first and silently return wrong rows.
 * Rebuilding from scratch each time avoids that entirely.
 */
trait ClampsPaginationPage
{
    private function paginateClamped(Closure $queryFactory, Request $request, int $basePerPage): LengthAwarePaginator
    {
        // Responsive row count: if mobile, reduce the base count (approx 60%)
        $deviceType = $request->header('X-Device-Type', 'desktop');
        $perPage = ($deviceType === 'mobile')
            ? (int) max(floor($basePerPage * 0.6), 1)
            : $basePerPage;

        $page = max((int) $request->input('page', 1), 1);

        $results = $queryFactory()
            ->paginate($perPage, ['*'], 'page', $page)
            ->appends($request->query());

        // Only re-fetch if the page came back empty AND it's genuinely past
        // the real last page (as opposed to a legitimately empty page 1,
        // e.g. an empty table or a search with zero matches -- lastPage()
        // is always at least 1, so currentPage() > lastPage() can only be
        // true when a page beyond the first was requested but no longer
        // exists).
        if ($results->isEmpty() && $results->currentPage() > $results->lastPage()) {
            return $queryFactory()
                ->paginate($perPage, ['*'], 'page', $results->lastPage())
                ->appends($request->query());
        }

        return $results;
    }
}