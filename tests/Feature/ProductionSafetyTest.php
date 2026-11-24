<?php

namespace Tests\Feature;

use App\Http\Controllers\PaymentController;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use App\Services\PaymentService;
use App\Services\Payments\SimulationGateway;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class ProductionSafetyTest extends TestCase
{
    use RefreshDatabase;

    private function assertHttpStatus(callable $action, int $status): void
    {
        try {
            $action();
            $this->fail('The operation should have been blocked.');
        } catch (HttpException $exception) {
            $this->assertSame($status, $exception->getStatusCode());
        }
    }

    public function test_simulation_remains_available_outside_production(): void
    {
        $this->assertInstanceOf(SimulationGateway::class, app(PaymentService::class)->gateway('simulation'));
    }

    public function test_unknown_gateway_does_not_fall_back_to_simulation(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        app(PaymentService::class)->gateway('invalid');
    }

    public function test_simulation_gateway_is_blocked_in_production(): void
    {
        $this->app->instance('env', 'production');
        $this->assertHttpStatus(fn () => app(PaymentService::class)->gateway('simulation'), 503);
    }

    public function test_invalid_production_configuration_does_not_create_a_payment(): void
    {
        $this->app->instance('env', 'production');
        config(['services.payments.driver' => 'simulation']);
        $this->assertHttpStatus(fn () => app(PaymentService::class)->start(new User, new Subscription, 15000, 'Test'), 503);
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_simulation_endpoints_are_blocked_in_production(): void
    {
        $this->app->instance('env', 'production');
        $controller = app(PaymentController::class);
        $this->assertHttpStatus(fn () => $controller->simulate(Request::create('/'), new Payment), 404);
        $this->assertHttpStatus(fn () => $controller->decide(Request::create('/', 'POST'), new Payment), 404);
    }

    public function test_demo_seeding_is_blocked_in_production(): void
    {
        $this->app->instance('env', 'production');
        $this->expectException(\LogicException::class);
        (new DatabaseSeeder)->run();
    }
}
