# Handoff: WatchTower — 무료 지갑 위험 스캔 (첫 출시)

## Overview
WatchTower는 **비수탁(non-custodial) DeFi 지갑 위험 감시 서비스**입니다. 사용자의 키·자금에 절대 접근하지 않고, 공개 온체인 데이터를 **읽기 전용**으로 분석해 위험한 토큰 승인(approval)을 알려줍니다. 첫 출시물은 "지갑 주소를 넣으면 위험 승인을 무료로 스캔해 보여주는" 페이지이며, 이 핸드오프는 그 출시에 필요한 7개 화면/산출물의 시각 사양을 담습니다.

핵심 원칙(카피·UI에 반드시 반영):
1. **비수탁** — 우리가 대신 실행하지 않는다. revoke 등 모든 거래는 사용자가 본인 지갑에서 직접 실행.
2. **비자문** — 매수/매도/투자 권유 절대 금지. "검토하세요 / 확인하세요"만 사용. 명령·보장 표현 금지.
3. **정직성** — 데이터 조회 실패 시 `STALE`을 숨기지 않고 명시.

## About the Design Files
이 번들의 `WatchTower Scan.dc.html`은 **HTML로 만든 디자인 레퍼런스**입니다 — 의도한 모양·동작을 보여주는 프로토타입이며, 그대로 복사해 배포할 프로덕션 코드가 아닙니다. 작업은 이 디자인을 **대상 코드베이스의 기존 환경(React/Next, Vue, SwiftUI 등)과 패턴·라이브러리로 재현**하는 것입니다. 아직 환경이 없다면 프로젝트에 가장 적합한 프레임워크를 골라 구현하세요(SSR·SEO·영구 URL 요건이 있으므로 Next.js App Router 등 SSR 가능한 스택 권장).

> `.dc.html`은 자체 런타임(`support.js`)으로 동작하는 단일 파일 디자인 컴포넌트입니다. 브라우저로 바로 열어 7개 프레임을 한 화면에서 확인할 수 있습니다. 구현 시에는 이 파일을 참조용으로만 사용하세요.

## Fidelity
**High-fidelity (hifi)** — 최종 컬러·타이포·간격·인터랙션이 확정된 시안입니다. UI를 픽셀 단위로 코드베이스의 기존 라이브러리·패턴으로 재현하세요. 표시된 주소·점수·토큰 값은 데모 데이터이며 실제 데이터로 대체됩니다.

---

## Screens / Views
파일 안에 7개 프레임이 가로로 배치되어 있습니다.

### 1) 스캔 결과 페이지 — Desktop (`/scan/{address}`)
SSR·SEO·영구 URL·공유용 페이지. 컨테이너 폭 1280px.

- **Nav** (상단): 배경 민트 `#A0F1BD`, 패딩 `28px 40px`. 좌측 로고(다크그린 라운드 마크 + "WatchTower" Work Sans 600/17px), 우측 텍스트 링크 "작동 원리"·"비수탁이란?"(Work Sans 500/12.5px) + 알약 버튼 "Pro 알아보기"(배경 `#2E4F21`, 흰 텍스트, radius 40px, 패딩 `10px 18px`).
- **Result header** (패딩 `44px 40px 36px`, 하단 보더 `1px #E9E9E7`): 좌/우 2열 `space-between`.
  - **좌(identity)**: 체인 칩(`#ECFCF2` 배경, `#CFE9D9` 보더, 다크그린 점 + "Ethereum · chainId 1") + 모노 라벨 "읽기 전용 분석". 아래 주소 `Roboto Mono 30px #2E4F21` + **마스킹 토글 버튼**(보더 `1px #C7C7C7`, 알약, 라벨 "주소 보기"↔"마스킹"). 아래 메타: "스캔 시각 …UTC" · "승인 47건 검토 · 위험 3건"(Work Sans 13px `#7D9276`).
  - **우(score+actions)**: **ScoreGauge** 128px(아래 컴포넌트 참고) + URGENT 배지, 그 옆 세로 버튼 2개 — "다시 스캔"(solid 다크그린)·"결과 공유"(아웃라인, ↗ 아이콘).
  - **STALE 배너**(헤더 하단, `margin-top:24px`): 배경 `#FBF5E3`, 보더 `#E8D9A8`, radius 12px. 앰버 점 + "일부 가격·잔액 데이터를 가져오지 못했습니다 — `STALE`. 영향받은 항목에 표시했습니다. 승인 권한 분석은 최신입니다." (`STALE`은 Roboto Mono).
