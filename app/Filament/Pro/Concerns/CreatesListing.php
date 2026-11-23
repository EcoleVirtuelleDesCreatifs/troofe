<?php

namespace App\Filament\Pro\Concerns;

use App\Services\ModerationService;
use Filament\Notifications\Notification;

/**
 * Page de création côté pro : propriétaire automatique, statut « en attente »,
 * respect de la limite de la formule, alerte des administrateurs.
 */
trait CreatesListing
{
    protected function authorizeAccess(): void
    {
        parent::authorizeAccess();

        if (! auth()->user()->canCreateListing()) {
            Notification::make()->title(__('Limite de votre formule atteinte'))
                ->body(__('Passez à une formule supérieure pour publier davantage de fiches.'))
                ->warning()->persistent()->actions([\Filament\Notifications\Actions\Action::make('offres')->label(__('Voir les formules'))->url(route('pricing'))])->send();
            $this->redirect(static::getResource()::getUrl('index'));
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data[static::$ownerColumn ?? 'owner_id'] = auth()->id();
        $data['status'] = 'pending';

        return $data;
    }

    protected function afterCreate(): void
    {
        app(ModerationService::class)->submitted($this->record);
        Notification::make()->title(__('Fiche envoyée pour validation'))->body(__('Vous serez prévenu par e-mail dès sa publication (sous 48 h).'))->success()->send();
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('edit', ['record' => $this->record]);
    }
}
