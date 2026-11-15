import {
    J as e,
    Kt as t,
    Q as n,
    X as r,
    fr as i,
    it as a,
    lr as o,
    nn as s,
    st as c,
} from './dist-C0kHilBU.js';
import { t as l } from './createLucideIcon-C6NZ5pDL.js';
import { it as u } from './app-DxgtVyfV.js';
var d = l({
        name: `circle-dashed`,
        size: 24,
        node: [
            [`path`, { d: `M10.1 2.182a10 10 0 0 1 3.8 0`, key: `5ilxe3` }],
            [`path`, { d: `M13.9 21.818a10 10 0 0 1-3.8 0`, key: `11zvb9` }],
            [
                `path`,
                { d: `M17.609 3.721a10 10 0 0 1 2.69 2.7`, key: `1iw5b2` },
            ],
            [`path`, { d: `M2.182 13.9a10 10 0 0 1 0-3.8`, key: `c0bmvh` }],
            [
                `path`,
                { d: `M20.279 17.609a10 10 0 0 1-2.7 2.69`, key: `1ruxm7` },
            ],
            [`path`, { d: `M21.818 10.1a10 10 0 0 1 0 3.8`, key: `qkgqxc` }],
            [`path`, { d: `M3.721 6.391a10 10 0 0 1 2.7-2.69`, key: `1mcia2` }],
            [
                `path`,
                { d: `M6.391 20.279a10 10 0 0 1-2.69-2.7`, key: `1fvljs` },
            ],
        ],
    }),
    f = l({
        name: `circle-x`,
        size: 24,
        node: [
            [`circle`, { cx: `12`, cy: `12`, r: `10`, key: `1mglay` }],
            [`path`, { d: `m15 9-6 6`, key: `1uzhvr` }],
            [`path`, { d: `m9 9 6 6`, key: `z0biqf` }],
        ],
        aliases: [`x-circle`],
    }),
    p = l({
        name: `clock`,
        size: 24,
        node: [
            [`circle`, { cx: `12`, cy: `12`, r: `10`, key: `1mglay` }],
            [`path`, { d: `M12 6v6l4 2`, key: `mmk7yg` }],
        ],
    }),
    m = l({
        name: `send`,
        size: 24,
        node: [
            [
                `path`,
                {
                    d: `M14.536 21.686a.5.5 0 0 0 .937-.024l6.5-19a.496.496 0 0 0-.635-.635l-19 6.5a.5.5 0 0 0-.024.937l7.93 3.18a2 2 0 0 1 1.112 1.11z`,
                    key: `1ffxy3`,
                },
            ],
            [`path`, { d: `m21.854 2.147-10.94 10.939`, key: `12cjpa` }],
        ],
    }),
    h = c({
        __name: `EtaBadge`,
        props: { status: {} },
        setup(c) {
            let l = c,
                h = {
                    valid: {
                        label: `مقبولة`,
                        icon: u,
                        classes: `bg-status-good/10 text-status-good ring-status-good/25`,
                    },
                    sent: {
                        label: `مُرسلة`,
                        icon: m,
                        classes: `bg-chart-1/10 text-chart-1 ring-chart-1/25`,
                    },
                    pending: {
                        label: `في انتظار الإرسال`,
                        icon: p,
                        classes: `bg-status-warning/10 text-status-warning ring-status-warning/25`,
                    },
                    submitted: {
                        label: `قيد المراجعة`,
                        icon: p,
                        classes: `bg-status-warning/10 text-status-warning ring-status-warning/25`,
                    },
                    rejected: {
                        label: `مرفوضة`,
                        icon: f,
                        classes: `bg-status-critical/10 text-status-critical ring-status-critical/25`,
                    },
                },
                g = e(
                    () =>
                        h[l.status] ?? {
                            label: `لم تُرسل`,
                            icon: d,
                            classes: `bg-muted text-muted-foreground ring-border`,
                        },
                );
            return (e, c) => (
                t(),
                n(
                    `span`,
                    {
                        class: o([
                            `inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium whitespace-nowrap ring-1 ring-inset`,
                            g.value.classes,
                        ]),
                    },
                    [
                        (t(), r(s(g.value.icon), { class: `size-3.5` })),
                        a(` ` + i(g.value.label), 1),
                    ],
                    2,
                )
            );
        },
    });
export { m as n, h as t };
