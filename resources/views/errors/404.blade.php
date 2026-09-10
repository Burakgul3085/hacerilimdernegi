@extends('layouts.error')

@section('title', 'Sayfa bulunamadı')
@section('description', 'Aradığınız sayfa bulunamadı. Ana sayfadan gezmeye devam edebilirsiniz.')

@section('content')
    <div class="relative w-full max-w-[34rem] rounded-[1.75rem] border border-line bg-paper px-8 py-12 text-center shadow-float sm:px-12 sm:py-16">
        <a href="{{ route('home') }}" class="inline-flex justify-center" data-no-veil>
            <img src="{{ $logoUrl }}" alt="{{ $settings['site_name'] }}" class="h-16 w-auto object-contain">
        </a>

        <p class="eyebrow mt-8">Hâcer İlim ve Kültür Derneği</p>
        <p class="mt-5 font-display text-7xl leading-none tracking-tight text-gold sm:text-8xl">404</p>
        <span class="mx-auto mt-6 block h-px w-16 bg-gold/50"></span>
        <h1 class="display-3 mt-6 text-balance">Aradığınız sayfa bulunamadı</h1>
        <p class="mx-auto mt-4 max-w-sm text-sm leading-relaxed text-muted sm:text-base">
            Bağlantı hatalı olabilir veya sayfa taşınmış olabilir. Ana sayfadan gezmeye devam edebilirsiniz.
        </p>

        <a href="{{ route('home') }}" class="btn btn-solid mt-9">
            Ana sayfaya dön
            <x-ui.icon name="arrow-right" class="h-4 w-4" />
        </a>
    </div>
@endsection
