<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;

class AsaasService
{
    private string $baseUrl;
    private string $apiKey;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.asaas.url', 'https://sandbox.asaas.com/api/v3'), '/');
        $this->apiKey  = (string) config('services.asaas.key', '');
    }

    /**
     * Cria um cliente no Asaas.
     * Dados obrigatórios: name, cpfCnpj
     * Opcionais: email, phone, mobilePhone, address, etc.
     */
    public function createCustomer(array $data): array
    {
        return $this->post('/customers', $data);
    }

    /**
     * Busca um cliente pelo ID.
     */
    public function getCustomer(string $customerId): array
    {
        return $this->get("/customers/{$customerId}");
    }

    /**
     * Cria uma assinatura no Asaas.
     * Campos obrigatórios: customer, billingType, value, nextDueDate, cycle
     * Para cartão de crédito: creditCard + creditCardHolderInfo
     */
    public function createSubscription(array $data): array
    {
        return $this->post('/subscriptions', $data);
    }

    /**
     * Busca uma assinatura pelo ID.
     */
    public function getSubscription(string $subscriptionId): array
    {
        return $this->get("/subscriptions/{$subscriptionId}");
    }

    /**
     * Cancela (remove) uma assinatura.
     */
    public function cancelSubscription(string $subscriptionId): array
    {
        return $this->delete("/subscriptions/{$subscriptionId}");
    }

    /**
     * Lista cobranças de uma assinatura.
     */
    public function getSubscriptionPayments(string $subscriptionId): array
    {
        return $this->get("/subscriptions/{$subscriptionId}/payments");
    }

    /**
     * Cria uma cobrança avulsa (não-recorrente) no Asaas.
     * Campos obrigatórios: customer, billingType, value, dueDate
     * Use externalReference para identificar o pagamento no webhook.
     */
    public function createPayment(array $data): array
    {
        return $this->post('/payments', $data);
    }

    /**
     * Busca uma cobrança avulsa pelo ID.
     */
    public function getPayment(string $paymentId): array
    {
        return $this->get("/payments/{$paymentId}");
    }

    /**
     * Lista cobranças de um customer.
     * Aceita filtros opcionais: offset, limit, status, billingType, etc.
     */
    public function listCustomerPayments(string $customerId, array $filters = []): array
    {
        return $this->get('/payments', array_merge(['customer' => $customerId], $filters));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers HTTP
    // ─────────────────────────────────────────────────────────────────────────

    private function post(string $path, array $data): array
    {
        $response = Http::withHeaders($this->headers())
            ->post($this->baseUrl . $path, $data);

        return $this->handleResponse($response, 'POST', $path);
    }

    private function get(string $path, array $query = []): array
    {
        $response = Http::withHeaders($this->headers())
            ->get($this->baseUrl . $path, $query);

        return $this->handleResponse($response, 'GET', $path);
    }

    private function delete(string $path): array
    {
        $response = Http::withHeaders($this->headers())
            ->delete($this->baseUrl . $path);

        return $this->handleResponse($response, 'DELETE', $path);
    }

    private function headers(): array
    {
        return [
            'access_token' => $this->apiKey,
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
        ];
    }

    private function handleResponse(Response $response, string $method, string $path): array
    {
        $body   = $response->json() ?? [];
        $status = $response->status();

        if ($response->failed()) {
            $errorMsg = $this->extractErrorMessage($body, $status);
            \Log::error("AsaasService {$method} {$path} falhou", [
                'status' => $status,
                'body'   => $body,
                'raw'    => $response->body(),
            ]);
            throw new \RuntimeException($errorMsg, $status);
        }

        return $body;
    }

    private function extractErrorMessage(array $body, int $status = 0): string
    {
        // Asaas retorna erros em body.errors[].description ou body.description
        if (!empty($body['errors'])) {
            $messages = array_column($body['errors'], 'description');
            $joined   = implode(' | ', array_filter($messages));
            return $joined ?: "Erro no Asaas (HTTP {$status})";
        }

        if (!empty($body['description'])) return $body['description'];
        if (!empty($body['message']))     return $body['message'];

        // Body vazio geralmente indica chave de API inválida ou corrompida
        if (empty($body)) {
            return match ($status) {
                401 => 'Chave de API Asaas inválida ou não autorizada (HTTP 401). Verifique ASAAS_API_KEY no Portainer.',
                403 => 'Acesso negado pela API Asaas (HTTP 403).',
                default => "Erro no Asaas (HTTP {$status}) — resposta vazia.",
            };
        }

        return "Erro no Asaas (HTTP {$status}): " . json_encode($body);
    }
}
