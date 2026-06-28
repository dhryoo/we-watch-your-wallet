# WatchTower — architecture.md

> 확정 결정은 `brief.md`(D-번호). 이 문서는 **구현 착수 가능 수준의 기술 설계**. 코드 스타일은 `CLAUDE.md`(Allman·4스페이스·camelCase·kebab-case).
> 안정성 우선(D6): 모든 컴포넌트는 **분리·복제·이전 가능**하게 설계한다.

## 0. 3-티어 폴리글랏 (네이티브 fleet) — saju-ai 패턴

```
                       사용자: 지갑 "주소만" 등록 (read-only, 키 0)
                                         │
                              nginx (단일 앞문, TLS)
                  ┌──────────────────────┴───────────────────────┐
          일반 경로(요청구동)                              비동기/알림(상시)
                  ▼                                              ▼
   PHP-FPM / Laravel (pm=ondemand, 유휴=제로)        Node Gateway / Worker (systemd 상주)
   · 랜딩 · 무료 /scan 결과 SSR                       · 모니터링 루프(적응형 폴링 + Alchemy 웹훅)
   · 인증(매직링크) · 대시보드                        · 탐지 엔진(in-process, 결정론적)
   · Creem 결제 + webhook → entitlement              · Claude 추론(in-process, 가드레일)
   · 지갑 등록 → Redis enqueue                        · 텔레그램 알림
                  └──────────────┬───────────────────────────────┘
                          Redis(글루: 큐·디바운스/dedup 상태·쿨다운·캐시)
                          Postgres(app 스키마=PHP / monitor 스키마=Node, 별도 role)
                          PgBouncer(트랜잭션 풀링)
   배포: Rocky 8 단일 서버 · PHP-FPM pool + systemd 유닛 · 공유 nginx/PG/Redis (Docker 미사용, D2)
```

**경로 원칙**: 온체인 이벤트=PUSH(Alchemy 웹훅), 상태성 리스크(HF·디페그)=적응형 POLL. Claude는 **탐지 이벤트 발생 시에만** 호출(상시 호출 금지). 탐지(결정론적)와 해석(LLM)은 분리.

## 1. 비타협 불변식 — CI 게이트로 강제 (brief §비타협)

```yaml
# compliance gate (CI). 위반 시 빌드 실패 → 실수로 머지 불가.
- no-key-columns:  '! grep -riE "private_key|mnemonic|seed_phrase|signing_key" migrations/ src/'
- rpc-readonly:    'npm run test:rpc-guard'        # FORBIDDEN 메서드 전부 throw
- advisory-filter: 'npm run test:advisory-filter'  # 매매권유/보장 표현 차단
- disclaimer:      'npm run test:disclaimer'        # 모든 alert 템플릿에 면책 고지 존재
```
- 비수탁: RPC read-only allowlist. `eth_sendRawTransaction`/`eth_sign`/`personal_sign` 코드 경로 부재.
- 비자문: DetectionEvent `action ∈ {REVIEW, INFORM}`만(BUY/SELL/SWAP/CLOSE enum 부재). Claude system 프롬프트 + 출력 post-filter 정규식.
- 정직성: RPC 실패 시 마지막 양호값을 정상 간주 **금지** → STALE.

## 2. 탐지 엔진 (packages/detect-engine, 결정론적·순수함수 — TDD 1순위)

이 패키지는 I/O 없는 순수 함수라 **TDD에 가장 적합**(saju-engine 패턴). 입력 `PositionSnapshot` → 출력 `DetectionEvent[]`.

```ts
type Severity = "INFO" | "WATCH" | "WARN" | "URGENT";
type DetectorId = "LIQUIDATION" | "DEPEG" | "APPROVAL" | "RUG";

interface DetectionEvent
{
    id: string;            // hash(detectorId|walletId|subjectKey|floor(ts/bucket)) — 멱등
    detectorId: DetectorId;
    walletId: string;
    chainId: number;
    subjectKey: string;
    severity: Severity;
    score: number;         // 0~100
    facts: Record<string, number | string>;
    recommendation: string[];          // "점검 항목" (명령 아님)
    action: "REVIEW" | "INFORM";       // 매매권유 구조적 불가
    revokeLink?: string;
    state: "PENDING" | "CONFIRMED" | "RESOLVED";
}
```

