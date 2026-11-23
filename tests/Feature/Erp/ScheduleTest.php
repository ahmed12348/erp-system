<?php

namespace Tests\Feature\Erp;

use Illuminate\Console\Scheduling\CallbackEvent;
use Illuminate\Console\Scheduling\Schedule;
use Tests\TestCase;

/**
 * الجدولة لازم تفضل كلها جوّه نفس العملية.
 *
 * `proc_open` و`exec` مقفولين على الاستضافة المشتركة. يعني أي مهمة
 * مجدولة بـ `Schedule::command()` — اللي بيفتح عملية PHP جديدة — مش
 * هتشتغل على السيرفر، وهتفشل في صمت من غير ما حد ياخد باله.
 *
 * الاختبار ده هو الحارس: أي مهمة جديدة تتكتب بـ `command()` هتكسر هنا
 * قبل ما توصل الإنتاج.
 */
class ScheduleTest extends TestCase
{
    public function test_no_scheduled_task_spawns_a_process(): void
    {
        $events = app(Schedule::class)->events();

        $this->assertNotEmpty($events, 'مفيش مهام مجدولة أصلاً.');

        foreach ($events as $event) {
            $this->assertInstanceOf(
                CallbackEvent::class,
                $event,
                'المهمة «'.($event->description ?? $event->command ?? '؟').'» '
                    .'بتفتح عملية جديدة — استعمل ->call() أو ->job() بدل ->command().',
            );
        }
    }

    /** ومفيش ولا مهمة بتنادي PHP من سطر الأوامر بأي طريقة تانية */
    public function test_no_scheduled_task_has_a_shell_command(): void
    {
        foreach (app(Schedule::class)->events() as $event) {
            $this->assertNull(
                $event->command,
                'المهمة «'.($event->description ?? '؟').'» ليها أمر shell.',
            );
        }
    }
}
