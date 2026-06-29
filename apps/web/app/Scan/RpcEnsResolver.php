<?php

namespace App\Scan;

use Illuminate\Support\Facades\Http;

/**
 * 공개 이더리움 RPC(eth_call)로 ENS를 해석. 제3자 이름-해석 서비스 의존 없음(RPC 노드만).
 * registry.resolver(node) → resolver.addr(node) 2단계. EIP-137 namehash는 keccak256.
 */
class RpcEnsResolver implements EnsResolver
{
    /** ENS 레지스트리(모든 메인넷 동일). */
    private const REGISTRY = '0x00000000000C2E074eC69A0dFb2997BA6C7d2e1e';
    private const ZERO_ADDRESS = '0x0000000000000000000000000000000000000000';
    private const SELECTOR_RESOLVER = '0178b8bf'; // resolver(bytes32)
    private const SELECTOR_ADDR = '3b3b57de';     // addr(bytes32)

    public function __construct(private readonly string $rpcUrl)
    {
    }

    public function resolve(string $name): ?string
    {
        $node = substr(Ens::namehash($name), 2); // 0x 제거

        $resolver = $this->ethCallAddress(self::REGISTRY, self::SELECTOR_RESOLVER . $node);

        if ($resolver === null)
        {
            return null; // 해당 이름에 resolver 미설정
        }

        return $this->ethCallAddress($resolver, self::SELECTOR_ADDR . $node);
    }

    /** eth_call 후 반환 워드를 주소로 디코드. 0 주소면 null. */
    private function ethCallAddress(string $to, string $data): ?string
    {
        $word = $this->ethCall($to, '0x' . $data);
        $hex = substr($word, 2); // 0x 제거

        if (strlen($hex) < 40)
        {
            return null;
        }

        $address = '0x' . strtolower(substr($hex, -40));

        return $address === self::ZERO_ADDRESS ? null : $address;
    }

    private function ethCall(string $to, string $data): string
    {
        try
        {
            $response = Http::timeout(10)
                ->acceptJson()
                ->post($this->rpcUrl, [
                    'jsonrpc' => '2.0',
                    'id' => 1,
                    'method' => 'eth_call',
                    'params' => [['to' => $to, 'data' => $data], 'latest'],
                ]);
        }
        catch (\Throwable $e)
        {
            throw new EnsException('ENS RPC request failed: ' . $e->getMessage(), 0, $e);
        }

        if (!$response->successful())
        {
            throw new EnsException("ENS RPC HTTP {$response->status()}");
        }

        $result = $response->json('result');

        if (!is_string($result) || !str_starts_with($result, '0x'))
        {
            throw new EnsException('ENS RPC returned no result');
        }

        return $result;
    }
}
