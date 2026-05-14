<?php

namespace App\Http\Controllers;

use App\Models\LegalContent;

class LegalContentController extends Controller
{
    public function show(LegalContent $legalContent)
    {
        return view('legal-content', compact('legalContent'));
    }

    public function showByUrl(string $url)
    {
        $legalContent = LegalContent::query()
            ->where('url', $url)
            ->first();

        if ($legalContent) {
            return $this->show($legalContent);
        }

        return app(PageController::class)->show($url);
    }
}
