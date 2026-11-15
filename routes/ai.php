<?php

use App\Mcp\Servers\ErpServer;
use Laravel\Mcp\Facades\Mcp;

/*
 * سيرفر الـ MCP — أدوات قراءة فقط على بيانات النظام.
 *
 * المصادقة كلها على جارد Passport الواحد (auth:api)، وفيه طريقتين للتوكن:
 *
 *  1) OAuth 2.1 — اللي claude.ai بيطلبه في الـ custom connectors.
 *     oauthRoutes() بتسجّل مسارات الاكتشاف (.well-known) وتسجيل العميل
 *     التلقائي (/oauth/register)، و Passport بيوفّر /oauth/authorize و /oauth/token.
 *
 *  2) توكن شخصي — أبسط لـ Claude Code وأي عميل بيسمح بهيدر:
 *     php artisan erp:mcp-token  →  Authorization: Bearer <token>
 *
 * الاتنين توكنات Passport، فنفس الجارد بيقبلهم من غير أي كود زيادة.
 */
Mcp::oauthRoutes();

Mcp::web('/mcp/erp', ErpServer::class)
    ->middleware('auth:api')
    ->name('mcp.erp');

/*
 * نفس السيرفر على stdio للتجربة المحلية من غير توكن:
 * php artisan mcp:start erp
 */
Mcp::local('erp', ErpServer::class);
