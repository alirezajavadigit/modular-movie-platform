<?php

declare(strict_types=1);

namespace Modules\Payment\Tests\Support;

use Closure;
use GuzzleHttp\Psr7\Response;
use Http\Client\Exception\NetworkException;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class FakeZarinPalServer implements ClientInterface
{
    private array $sessions = [];

    private array $verifyCalls = [];

    private int $requestCount = 0;

    private ?int $failureStatus = null;

    private bool $timingOut = false;

    private int $verifyDelayMicroseconds = 0;

    private ?Closure $beforeVerify = null;

    public function markPaid(string $authority): void
    {
        $this->sessions[$authority]['paid'] = true;
    }

    public function markVerified(string $authority): void
    {
        $this->sessions[$authority]['paid'] = true;
        $this->sessions[$authority]['verified'] = true;
    }

    public function markFailed(string $authority): void
    {
        $this->sessions[$authority]['failed'] = true;
    }

    public function isVerified(string $authority): bool
    {
        return $this->sessions[$authority]['verified'] ?? false;
    }

    public function setUnavailable(bool $unavailable): void
    {
        $this->failureStatus = $unavailable ? 502 : null;
    }

    public function failWithStatus(?int $status): void
    {
        $this->failureStatus = $status;
    }

    public function setTimingOut(bool $timingOut): void
    {
        $this->timingOut = $timingOut;
    }

    public function delayVerify(int $microseconds): void
    {
        $this->verifyDelayMicroseconds = $microseconds;
    }

    public function beforeNextVerify(Closure $callback): void
    {
        $this->beforeVerify = $callback;
    }

    public function verifyCallsFor(string $authority): int
    {
        return count(array_filter($this->verifyCalls, fn (string $called) => $called === $authority));
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $body = json_decode((string) $request->getBody(), true) ?? [];
        $path = $request->getUri()->getPath();

        if (str_ends_with($path, '/request.json')) {
            return $this->createSession((int) $body['amount']);
        }

        if ($this->timingOut) {
            throw new NetworkException('cURL error 28: Operation timed out', $request);
        }

        if ($this->failureStatus !== null) {
            return new Response($this->failureStatus, [], 'Gateway error');
        }

        if (str_ends_with($path, '/inquiry.json')) {
            return $this->inquiry((string) $body['authority']);
        }

        return $this->verify((string) $body['authority'], (int) $body['amount']);
    }

    private function createSession(int $amount): ResponseInterface
    {
        $this->requestCount++;
        $authority = 'A' . str_pad((string) $this->requestCount, 35, '0', STR_PAD_LEFT);
        $this->sessions[$authority] = ['amount' => $amount, 'paid' => false, 'verified' => false, 'failed' => false];

        return $this->data(['code' => 100, 'message' => 'Success', 'authority' => $authority, 'fee_type' => 'Merchant', 'fee' => 0]);
    }

    private function verify(string $authority, int $amount): ResponseInterface
    {
        $this->verifyCalls[] = $authority;

        if ($this->beforeVerify !== null) {
            $callback = $this->beforeVerify;
            $this->beforeVerify = null;
            $callback();
        }

        if ($this->verifyDelayMicroseconds > 0) {
            usleep($this->verifyDelayMicroseconds);
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

    private function inquiry(string $authority): ResponseInterface
    {
        $session = $this->sessions[$authority] ?? null;

        if ($session === null) {
            return $this->error(-54, 'Invalid authority.');
        }

        $status = match (true) {
            $session['verified'] => 'VERIFIED',
            $session['paid']     => 'PAID',
            $session['failed']   => 'FAILED',
            default              => 'IN_BANK',
        };

        return $this->data(['code' => 100, 'message' => 'Success', 'status' => $status]);
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
