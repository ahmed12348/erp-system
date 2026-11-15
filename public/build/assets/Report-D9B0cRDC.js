import {
    Kt as e,
    Tn as t,
    X as n,
    Y as r,
    at as i,
    or as a,
    st as o,
} from './dist-C0kHilBU.js';
import { h as s } from './app-DxgtVyfV.js';
import { t as c } from './PrintSheet-DmPvwsZK.js';
import { t as l } from './ReportTable-zNTohy48.js';
var u = { class: `mt-7` },
    d = o({
        __name: `Report`,
        props: {
            company: {},
            report: {},
            filters: {},
            data: {},
            printedAt: {},
        },
        setup(o) {
            let d = o,
                f = {
                    period: `تقرير المبيعات على الفترة`,
                    product: `تقرير المبيعات حسب الصنف`,
                    customer: `تقرير المبيعات حسب التاجر`,
                    inventory: `تقرير تقييم المخزون`,
                },
                p =
                    d.report === `inventory`
                        ? `الأرصدة كما هي بتاريخ الطباعة`
                        : `الفترة من ${s(d.filters.from)} إلى ${s(d.filters.to)}`;
            return (s, d) => (
                e(),
                n(
                    c,
                    {
                        company: o.company,
                        title: f[o.report] ?? `تقرير`,
                        subtitle: a(p),
                        'printed-at': o.printedAt,
                    },
                    {
                        default: t(() => [
                            r(`div`, u, [
                                i(
                                    l,
                                    {
                                        report: o.report,
                                        data: o.data,
                                        print: ``,
                                    },
                                    null,
                                    8,
                                    [`report`, `data`],
                                ),
                            ]),
                        ]),
                        _: 1,
                    },
                    8,
                    [`company`, `title`, `subtitle`, `printed-at`],
                )
            );
        },
    });
export { d as default };
