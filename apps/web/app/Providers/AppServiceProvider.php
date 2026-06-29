<?php

namespace App\Providers;

use App\Scan\ClaudeRiskExplainer;
use App\Scan\EnsResolver;
use App\Scan\GoPlusGateway;
use App\Scan\GoPlusHttpGateway;
use App\Scan\GoPlusScanner;
use App\Scan\NullRiskExplainer;
use App\Scan\RiskExplainer;
use App\Scan\RpcEnsResolver;
use App\Scan\Turnstile;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(GoPlusGateway::class, GoPlusHttpGateway::class);
        $this->app->bind(EnsResolver::class, static fn () => new RpcEnsResolver((string) config('scan.ens.rpc_url', 'https://ethereum-rpc.publicnode.com')));
        $this->app->singleton(Turnstile::class, static fn () => new Turnstile(config('scan.turnstile.secret')));

        // 키 설정 + 활성 시에만 Claude 설명, 아니면 no-op(템플릿 폴백). GoPlusScanner가 주입받는다.
        $this->app->bind(RiskExplainer::class, static function () {
            $key = (string) (config('scan.llm.api_key') ?? '');

            if ($key === '' || !config('scan.llm.enabled', true))
            {
                return new NullRiskExplainer();
            }

            return new ClaudeRiskExplainer($key, (string) config('scan.llm.model', 'claude-haiku-4-5'));
        });

        // 스캔당 LLM 호출 상한을 config에서 주입(클래스는 config 비의존 → 단위 테스트 용이).
        $this->app->bind(GoPlusScanner::class, static fn ($app) => new GoPlusScanner(
            $app->make(GoPlusGateway::class),
            $app->make(RiskExplainer::class),
            (int) config('scan.llm.max_per_scan', 1),
        ));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // 임시 HTTPS 프록시(LAN 휴대폰 테스트) 뒤에서 asset()/url()/redirect() 가 https 외부주소를 쓰도록.
        if ($publicUrl = env('APP_PUBLIC_URL'))
        {
            URL::forceScheme('https');
            URL::forceRootUrl($publicUrl);
        }
    }
}
