<?php

namespace Tests\Feature;

use App\Filament\Pro\Resources\RestaurantResource\Pages\CreateRestaurant;
use App\Models\Restaurant;
use App\Models\User;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class ProfessionalActionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->seed(PlanSeeder::class);
    }

    private function restaurantData(): array
    {
        return ['name' => 'Restaurant opérationnel', 'cuisine' => 'Ivoirienne', 'description' => 'Présentation du restaurant de test.', 'city' => 'Abidjan', 'country' => 'CI'];
    }

    public function test_restaurateur_can_create_a_pending_listing_through_the_pro_form(): void
    {
        $user = User::factory()->create(['roles' => ['restaurateur']]);
        $this->actingAs($user)->get('/pro')->assertOk();
        Livewire::test(CreateRestaurant::class)->fillForm($this->restaurantData())->call('create')->assertHasNoFormErrors();
        $record = Restaurant::firstOrFail();
        $this->assertSame($user->id, $record->owner_id);
        $this->assertSame('pending', $record->status);
        $this->assertNotEmpty($record->slug);
    }

    public static function restrictedPages(): array
    {
        return [['artiste', '/pro/restaurants/create'], ['artiste', '/pro/events/create'], ['restaurateur', '/pro/talent/create']];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('restrictedPages')]
    public function test_direct_creation_urls_respect_professional_roles(string $role, string $path): void
    {
        $this->actingAs(User::factory()->create(['roles' => [$role]]))->get($path)->assertForbidden();
    }

    public function test_a_form_opened_before_the_quota_was_used_cannot_exceed_the_plan(): void
    {
        $user = User::factory()->create(['roles' => ['restaurateur']]);
        $this->actingAs($user)->get('/pro')->assertOk();
        $form = Livewire::test(CreateRestaurant::class)->fillForm($this->restaurantData());
        Restaurant::create(['owner_id' => $user->id, 'name' => 'Fiche existante', 'cuisine' => 'Ivoirienne', 'city' => 'Abidjan', 'country' => 'CI', 'status' => 'pending']);
        $this->assertFalse($user->fresh()->canCreateListing());
        $form->call('create');
        $this->assertSame(1, Restaurant::where('owner_id', $user->id)->count());
    }
}
