<?php

namespace App\Tests\Feature\Controllers\Web;

use App\Controllers\Web\Payment as Controller;
use App\Model\Order;
use App\Tests\Feature\Controllers\RequestStatusTrait;
use App\Tests\TestCase;

class PaymentTest extends TestCase
{
    use RequestStatusTrait;

    protected function getUri(): string
    {
        return '/api/web/payment';
    }

    protected function getController(): string
    {
        return Controller::class;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->any($this->getUri(), [$this->getController(), 'process']);
    }

    public function testRobokassaSuccess(): void
    {
        /** @var ORder $order */
        $order = Order::query()->where('service', '=', 'robokassa')
            ->where('status', '!=', 2)
            ->firstOrFail();

        $data = [
            'InvId' => $order->getKey(),
            'IsTest' => 1,
            'OutSum' => $order->cost,
            'SignatureValue' => sha1(implode(':', [$order->cost, $order->id, getenv('ROBOKASSA_TEST_PASS_2')])),
        ];

        $request = $this->createRequest('POST', $this->getUri(), $data);
        $response = $this->app->handle($request);

        self::assertEquals(200, $response->getStatusCode(), 'Ожидается ответ 200');
        self::assertJson($response->getBody()->__toString(), 'Ожидается JSON');
    }

    public function testRobokassaFail(): void
    {
        /** @var ORder $order */
        $order = Order::query()->where('service', '=', 'robokassa')
            ->where('status', '!=', 2)
            ->firstOrFail();

        $data = [
            'InvId' => $order->getKey(),
            'IsTest' => 1,
            'OutSum' => 1,
            'SignatureValue' => '',
        ];

        $request = $this->createRequest('POST', $this->getUri(), $data);

        $response = $this->app->handle($request);

        self::assertEquals(422, $response->getStatusCode(), 'Ожидается ответ 422');
        self::assertJson($response->getBody()->__toString(), 'Ожидается JSON');
    }

    public function testPaypalSuccess()
    {
        self::markTestSkipped('С ходу не понял как покрыть тестами paypal');
    }
}
