<?php 

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\HtmlFilterService;
use Illuminate\Support\Facades\Auth;

class ArticleController extends Controller
{
    public function index(Request $request, HtmlFilterService $htmlFilterService)
    {
        $articles = Article::latest()->where('published', true)->take(6)->get();

        // SECURE
        //$articles = $htmlFilterService->filterHtmlCollectionByField($articles,'content');
        if ($request->wantsJson()) {
            return response()->json($articles);
        }
        
        return view('articles.index', compact('articles'));
    }

    // SECURE: protects against SQL Injection through prepared statements.
    public function search(Request $request)
    {
        $searchTerm = $request->input('search');

        $articles = Article::query()
            ->where('published', true)
            ->where(function ($query) use ($searchTerm) {
                $query->where('title', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('content', 'LIKE', "%{$searchTerm}%");
            })
            ->get();
        
        return view('articles.index', compact('articles'));
    }
    
    public function show(Article $article, Request $request)
    {
        $canViewUnpublished = $request->user()?->isAdmin()
            || (int) $request->user()?->getAuthIdentifier() === (int) $article->user_id;

        abort_unless($article->published || $canViewUnpublished, 404);

        if ($request->wantsJson()) {
            return response()->json($article);
        }
        
        return view('articles.show', compact('article'));
    }

    // SECURE
    // public function show(Article $article, Request $request,HtmlFilterService $htmlFilterService)
    // {
    //     $article->content = $htmlFilterService->filterHtml($article->content);
    //     if ($request->wantsJson()) {
    //         return response()->json($article);
    //     }
        
    //     return view('articles.show', compact('article'));
    // }
    
    public function create()
    {
        return view('articles.create');
    }
    
    public function store(Request $request/*,HtmlFilterService $htmlFilterService*/)
    {
        $articleData = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
        ]);

        // SECURE
        //$articleData['content'] = $htmlFilterService->filterHtml($articleData['content']);

        $articleData['user_id']= Auth::id();
        
        $article = Article::create($articleData);
        
        if ($request->wantsJson()) {
            return response()->json($article, 201);
        }
        
        return redirect()->route('articles.index');
    }

    public function edit(Article $article)
    {
        if (! $this->isAuthor($article)) {
            return back()->with('errors', 'Not authorized');
        }

        return view('articles.edit',compact('article'));
    }

    public function update(Request $request, Article $article/*,HtmlFilterService $htmlFilterService*/)
    {
        if (! $this->isAuthor($article)) {
            return back()->with('errors', 'Not authorized');
        }

        $articleData = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
        ]);

        // SECURE
        //$articleData['content'] = $htmlFilterService->filterHtml($articleData['content']);

        $article->update($articleData);
        
        if ($request->wantsJson()) {
            return response()->json($article, 200);
        }
        
        return redirect()->route('articles.show', $article);
    }
    
    public function destroy(Article $article, Request $request)
    {
        if (! $this->isAuthor($article)) {
            return back()->with('errors', 'Not authorized');
        }

        $article->delete();
        
        if ($request->wantsJson()) {
            return response()->json(null, 204);
        }
        
        return redirect()->route('articles.index')->with('message','Article deleted successfully');
    }

    private function isAuthor(Article $article): bool
    {
        return (int) Auth::id() === (int) $article->user_id;
    }
}
