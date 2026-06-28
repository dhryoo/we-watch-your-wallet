<?php

namespace Tests\Feature;

use App\Scan\GoPlusException;
use App\Scan\GoPlusHttpGateway;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GoPlusHttpGatewayTest extends TestCase
{
    private string $address = '0x7a3f9b2c4D5e6F7a8B9c0D1e2F3a4B5c6D7e9c2D';

    public function testCode2MarksPartial(): void
    {
        Http::fake(['api.gopluslabs.io/api/v2/token_approval_security/*' => Http::response(['code' => 2, 'result' => []])]);

        $gateway = new GoPlusHttpGateway();
        $gateway->approvals($this->address, 1);

        $this->assertTrue($gateway->wasPartial()); // code 2 = 아직 스캐닝 → 부분
    }

    public function testCode1IsNotPartial(): void
    {
        Http::fake(['api.gopluslabs.io/api/v2/token_approval_security/*' => Http::response(['code' => 1, 'result' => []])]);

        $gateway = new GoPlusHttpGateway();
        $gateway->approvals($this->address, 1);

        $this->assertFalse($gateway->wasPartial());
    }

    public function testOneNftEndpointFailureMarksPartialAndReturnsOther(): void
    {
        Http::fake([
            'api.gopluslabs.io/api/v2/nft721_approval_security/*' => Http::response(['code' => 1, 'result' => [['nft_symbol' => 'X', 'approved_list' => []]]]),
            'api.gopluslabs.io/api/v2/nft1155_approval_security/*' => Http::response('upstream error', 500),
        ]);

        $gateway = new GoPlusHttpGateway();
        $rows = $gateway->nftApprovals($this->address, 1);

        $this->assertTrue($gateway->wasPartial()); // 한쪽만 받음 → 부분
        $this->assertCount(1, $rows);
    }

    public function testBothNftEndpointsFailThrows(): void
    {
        Http::fake(['api.gopluslabs.io/*' => Http::response('upstream error', 500)]);

        $this->expectException(GoPlusException::class);
        (new GoPlusHttpGateway())->nftApprovals($this->address, 1);
    }
}
