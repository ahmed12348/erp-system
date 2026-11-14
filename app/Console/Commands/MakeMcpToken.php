<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

/**
 * بيطلع توكن Sanctum للاتصال بسيرفر الـ MCP.
 *
 * التوكن بيتعرض مرة واحدة بس — لو ضاع اعمل واحد جديد.
 */
class MakeMcpToken extends Command
{
    protected $signature = 'erp:mcp-token
        {email=admin@erp.test : إيميل المستخدم اللي التوكن بإسمه}
        {--name=mcp : اسم التوكن}';

    protected $description = 'إنشاء توكن للاتصال بسيرفر الـ MCP بتاع النظام';

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();

        if (! $user) {
            $this->error('مفيش مستخدم بالإيميل ده: '.$this->argument('email'));

            return self::FAILURE;
        }

        $name = (string) $this->option('name');

        $user->tokens()->where('name', $name)->delete();
        $token = $user->createToken($name)->plainTextToken;

        $this->newLine();
        $this->line('  التوكن (انسخه دلوقتي، مش هيتعرض تاني):');
        $this->line('  '.$token);
        $this->newLine();
        $this->line('  الاتصال من Claude Code:');
        $this->line('  claude mcp add --transport http erp '.url('/mcp/erp')
            .' --header "Authorization: Bearer '.$token.'"');
        $this->newLine();

        return self::SUCCESS;
    }
}
