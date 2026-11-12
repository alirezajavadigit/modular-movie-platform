<?php

declare(strict_types=1);

namespace Modules\Payment\Tests\Support;

use GuzzleHttp\Psr7\Response;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class FakeZarinPalServer implements ClientInterface
{
    private array $sessions = [];

    private array $verifyCalls = [];

    private int $requestCount = 0;

    private bool $unavailable = false;

    public function markPaid(string $authority): void
    {
        $this->sessions[$authority]['paid'] = true;
    }

    public function markVerified(string $authority): void
    {
        $this->sessions[$authority]['paid'] = true;
        $this->sessions[$authority]['verified'] = true;
    }

    public function isVerified(string $authority): bool
    {
        return $this->sessions[$authority]['verified'] ?? false;
    }

    public function setUnavailable(bool $unavailable): void
    {
        $this->unavailable = $unavailable;
    }

    public function verifyCallsFor(string $authority): int
    {
        return count(array_filter($this->verifyCalls, fn (string $called) => $called === $authority));
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $body = json_decode((string) $request->getBody(), true) ?? [];

        if (str_ends_with($request->getUri()->getPath(), '/request.json')) {
            return $this->createSession((int) $body['amount']);
        }

        return $this->verify((string) $body['authority'], (int) $body['amount']);
    }

    private function createSession(int $amount): ResponseInterface
    {
        $this->requestCount++;
        $authority = 'A' . str_pad((string) $this->requestCount, 35, '0', STR_PAD_LEFT);
        $this->sessions[$authority] = ['amount' => $amount, 'paid' => false, 'verified' => false];

        return $this->data(['code' => 100, 'message' => 'Success', 'authority' => $authority, 'fee_type' => 'Merchant', 'fee' => 0]);
    }

    private function verify(string $authority, int $amount): ResponseInterface
    {
        $this->verifyCalls[] = $authority;

        if ($this->unavailable) {
            return new Response(502, [], 'Bad Gateway');
        }

        $session = $this->sessions[$authority] ?? null;

        if ($session === null) {
            return $this->error(-54, 'Invalid authority.');
        }

        if (!$session['paid']) {
            return $this->error(-51, 'Session is not valid, session is not active paid try.');
        }

        if ($session['amount'] !== $amount) {
            return $this->error(-50, 'Session is not valid, amounts values is not the same.');
        }

        $code = $session['verified'] ? 101 : 100;
        $this->sessions[$authority]['verified'] = true;

        return $this->data([
            'code'      => $code,
            'message'   => $code === 100 ? 'Paid' : 'Verified',
            'card_hash' => 'hash',
            'card_pan'  => '502229******5995',
            'ref_id'    => 201,
            'fee_type'  => 'Merchant',
            'fee'       => 0,
        ]);
    }

    private function data(array $data): ResponseInterface
    {
        return new Response(200, ['Content-Type' => 'application/json'], json_encode(['data' => $data, 'errors' => []]));
    }

    private function error(int $code, string $message): ResponseInterface
    {
        return new Response(422, ['Content-Type' => 'application/json'], json_encode([
            'data'   => [],
            'errors' => ['code' => $code, 'message' => $message, 'validations' => []],
        ]));
    }
}
