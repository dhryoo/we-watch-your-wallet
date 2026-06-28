/**
 * 비수탁(non-custodial) 불변식: RPC 호출은 read-only allowlist에 등록된 메서드만 허용한다.
 * allowlist에 없는 메서드(서명·전송 계열 등)는 호출 경로 자체를 막기 위해 throw 한다.
 * architecture.md §1 rpc-readonly 게이트.
 */

export const FORBIDDEN_RPC_METHODS = [
    "eth_sendRawTransaction",
    "eth_sendTransaction",
    "eth_sign",
    "eth_signTransaction",
    "eth_signTypedData",
    "eth_signTypedData_v4",
    "personal_sign"
] as const;

export class ForbiddenRpcMethodError extends Error
{
    constructor(method: string)
    {
        super(`Forbidden RPC method (not in read-only allowlist): ${method}`);
        this.name = "ForbiddenRpcMethodError";
    }
}

export function assertReadOnlyRpcMethod(method: string, allowlist: readonly string[]): void
{
    if (!allowlist.includes(method))
    {
        throw new ForbiddenRpcMethodError(method);
    }
}
