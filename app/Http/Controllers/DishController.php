<?php

namespace App\Http\Controllers;

use App\Models\Dish;
use App\Support\SchemaOrg;
use App\Support\Faq;
use Illuminate\Http\Request;

class DishController extends Controller
{
    public function index(Request $request)
    {
        $query = Dish::published();
        if ($q = $request->string('q')->trim()->value()) {
            $query->where(fn ($w) => $w->where('name', 'like', "%$q%")->orWhere('summary', 'like', "%$q%")->orWhere('summary_en', 'like', "%$q%"));
        }
        if ($c = $request->query('pays')) {
            $query->where('origin_country', $c);
        }

        seo()->title(__('Encyclopédie des plats africains : histoire et recettes'))
            ->description(__('Attiéké, thiéboudienne, jollof, ndolé, kedjenou… L\'origine, les ingrédients et la recette des grands plats d\'Afrique de l\'Ouest et centrale.'))
            ->schema(SchemaOrg::breadcrumbs([__('Encyclopédie') => null]));

        return view('pages.dishes.index', [
            'dishes' => $query->orderBy('name')->paginate(18)->withQueryString(),
            'countries' => Dish::published()->distinct()->orderBy('origin_country')->pluck('origin_country'),
        ]);
    }

    public function show(Dish $dish)
    {
        abort_unless($dish->isPublished() || auth()->user()?->isAdmin(), 404);
        $dish->increment('views');

        $restaurants = $dish->restaurants()->published()->take(6)->get();
        seo()->title($dish->tr('seo_title') ?: __(':name : origine, ingrédients et recette', ['name' => $dish->name]))
            ->description($dish->tr('seo_description') ?: $dish->tr('summary'))
            ->image($dish->imageUrl())->type('article')->dates($dish->created_at, $dish->updated_at)
            ->schema(SchemaOrg::dish($dish))
            ->schema(SchemaOrg::breadcrumbs([__('Encyclopédie') => route('dishes.index'), __($dish->origin_country) => route('dishes.index', ['pays' => $dish->origin_country]), $dish->name => null]))
            ->faq(Faq::dish($dish, $restaurants));

        return view('pages.dishes.show', [
            'dish' => $dish,
            'restaurants' => $restaurants,
            'craftsmen' => $dish->craftsmen()->published()->get(),
            'related' => Dish::published()->where('id', '!=', $dish->id)->where('origin_country', $dish->origin_country)->take(4)->get(),
        ]);
    }
}
