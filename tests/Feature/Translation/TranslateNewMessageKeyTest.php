<?php

namespace Tests\Feature\Translation;

use Tests\TestCase;

class TranslateNewMessageKeyTest extends TestCase
{
    private string $newMessagesPath;

    private string $originalNewMessages;

    protected function setUp(): void
    {
        parent::setUp();

        $this->newMessagesPath = base_path('resources/lang/'.getDefaultLanguage().'/new-messages.php');
        $this->originalNewMessages = (string) file_get_contents($this->newMessagesPath);
    }

    protected function tearDown(): void
    {
        file_put_contents($this->newMessagesPath, $this->originalNewMessages);

        parent::tearDown();
    }

    public function test_a_placeholder_key_learned_mid_request_renders_its_text_on_every_later_call(): void
    {
        $this->loadNewMessagesGroup();
        $key = 'Freshly learned sentence for :shop '.uniqid();

        $firstRender = translate($key, ['shop' => 'Acme']);
        $secondRender = translate($key, ['shop' => 'Acme']);

        $expected = str_replace(':shop', 'Acme', $key);
        $this->assertSame($expected, $firstRender);
        $this->assertSame($expected, $secondRender);
    }

    public function test_a_plain_key_learned_mid_request_renders_its_text_after_the_memo_is_bypassed(): void
    {
        $this->loadNewMessagesGroup();
        $key = 'Freshly learned plain sentence '.uniqid();

        translate($key);

        $this->assertSame($key, getOrPutTranslateMessageValueByKey(local: getDefaultLanguage(), key: $key));
    }

    private function loadNewMessagesGroup(): void
    {
        $existingKey = array_key_first(include $this->newMessagesPath);

        $this->assertNotNull($existingKey);
        __('new-messages.'.$existingKey);
    }
}
