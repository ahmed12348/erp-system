import {
    $t as e,
    Kt as t,
    Q as n,
    Y as r,
    Z as i,
    fr as a,
    lr as o,
    st as s,
} from './dist-C0kHilBU.js';
var c = { class: `rounded-xl border border-border bg-card shadow-soft` },
    l = {
        key: 0,
        class: `flex flex-wrap items-center justify-between gap-3 border-b border-border px-5 py-4`,
    },
    u = { class: `min-w-0 space-y-0.5` },
    d = { key: 0, class: `truncate text-base font-semibold` },
    f = { key: 1, class: `truncate text-xs text-muted-foreground` },
    p = { key: 0, class: `flex items-center gap-2` },
    m = s({
        __name: `SectionCard`,
        props: { title: {}, description: {}, flush: { type: Boolean } },
        setup(s) {
            return (m, h) => (
                t(),
                n(`section`, c, [
                    s.title || m.$slots.actions
                        ? (t(),
                          n(`header`, l, [
                              r(`div`, u, [
                                  s.title
                                      ? (t(), n(`h2`, d, a(s.title), 1))
                                      : i(``, !0),
                                  s.description
                                      ? (t(), n(`p`, f, a(s.description), 1))
                                      : i(``, !0),
                              ]),
                              m.$slots.actions
                                  ? (t(), n(`div`, p, [e(m.$slots, `actions`)]))
                                  : i(``, !0),
                          ]))
                        : i(``, !0),
                    r(
                        `div`,
                        { class: o(s.flush ? `` : `p-5`) },
                        [e(m.$slots, `default`)],
                        2,
                    ),
                ])
            );
        },
    });
export { m as t };