- **Body** (패딩 `36px 40px 8px`): 제목 "위험한 토큰 승인 **3건**"(Work Sans 400/30px, 숫자 `#7D9276`) + 우측 모노 "위험도 높은 순 정렬". 아래 **RiskApprovalCard** 세로 스택(gap 18px), severity 내림차순(URGENT→WARN→WATCH).
- **Conversion CTA** (`margin:36px 40px`): 배경 `#ECFCF2`, 보더 `#CFE9D9`, radius 20px, 패딩 `36px 40px`. 좌측 카피("이 지갑을 계속 지켜보세요" + 설명, 키·자금 요구 안 함 명시), 우측 폭 420px — 이메일 입력 1줄 + "모니터링하기" 버튼 한 줄, 그 아래 **Cloudflare Turnstile 자리**(흰 박스, 체크박스 + "사람인지 확인 중…" + 모노 "CLOUDFLARE TURNSTILE").
- **Footer**: 배경 `#2E4F21`. 워드마크 + 면책 고지 전문(Roboto Mono 11px `#9BBF9F`, 아래 Design Tokens 하단 카피 참고).

### 2) 스캔 결과 — Mobile (390px)
화면 1의 동일 시스템 압축본: 햄버거 내브, 헤더(체인 칩·마스킹 주소 `0x7a3f••••9c2D`·메타·104px 게이지+URGENT+다시스캔·STALE 배너), 위험 카드 1장 확장형, 모바일 CTA(이메일+버튼+Turnstile 축약), 다크그린 푸터.

### 3) 안전 / 빈 상태 — Desktop (1280px)
위험 승인이 없을 때. Nav + 헤더(주소·게이지는 점수 **8/100**, severity **INFO · 양호** 그린 톤) + 중앙 정렬 빈 상태: 80px 원형 체크 마크(`#ECFCF2` 배경, 다크그린 ✓), 헤드라인 "위험한 승인이 없습니다"(Work Sans 300/46px/`-.06em`), 설명 2줄(본문 + 모노 "주기적으로 다시 확인" 안내), 버튼 2개("이 지갑 모니터링하기" solid · "결과 공유" 아웃라인). 다크그린 푸터.

### 4) OG 공유 카드 (1200×630)
소셜 미리보기 이미지. 배경 `#2E4F21`, 우상단 ambient 그린 원 2개. 패딩 `64px 72px`. 상단 워드마크 + 모노 "지갑 위험 스캔". 중앙 좌측: 체인 칩(마스킹 `0x12…ab34`) + 대형 "위험 승인 / 3건 발견"(Work Sans 200→400, 76px, `-.05em`) + URGENT 배지(큰 사이즈). 중앙 우측: **210px ScoreGauge**(점수 82, 안쪽 원 `#2E4F21`). 하단 보더 위 CTA "내 지갑도 무료로 스캔 →"(`→`는 민트) + 모노 "watchtower.xyz · 비수탁 · 읽기 전용".
**민감정보 금지**: 전체 주소·토큰 상세 노출 안 함. 한눈에 읽히는 큰 타이포.

### 5) 이메일 리드 — Desktop (본문 폭 660px, Pro 전환 깔때기)
- **메타 카드**(별도): 제목 "0x12…ab34 지갑에서 긴급 위험 승인 3건이 확인되었습니다", 프리뷰 텍스트 "무제한 USDC 승인을 포함합니다. 내용을 검토하고 필요하면 직접 취소하세요."
- **이메일 본문**(배경 `#F4F7F5` 패딩 안에 흰 카드 radius 16px): 민트 헤더 워드마크 → "지갑 위험 스캔 요약" + 메타 → **요약 2칸**(전체 위험 82·URGENT / 위험 승인 3건) → **최상위 위험** 카드(USDC·MALICIOUS·무제한·설명) → **RevokeButton**(revoke.cash 링크) + 모노 캡션 → **Pro CTA**(`#ECFCF2` 박스, "지속 모니터링 + 텔레그램 알림" + "Pro 시작하기") → 다크그린 푸터(면책 전문 + "수신 거부 · 알림 설정").

### 6) 이메일 — Mobile (360px)
화면 5 압축본.

### 7) 디자인 토큰 & 재사용 컴포넌트 (1280px)
컬러 스와치 12종, 타이포 스케일, 간격·라운드 견본, 컴포넌트 스펙(SeverityBadge×4 / ScoreGauge / RevokeButton / SpenderTag×3), 복사용 `:root` CSS 블록. 아래 Design Tokens 섹션과 동일.

---

## Reusable Components

