<?php

namespace App\Http\Controllers;

use App\Enums\ProgramType;
use App\Models\Program;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProgramController extends Controller
{
    public function index(Request $request): View
    {
        $programs = Program::query()
            ->published()
            ->when($request->filled('tur'), fn ($query) => $query->where('type', $request->string('tur')))
            ->upcoming()
            ->paginate(9)
            ->withQueryString();

        return view('pages.programs.index', [
            'programs' => $programs,
            'types' => ProgramType::cases(),
            'currentType' => $request->string('tur')->toString(),
        ]);
    }

    public function show(Program $program): View
    {
        abort_unless($program->is_published, 404);

        return view('pages.programs.show', compact('program'));
    }
}
