@extends('layouts.app')
@section('title', $title)

@section('content')
<article class="mx-auto max-w-3xl px-4 py-16">
    <h1 class="font-display text-5xl text-forest">{{ $title }}</h1>
    <div class="prose-hacer mt-8 whitespace-pre-line">{{ $body ?: 'Bu metin yönetim panelinden düzenlenir. Kişisel veriler Hostinger KVM sunucusunda (Almanya, Frankfurt) saklanır.' }}</div>
</article>
@endsection