### SeverityBadge
4단계. **색만으로 구분 금지** — 항상 영문 라벨 + 한글 라벨 + 도형 마크 동반(접근성). 알약형, radius 40px, 패딩 `7px 14px`, gap 7px. 마크(Unicode 도형, 텍스트 컬러로 렌더) + "LABEL · 한글"(Work Sans 600/11.5px/`+.02em`).

| 단계 | mark | text(fg) | bg | border(bd) | accent(ic) |
|---|---|---|---|---|---|
| INFO · 정보 | ● | `#3F5660` | `#EEF3F5` | `#C3CED4` | `#5B7280` |
| WATCH · 주의 | ● | `#7A5E10` | `#FBF5E3` | `#E8D9A8` | `#C39A2C` |
| WARN · 경고 | ◆ | `#8E4419` | `#FBEFE7` | `#EFCBB2` | `#C2622E` |
| URGENT · 긴급 | ▲ | `#8E2018` | `#FBEAE8` | `#EFB5B0` | `#B3382E` |

### ScoreGauge (0–100)
원형. 바깥 원 `conic-gradient(<accent> 0% <score>%, <track> <score>% 100%)`, 안쪽 흰 원(라이트 배경) 또는 `#2E4F21`(다크 배경)으로 링 두께 ~15px. 중앙: 점수 `Work Sans 200–300, -.05em`(라이트 위 `#2E4F21`, 다크 위 흰색) + "/ 100" 모노 캡션. accent는 전체 severity 색. 사이즈 변형: 104 / 128 / 210px. track 색 — 라이트 `#EFE7E4`, 다크 `#3A6029`.

### RiskApprovalCard
보더 `1px <sev.bd>`, radius 18px, 흰 배경.
- **상단 스트립**(배경 `<sev.bg>`, 패딩 `18px 24px`, 하단 보더 `<sev.bd>`): 좌측 — 토큰 아바타(34px 원, 이니셜) + 심볼(Work Sans 600/15px) + 주소(Roboto Mono 10.5px `#8C9A89`) + **SpenderTag** + **EOA 칩**(`#F4F7F5`/`#DDE4DD`, "컨트랙트"/"EOA"). 우측 — SeverityBadge.
- **본문**(패딩 `22px 24px 24px`): 좌열 — "승인 한도" + **무제한 배지**(`#FBEAE8`/`#EFB5B0`, "무제한 · Unlimited") 또는 한도 텍스트(+ STALE이면 모노 "잔액 STALE" 칩), 위험 신호 칩들(알약, `#F9F9F9`/`#E6E6E3`, 좌측 점 = sev.ic), 쉬운 설명 1–3문장(Work Sans 15px), "왜 중요한가" 박스(`#F4F7F5` radius 12px, 설명 + 체크리스트 — 15px 빈 체크박스 + 항목). 우열(폭 248px) — 액션 박스: "권장 점검" 라벨 + **RevokeButton** + 모노 캡션.

### SpenderTag
모노 라벨, radius 6px, 패딩 `5px 9px`. MALICIOUS=`#FBEAE8`/`#EFB5B0`/`#8E2018` · UNVERIFIED=`#FBF5E3`/`#E8D9A8`/`#7A5E10` · VERIFIED=`#ECFCF2`/`#CFE9D9`/`#2E4F21`.

### RevokeButton
solid 다크그린 `#2E4F21`, 흰 텍스트, radius 40px(알약), DM Sans 500/14px, "Revoke 페이지 열기 ↗".
- `href = https://revoke.cash/address/{지갑주소}?chainId={체인ID}` , `target="_blank" rel="noopener noreferrer"` (새 탭).
- **버튼 옆 캡션 필수**(Roboto Mono ~10px `#9AA897`): "취소(revoke)는 본인 지갑에서 직접 실행됩니다 — 우리는 대신 실행하지 않습니다."

---

## Interactions & Behavior
- **마스킹 토글**: 헤더 주소를 `0x7a3f••••••9c2D`(마스킹) ↔ 전체 주소로 전환. 라벨도 "주소 보기"↔"마스킹". 기본값 = 마스킹.
- **다시 스캔**: 동일 주소·체인으로 재조회 트리거.
- **Revoke 버튼**: revoke.cash 주소 페이지를 새 탭으로 연다. 우리 서비스는 트랜잭션을 생성·실행하지 않는다.
- **결과 공유**: 이 결과의 OG 카드(영구 URL) 공유.
- **모니터링 폼**: 이메일 + Turnstile 검증 후 구독. 성공/실패/검증중 상태 필요.
- **정렬**: 위험 카드는 severity 내림차순(URGENT→WARN→WATCH→INFO).
- **반응형**: 1280px 데스크톱 → 390px 모바일(헤더 세로 적층, 카드 단일 열, 우열 액션이 본문 하단으로).
- **상태**: 위험 있음(카드 리스트) / 위험 없음(빈 상태 화면) / STALE(배너 + 항목 칩) 세 가지를 모두 처리.

