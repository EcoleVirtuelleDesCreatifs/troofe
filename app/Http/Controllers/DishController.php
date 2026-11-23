<?php

namespace App\Http\Controllers;

use App\Models\Dish;
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

        return view('pages.dishes.index', [
            'dishes' => $query->orderBy('name')->paginate(18)->withQueryString(),
            'countries' => Dish::published()->distinct()->orderBy('origin_country')->pluck('origin_country'),
        ]);
    }

    public function show(Dish $dish)
    {
        abort_unless($dish->isPublished() || auth()->user()?->isAdmin(), 404);
        $dish->increment('views');

        return view('pages.dishes.show', [
            'dish' => $dish,
            'restaurants' => $dish->restaurants()->published()->take(6)->get(),
            'craftsmen' => $dish->craftsmen()->published()->get(),
            'related' => Dish::published()->where('id', '!=', $dish->id)->where('origin_country', $dish->origin_country)->take(4)->get(),
        ]);
    }
}
