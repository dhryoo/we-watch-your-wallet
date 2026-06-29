<?php

namespace Tests\Feature;

use App\Scan\EnsException;
use App\Scan\RpcEnsResolver;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EnsResolverTest extends TestCase
{
    private const RPC = 'https://rpc.test/eth';

    /** 32바이트 워드에 주소를 우측정렬해 ABI 워드로 만든다. */
    private function word(string $address): string
    {
        return '0x' . str_repeat('0', 24) . substr(strtolower($address), 2);
    }

    private function rpcResult(string $hex): array
    {
        return ['jsonrpc' => '2.0', 'id' => 1, 'result' => $hex];
    }

    public function testResolvesNameToAddressViaRegistryThenResolver(): void
    {
        Http::fake([
            self::RPC => Http::sequence()
                // 1) registry.resolver(node) → public resolver
                ->push($this->rpcResult($this->word('0x4976fb03C32e5B8cfe2B6cCB31c09Ba78EBaBa41')))
                // 2) resolver.addr(node) → vitalik.eth
                ->push($this->rpcResult($this->word('0xd8dA6BF26964aF9D7eEd9e03E53415D37aA96045'))),
        ]);

        $resolved = (new RpcEnsResolver(self::RPC))->resolve('vitalik.eth');

        $this->assertSame('0xd8da6bf26964af9d7eed9e03e53415d37aa96045', $resolved);
    }

    public function testReturnsNullWhenNoResolverIsSet(): void
    {
        Http::fake([
            self::RPC => Http::sequence()->push($this->rpcResult($this->word('0x0000000000000000000000000000000000000000'))),
        ]);

        $this->assertNull((new RpcEnsResolver(self::RPC))->resolve('no-resolver.eth'));
    }

    public function testReturnsNullWhenResolverHasNoAddressRecord(): void
    {
        Http::fake([
            self::RPC => Http::sequence()
                ->push($this->rpcResult($this->word('0x4976fb03C32e5B8cfe2B6cCB31c09Ba78EBaBa41')))
                ->push($this->rpcResult($this->word('0x0000000000000000000000000000000000000000'))),
        ]);

        $this->assertNull((new RpcEnsResolver(self::RPC))->resolve('noaddr.eth'));
    }

    public function testThrowsOnRpcFailureSoCallerNeverFakesAResult(): void
    {
        Http::fake([self::RPC => Http::response('upstream down', 503)]);

        $this->expectException(EnsException::class);
        (new RpcEnsResolver(self::RPC))->resolve('vitalik.eth');
    }
}
