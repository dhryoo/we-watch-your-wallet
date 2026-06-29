<?php

return [
    // 4중 비용격리 설정
    'cache_ttl' => (int) env('SCAN_CACHE_TTL', 86400),         // 주소별 24h 캐시
    // IP 일일 신선스캔 한도. 주의: 신선 스캔 1회 ≈ GoPlus 3콜(token + nft721 + nft1155) + 최대 LLM 1콜. 한도 산정 시 고려.
    'ip_daily_limit' => (int) env('SCAN_IP_DAILY_LIMIT', 30),
    'wallet_daily_limit' => (int) env('SCAN_WALLET_DAILY_LIMIT', 5), // 지갑 일일 한도
    'monitor_ip_daily_limit' => (int) env('MONITOR_IP_DAILY_LIMIT', 10), // 리드 폼 IP 일일 제출 한도(스캔 버킷과 분리)
    'turnstile' => [
        'sitekey' => env('TURNSTILE_SITEKEY'),  // 미설정 시 위젯 미표시
        'secret' => env('TURNSTILE_SECRET'),    // 미설정 시 검증 우회(개발/1주차)
    ],
    // Cloudflare Web Analytics(쿠키리스·무PII, "무추적" 약속과 양립). 토큰 미설정 시 비콘 미렌더.
    'analytics' => [
        'cf_token' => env('CF_ANALYTICS_TOKEN'),
    ],
    // 오픈소스 저장소 URL. 설정 시에만 nav "Open source" 링크 + 신뢰페이지 "Verify it yourself" 노출(정직성: 공개 전엔 주장 안 함).
    'repo_url' => env('GITHUB_REPO_URL'),
    // ENS 이름 해석용 공개 이더리움 RPC(키 불필요, eth_call). 제3자 이름-해석 서비스 미사용. 결과는 24h 캐시.
    // 기본값 publicnode(키리스). cloudflare-eth.com은 공개 게이트웨이 종료(-32046). 키 RPC는 ENS_RPC_URL로 교체.
    'ens' => [
        'rpc_url' => env('ENS_RPC_URL', 'https://ethereum-rpc.publicnode.com'),
    ],
    // 위험 평문 설명용 LLM. 키 미설정 시 NullRiskExplainer → 결정론적 템플릿 폴백(idle-zero 유지).
    // 키 설정 시 신선 스캔에서만 Haiku 호출(24h 캐시·일일한도로 비용 묶임).
    'llm' => [
        'enabled' => (bool) env('SCAN_LLM_ENABLED', true),
        'api_key' => env('ANTHROPIC_API_KEY'),
        'model' => env('SCAN_LLM_MODEL', 'claude-haiku-4-5'),
        // 스캔당 LLM 호출 상한(최상위 위험 K건만). 비용·지연을 위험 수와 무관하게 묶는다.
        'max_per_scan' => (int) env('SCAN_LLM_MAX_PER_SCAN', 1),
    ],
];
