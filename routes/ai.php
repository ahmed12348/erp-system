<?php

use App\Mcp\Servers\ErpServer;
use Laravel\Mcp\Facades\Mcp;

/*
 * سيرفر الـ MCP — أدوات قراءة فقط على بيانات النظام.
 *
 * محمي بـ Sanctum: العميل لازم يبعت Authorization: Bearer <token>،
 * والتوكن بيتعمل من `php artisan erp:mcp-token`.
 */
Mcp::web('/mcp/erp', ErpServer::class)
    ->middleware('auth:sanctum')
    ->name('mcp.erp');

/*
 * نفس السيرفر على stdio للتجربة المحلية من غير توكن:
 * php artisan mcp:start erp
 */
Mcp::local('erp', ErpServer::class);
