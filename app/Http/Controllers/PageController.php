<?php

namespace App\Http\Controllers;

use App\Models\Page;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class PageController extends Controller
{
    public function index()
    {
        return response()->json(Page::all());
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'title' => 'required',
            'slug' => 'nullable|unique:pages,slug',
            'meta_title' => 'nullable',
            'meta_keywords' => 'nullable',
            'meta_description' => 'nullable',
            'content' => 'nullable',
        ]);

        $page = Page::create($request->all());
        return response()->json($page);
    }

    public function show(string $slug): View
    {
        $page = Page::query()->firstWhere('slug', $slug);
        return view('page', ['page' => $page]);
    }
}
