<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureArticleAuthor
{
    public function handle(Request $request, Closure $next): Response
    {
        $article = $request->route('article');

        if (!$article || $request->user()?->id !== $article->user_id) {
            return redirect()->route('articles.show', $article)->with('message', 'Not authorized');
        }

        return $next($request);
    }
}
