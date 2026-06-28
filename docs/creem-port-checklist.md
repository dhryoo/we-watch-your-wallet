# Creem 이식 체크리스트 (saju-ai `apps/web` → watch_tower)

> 출처: `/Users/idfeelme/2025_project/saju-ai/apps/web` 실제 코드 검토(2026-06-23).
> **타이밍**: WatchTower P0는 **결제 없음**(무료 훅 + Pro 대기리스트). Creem 이식은 **Phase 4**. 이 문서는 그때를 위한 사전 분류.
> **핵심 결론**: 결제 plumbing의 ~70%가 도메인 비의존 → **그대로** 이식. saju 종속은 가격·상품·페이월·기능목록(~30%)뿐. 이게 D1/D3(결제 재사용이 폴리글랏을 정당화)의 실증.

## 분류표

| saju 파일 | 분류 | 사유 / 작업 |
|---|---|---|
| `app/Billing/Creem/CreemClient.php` | 🟢 **그대로** | 순수 Creem REST 래퍼(`createCheckout`·`cancelSubscription`, `x-api-key`). 도메인 0. config 키만 읽음. |
| `app/Billing/Creem/CreemSignature.php` | 🟢 **그대로** | HMAC-SHA256 raw-body 검증(`hash_equals` timing-safe). 완전 범용. |
| `app/Billing/Creem/CreemWebhookProcessor.php` | 🟢 **그대로** | 이벤트→`subscriptions` 멱등(`updateOrCreate` by `provider_subscription_id`), `STATUS_MAP`. `metadata['plan']` 값만 데이터 차이(코드 아님). |
| `app/Http/Controllers/CreemWebhookController.php` | 🟢 **그대로** | raw 바디→서명검증→process→200. FPM 블로킹 금지 패턴 동일. |
| `app/Models/Subscription.php` | 🟢 **그대로** | `isActive()`(active/trialing + 만료체크), casts, 관계. 범용. |
| `database/migrations/*create_subscriptions_table.php` | 🟢 **그대로** | 범용 스키마(user_id·provider·provider_subscription_id unique·status·plan·current_period_end). WatchTower 동일. |
| `database/migrations/*add_cancel_at_period_end*.php` | 🟢 **그대로** | `cancel_at_period_end` 플래그. (신규 앱이면 create에 통합 가능.) |
| `bootstrap/app.php` (CSRF except `webhooks/*`) | 🟢 **그대로(패턴)** | webhook CSRF 면제 한 줄. 새 Laravel 앱에 동일 적용. |
| `routes/web.php` (`POST /webhooks/creem`) | 🟢 **그대로** | webhook 라우트 동일. subscribe 라우트는 라우트만 재사용, 컨트롤러 교체. |
| `config/services.php` (creem 블록) | 🟡 **수정(소)** | 구조 동일(key·base_url·webhook_secret·products). `products` 키만 교체: `monthly/annual` → `pro_monthly/pro_annual`(+`treasury`). |
| `.env.example` (`CREEM_*`) | 🟡 **수정(소)** | 같은 변수, product id·주석만 WatchTower로. |
| `app/Billing/Entitlements.php` | 🟡 **수정(확장)** | `allows(feature)` 메커니즘(premium_features에 있으면 구독자만)은 **그대로 재사용**. **추가**: 수량 게이팅 `limit('wallet_limit')`(Free=1, Pro=25, Treasury=∞). saju는 boolean만, WatchTower는 지갑 수 한도 필요. |
| `app/Http/Controllers/SubscriptionController.php` | 🟡 **수정(분할)** | `checkout()`·`cancel()`는 ~90% 그대로(범용 Creem 플로우) — `success_url` 라우트·plan 키만 교체. `show()`·`perks()`는 saju 페이월(讀運語合五 카피) → **WatchTower 페이월로 재작성**. |
| `config/pricing.php` | 🔴 **신규** | saju 가격($9.99/$59.99)·기능(daily_dos_donts·ai_advisor·compatibility…)은 전부 saju. WatchTower로 재작성(아래 매핑). 구조는 동일. |
| `tests/Feature/CreemCheckoutTest.php` | 🟢 **그대로(거의)** | 체크아웃 플로우 테스트. plan/product 설정만 어댑트. |
| `tests/Feature/CreemWebhookTest.php` | 🟢 **그대로(거의)** | 서명검증·멱등·상태매핑 테스트 = 직접 재사용(가장 가치 큼). 페이로드 plan 값만 어댑트. |
| `tests/Feature/SubscriptionTest.php` | 🟡 **수정** | WatchTower 플랜/라우트로 어댑트. |
| `tests/Feature/SubscriptionCancelTest.php` | 🟡 **수정** | 기간말 취소 테스트, 라우트 어댑트. |
| `tests/Feature/EntitlementsTest.php` | 🟡 **수정** | WatchTower 기능 + 신규 `limit()` 테스트 추가. |