### 2.1 [P0] 청산 근접 (LIQUIDATION)
- HF는 Aave v3 `Pool.getUserAccountData(user)` 온체인 반환값을 **권위값으로 사용**(식 재구현 금지). `HF = healthFactor / 1e18`.
- 청산까지 거리: `dropToLiq% = (1 - 1/HF) * 100` (HF=1.25 → 20%. "단일담보 근사" 라벨).
- severity: HF≥1.5 INFO / 1.3≤HF<1.5 WATCH / 1.1≤HF<1.3 또는 dropToLiq%<15 WARN / HF<1.1 또는 ΔHF>0.15/scan URGENT(escalateBypass).
- `score = clamp(round((1.5-HF)/0.5*100), 0, 100)`.
- FN: RPC 실패 → STALE 이벤트(정상간주 금지). 미지원 프로토콜 보유 → "범위 밖" 고지.

### 2.2 [P0] 위험 Approval (APPROVAL)
- risk score 가산: MALICIOUS +60 / EOA spender +30 / UNVERIFIED +25 / unlimited(≥2²⁵⁶/2) +20 / NFT setApprovalForAll +20 / 잔액 50배↑ +15 / 고가치토큰 +10 / 최근권한 +10.
- severity: <20 INFO / <40 WATCH / <65 WARN / ≥65 또는 MALICIOUS 단독 URGENT.
- VERIFIED 라우터 무제한은 감산·상한 WATCH(피로 방지)하되 "무제한" 사실은 표기.
- revokeLink = `https://revoke.cash/address/{owner}?chainId={id}` (표시만, 실행은 사용자).
- FN: Permit2/EIP-2612 permit는 onchain allowance에 안 잡힘 → 별도 조회 필수, 미조회 시 범위 고지.

### 2.3 공통 디바운스 상태기계
```
RAW → PENDING(firstSeen) → (N_confirm 연속) → CONFIRMED → 알림
CONFIRMED → (M_clear 연속 해제) → RESOLVED
escalateBypass: severity 한 단계↑ 악화 시 즉시(디바운스 무시)
cooldown: 동일 subjectKey·severity 재알림 최소 6h
멱등: 같은 입력 → 같은 event.id
```

### 2.4 [P1] 디페그·러그 — 연기
- 디페그: fiat stable median 편차(0.3/0.5/2/5%)+시간창. yield토큰은 ERC4626 `convertToAssets`로 NAV 구해 discount 판정($1 페그 아님). Pendle PT는 만기 디스카운트 정상.
- 러그: 유동성 급감·대규모 출금·OwnershipTransferred·proxy implChanged·GoPlus 허니팟. 이벤트 push 중심.

## 3. Claude 추론 (packages/risk-explainer, LLM+가드레일 — FakeModel로 TDD)

saju-ai `llm-copy` 패턴. **탐지 이벤트 발생 시에만** 호출. 입력=검증된 facts JSON, 출력=구조화.

```ts
interface RiskExplanation
{
    severity: "info" | "warning" | "critical";
    plainExplanation: string;          // facts 근거, 1~3문장
    whyItMatters: string;
    checkList: Array<{ item: string; kind: "verify" | "monitor" | "revoke_link" }>;
    confidence: number;                // 0~1
    sources: Array<{ factKey: string; value: string }>;  // 입력 factKey만 인용(환각방어)
}
```
- 모델: P0 **Haiku 4.5**(`claude-haiku-4-5`). `@anthropic-ai/sdk`. `estimateCostUsd(usage, model)` 비용추적(saju 재사용).
- 환각 방어: 툴 미부여 + `sources` 필수 + 구조화 출력 + 출력 post-filter(금지동사 정규식) + `stop_reason:"refusal"` 선검사 후 generic 폴백.
- 비용: 알림당 ~$0.0044(추정). 디바운스/dedup/쿨다운/캐시로 통제. 무료 훅은 **무조건 Haiku**.
- **TDD**: `FakeModel`(고정 출력) + `UsageStubModel`(비용 검증)으로 실 API 없이 테스트(saju test 패턴).

## 4. 무료 approval 스캔 훅 (첫 출시물, PHP `/scan` + Node API)

- 데이터: **GoPlus Approval Security API**(키 불필요 30/min) 코어 + revoke.cash whois 라벨(정적 번들).
- **4중 비용 격리(필수)**: 캡차(Turnstile) + IP·지갑 일일한도 + 주소별 24h 캐시(Redis) + **1주차 평문은 템플릿(Claude 미사용)**.
- 결과: `/scan/{address}` SSR(SEO·영구URL·공유 백링크) + OG 이미지(주소 마스킹) + 이메일 리드(→Pro 깔때기).
- revoke: 사용자 본인 지갑(우리 미대행). risk score = 결정론적 가중합(자문 아님).

