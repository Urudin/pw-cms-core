<?php

namespace App\Http\Controllers;

use App\Models\LegalContent;
use Illuminate\Http\Request;

class LegalContentController extends Controller
{
    public function show(LegalContent $legalContent)
    {
        return view('legal-content', compact('legalContent'));
    }

    public function dataHandlingCourse()
    {
        return $this->show(LegalContent::query()->findOrFail(1));
    }

    public function aszf()
    {
        return $this->show(LegalContent::query()->findOrFail(2));
    }
}