**합계**: 🟢 그대로 11 · 🟡 수정 6 · 🔴 신규 1. **plumbing은 그대로, 가격/페이월/기능목록만 교체.**

## WatchTower 플랜·기능 매핑 (config/pricing.php 재작성용)

```php
'plans' => [
    'pro_monthly' => ['price' => 14.00, 'interval' => 'month'],
    'pro_annual'  => ['price' => 120.00, 'interval' => 'year', 'badge' => '≈ 3 MONTHS FREE'],
    // 'treasury' (B2B) — P1, 별도 영업/인보이스 가능성
],
'limits' => [                        // ← Entitlements.limit() 신규
    'wallet_limit' => ['free' => 1, 'pro' => 25, 'treasury' => -1],   // -1 = 무제한
],
'premium_features' => [              // ← Entitlements.allows() 그대로
    'realtime_alerts',   // 실시간(분단위) 청산/approval 경고
    'telegram_channel',  // 텔레그램 알림
    'multi_wallet',      // 2개 이상 지갑(수량은 limits로)
    'depeg_detector',    // P1
    'rug_detector',      // P1
    'claude_explanation',// Claude 맥락 해석(무료는 템플릿)
    'api_webhook',       // Treasury
    'white_label',       // Treasury
],
```
- **Free**: 지갑 1개 + 무료 approval 스캔 + 기본(템플릿) 경고.
- **Pro**: 다지갑(≤25) + 실시간 + 텔레그램 + Claude 해석.
- **Treasury(P1)**: 멀티시그 + API/webhook + 화이트라벨.

## 이식 절차 (Phase 4)
1. 🟢 파일 6개 그대로 복사: `Creem/*` 3개 + `CreemWebhookController` + `Subscription` 모델 + 마이그레이션 2개.
2. `bootstrap/app.php` CSRF 면제·webhook 라우트 추가.
3. `config/services.php` creem 블록 복사 후 `products` 키 교체.
4. `config/pricing.php` 신규 작성(위 매핑).
5. `Entitlements.php` 복사 + `limit()` 메서드 추가.
6. `SubscriptionController` 복사 후 `show()`/`perks()` 재작성, `success_url` 라우트 교체.
7. 테스트: webhook/checkout 테스트 그대로, subscription/entitlements 어댑트.

## 셋업 / 검증 (saju setup-checklist 이어감)
- Creem 계정 → **Test mode** → API key(`creem_test_...`).
- 상품 생성: **공식 CLI**(`npm i -g @creem_io/cli`, `creem login`, `creem products create`) 또는 REST `POST /v1/products` → Claude Code가 자동화 가능. 각 상품에 **7일 trial은 대시보드에서**(CLI 불가).
- `.env`: `CREEM_API_KEY` / `CREEM_BASE_URL`(test: `https://test-api.creem.io/v1`) / `CREEM_WEBHOOK_SECRET`(whsec_…) / `CREEM_PRODUCT_PRO_MONTHLY` / `CREEM_PRODUCT_PRO_ANNUAL`.
- webhook: https 필수(Creem은 `stripe listen` 같은 포워더 없음) → 로컬은 **ngrok** URL 등록.
- ⚠️ **검증 필요(saju가 남긴 research gap 상속)**: 실 test-webhook 페이로드로 **필드명 확정** — `current_period_end_date`, `metadata.user_id` 위치, `eventType`/`object` 구조. `CreemWebhookProcessor`가 이 필드들에 의존.
- 수수료(PRICE_POLICY): Creem ≈ 3.9%+$0.40(실효 ~9.5%). $10 미만 저가 구독은 고정수수료 비중↑ → Pro 가격 확정 시 실효수수료 재계산.

## 비수탁 정합성
Creem은 **법정통화 MoR** → 코인 미수취, 우리는 자금흐름 밖(Creem 장부). 비수탁(D1·brief 불변식1) 무위반. 자금 코드 경로 없음 유지.
