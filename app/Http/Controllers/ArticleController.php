<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Support\SchemaOrg;
use Illuminate\Http\Request;

class ArticleController extends Controller
{
    public function index(Request $request)
    {
        $query = Article::published()->latest('published_at');
        if ($c = $request->query('rubrique')) {
            $query->where('category', $c);
        }

        seo()->title(__('Actualités de la gastronomie africaine'))->description(__('Portraits de chefs, tendances, recettes et annonces : le magazine Trôofê de la table africaine.'))->schema(SchemaOrg::breadcrumbs([__('Actualités') => null]));

        return view('pages.articles.index', [
            'articles' => $query->paginate(9)->withQueryString(),
            'categories' => Article::published()->distinct()->pluck('category'),
        ]);
    }

    public function show(Article $article)
    {
        abort_unless(($article->status === 'published' && $article->published_at?->isPast()) || auth()->user()?->isAdmin(), 404);
        $article->increment('views');

        seo()->title($article->tr('seo_title') ?: $article->tr('title'))->description($article->tr('seo_description') ?: $article->tr('excerpt'))
            ->image($article->coverUrl())->type('article')->dates($article->published_at, $article->updated_at)
            ->schema(SchemaOrg::article($article->loadMissing('author')))
            ->schema(SchemaOrg::breadcrumbs([__('Actualités') => route('articles.index'), $article->tr('title') => null]));

        return view('pages.articles.show', [
            'article' => $article->load('author'),
            'more' => Article::published()->where('id', '!=', $article->id)->latest('published_at')->take(3)->get(),
        ]);
    }
}