## State Management
- `address`, `chainId`, `masked(bool)`, `scanTimestamp`
- `scanResult`: `{ score:0-100, severity, approvalsReviewed, risks: RiskApproval[] }`
- `RiskApproval`: `{ token, tokenAddress, spenderTag, isContract, unlimited, limitText?, stale, signals[], explain, why, checklist[], severity, revokeUrl }`
- 데이터 페치: 온체인 approval 조회(읽기 전용 RPC/인덱서). 실패·지연 시 해당 필드 `stale=true`로 표시(숨기지 않음).
- 모니터링 폼: `email`, `turnstileToken`, `submitState`.

## Design Tokens
```css
:root {
  /* brand */
  --wt-green: #2E4F21; --wt-green-700: #3C4A36;
  --wt-mint: #A0F1BD;  --wt-mint-50: #ECFCF2;
  --wt-sage: #7D9276;  --wt-surface: #F4F7F5;
  --wt-line: #C7C7C7;  --wt-line-soft: #E6E6E3;
  /* severity */
  --sev-info:   #5B7280; --sev-info-bg:   #EEF3F5; --sev-info-bd:   #C3CED4;
  --sev-watch:  #C39A2C; --sev-watch-bg:  #FBF5E3; --sev-watch-bd:  #E8D9A8;
  --sev-warn:   #C2622E; --sev-warn-bg:   #FBEFE7; --sev-warn-bd:   #EFCBB2;
  --sev-urgent: #B3382E; --sev-urgent-bg: #FBEAE8; --sev-urgent-bd: #EFB5B0;
  /* type */
  --font-display: "Work Sans"; --font-ui: "DM Sans"; --font-mono: "Roboto Mono";
  /* radius */ --r-sm: 6px; --r-md: 14px; --r-pill: 40px;
  /* space  */ --s-1: 8px; --s-2: 16px; --s-3: 24px; --s-4: 40px; --s-5: 64px;
}
```
**Typography 스케일**: Display = Work Sans 200 / 46–80px / `-.05~-.08em` · Heading = Work Sans 400 / 27–30px / `-.05em` · Body = Work Sans 400 / 15px / `-.02em` · Button = DM Sans 500 / 14px / `-.01em` · Caption/법적고지 = Roboto Mono 400 / 10–12px / `+.03em`. 부가 세리프 악센트로 Spectral 사용 가능.

**면책 고지(푸터·이메일 하단 공통, 그대로 사용)**:
> 본 정보는 정보 제공 목적이며 투자·매매 권유가 아닙니다. revoke 등 모든 실행은 사용자 본인 지갑에서 직접 수행되며, RPC 데이터는 지연·오류가 있을 수 있습니다.

## Copy Rules (엄수)
- 권유/명령 금지: "지금 파세요 / 취소하세요" ❌ → "이 승인을 검토하고, 필요하면 본인이 직접 취소(revoke)하세요" ✅
- 보장 표현 금지. "안전합니다" 대신 "위험 신호가 발견되지 않았습니다".
- 데이터 신선도: 실패·지연 시 항상 `STALE` 명시.

## Fonts / Assets
- **폰트**: Work Sans, DM Sans, Roboto Mono(필수), Spectral(선택) — Google Fonts. 코드베이스 폰트 로딩 방식에 맞춰 셀프호스팅 권장.
- **아이콘**: severity 마크는 Unicode 도형(● ◆ ▲)을 텍스트 컬러로 렌더. 로고/체크/화살표(↗ →)는 단순 도형 — 기존 아이콘 라이브러리로 대체 가능.
- **이미지 에셋 없음**: 모든 시각요소가 CSS로 구성됨(게이지=conic-gradient). OG 카드는 서버에서 PNG로 렌더(예: Satori/`@vercel/og`)하는 것을 권장.
- 별도 브랜드 에셋 번들 없음 — 위 토큰이 단일 출처(source of truth)입니다.

## Files
- `WatchTower Scan.dc.html` — 7개 프레임 전체 디자인 레퍼런스(브라우저로 바로 열림).
- `support.js` — 위 파일의 런타임(참조용, 구현에는 불필요).
