<?php

namespace Tests\Feature;

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Tests\TestCase;

class HomeQrTest extends TestCase
{
    public function testHomeIncludesQrScannerScaffolding(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Scan a wallet QR code');
        $response->assertSee('id="wt-qr-overlay"', false);
        $response->assertSee('id="wt-qr-video"', false);
        $response->assertSee('Upload a photo');
        $response->assertSee('the camera image never leaves your browser');
        $response->assertSee('js/jsQR.js', false);
        $response->assertSee('js/wallet-qr.js', false);
    }

    public function testQrAssetFilesExistAndAreSelfHosted(): void
    {
        $this->assertFileExists(public_path('js/jsQR.js'));
        $this->assertFileExists(public_path('js/wallet-qr.js'));
        $this->assertStringContainsString('jsQR', (string) file_get_contents(public_path('js/wallet-qr.js')));
    }

    public function testStoreExtractsAddressFromEip681Uri(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);
        $address = '0x4862733B5FdDFd35f35ea8CCf08F5045e57388B3';

        $this->post('/scan', ['address' => "ethereum:{$address}@1"])
            ->assertRedirect('/scan/' . $address);
    }

    public function testStoreRejectsTextWithoutAddress(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);

        $this->from('/')
            ->post('/scan', ['address' => 'wc:abc@2?relay-protocol=irn'])
            ->assertRedirect('/')
            ->assertSessionHasErrors('address');
    }
}
