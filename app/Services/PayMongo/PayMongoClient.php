<?php

namespace App\Services\PayMongo;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class PayMongoClient
{
    public function __construct(
        protected string $secretKey,
        protected string $baseUrl,
    ) {
    }

    public static function make(): self
    {
        $secret = (string) config('paymongo.secret_key');

        if ($secret === '') {
            throw new RuntimeException('PAYMONGO_SECRET_KEY is not set.');
        }

        return new self($secret, (string) config('paymongo.base_url'));
    }

    public function createCheckoutSession(array $attributes): array
    {
        return $this->request('POST', '/checkout_sessions', [
            'data' => [
                'attributes' => $attributes,
            ],
        ]);
    }

    public function getCheckoutSession(string $id): array
    {
        return $this->request('GET', '/checkout_sessions/'.$id);
    }

    public function getPayment(string $id): array
    {
        return $this->request('GET', '/payments/'.$id);
    }

    public function createCustomer(array $attributes): array
    {
        return $this->request('POST', '/customers', [
            'data' => ['attributes' => $attributes],
        ]);
    }

    protected function request(string $method, string $path, array $body = []): array
    {
        $request = $this->http();

        $response = $method === 'GET'
            ? $request->get($path)
            : $request->{$method === 'POST' ? 'post' : 'put'}($path, $body);

        if ($response->failed()) {
            throw new PayMongoException(
                'PayMongo request failed: '.$response->status().' '.$response->body(),
                $response->status(),
            );
        }

        return $response->json('data', []);
    }

    protected function http(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl)
            ->withBasicAuth($this->secretKey, '')
            ->acceptJson()
            ->asJson()
            ->timeout(30);
    }
}