## 5. PHP/Laravel (요청구동 표면) — saju 재사용 최대

- 인증(매직링크), 대시보드, 지갑 등록(→Redis enqueue), 무료 `/scan` SSR.
- **Creem 결제(D1)**: saju `app/Billing/Creem/*` 이식 — `CreemClient`(`POST /v1/checkouts`, x-api-key), `CreemWebhookProcessor`(서명검증+멱등→`subscriptions`). webhook은 RAW 바디 서명검증 후 파싱, FPM 블로킹 금지(enqueue 후 즉시 반환).
- `pm=ondemand` + `pm.process_idle_timeout` → 유휴 워커 0(D6 idle-zero).

## 6. 데이터·인프라 (D5)

```sql
-- monitor 스키마(Node). 비수탁: 키 컬럼 부재.
CREATE TABLE wallets (id uuid PK, user_id uuid, chain_id int, address bytea, label text,
                      alchemy_in_webhook bool, UNIQUE(user_id, chain_id, address));
CREATE TABLE events (id uuid PK, wallet_id uuid, detector text, subject_key text,
                     severity text, score numeric, facts jsonb, state text,
                     dedup_key text UNIQUE,            -- 멱등 = 중복알림·불필요 Claude호출 차단
                     detected_at timestamptz);
CREATE TABLE alerts (id uuid PK, event_id uuid, channel text, sent_at timestamptz, status text);
CREATE TABLE approval_scans (id uuid PK, address bytea, chain_id int, result jsonb, scanned_at timestamptz);
-- 해자(P1): PII 분리
CREATE TABLE spender_label (spender_address bytea PK, chain_id int, risk_class text, evidence_count int, confidence numeric);
CREATE TABLE risk_event_corpus (id uuid PK, addr_hash bytea, chain_id int, event_type text, payload_json jsonb, detected_at timestamptz);  -- user FK 없음
```
- app 스키마(PHP): users, subscriptions(Creem 미러). monitor 스키마(Node): 위. **별도 DB·role**, PgBouncer 경유.
- Redis: 잡 큐·디바운스/dedup 상태·쿨다운·스캔 캐시. WatchTower 전용 ACL.
- 데이터 소스: Alchemy(RPC+Address Activity Webhook, 30M CU/월) · Aave `getUserAccountData`(eth_call, 권위값) · GoPlus(approval) · Chainlink/DeFiLlama(가격). **Zerion 미사용**(소진 시 전면정지 리스크).

## 7. 스케일·안정성 (D6) — "서버 추가로 스케일아웃"

| 컴포넌트 | 지금(1서버) | 스케일 경로 |
|---|---|---|
| 웹(PHP) | pm=ondemand pool | **stateless** → nginx/LB 뒤 N대 복제 |
| 모니터 워커 | 상주 데몬 1개 | **wallet 해시 샤딩** → K 프로세스/서버. 샤드 소유권 Redis로 조정 |
| Postgres | 공유 인스턴스+별도 DB | 전용 DB 서버 → 읽기 복제본 |
| Redis | 공유+ACL | 전용 인스턴스 → 클러스터 |
| 탐지/해석 | in-process | 순수함수/무상태라 수평 분할 자유 |

**안정성 장치**: at-least-once + 멱등(dedup_key) → 재시작/장애 복구 안전 · 다중 인덱서 합의(false-negative↓) · 재시도/백오프 · graceful degradation(STALE 노출) · `/health`·`X-Service-Version`·구조화 로그.

> 핵심: 모니터 워커를 **처음부터 "샤드 1개를 맡은 데몬"** 으로 작성하면(샤드 키 입력), 나중에 코드 변경 없이 샤드 N개로 수평 확장된다. 이게 D6를 만족하는 단일 설계 포인트.

## 8. 배포(네이티브, D2) — 다음 세션에서 구체화

- nginx server 블록(WatchTower) + PHP-FPM pool(전용 유저, max_children 캡) + Node 모니터 **systemd 유닛**(`Restart=always`, `MemoryMax`, 하드닝).
- 로컬 dev: postgres/redis docker-compose(stateful만, saju Phase-0 패턴). **프로덕션 Docker 없음.**
- 자세한 fleet 운영(pool 템플릿·systemd·PgBouncer·서비스 추가 절차)은 별도 `docs/fleet-runbook.md`(추후).
