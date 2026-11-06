<?php

namespace App\Http\Controllers\Web\v6;

use App\Http\Controllers\Controller;
use App\Services\New\LanguageService;
use Illuminate\Http\RedirectResponse;

class LocaleController extends Controller
{
    public const SESSION_KEY = 'landing_local';

    public function __construct(private readonly LanguageService $languageService) {}

    public function switch(string $locale): RedirectResponse
    {
        if ($this->languageService->exists(params: ['code' => $locale])) {
            session([self::SESSION_KEY => $locale]);
        }

        return redirect()->back();
    }
}
