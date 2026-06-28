# WatchTower — brief.md (확정된 설계 진실)

> 이 문서가 **진실**이다. 임의로 바꾸지 않는다. 변경은 결정(D-번호)을 갱신하고 사유를 남긴다.
> 작업 시작 전 함께 읽기: `docs/architecture.md`(기술 설계), `plan.md`(TDD 작업), `CLAUDE.md`(코드 스타일·TDD 규칙).

## 프로젝트 한 줄
**비수탁 온체인 자산 모니터링 SaaS.** 사용자가 지갑 **주소만** 읽기 전용으로 등록하면, 여러 체인/프로토콜 포지션을 24/7 감시해 **청산 근접·디페그·위험 approval·러그**를 탐지하고, **Claude가 "왜 위험한지 + 무엇을 점검할지"를 평문으로 해석**해 알린다. 단순 임계치 알림봇이 아니라 **Claude의 맥락 해석**이 차별점.

이 제품은 솔라나 idea 프로젝트의 사업 후보 32선 적대적 검증에서 **종합 1위(4.2)**로 선정된 앵커다. 선정 절차·근거는 `/Users/idfeelme/2025_project/solana/idea`의 전략 보고서 참조(이 레포는 빌드 전용).

## 비타협 3대 불변식 (코드·CI 게이트로 강제 — architecture.md §1)
1. **비수탁(Non-custodial)** — 개인키/시드/자금 미보유. DB에 키 컬럼 자체가 없다. RPC는 read-only allowlist. revoke·실행은 사용자 본인 지갑(revoke.cash 딥링크 위임).
2. **비자문(非諮問)** — 한국 자본시장법 회피. 매수/매도 권유 0, 1:1 유료 맞춤자문 미구현, 동일 방법론 일괄 적용. 출력은 "사실 + 점검 항목"만.
3. **정직성(False-negative 방어)** — "놓칠 수 있음"을 숨기지 않는다. 다중 인덱서 합의, 공개 커버리지 매트릭스, best-effort 언어, RPC 실패 시 STALE 표시(침묵 금지).

## P0 범위 (12주 첫 출시 — 이것만 만든다)
| 영역 | P0 | 미룸(P1+) |
|---|---|---|
| 체인 | **1개**(Base 또는 Arbitrum, D8 미정) | 멀티체인·L1·Solana |
| 탐지기 | **2개**: 청산 근접(Aave v3 HF) · ERC20 approval | 디페그(yield NAV)·러그 |
| 첫 출시물 | **무료 approval 스캔**(주소→위험승인+revoke+공유카드+이메일) | — |
| 알림 | **텔레그램** | 이메일·웹푸시 |
| Claude | **Haiku 단일** + 디바운스/dedup/쿨다운 | Sonnet/Opus 라우팅·Batches |
| 결제 | **없음**(무료훅 + Pro 대기리스트) | Creem 구독·B2B |
| 비타협 | 비수탁·비자문·정직성(P0부터) | — |

## 확정 결정 (Decisions)
| D | 결정 | 사유 |
|---|---|---|
| **D1** | **결제 = Creem(MoR)** | 한국 셀러 지원·글로벌 VAT 대행·실효 ~9.5%(Paddle보다 쌈). saju-ai `app/Billing/Creem/*`(체크아웃·webhook 서명검증·멱등) **재사용**. |
| **D2** | **배포 = 네이티브(Docker 미사용)** | 한 서버에 10+ 서비스 운영·유휴 자원 최소화 목표. PHP-FPM `pm=ondemand`가 idle-zero 엔진. systemd로 데몬 관리. docker-compose는 **로컬 개발 stateful 서비스 전용**. |
| **D3** | **폴리글랏 PHP + Node** | 요청구동(페이지·인증·**결제**·scan API·대시보드)=PHP/Laravel `pm=ondemand`(유휴=제로). 상시/AI(모니터 워커·탐지엔진·Claude)=Node/TS. saju-ai와 동일 패턴. |
| **D4** | **모니터 = 상주 데몬(systemd) 1개** | 24/7 감시는 본질적 always-on. ~200MB·CPU≈0. **단, wallet 샤딩 가능하게 설계**(스케일아웃 대비). |
| **D5** | **공유 Postgres/Redis + 서비스별 DB·role + PgBouncer** | 16GB에 10+ 서비스 메모리 최적. WatchTower는 별도 DB·role·Redis ACL로 격리(크립토 제품). |
| **D6** | **설계 기조 = 안정성 우선, 스케일=서버 추가(수평)** | 사용자 급증 시 새 서버로 업그레이드 예정. 단일 박스 micro-opt 지양. 컴포넌트를 **분리·이전 가능**하게(stateless 웹·샤딩 워커·분리형 DB/Redis). |
| **D7** | **Claude = @anthropic-ai/sdk, Haiku 4.5(P0)** | saju-ai 패턴. `estimateCostUsd` 비용추적 재사용. 모델 라우팅(Sonnet/Opus)·Batches는 P1. |
| **D8** | **코드 스타일** | Allman 중괄호·4스페이스·camelCase·kebab-case 파일·named export. TS strict. (CLAUDE.md) |

## 비목표(Non-goals) / 금지
1. 자금·키·시드 보유 금지(수탁/VASP 회피). revoke 트랜잭션 대행 금지.
2. 매수/매도·종목 추천·수익보장·1:1 유료 자문 금지(자본시장법).
3. 예측시장 베팅 알선 금지(사행성).
4. 단일 박스 자원 최적화를 **안정성·제품 핵심(적시 경고)보다 우선하지 않는다**.
5. Docker 프로덕션 도입 금지(D2). 로컬 dev compose는 허용.

## 미결정(다음 세션에서 확정)
- **D8a**: P0 체인 = Base vs Arbitrum (사용자/렌딩 분포로).
- 인증: 자체 매직링크(Laravel Fortify) vs 소셜(Google) 우선순위.
- 법률검토: 유사투자자문 비등록 운영 라인·ToS 면책·PIPA(착수 전 1회).

## 스케일·안정성 철학 (D6 상술 → architecture.md §7)
- **웹 tier stateless** → nginx 뒤 N대 복제 가능.
- **모니터 워커는 wallet 해시 샤딩** → K 프로세스/서버로 수평 분할(지금은 1 데몬).
- **DB/Redis 분리 가능** → 전용 서버 → 읽기 복제본.
- **at-least-once + 멱등(dedup_key)** → 재시작·장애 복구에 안전.
- 관측성: `/health`, `X-Service-Version`, 구조화 로그(JSON/stdout) — saju-ai NFR 이어감.
