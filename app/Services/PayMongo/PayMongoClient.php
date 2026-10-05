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
    ) {}

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

    /**
     * Retrieve a payment by PayMongo payment id ("pay_xxxx") or by reference
     * number ("pm_reference_number" from email receipts / QR Ph links).
     *
     * The payments list endpoint does not officially support filtering by
     * reference, so this is best-effort: exact id first, then a small list scan.
     */
    public function retrievePayment(string $referenceOrId): array
    {
        if (str_starts_with($referenceOrId, 'pay_')) {
            return $this->getPayment($referenceOrId);
        }

        // PayMongo supports ?reference_number on some channels but is
        // undocumented for payments; try it and gracefully fall back.
        $queryResponse = $this->http()->get('/payments', [
            'reference_number' => $referenceOrId,
        ]);

        if ($queryResponse->successful()) {
            $items = $queryResponse->json('data', []);

            if (is_array($items) && count($items) === 1) {
                return $items[0];
            }
        }

        // Best-effort scan of the most recent 100 payments. This is not
        // guaranteed to find anything; the user may need to supply the pay_ id.
        $listResponse = $this->http()->get('/payments', ['limit' => 100]);

        if ($listResponse->failed()) {
            throw new PayMongoException(
                'PayMongo payments list failed: '.$listResponse->status().' '.$listResponse->body(),
                $listResponse->status(),
            );
        }

        $items = $listResponse->json('data', []);

        if (! is_array($items)) {
            throw new PayMongoException('Unexpected PayMongo payments list response.');
        }

        foreach ($items as $item) {
            $external = data_get($item, 'attributes.external_reference_number');
            $metadataRef = data_get($item, 'attributes.metadata.pm_reference_number');

            if ($external === $referenceOrId || $metadataRef === $referenceOrId) {
                return $item;
            }
        }

        throw new PayMongoException('Payment not found for reference: '.$referenceOrId, 404);
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
