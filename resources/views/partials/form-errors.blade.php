@if ($errors->any())
    <div class="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-800">
        <ul class="list-disc pl-4">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
