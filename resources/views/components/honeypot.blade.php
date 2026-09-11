@props(['id' => 'website'])

<div class="pointer-events-none absolute -left-[9999px] h-px w-px overflow-hidden opacity-0" aria-hidden="true">
    <label for="{{ $id }}">Website</label>
    <input id="{{ $id }}" type="text" name="website" value="" tabindex="-1" autocomplete="off">
</div>
