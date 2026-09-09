<?php

namespace App\Http\Controllers;

use App\Enums\ProgramType;
use App\Models\Program;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProgramController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->string('ara'));

        $programs = Program::query()
            ->published()
            ->when($request->filled('tur'), fn (Builder $query) => $query->where('type', $request->string('tur')))
            ->when($search !== '', function (Builder $query) use ($search): void {
                $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $search).'%';

                $query->where(fn (Builder $builder) => $builder
                    ->where('title', 'like', $like)
                    ->orWhere('instructor', 'like', $like)
                    ->orWhere('description', 'like', $like));
            })
            ->upcoming()
            ->paginate(9)
            ->withQueryString();

        return view('pages.programs.index', [
            'programs' => $programs,
            'types' => ProgramType::cases(),
            'currentType' => $request->string('tur')->toString(),
            'currentSearch' => $search,
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
