@extends('layouts.app')
@section('title', $program->title)

@section('content')
<article class="mx-auto max-w-3xl px-4 py-16">
    <p class="text-gold uppercase tracking-widest text-xs">{{ $program->type->label() }}</p>
    <h1 class="mt-3 font-display text-5xl text-forest">{{ $program->title }}</h1>
    <p class="mt-4 text-muted">{{ optional($program->starts_at)->translatedFormat('d F Y H:i') }} @if($program->location) · {{ $program->location }} @endif</p>
    @if ($program->instructor)
        <p class="mt-2 font-medium text-forest">{{ $program->instructor }}</p>
    @endif
    <div class="prose-hacer mt-8">{!! $program->description !!}</div>
</article>
@endsection
