<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\Http\Request;

class ArticleController extends Controller
{
    public function index(Request $request)
    {
        $query = Article::published()->latest('published_at');
        if ($c = $request->query('rubrique')) {
            $query->where('category', $c);
        }

        return view('pages.articles.index', [
            'articles' => $query->paginate(9)->withQueryString(),
            'categories' => Article::published()->distinct()->pluck('category'),
        ]);
    }

    public function show(Article $article)
    {
        abort_unless(($article->status === 'published' && $article->published_at?->isPast()) || auth()->user()?->isAdmin(), 404);
        $article->increment('views');

        return view('pages.articles.show', [
            'article' => $article->load('author'),
            'more' => Article::published()->where('id', '!=', $article->id)->latest('published_at')->take(3)->get(),
        ]);
    }
}
