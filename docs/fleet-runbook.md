# Fleet Runbook — 단일 서버 10+ 서비스 (Rocky 8, 네이티브, Docker 미사용)

> 목적: 한 대의 임대서버에서 10+ 서비스를 **유휴 시 자원 최소(idle-zero)** 로 안정 운영하는 공통 기반(D2·D5·D6).
> 원칙: **요청구동=PHP-FPM `pm=ondemand`(유휴 워커 0)**, **상시/AI=Node systemd 데몬(개수 최소)**, **공유 nginx/PG/Redis + 서비스별 격리**.
> 서버: Rocky Linux 8 · Xeon Quad-Core · 16GB · SSD 250G(핫) + SATA 2TB(콜드/백업). systemd 기반.
> 스케일: **사양 부족 시 새 서버 추가(수평)**. 이 런북의 컴포넌트는 전부 분리·이전 가능하게 설계.

## 0. 저장소/런타임 (Rocky 8)
- PHP 8.x: **Remi** repo(`dnf module reset php; dnf module enable php:remi-8.x`) → `php-fpm`.
- Postgres 16/17: **PGDG** repo(`dnf install postgresql16-server`). 데이터는 SSD.
- Redis: dnf(또는 Remi). PgBouncer: `dnf install pgbouncer`. nginx: dnf. Node: nodesource 또는 nvm.
- certbot(Let's Encrypt): `dnf install certbot python3-certbot-nginx`.

## 1. 자원 모델 — "상주 프로세스 합계"가 유일한 한계선
| 구성 | idle RAM(대략) | 비고 |
|---|---|---|
| OS+sshd+journald | ~0.8G | |
| nginx | ~0.1G | 1개 공유 |
| PHP-FPM 마스터 + idle pool ×N | **~0.2G** | ondemand → 자식 0 |
| Postgres(shared_buffers ~2G) | ~2.5G | SSD |
| Redis(maxmemory ~512M) | ~0.7G | |
| PgBouncer | ~0.05G | |
| **상주 Node 데몬 ×M** | **~0.2G/개** | ← 이게 한계선. **M을 통제** |
| **idle 베이스라인** | **≈5~6G** | 16G에 여유 |

> 요청구동 PHP 서비스는 10개든 20개든 유휴면 거의 공짜. **상주 Node 데몬 개수(M)** 만 감시하라. WatchTower 모니터=정당한 1개. 대부분 서비스는 ondemand로 충분.

## 2. nginx — 단일 앞문, 서비스별 server 블록
`/etc/nginx/conf.d/{service}.conf`:
```nginx
# {SERVICE} — 요청구동=PHP-FPM, 스트리밍=Node(선택)
upstream {service}_node { server 127.0.0.1:{NODE_PORT}; keepalive 16; }

server
{
    listen 443 ssl http2;
    server_name {service}.example.com;
    ssl_certificate     /etc/letsencrypt/live/{service}.example.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/{service}.example.com/privkey.pem;

    root /var/www/{service}/public;     # Laravel public
    index index.php;

    location = /health { proxy_pass http://{service}_node/health; proxy_set_header Host $host; }

    # SSE/스트리밍 → Node (버퍼링 off 필수, 안 끄면 토큰 뭉침)
    location /api/stream
    {
        proxy_pass http://{service}_node/stream;
        proxy_http_version 1.1;
        proxy_set_header Connection "";
        proxy_buffering off; proxy_cache off; proxy_read_timeout 1h;
        chunked_transfer_encoding on;
    }

    # 기본 → PHP-FPM (Laravel). webhook은 RAW 바디 유지(buffering 변형 금지).
    location /        { try_files $uri /index.php?$query_string; }
    location ~ \.php$
    {
        fastcgi_pass unix:/run/php-fpm/{service}.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
# certbot이 80→443 redirect 블록 자동 추가
```

## 3. PHP-FPM pool — 서비스별 (idle-zero 엔진)
`/etc/php-fpm.d/{service}.conf`:
```ini
[{service}]
user = {service}
group = {service}
listen = /run/php-fpm/{service}.sock
listen.owner = nginx
listen.group = nginx
listen.mode = 0660

pm = ondemand                  ; 유휴=자식 0 (idle-zero)
pm.max_children = 8            ; 서비스별 캡 (한 서비스 폭주가 박스 OOM 못 내게)
pm.process_idle_timeout = 10s  ; 유휴 워커 종료
pm.max_requests = 500          ; 워커 재활용(누수 방지)

php_admin_value[memory_limit] = 128M
php_admin_value[opcache.enable] = 1
slowlog = /var/log/php-fpm/{service}-slow.log
request_slowlog_timeout = 5s
catch_workers_output = yes
```
> `pm.max_children` 합이 박스 RAM을 넘지 않게 fleet 예산 관리. 서비스당 워커 ~50M 가정 → 8×50M=400M 피크/서비스. 동시에 busy인 서비스는 보통 소수.
`systemctl reload php-fpm` 로 적용.

## 4. systemd — Node 데몬 (Docker mem-limit·restart 대체 + OS 격리)
`/etc/systemd/system/{service}-monitor.service`:
```ini
[Unit]
Description={SERVICE} monitor worker
After=network-online.target postgresql.service redis.service pgbouncer.service
Wants=network-online.target

[Service]
Type=simple
User={service}
Group={service}
WorkingDirectory=/var/www/{service}
EnvironmentFile=/etc/{service}/monitor.env
ExecStart=/usr/bin/node /var/www/{service}/apps/gateway/dist/monitor.js
Restart=always
RestartSec=3

# 자원 한도 (cgroup = Docker mem limit 대체)
MemoryMax=512M
CPUQuota=80%

# 하드닝 (공유 호스트에서 Docker보다 강한 OS 격리)
NoNewPrivileges=true
ProtectSystem=strict
ProtectHome=true
PrivateTmp=true
ReadWritePaths=/var/log/{service}

[Install]
WantedBy=multi-user.target
```
`systemctl daemon-reload && systemctl enable --now {service}-monitor`.

### 4.1 스케일아웃(D6): 샤딩 템플릿 유닛
모니터를 **처음부터 "샤드 1개 담당"** 으로 작성(SHARD_INDEX/SHARD_TOTAL 입력). 부하 증가 시 코드 변경 없이 프로세스 N개:
`/etc/systemd/system/{service}-monitor@.service` 에 `Environment=SHARD_INDEX=%i`, `Environment=SHARD_TOTAL={N}` →
```
systemctl enable --now {service}-monitor@0 {service}-monitor@1 ... {service}-monitor@{N-1}
```
지갑은 `hash(address) % SHARD_TOTAL == SHARD_INDEX` 로 소유. 새 서버 추가 시 일부 인덱스를 그 서버로 이주.

## 5. Postgres — 서비스별 DB/role
```sql
CREATE ROLE {service} LOGIN PASSWORD '...';
CREATE DATABASE {service} OWNER {service};
\c {service}
CREATE SCHEMA app     AUTHORIZATION {service};   -- PHP 영역
CREATE SCHEMA monitor AUTHORIZATION {service};   -- Node 영역
```
- `shared_buffers ~2GB`, `work_mem` 작게(다수 서비스), `max_connections` 적당히(PgBouncer가 흡수).
- WAL 아카이브/백업은 **SATA 2TB**. 핫 데이터(`PGDATA`)는 SSD.
- 백업: 서비스별 `pg_dump`(cron) → SATA.

## 6. PgBouncer — 연결 폭주 차단 (10+ 서비스 필수)
`/etc/pgbouncer/pgbouncer.ini`:
```ini
[databases]
saju_ai    = host=127.0.0.1 port=5432 dbname=saju_ai
watchtower = host=127.0.0.1 port=5432 dbname=watchtower
; 서비스 추가 시 한 줄씩

[pgbouncer]
listen_addr = 127.0.0.1
listen_port = 6432
auth_type = scram-sha-256
auth_file = /etc/pgbouncer/userlist.txt
pool_mode = transaction        ; 다수 서비스에 최적(연결 점유 최소)
max_client_conn = 1000
default_pool_size = 20         ; (db,user)당
reserve_pool_size = 5
```
> **앱은 5432가 아니라 127.0.0.1:6432(PgBouncer)로 접속.** 10+ 서비스 × FPM 워커 × Node 데몬의 연결을 소수의 실제 PG 연결로 압축 → Postgres 메모리·`max_connections` 보호. (Node 측은 `pool_mode=transaction`과 호환되게 prepared statement 주의.)

## 7. Redis — 서비스별 ACL
`redis.conf` / `users.acl`:
```
user {service} on >{password} ~{service}:* +@all -@dangerous
```
- 키는 `{service}:` 프리픽스 강제. `maxmemory` 설정.
- ⚠️ **큐 vs 캐시 eviction 충돌**: 잡 큐는 `noeviction` 필요(잃으면 안 됨), 캐시는 `allkeys-lru` 선호. 둘 다 쓰면 **큐용/캐시용 논리 분리**(키 정책) 또는 트래픽 커지면 캐시 전용 별도 Redis 인스턴스. WatchTower는 디바운스/dedup 상태(잃으면 안 됨) + 스캔 캐시(버려도 됨) 공존 → 초기엔 noeviction + 캐시 TTL, 성장 시 분리.

## 8. 신규 서비스 온보딩 절차 (체크리스트)
1. 리눅스 유저 + 디렉터리: `useradd -r {service}`; `/var/www/{service}`, `/etc/{service}`, `/var/log/{service}`.
2. Postgres: role+db+schema(§5). PgBouncer `[databases]` + `userlist.txt` 추가 → `systemctl reload pgbouncer`.
3. Redis: ACL 유저(쓰면).
4. PHP-FPM: pool 파일(§3) → `systemctl reload php-fpm`.
5. Node 데몬(있으면): systemd 유닛(§4) → `daemon-reload; enable --now`.
6. nginx: server 블록(§2) + `certbot --nginx -d {service}.example.com` → `nginx -t && systemctl reload nginx`.
7. 배포: 코드 deploy(git pull/build), `php artisan migrate`(app), drizzle migrate(monitor).
8. 검증: `curl https://{service}.example.com/health` · `journalctl -u {service}-monitor -f` · `free -m`(상주 RAM 추세).

## 9. 안정성/관측 (D6)
- 모든 서비스: `/health`, `X-Service-Version` 헤더, 구조화 로그(JSON→journald/stdout).
- 스왑 2~4GB 안전장치. `node_exporter`(경량) 또는 `free`/`systemctl status`로 RAM/CPU 추세 감시.
- **언제 새 서버**: 상주 Node 데몬 합계 RAM이 한계 접근, 또는 Postgres working set이 shared_buffers 초과 지속, 또는 CPU 동시 버스트 포화 → 그 서비스(또는 DB)를 전용 서버로 이주(컴포넌트가 분리형이라 가능).
- 프로덕션 Docker 없음(D2). 로컬 dev만 postgres/redis docker-compose(saju Phase-0 패턴) 허용.
