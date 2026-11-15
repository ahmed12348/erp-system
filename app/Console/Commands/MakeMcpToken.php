<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Laravel\Mcp\Server\Registrar;

/**
 * بيطلع توكن Passport شخصي للاتصال بسيرفر الـ MCP من Claude Code.
 *
 * التوكن بيتعرض مرة واحدة بس — لو ضاع اعمل واحد جديد.
 * (claude.ai على الويب بيستخدم OAuth بدل التوكن ده — شوف README.)
 */
class MakeMcpToken extends Command
{
    protected $signature = 'erp:mcp-token
        {email=admin@erp.test : إيميل المستخدم اللي التوكن بإسمه}
        {--name=mcp : اسم التوكن}';

    protected $description = 'إنشاء توكن شخصي للاتصال بسيرفر الـ MCP بتاع النظام';

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();

        if (! $user) {
            $this->error('مفيش مستخدم بالإيميل ده: '.$this->argument('email'));

            return self::FAILURE;
        }

        if ($this->call('erp:mcp-setup') !== self::SUCCESS) {
            return self::FAILURE;
        }

        $name = (string) $this->option('name');

        // نلغي التوكن القديم بنفس الاسم عشان ما نكدّسش توكنات شغالة
        $user->tokens()->where('name', $name)->get()->each->revoke();

        $token = $user->createToken($name, [Registrar::OAUTH_SCOPE])->accessToken;

        $this->newLine();
        $this->line('  التوكن (انسخه دلوقتي، مش هيتعرض تاني):');
        $this->line('  '.$token);
        $this->newLine();
        $this->line('  الاتصال من Claude Code:');
        $this->line('  claude mcp add --transport http erp '.url('/mcp/erp')
            .' --header "Authorization: Bearer <التوكن>"');
        $this->newLine();

        return self::SUCCESS;
    }
}
