<?php

namespace App\Http\Controllers;

use App\Services\ReadingReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __construct(
        private readonly ReadingReportService $readingReportService
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();

        $stats = $this->readingReportService
            ->generate($user);

        return view('reports.index', compact('stats'));
    }
}
