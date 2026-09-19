<?php

namespace App\Filament\Forms\Components;

use Closure;
use Filament\Forms\Components\Concerns\CanLimitItemsLength;
use Filament\Forms\Components\Field;
use Filament\Support\Components\Contracts\HasEmbeddedView;
use Illuminate\Support\Js;

/**
 * Başvuran seçimini kurumsal tablo görünümünde sunar.
 */
class ApplicantPickerTable extends Field implements HasEmbeddedView
{
    use CanLimitItemsLength;

    /**
     * @var list<array{id: string, name: string, email: string, phone: string, status: string}>|Closure|null
     */
    protected array|Closure|null $applicants = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->default([]);
    }

    /**
     * @param  list<array{id: string, name: string, email: string, phone: string, status: string}>|Closure  $applicants
     */
    public function applicants(array|Closure $applicants): static
    {
        $this->applicants = $applicants;

        return $this;
    }

    /**
     * @return list<array{id: string, name: string, email: string, phone: string, status: string}>
     */
    public function getApplicants(): array
    {
        /** @var list<array{id: string, name: string, email: string, phone: string, status: string}> $applicants */
        $applicants = $this->evaluate($this->applicants) ?? [];

        return $applicants;
    }

    public function toEmbeddedHtml(): string
    {
        $applicants = $this->getApplicants();
        $ids = array_values(array_map('strval', array_column($applicants, 'id')));
        $statePath = $this->getStatePath();

        return view('filament.forms.components.applicant-picker-table', [
            'applicants' => $applicants,
            'idsJs' => Js::from($ids),
            'statePath' => $statePath,
            'statePathJs' => Js::from($statePath),
            'wireModel' => $this->applyStateBindingModifiers('wire:model'),
            'isDisabled' => $this->isDisabled(),
            'hasError' => $this->hasErrorForPath($statePath),
        ])->render();
    }
}
