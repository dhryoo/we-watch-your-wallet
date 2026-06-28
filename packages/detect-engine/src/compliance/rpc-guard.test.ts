import { describe, expect, it } from "vitest";
import { assertReadOnlyRpcMethod, ForbiddenRpcMethodError } from "./rpc-guard";

describe("rpc-guard", () =>
{
    it("shouldThrowOnForbiddenMethodWithEmptyAllowlist", () =>
    {
        const emptyAllowlist: readonly string[] = [];

        expect(() => assertReadOnlyRpcMethod("eth_sendRawTransaction", emptyAllowlist))
            .toThrow(ForbiddenRpcMethodError);
    });
});
