<?php

namespace App\Http\Controllers;

use App\Models\Program;
use App\Support\ProgramFeed;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProgramController extends Controller
{
    public function index(Request $request): View
    {
        $scope = $request->string('durum')->toString() === 'gecmis' ? 'gecmis' : 'yaklasan';
        $month = $request->string('ay')->toString();

        if (preg_match('/^\d{4}-\d{2}$/', $month) !== 1) {
            $month = '';
        }

        return view('pages.programs.index', [
            'grouped' => ProgramFeed::grouped($scope, $month),
            'monthOptions' => ProgramFeed::monthOptions($scope),
            'currentMonth' => $month,
            'currentScope' => $scope,
        ]);
    }

    public function show(Program $program): View
    {
        abort_unless($program->is_published, 404);

        $related = Program::query()
            ->published()
            ->upcoming()
            ->whereKeyNot($program->getKey())
            ->where('type', $program->type)
            ->limit(4)
            ->get();

        return view('pages.programs.show', compact('program', 'related'));
    }
}
