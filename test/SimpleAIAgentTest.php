<?php

namespace losthost\SimpleAI\Test;

use PHPUnit\Framework\TestCase;
use losthost\SimpleAI\SimpleAIAgent;
use losthost\SimpleAI\types\ProviderRegistry;
use losthost\SimpleAI\types\FakeProvider;
use losthost\DB\DB;
use losthost\DB\DBView;

class SimpleAIAgentTest extends TestCase
{
    protected static string $api_key;

    public static function setUpBeforeClass(): void
    {
        self::$api_key = OPENAI_API_KEY ?: 'test_key';

        DB::connect(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PREF);

        ProviderRegistry::register('fake', function () {
            return new FakeProvider([
                [
                    'id' => 'r1',
                    'created' => time(),
                    'choices' => [[
                        'finish_reason' => 'stop',
                        'message' => ['role' => 'assistant', 'content' => 'London'],
                    ]],
                ],
                [
                    'id' => 'r2',
                    'created' => time(),
                    'choices' => [[
                        'finish_reason' => 'stop',
                        'message' => ['role' => 'assistant', 'content' => 'London'],
                    ]],
                ],
            ]);
        });
    }

    protected function setUp(): void
    {
        DB::query('TRUNCATE TABLE [sai_context]');
    }
    
    public function testBuildMethod(): void
    {
        $agent = SimpleAIAgent::build(self::$api_key, 'fake')->setModel('x');
        $this->assertInstanceOf(SimpleAIAgent::class, $agent);
    }

    public function testContextPersistence(): void
    {
        DB::query('TRUNCATE TABLE [sai_context]');

        $user_id = 'test_user_' . uniqid();
        $dialog_id = 'test_dialog_' . uniqid();

        $agent = SimpleAIAgent::build(self::$api_key, 'fake')
            ->setUserId($user_id)
            ->setDialogId($dialog_id)
            ->setPrompt('Отвечай коротко')
            ->setTimeout(60)
            ->setModel('x');

        $agent->ask('Столица Англии')->asString();

        $rows = $this->fetchContextRows($user_id, $dialog_id);
        $this->assertCount(3, $rows);
        $this->assertEquals('system', $rows[0][0]);
        $this->assertEquals('Отвечай коротко', $rows[0][1]);
        $this->assertEquals('user', $rows[1][0]);
        $this->assertEquals('Столица Англии', $rows[1][1]);
        $this->assertEquals('assistant', $rows[2][0]);
        $this->assertEquals('London', $rows[2][1]);

        $agent->ask('Повтори ответ')->asString();

        $rows2 = $this->fetchContextRows($user_id, $dialog_id);
        $this->assertCount(5, $rows2);
        $this->assertEquals('user', $rows2[3][0]);
        $this->assertEquals('Повтори ответ', $rows2[3][1]);
        $this->assertEquals('assistant', $rows2[4][0]);
        $this->assertEquals('London', $rows2[4][1]);
    }

    protected function fetchContextRows(string $user_id, string $dialog_id): array
    {
        $view = new DBView(
            'SELECT role, content, tool_call_id FROM [sai_context] WHERE user_id=? AND dialog_id=? ORDER BY id',
            [$user_id, $dialog_id]
        );

        $rows = [];
        while ($view->next()) {
            $rows[] = [$view->role, $view->content, $view->tool_call_id];
        }
        return $rows;
    }

    protected function tearDown(): void
    {
        try {
            //DB::query('TRUNCATE TABLE [sai_context]');
        } catch (\Throwable $e) {
        }
    }
}
