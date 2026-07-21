<?php

namespace App\Http\Middleware;

use App\Models\Article;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureArticleAuthor
{
    public function handle(Request $request, Closure $next): Response
    {
        $article = $request->route('article');

        if (! $article instanceof Article
            || (int) $request->user()?->getAuthIdentifier() !== (int) $article->user_id) {
            return back()->with('errors', 'Not authorized');
        }

        return $next($request);
    }
}
