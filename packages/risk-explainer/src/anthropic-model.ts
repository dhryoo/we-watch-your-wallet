/**
 * 실 Claude 호출 어댑터(@anthropic-ai/sdk). 기본 모델 Haiku 4.5.
 * 비자문 가드레일 system 프롬프트 + 구조화 JSON 출력. 환각 방어(grounding·post-filter)는 explainRisk 가 담당.
 */
import Anthropic from "@anthropic-ai/sdk";
import { HAIKU_MODEL_ID, type ExplainRequest, type Model, type ModelResult, type ModelStopReason } from "./model";

const SYSTEM_PROMPT = [
    "당신은 온체인 위험을 설명하는 보조자입니다.",
    "입력으로 받은 facts 에 근거해서만 설명하고, sources 의 factKey 는 입력 facts 의 키만 사용하세요.",
    "매매를 권유하거나 보장하지 마세요(매수/매도/buy/sell 등 금지). 점검 항목만 제시하세요.",
    "출력은 RiskExplanation JSON 한 개만 반환하세요."
].join(" ");

function buildUserPrompt(request: ExplainRequest): string
{
    return JSON.stringify({ severity: request.severity, facts: request.facts });
}

export class AnthropicModel implements Model
{
    constructor(
        private readonly client: Anthropic,
        readonly modelId: string = HAIKU_MODEL_ID
    )
    {
    }

    async generate(request: ExplainRequest): Promise<ModelResult>
    {
        const response = await this.client.messages.create({
            model: this.modelId,
            max_tokens: 1024,
            system: SYSTEM_PROMPT,
            messages: [{ role: "user", content: buildUserPrompt(request) }]
        });

        const text = response.content
            .filter((block): block is Anthropic.TextBlock => block.type === "text")
            .map((block) => block.text)
            .join("");

        const stopReason = (response.stop_reason ?? "end_turn") as ModelStopReason;

        let parsed: unknown = null;
        if (stopReason !== "refusal")
        {
            try
            {
                parsed = JSON.parse(text);
            }
            catch
            {
                parsed = null;
            }
        }

        return {
            stopReason,
            parsed,
            usage: {
                input_tokens: response.usage.input_tokens,
                output_tokens: response.usage.output_tokens,
                cache_creation_input_tokens: response.usage.cache_creation_input_tokens ?? undefined,
                cache_read_input_tokens: response.usage.cache_read_input_tokens ?? undefined
            }
        };
    }
}
