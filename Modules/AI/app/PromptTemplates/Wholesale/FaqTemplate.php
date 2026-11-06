<?php

namespace Modules\AI\app\PromptTemplates\Wholesale;

use Modules\AI\app\Contracts\PromptTemplateInterface;
use Modules\AI\app\Response\WholesaleAIResponse;

class FaqTemplate implements PromptTemplateInterface
{
    public function build(?string $context = null, ?string $language = null, ?string $description = null, ?array $options = null): string
    {
        $questionLimit = WholesaleAIResponse::FAQ_QUESTION_MAX_LENGTH;
        $answerLimit = WholesaleAIResponse::FAQ_ANSWER_MAX_LENGTH;
        $existingQuestions = $this->existingQuestionsBlock($options['existing_questions'] ?? []);
        $translationLanguages = $options['translation_languages'] ?? [];
        $translationsExample = $this->translationsExample($translationLanguages);
        $translationsRule = $this->translationsRule($translationLanguages, $questionLimit, $answerLimit);

        return <<<PROMPT
            You are an expert wholesale catalogue copywriter who answers the questions trade buyers ask before placing a bulk order.
            Given the following wholesale product:
            - Name: {$context}
            - Description: {$description}{$existingQuestions}

            Generate ONLY a JSON object for exactly ONE frequently asked question with this structure:
            {
              "question": "What is the minimum order?",
              "answer": "Orders start at the minimum quantity shown on the pricing table."{$translationsExample}
            }

            Rules:
            1. Ask the single most useful question a wholesale buyer of THIS product would still have — bulk pricing, packaging, materials, sizing or specifications, storage, delivery, returns or warranty.
            2. The "question" must be at most {$questionLimit} characters, including spaces and the question mark.
            3. The "answer" must be at most {$answerLimit} characters, including spaces.
            4. Answer only from the name and description. Never invent a price, a quantity, a certification or a delivery time that is not stated; answer generally instead.
            5. Never repeat or rephrase a question that is already answered.
            6. Write the question and the answer in {$language}.
            7. Plain text only — no HTML, no markdown, no numbering.{$translationsRule}

            === OUTPUT FORMAT RULE ===
             - Return ONLY the raw JSON object — no code blocks, no markdown, no explanation, no labels, no timestamps, no extra text,(do not include ```json```).
             - The response must start with "{" and end with "}".
             - Only respond with "INVALID_INPUT" if the name or description is completely irrelevant, nonsensical, or empty.
             - Do not return generic explanations, fallback messages, or apologies.
        PROMPT;
    }

    public function getType(): string
    {
        return 'wholesale_faq';
    }

    private function existingQuestionsBlock(array $questions): string
    {
        if ($questions === []) {
            return '';
        }

        return "\n            - Questions already answered: ".implode(' | ', $questions);
    }

    private function translationsExample(array $languages): string
    {
        if ($languages === []) {
            return '';
        }

        $entries = array_map(
            fn (string $code): string => '"'.$code.'": {"question": "…", "answer": "…"}',
            array_keys($languages),
        );

        return ",\n              \"translations\": {".implode(', ', $entries).'}';
    }

    private function translationsRule(array $languages, int $questionLimit, int $answerLimit): string
    {
        if ($languages === []) {
            return '';
        }

        $targets = implode(', ', array_map(
            fn (string $code, string $name): string => "\"{$code}\" = {$name}",
            array_keys($languages),
            $languages,
        ));

        return "\n            8. In \"translations\", translate the same question and answer into each of these languages, keyed by the code shown: {$targets}. Keep the meaning identical, keep each translated question at most {$questionLimit} characters and each translated answer at most {$answerLimit} characters, and include every listed code.";
    }
}
