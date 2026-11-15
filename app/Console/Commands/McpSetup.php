<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;

/**
 * تجهيز Passport لسيرفر الـ MCP — مفاتيح التشفير وعميل التوكنات الشخصية.
 *
 * الأمر ده آمن لو اتشغّل أكتر من مرة: بيعمل الناقص بس. على الاستضافة
 * المشتركة النشر بيبقى: git pull → composer install → php artisan migrate
 * → php artisan erp:mcp-setup (مرة واحدة بعد أول نشر).
 */
class McpSetup extends Command
{
    protected $signature = 'erp:mcp-setup';

    protected $description = 'تجهيز مفاتيح Passport وعميل التوكنات الشخصية لسيرفر الـ MCP';

    public function handle(ClientRepository $clients): int
    {
        $this->ensureKeys();
        $this->ensurePersonalAccessClient($clients);

        return self::SUCCESS;
    }

    /** مفاتيح التوقيع — مش بتتحط في git، فلازم تتولّد على السيرفر */
    private function ensureKeys(): void
    {
        $private = Passport::keyPath('oauth-private.key');
        $public = Passport::keyPath('oauth-public.key');

        if (file_exists($private) && file_exists($public)) {
            $this->line('  مفاتيح Passport موجودة.');

            return;
        }

        // KeysCommand بيستخدم إضافة openssl في PHP — مش محتاج exec()
        $this->call('passport:keys');
    }

    /** عميل الـ personal_access — من غيره createToken() بيرمي استثناء */
    private function ensurePersonalAccessClient(ClientRepository $clients): void
    {
        $provider = config('auth.guards.api.provider', 'users');

        $exists = Passport::client()->newQuery()
            ->where('revoked', false)
            ->get()
            ->contains(fn ($client) => $client->hasGrantType('personal_access'));

        if ($exists) {
            $this->line('  عميل التوكنات الشخصية موجود.');

            return;
        }

        $clients->createPersonalAccessGrantClient(
            config('app.name').' MCP',
            is_string($provider) ? $provider : 'users',
        );

        $this->info('  تم إنشاء عميل التوكنات الشخصية.');
    }
}
