/**
 * 멱등 event id 생성 — 결정론적 해시. architecture.md §2.
 * id = sha256(detectorId|walletId|subjectKey|floor(ts/bucketMs)).
 * 같은 (대상, 시간버킷) → 같은 id → 중복 알림 dedup.
 */
import { createHash } from "node:crypto";

export interface EventIdInput
{
    detectorId: string;
    walletId: string;
    subjectKey: string;
    ts: number;
    bucketMs: number;
}

export function generateEventId(input: EventIdInput): string
{
    const bucket = Math.floor(input.ts / input.bucketMs);
    const material = `${input.detectorId}|${input.walletId}|${input.subjectKey}|${bucket}`;
    return createHash("sha256").update(material).digest("hex");
}
