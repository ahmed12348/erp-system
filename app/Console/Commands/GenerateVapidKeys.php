<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Minishlink\WebPush\VAPID;

/**
 * بيولّد مفاتيح VAPID لإشعارات المتصفح.
 *
 * بيطبعها بس وما بيكتبش في `.env` — المفتاح الخاص سرّ، والكتابة
 * التلقائية في ملف الإعدادات بتخلّي حد ينسخه من غير ما ياخد باله.
 */
class GenerateVapidKeys extends Command
{
    protected $signature = 'erp:vapid-keys';

    protected $description = 'بيولّد مفاتيح VAPID لإشعارات المتصفح';

    public function handle(): int
    {
        try {
            $keys = VAPID::createVapidKeys();
        } catch (\Throwable $e) {
            /*
             * التوليد محتاج OpenSSL بمنحنى prime256v1. بيقع على بعض
             * أجهزة ويندوز اللي مش لاقية `openssl.cnf` — على لينكس
             * (ومنه السيرفر) بيشتغل عادي.
             */
            $this->error('مقدرتش أولّد المفاتيح: '.$e->getMessage());
            $this->newLine();
            $this->line('غالبًا OpenSSL مش لاقي ملف الإعدادات بتاعه.');
            $this->line('على ويندوز: ظبّط OPENSSL_CONF على مسار openssl.cnf.');
            $this->line('أو ولّدهم على السيرفر مباشرة — هناك بيشتغل عادي.');

            return self::FAILURE;
        }

        $this->info('حط السطور دي في .env:');
        $this->newLine();
        $this->line('VAPID_PUBLIC_KEY='.$keys['publicKey']);
        $this->line('VAPID_PRIVATE_KEY='.$keys['privateKey']);
        $this->line('VAPID_SUBJECT='.config('app.url'));
        $this->newLine();
        $this->warn('المفتاح الخاص سرّ — ماتحطّهوش في git ولا تبعته لحد.');

        return self::SUCCESS;
    }
}
