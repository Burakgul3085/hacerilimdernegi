@extends('layouts.app')

@section('title', $activity->title.' — Katılım başvurusu')
@section('description', '“'.$activity->title.'” faaliyeti için katılım formu.')

@section('content')

<x-page-header
    eyebrow="Katılım"
    :title="$activity->title"
    lead="Formu doldurun, dernek yönetimi sizinle iletişime geçsin."
    :breadcrumbs="[
        ['label' => 'Faaliyetler', 'url' => route('activities.index')],
        ['label' => $activity->title, 'url' => route('activities.show', $activity)],
        ['label' => 'Katılım başvurusu'],
    ]" />

<section class="shell pb-16 lg:pb-24">
    <x-participation-form
        class="mt-0"
        :action="route('activities.register', $activity)"
        context="activity"
        :fields="$activity->registrationFieldDefinitions()"
        title="Katılım başvurusu"
        :lead="'“'.$activity->title.'” için başvurunuzu gönderin.'"
    />
</section>

@endsection
