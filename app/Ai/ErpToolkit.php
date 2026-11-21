<?php

namespace App\Ai;

use App\Mcp\Servers\ErpServer;
use Laravel\Mcp\Server\Tool as McpTool;
use Prism\Prism\Tool;
use Prism\Prism\Tools\LaravelMcpTool;
use ReflectionClass;

/**
 * أدوات المحادثة = نفس أدوات الـ MCP بالظبط.
 *
 * مفيش نسخة تانية من الاستعلامات هنا. Prism بيلف أدوات
 * `Laravel\Mcp\Server\Tool` جاهزة في `LaravelMcpTool` — بياخد الاسم
 * والوصف و inputSchema من الأداة نفسها — فإحنا بنقرا قايمة أدوات
 * `ErpServer` ونلفّها وخلاص.
 *
 * النتيجة: أي أداة جديدة تتضاف للسيرفر بتبقى متاحة في المحادثة من غير
 * أي تعديل هنا، والاتنين بيفضلوا متفقين على نفس الأرقام والصياغة.
 *
 * كل الأدوات دي قراءة فقط (متعلّمة `#[IsReadOnly]`)، فالمحادثة
 * ماتقدرش تعدّل أي حاجة في النظام مهما اتسألت.
 */
class ErpToolkit
{
    /**
     * أدوات المستخدم الحالي كأدوات Prism.
     *
     * الصلاحيات: كل الأدوات قراءة فقط، والأدوار التلاتة بتقدر تشوف نفس
     * الشاشات في الواجهة — فالمحادثة بتجاوب على أي حاجة المستخدم كان
     * يقدر يوصلها بنفسه من القوايم. الحماية الحقيقية إن المسار كله
     * محمي بـ `auth` ومفيش أداة بتكتب.
     *
     * @return array<int, Tool>
     */
    public function tools(): array
    {
        return array_values(array_filter(array_map(
            fn (string $tool): ?Tool => $this->wrap($tool),
            $this->toolClasses(),
        )));
    }

    /**
     * قايمة أدوات السيرفر. الخاصية محمية، فبنقراها بالـ reflection بدل
     * ما ندوبلكها هنا وتبوظ أول ما حد يضيف أداة.
     *
     * @return array<int, class-string>
     */
    private function toolClasses(): array
    {
        $property = (new ReflectionClass(ErpServer::class))->getProperty('tools');

        /** @var array<int, class-string> $tools */
        $tools = $property->getDefaultValue() ?? [];

        return $tools;
    }

    /**
     * @param  class-string  $class
     */
    private function wrap(string $class): ?Tool
    {
        $tool = app($class);

        if (! $tool instanceof McpTool || ! $tool->eligibleForRegistration()) {
            return null;
        }

        return new LaravelMcpTool($tool);
    }
}
