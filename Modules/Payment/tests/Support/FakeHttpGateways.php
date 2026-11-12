<?php

declare(strict_types=1);

namespace Modules\Payment\Tests\Support;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

final class FakeHttpGateways
{
    public array $stripeSessions = [];

    public array $paypalOrders = [];

    public array $zibalTracks = [];

    public array $calls = [];

    private int|string|null $failure = null;

    private int $sequence = 0;

    public function register(): void
    {
        Http::preventStrayRequests();
        Http::fake(fn (Request $request) => $this->handle($request));
    }

    public function failWith(int|string|null $failure): void
    {
        $this->failure = $failure;
    }

    public function callsTo(string $needle): int
    {
        return count(array_filter($this->calls, fn (string $call) => str_contains($call, $needle)));
    }

    public function payStripe(string $sessionId, ?int $amountTotal = null, string $currency = 'usd'): void
    {
        $this->stripeSessions[$sessionId] = array_merge($this->stripeSessions[$sessionId], [
            'status'         => 'complete',
            'payment_status' => 'paid',
            'amount_total'   => $amountTotal ?? $this->stripeSessions[$sessionId]['amount_total'],
            'currency'       => $currency,
        ]);
    }

    public function expireStripe(string $sessionId): void
    {
        $this->stripeSessions[$sessionId]['status'] = 'expired';
    }

    public function approvePayPal(string $orderId, string $captureStatus = 'COMPLETED', ?string $captureIssue = null): void
    {
        $this->paypalOrders[$orderId]['status'] = 'APPROVED';
        $this->paypalOrders[$orderId]['capture_status'] = $captureStatus;
        $this->paypalOrders[$orderId]['capture_issue'] = $captureIssue;
    }

    public function payZibal(string $trackId): void
    {
        $this->zibalTracks[$trackId]['paid'] = true;
    }

    public function forceZibalResult(string $trackId, int $result): void
    {
        $this->zibalTracks[$trackId]['forced_result'] = $result;
    }

    private function handle(Request $request)
    {
        $url = $request->url();
        $this->calls[] = $request->method() . ' ' . $url;

        if ($this->failure === 'timeout') {
            throw new ConnectionException('cURL error 28: Operation timed out after 30001 milliseconds');
        }

        if (is_int($this->failure)) {
            return Http::response(['error' => 'gateway error'], $this->failure);
        }

        return match (true) {
            str_contains($url, 'api.stripe.com')   => $this->stripe($request),
            str_contains($url, 'paypal.com')       => $this->paypal($request),
            str_contains($url, 'gateway.zibal.ir') => $this->zibal($request),
        };
    }

    private function stripe(Request $request)
    {
        if ($request->method() === 'POST' && str_ends_with($request->url(), '/checkout/sessions')) {
            $id = 'cs_test_' . (++$this->sequence);
            $this->stripeSessions[$id] = [
                'id'             => $id,
                'object'         => 'checkout.session',
                'status'         => 'open',
                'payment_status' => 'unpaid',
                'amount_total'   => (int) $request->data()['line_items[0][price_data][unit_amount]'],
                'currency'       => $request->data()['line_items[0][price_data][currency]'],
                'url'            => "https://checkout.stripe.test/{$id}",
            ];

            return Http::response($this->stripeSessions[$id]);
        }

        $id = rawurldecode(basename((string) parse_url($request->url(), PHP_URL_PATH)));

        return isset($this->stripeSessions[$id])
            ? Http::response($this->stripeSessions[$id])
            : Http::response(['error' => ['message' => 'No such checkout.session']], 404);
    }

    private function paypal(Request $request)
    {
        $path = (string) parse_url($request->url(), PHP_URL_PATH);

        if (str_ends_with($path, '/v1/oauth2/token')) {
            return Http::response(['access_token' => 'paypal-test-token', 'token_type' => 'Bearer']);
        }

        if ($request->method() === 'POST' && str_ends_with($path, '/v2/checkout/orders')) {
            $id = 'PAYPAL-ORDER-' . (++$this->sequence);
            $this->paypalOrders[$id] = [
                'status'         => 'CREATED',
                'amount'         => $request->data()['purchase_units'][0]['amount']['value'],
                'capture_status' => null,
                'capture_issue'  => null,
            ];

            return Http::response([
                'id'     => $id,
                'status' => 'CREATED',
                'links'  => [['rel' => 'approve', 'href' => "https://www.sandbox.paypal.test/checkoutnow?token={$id}"]],
            ], 201);
        }

        preg_match('#/v2/checkout/orders/([^/]+)(/capture)?$#', $path, $matches);
        $id = $matches[1] ?? '';
        $order = $this->paypalOrders[$id] ?? null;

        if ($order === null) {
            return Http::response(['name' => 'RESOURCE_NOT_FOUND', 'details' => [['issue' => 'INVALID_RESOURCE_ID']]], 404);
        }

        if (($matches[2] ?? '') === '') {
            return Http::response($this->paypalOrderBody($id));
        }

        if ($order['status'] === 'COMPLETED') {
            return $this->paypalIssue('ORDER_ALREADY_CAPTURED');
        }

        if ($order['status'] !== 'APPROVED') {
            return $this->paypalIssue('ORDER_NOT_APPROVED');
        }

        if ($order['capture_issue'] !== null) {
            return $this->paypalIssue($order['capture_issue']);
        }

        $this->paypalOrders[$id]['status'] = 'COMPLETED';

        return Http::response($this->paypalOrderBody($id), 201);
    }

    private function paypalOrderBody(string $id): array
    {
        $order = $this->paypalOrders[$id];
        $body = ['id' => $id, 'status' => $order['status']];

        if ($order['status'] === 'COMPLETED') {
            $body['purchase_units'] = [[
                'payments' => ['captures' => [[
                    'id'     => "CAPTURE-{$id}",
                    'status' => $order['capture_status'],
                    'amount' => ['currency_code' => 'USD', 'value' => $order['amount']],
                ]]],
            ]];
        }

        return $body;
    }

    private function paypalIssue(string $issue)
    {
        return Http::response([
            'name'    => 'UNPROCESSABLE_ENTITY',
            'details' => [['issue' => $issue]],
            'message' => 'The requested action could not be performed, semantically incorrect, or failed business validation.',
        ], 422);
    }

    private function zibal(Request $request)
    {
        if (str_ends_with($request->url(), '/v1/request')) {
            $trackId = (string) (1000 + (++$this->sequence));
            $this->zibalTracks[$trackId] = ['amount' => $request->data()['amount'], 'paid' => false, 'verified' => false];

            return Http::response(['result' => 100, 'trackId' => (int) $trackId, 'message' => 'success']);
        }

        $trackId = (string) ($request->data()['trackId'] ?? '');
        $track = $this->zibalTracks[$trackId] ?? null;

        if ($track === null) {
            return Http::response(['result' => 203, 'message' => 'trackId نامعتبر می‌باشد.']);
        }

        if (isset($track['forced_result'])) {
            return Http::response(['result' => $track['forced_result'], 'message' => 'forced']);
        }

        if (!$track['paid']) {
            return Http::response(['result' => 202, 'message' => 'سفارش پرداخت نشده یا ناموفق بوده است.']);
        }

        if ($track['verified']) {
            return Http::response(['result' => 201, 'message' => 'قبلا تایید شده.', 'amount' => $track['amount']]);
        }

        $this->zibalTracks[$trackId]['verified'] = true;

        return Http::response(['result' => 100, 'message' => 'success', 'amount' => $track['amount'], 'status' => 1]);
    }
}
