import {
    Dn as e,
    J as t,
    Kt as n,
    M as r,
    O as i,
    Q as a,
    Qt as o,
    R as s,
    Tn as c,
    X as ee,
    Y as l,
    Z as u,
    at as d,
    bn as f,
    fr as p,
    it as m,
    lr as h,
    o as te,
    or as g,
    r as ne,
    st as _,
} from './dist-C0kHilBU.js';
import { t as v } from './button-BRBVuDNP.js';
import { t as y } from './Label-BhHH3wmI.js';
import { t as re } from './plus-DGsV77u5.js';
import { t as ie } from './trash-DJlV-Hhi.js';
import { t as b } from './Input-8-HQLciI.js';
import { _ as x, v as ae } from './app-DxgtVyfV.js';
import { t as oe } from './PageHeader-FN2NW817.js';
import { t as S } from './InputError-_9qlcKk2.js';
import { t as se } from './Spinner-S_eIiGCn.js';
import { t as C } from './SectionCard-sO5uXxKO.js';
var ce = { class: `grid gap-5 lg:grid-cols-3` },
    le = { class: `space-y-4` },
    ue = { class: `space-y-2` },
    de = [`value`],
    fe = { class: `space-y-2` },
    w = [`value`],
    T = { class: `grid grid-cols-2 gap-3` },
    E = { class: `space-y-2` },
    D = { class: `space-y-2` },
    O = { class: `grid grid-cols-2 gap-3` },
    k = { class: `space-y-2` },
    A = { class: `space-y-2` },
    j = { class: `scrollbar-thin overflow-x-auto` },
    M = { class: `w-full text-sm` },
    N = { class: `border-b border-border text-xs text-muted-foreground` },
    P = { class: `w-32 px-3 py-2.5 text-start font-medium` },
    F = { class: `divide-y divide-border` },
    I = { class: `px-3 py-2` },
    L = [`onUpdate:modelValue`, `data-test`],
    R = [`value`],
    z = { key: 0, class: `mt-1 text-xs text-muted-foreground` },
    B = { class: `px-3 py-2` },
    V = { class: `px-3 py-2` },
    pe = { class: `px-3 py-2 font-semibold tabular-nums` },
    me = [`data-test`],
    he = { class: `px-3 py-2` },
    ge = [`aria-label`, `onClick`],
    _e = { class: `grid gap-5 lg:grid-cols-2` },
    ve = { class: `grid gap-3 sm:grid-cols-3` },
    ye = { class: `space-y-2` },
    be = { class: `space-y-2` },
    xe = { class: `space-y-2` },
    Se = { class: `mt-3 text-sm text-muted-foreground` },
    Ce = { class: `font-semibold text-foreground` },
    H = { class: `grid gap-4 sm:grid-cols-2` },
    we = { class: `space-y-2` },
    Te = { class: `space-y-2 text-sm` },
    Ee = { class: `flex justify-between` },
    De = { class: `font-medium tabular-nums` },
    Oe = { class: `flex justify-between` },
    ke = { class: `font-medium tabular-nums` },
    Ae = {
        class: `flex justify-between border-t border-border pt-2 font-bold`,
    },
    je = { class: `tabular-nums`, 'data-test': `purchase-total` },
    Me = { class: `flex justify-between text-base font-bold` },
    Ne = { class: `tabular-nums`, 'data-test': `purchase-total-egp` },
    Pe = { class: `flex justify-between text-muted-foreground` },
    Fe = { class: `tabular-nums` },
    Ie = {
        class: `flex justify-between border-t border-border pt-2 font-semibold`,
    },
    Le = { class: `tabular-nums` },
    Re = { class: `flex justify-end` },
    U = `h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm`,
    W = _({
        layout: (e) => ({ breadcrumbs: e.breadcrumbs }),
        __name: `Create`,
        props: {
            suppliers: {},
            warehouses: {},
            products: {},
            rates: {},
            today: {},
            breadcrumbs: {},
        },
        setup(_) {
            let W = _,
                G = te({
                    supplier_id: ``,
                    warehouse_id: W.warehouses[0]?.id ?? ``,
                    date: W.today,
                    due_date: ``,
                    currency: `USD`,
                    exchange_rate: W.rates.USD ?? 1,
                    shipping_cost: 0,
                    customs_cost: 0,
                    clearance_cost: 0,
                    discount: 0,
                    notes: ``,
                    items: [{ product_id: ``, quantity: 1, unit_price: `` }],
                }),
                ze = t(
                    () =>
                        W.suppliers.find(
                            (e) => e.id === Number(G.supplier_id),
                        ) ?? null,
                );
            (f(ze, (e) => {
                e &&
                    ((G.currency = e.currency),
                    (G.exchange_rate = W.rates[e.currency] ?? 1));
            }),
                f(
                    () => G.currency,
                    (e) => {
                        G.exchange_rate = W.rates[e] ?? G.exchange_rate;
                    },
                ));
            let K = t(() => new Map(W.products.map((e) => [e.id, e])));
            function q() {
                G.items.push({ product_id: ``, quantity: 1, unit_price: `` });
            }
            function Be(e) {
                (G.items.splice(e, 1), G.items.length === 0 && q());
            }
            function J(e) {
                return Number(e.quantity || 0) * Number(e.unit_price || 0);
            }
            let Y = t(() => G.items.reduce((e, t) => e + J(t), 0)),
                X = t(() => Math.max(Y.value - Number(G.discount || 0), 0)),
                Z = t(() => Number(G.exchange_rate || 0)),
                Q = t(() => X.value * Z.value),
                $ = t(
                    () =>
                        Number(G.shipping_cost || 0) +
                        Number(G.customs_cost || 0) +
                        Number(G.clearance_cost || 0),
                );
            function Ve(e) {
                let t = Number(e.quantity || 0);
                if (t <= 0) return 0;
                let n = Y.value > 0 ? $.value * (J(e) / Y.value) : 0;
                return Number(e.unit_price || 0) * Z.value + n / t;
            }
            function He() {
                G.transform((e) => ({
                    ...e,
                    due_date: e.due_date === `` ? null : e.due_date,
                    items: e.items.filter((e) => e.product_id !== ``),
                })).post(`/purchases`, { preserveScroll: !0 });
            }
            return (t, f) => (
                n(),
                a(
                    s,
                    null,
                    [
                        d(g(ne), { title: `فاتورة شراء جديدة` }),
                        l(
                            `form`,
                            {
                                class: `flex flex-1 flex-col gap-5 p-4 md:p-6`,
                                onSubmit: r(He, [`prevent`]),
                            },
                            [
                                d(oe, {
                                    title: `فاتورة شراء جديدة`,
                                    description: `فاتورة استيراد بعملة المورد — مصاريف الشحن والجمارك بتتوزّع على الأصناف بالتناسب`,
                                }),
                                l(`div`, ce, [
                                    d(
                                        C,
                                        { title: `بيانات الفاتورة` },
                                        {
                                            default: c(() => [
                                                l(`div`, le, [
                                                    l(`div`, ue, [
                                                        d(
                                                            g(y),
                                                            {
                                                                for: `supplier_id`,
                                                            },
                                                            {
                                                                default: c(
                                                                    () => [
                                                                        ...(f[11] ||=
                                                                            [
                                                                                m(
                                                                                    `المورد`,
                                                                                    -1,
                                                                                ),
                                                                            ]),
                                                                    ],
                                                                ),
                                                                _: 1,
                                                            },
                                                        ),
                                                        e(
                                                            l(
                                                                `select`,
                                                                {
                                                                    id: `supplier_id`,
                                                                    'onUpdate:modelValue':
                                                                        (f[0] ||=
                                                                            (
                                                                                e,
                                                                            ) =>
                                                                                (g(
                                                                                    G,
                                                                                ).supplier_id =
                                                                                    e)),
                                                                    class: h(U),
                                                                    required: ``,
                                                                    'data-test': `purchase-supplier`,
                                                                },
                                                                [
                                                                    (f[12] ||=
                                                                        l(
                                                                            `option`,
                                                                            {
                                                                                value: ``,
                                                                                disabled: ``,
                                                                            },
                                                                            `اختر المورد`,
                                                                            -1,
                                                                        )),
                                                                    (n(!0),
                                                                    a(
                                                                        s,
                                                                        null,
                                                                        o(
                                                                            _.suppliers,
                                                                            (
                                                                                e,
                                                                            ) => (
                                                                                n(),
                                                                                a(
                                                                                    `option`,
                                                                                    {
                                                                                        key: e.id,
                                                                                        value: e.id,
                                                                                    },
                                                                                    p(
                                                                                        e.name,
                                                                                    ) +
                                                                                        p(
                                                                                            e.country
                                                                                                ? ` — ${e.country}`
                                                                                                : ``,
                                                                                        ),
                                                                                    9,
                                                                                    de,
                                                                                )
                                                                            ),
                                                                        ),
                                                                        128,
                                                                    )),
                                                                ],
                                                                512,
                                                            ),
                                                            [
                                                                [
                                                                    i,
                                                                    g(G)
                                                                        .supplier_id,
                                                                ],
                                                            ],
                                                        ),
                                                        d(
                                                            S,
                                                            {
                                                                message:
                                                                    g(G).errors
                                                                        .supplier_id,
                                                            },
                                                            null,
                                                            8,
                                                            [`message`],
                                                        ),
                                                    ]),
                                                    l(`div`, fe, [
                                                        d(
                                                            g(y),
                                                            {
                                                                for: `warehouse_id`,
                                                            },
                                                            {
                                                                default: c(
                                                                    () => [
                                                                        ...(f[13] ||=
                                                                            [
                                                                                m(
                                                                                    `المخزن`,
                                                                                    -1,
                                                                                ),
                                                                            ]),
                                                                    ],
                                                                ),
                                                                _: 1,
                                                            },
                                                        ),
                                                        e(
                                                            l(
                                                                `select`,
                                                                {
                                                                    id: `warehouse_id`,
                                                                    'onUpdate:modelValue':
                                                                        (f[1] ||=
                                                                            (
                                                                                e,
                                                                            ) =>
                                                                                (g(
                                                                                    G,
                                                                                ).warehouse_id =
                                                                                    e)),
                                                                    class: h(U),
                                                                    required: ``,
                                                                },
                                                                [
                                                                    (n(!0),
                                                                    a(
                                                                        s,
                                                                        null,
                                                                        o(
                                                                            _.warehouses,
                                                                            (
                                                                                e,
                                                                            ) => (
                                                                                n(),
                                                                                a(
                                                                                    `option`,
                                                                                    {
                                                                                        key: e.id,
                                                                                        value: e.id,
                                                                                    },
                                                                                    p(
                                                                                        e.name,
                                                                                    ),
                                                                                    9,
                                                                                    w,
                                                                                )
                                                                            ),
                                                                        ),
                                                                        128,
                                                                    )),
                                                                ],
                                                                512,
                                                            ),
                                                            [
                                                                [
                                                                    i,
                                                                    g(G)
                                                                        .warehouse_id,
                                                                ],
                                                            ],
                                                        ),
                                                        d(
                                                            S,
                                                            {
                                                                message:
                                                                    g(G).errors
                                                                        .warehouse_id,
                                                            },
                                                            null,
                                                            8,
                                                            [`message`],
                                                        ),
                                                    ]),
                                                    l(`div`, T, [
                                                        l(`div`, E, [
                                                            d(
                                                                g(y),
                                                                {
                                                                    for: `currency`,
                                                                },
                                                                {
                                                                    default: c(
                                                                        () => [
                                                                            ...(f[14] ||=
                                                                                [
                                                                                    m(
                                                                                        `العملة`,
                                                                                        -1,
                                                                                    ),
                                                                                ]),
                                                                        ],
                                                                    ),
                                                                    _: 1,
                                                                },
                                                            ),
                                                            e(
                                                                l(
                                                                    `select`,
                                                                    {
                                                                        id: `currency`,
                                                                        'onUpdate:modelValue':
                                                                            (f[2] ||=
                                                                                (
                                                                                    e,
                                                                                ) =>
                                                                                    (g(
                                                                                        G,
                                                                                    ).currency =
                                                                                        e)),
                                                                        class: h(
                                                                            U,
                                                                        ),
                                                                        'data-test': `purchase-currency`,
                                                                    },
                                                                    [
                                                                        ...(f[15] ||=
                                                                            [
                                                                                l(
                                                                                    `option`,
                                                                                    {
                                                                                        value: `USD`,
                                                                                    },
                                                                                    `دولار أمريكي`,
                                                                                    -1,
                                                                                ),
                                                                                l(
                                                                                    `option`,
                                                                                    {
                                                                                        value: `EUR`,
                                                                                    },
                                                                                    `يورو`,
                                                                                    -1,
                                                                                ),
                                                                                l(
                                                                                    `option`,
                                                                                    {
                                                                                        value: `TRY`,
                                                                                    },
                                                                                    `ليرة تركية`,
                                                                                    -1,
                                                                                ),
                                                                                l(
                                                                                    `option`,
                                                                                    {
                                                                                        value: `EGP`,
                                                                                    },
                                                                                    `جنيه مصري`,
                                                                                    -1,
                                                                                ),
                                                                            ]),
                                                                    ],
                                                                    512,
                                                                ),
                                                                [
                                                                    [
                                                                        i,
                                                                        g(G)
                                                                            .currency,
                                                                    ],
                                                                ],
                                                            ),
                                                            d(
                                                                S,
                                                                {
                                                                    message:
                                                                        g(G)
                                                                            .errors
                                                                            .currency,
                                                                },
                                                                null,
                                                                8,
                                                                [`message`],
                                                            ),
                                                        ]),
                                                        l(`div`, D, [
                                                            d(
                                                                g(y),
                                                                {
                                                                    for: `exchange_rate`,
                                                                },
                                                                {
                                                                    default: c(
                                                                        () => [
                                                                            ...(f[16] ||=
                                                                                [
                                                                                    m(
                                                                                        `سعر الصرف`,
                                                                                        -1,
                                                                                    ),
                                                                                ]),
                                                                        ],
                                                                    ),
                                                                    _: 1,
                                                                },
                                                            ),
                                                            d(
                                                                g(b),
                                                                {
                                                                    id: `exchange_rate`,
                                                                    modelValue:
                                                                        g(G)
                                                                            .exchange_rate,
                                                                    'onUpdate:modelValue':
                                                                        (f[3] ||=
                                                                            (
                                                                                e,
                                                                            ) =>
                                                                                (g(
                                                                                    G,
                                                                                ).exchange_rate =
                                                                                    e)),
                                                                    type: `number`,
                                                                    step: `0.0001`,
                                                                    min: `0.0001`,
                                                                    dir: `ltr`,
                                                                    class: `text-start`,
                                                                    'data-test': `purchase-rate`,
                                                                },
                                                                null,
                                                                8,
                                                                [`modelValue`],
                                                            ),
                                                            d(
                                                                S,
                                                                {
                                                                    message:
                                                                        g(G)
                                                                            .errors
                                                                            .exchange_rate,
                                                                },
                                                                null,
                                                                8,
                                                                [`message`],
                                                            ),
                                                        ]),
                                                    ]),
                                                    l(`div`, O, [
                                                        l(`div`, k, [
                                                            d(
                                                                g(y),
                                                                { for: `date` },
                                                                {
                                                                    default: c(
                                                                        () => [
                                                                            ...(f[17] ||=
                                                                                [
                                                                                    m(
                                                                                        `تاريخ الفاتورة`,
                                                                                        -1,
                                                                                    ),
                                                                                ]),
                                                                        ],
                                                                    ),
                                                                    _: 1,
                                                                },
                                                            ),
                                                            d(
                                                                g(b),
                                                                {
                                                                    id: `date`,
                                                                    modelValue:
                                                                        g(G)
                                                                            .date,
                                                                    'onUpdate:modelValue':
                                                                        (f[4] ||=
                                                                            (
                                                                                e,
                                                                            ) =>
                                                                                (g(
                                                                                    G,
                                                                                ).date =
                                                                                    e)),
                                                                    type: `date`,
                                                                    required: ``,
                                                                },
                                                                null,
                                                                8,
                                                                [`modelValue`],
                                                            ),
                                                            d(
                                                                S,
                                                                {
                                                                    message:
                                                                        g(G)
                                                                            .errors
                                                                            .date,
                                                                },
                                                                null,
                                                                8,
                                                                [`message`],
                                                            ),
                                                        ]),
                                                        l(`div`, A, [
                                                            d(
                                                                g(y),
                                                                {
                                                                    for: `due_date`,
                                                                },
                                                                {
                                                                    default: c(
                                                                        () => [
                                                                            ...(f[18] ||=
                                                                                [
                                                                                    m(
                                                                                        `الاستحقاق`,
                                                                                        -1,
                                                                                    ),
                                                                                ]),
                                                                        ],
                                                                    ),
                                                                    _: 1,
                                                                },
                                                            ),
                                                            d(
                                                                g(b),
                                                                {
                                                                    id: `due_date`,
                                                                    modelValue:
                                                                        g(G)
                                                                            .due_date,
                                                                    'onUpdate:modelValue':
                                                                        (f[5] ||=
                                                                            (
                                                                                e,
                                                                            ) =>
                                                                                (g(
                                                                                    G,
                                                                                ).due_date =
                                                                                    e)),
                                                                    type: `date`,
                                                                },
                                                                null,
                                                                8,
                                                                [`modelValue`],
                                                            ),
                                                            d(
                                                                S,
                                                                {
                                                                    message:
                                                                        g(G)
                                                                            .errors
                                                                            .due_date,
                                                                },
                                                                null,
                                                                8,
                                                                [`message`],
                                                            ),
                                                        ]),
                                                    ]),
                                                ]),
                                            ]),
                                            _: 1,
                                        },
                                    ),
                                    d(
                                        C,
                                        {
                                            title: `الأصناف`,
                                            flush: ``,
                                            class: `lg:col-span-2`,
                                        },
                                        {
                                            actions: c(() => [
                                                d(
                                                    g(v),
                                                    {
                                                        type: `button`,
                                                        variant: `outline`,
                                                        size: `sm`,
                                                        onClick: q,
                                                    },
                                                    {
                                                        default: c(() => [
                                                            d(g(re), {
                                                                class: `size-4`,
                                                            }),
                                                            (f[19] ||= m(
                                                                ` إضافة سطر `,
                                                                -1,
                                                            )),
                                                        ]),
                                                        _: 1,
                                                    },
                                                ),
                                            ]),
                                            default: c(() => [
                                                l(`div`, j, [
                                                    l(`table`, M, [
                                                        l(`thead`, N, [
                                                            l(`tr`, null, [
                                                                (f[20] ||= l(
                                                                    `th`,
                                                                    {
                                                                        class: `px-3 py-2.5 text-start font-medium`,
                                                                    },
                                                                    ` الصنف `,
                                                                    -1,
                                                                )),
                                                                (f[21] ||= l(
                                                                    `th`,
                                                                    {
                                                                        class: `w-24 px-3 py-2.5 text-start font-medium`,
                                                                    },
                                                                    ` الكمية `,
                                                                    -1,
                                                                )),
                                                                l(
                                                                    `th`,
                                                                    P,
                                                                    ` السعر (` +
                                                                        p(
                                                                            g(G)
                                                                                .currency,
                                                                        ) +
                                                                        `) `,
                                                                    1,
                                                                ),
                                                                (f[22] ||= l(
                                                                    `th`,
                                                                    {
                                                                        class: `w-32 px-3 py-2.5 text-start font-medium`,
                                                                    },
                                                                    ` الإجمالي `,
                                                                    -1,
                                                                )),
                                                                (f[23] ||= l(
                                                                    `th`,
                                                                    {
                                                                        class: `w-36 px-3 py-2.5 text-start font-medium`,
                                                                    },
                                                                    ` التكلفة الواصلة `,
                                                                    -1,
                                                                )),
                                                                (f[24] ||= l(
                                                                    `th`,
                                                                    {
                                                                        class: `w-10 px-3 py-2.5`,
                                                                    },
                                                                    null,
                                                                    -1,
                                                                )),
                                                            ]),
                                                        ]),
                                                        l(`tbody`, F, [
                                                            (n(!0),
                                                            a(
                                                                s,
                                                                null,
                                                                o(
                                                                    g(G).items,
                                                                    (t, r) => (
                                                                        n(),
                                                                        a(
                                                                            `tr`,
                                                                            {
                                                                                key: r,
                                                                            },
                                                                            [
                                                                                l(
                                                                                    `td`,
                                                                                    I,
                                                                                    [
                                                                                        e(
                                                                                            l(
                                                                                                `select`,
                                                                                                {
                                                                                                    'onUpdate:modelValue':
                                                                                                        (
                                                                                                            e,
                                                                                                        ) =>
                                                                                                            (t.product_id =
                                                                                                                e),
                                                                                                    class: h(
                                                                                                        U,
                                                                                                    ),
                                                                                                    'data-test': `pline-product-${r}`,
                                                                                                },
                                                                                                [
                                                                                                    (f[25] ||=
                                                                                                        l(
                                                                                                            `option`,
                                                                                                            {
                                                                                                                value: ``,
                                                                                                                disabled: ``,
                                                                                                            },
                                                                                                            ` اختر الصنف `,
                                                                                                            -1,
                                                                                                        )),
                                                                                                    (n(
                                                                                                        !0,
                                                                                                    ),
                                                                                                    a(
                                                                                                        s,
                                                                                                        null,
                                                                                                        o(
                                                                                                            _.products,
                                                                                                            (
                                                                                                                e,
                                                                                                            ) => (
                                                                                                                n(),
                                                                                                                a(
                                                                                                                    `option`,
                                                                                                                    {
                                                                                                                        key: e.id,
                                                                                                                        value: e.id,
                                                                                                                    },
                                                                                                                    p(
                                                                                                                        e.name,
                                                                                                                    ) +
                                                                                                                        ` (` +
                                                                                                                        p(
                                                                                                                            e.sku,
                                                                                                                        ) +
                                                                                                                        `) `,
                                                                                                                    9,
                                                                                                                    R,
                                                                                                                )
                                                                                                            ),
                                                                                                        ),
                                                                                                        128,
                                                                                                    )),
                                                                                                ],
                                                                                                8,
                                                                                                L,
                                                                                            ),
                                                                                            [
                                                                                                [
                                                                                                    i,
                                                                                                    t.product_id,
                                                                                                ],
                                                                                            ],
                                                                                        ),
                                                                                        K.value.get(
                                                                                            Number(
                                                                                                t.product_id,
                                                                                            ),
                                                                                        )
                                                                                            ? (n(),
                                                                                              a(
                                                                                                  `p`,
                                                                                                  z,
                                                                                                  ` آخر تكلفة: ` +
                                                                                                      p(
                                                                                                          g(
                                                                                                              x,
                                                                                                          )(
                                                                                                              K.value.get(
                                                                                                                  Number(
                                                                                                                      t.product_id,
                                                                                                                  ),
                                                                                                              )
                                                                                                                  .lastCost,
                                                                                                          ),
                                                                                                      ),
                                                                                                  1,
                                                                                              ))
                                                                                            : u(
                                                                                                  ``,
                                                                                                  !0,
                                                                                              ),
                                                                                    ],
                                                                                ),
                                                                                l(
                                                                                    `td`,
                                                                                    B,
                                                                                    [
                                                                                        d(
                                                                                            g(
                                                                                                b,
                                                                                            ),
                                                                                            {
                                                                                                modelValue:
                                                                                                    t.quantity,
                                                                                                'onUpdate:modelValue':
                                                                                                    (
                                                                                                        e,
                                                                                                    ) =>
                                                                                                        (t.quantity =
                                                                                                            e),
                                                                                                type: `number`,
                                                                                                step: `0.001`,
                                                                                                min: `0.001`,
                                                                                                dir: `ltr`,
                                                                                                class: `text-start`,
                                                                                                'data-test': `pline-qty-${r}`,
                                                                                            },
                                                                                            null,
                                                                                            8,
                                                                                            [
                                                                                                `modelValue`,
                                                                                                `onUpdate:modelValue`,
                                                                                                `data-test`,
                                                                                            ],
                                                                                        ),
                                                                                    ],
                                                                                ),
                                                                                l(
                                                                                    `td`,
                                                                                    V,
                                                                                    [
                                                                                        d(
                                                                                            g(
                                                                                                b,
                                                                                            ),
                                                                                            {
                                                                                                modelValue:
                                                                                                    t.unit_price,
                                                                                                'onUpdate:modelValue':
                                                                                                    (
                                                                                                        e,
                                                                                                    ) =>
                                                                                                        (t.unit_price =
                                                                                                            e),
                                                                                                type: `number`,
                                                                                                step: `0.01`,
                                                                                                min: `0`,
                                                                                                dir: `ltr`,
                                                                                                class: `text-start`,
                                                                                                'data-test': `pline-price-${r}`,
                                                                                            },
                                                                                            null,
                                                                                            8,
                                                                                            [
                                                                                                `modelValue`,
                                                                                                `onUpdate:modelValue`,
                                                                                                `data-test`,
                                                                                            ],
                                                                                        ),
                                                                                    ],
                                                                                ),
                                                                                l(
                                                                                    `td`,
                                                                                    pe,
                                                                                    p(
                                                                                        g(
                                                                                            x,
                                                                                        )(
                                                                                            J(
                                                                                                t,
                                                                                            ),
                                                                                            g(
                                                                                                G,
                                                                                            )
                                                                                                .currency,
                                                                                            2,
                                                                                        ),
                                                                                    ),
                                                                                    1,
                                                                                ),
                                                                                l(
                                                                                    `td`,
                                                                                    {
                                                                                        class: `px-3 py-2 tabular-nums`,
                                                                                        'data-test': `pline-landed-${r}`,
                                                                                    },
                                                                                    p(
                                                                                        g(
                                                                                            x,
                                                                                        )(
                                                                                            Ve(
                                                                                                t,
                                                                                            ),
                                                                                        ),
                                                                                    ),
                                                                                    9,
                                                                                    me,
                                                                                ),
                                                                                l(
                                                                                    `td`,
                                                                                    he,
                                                                                    [
                                                                                        l(
                                                                                            `button`,
                                                                                            {
                                                                                                type: `button`,
                                                                                                class: `text-muted-foreground transition-colors hover:text-status-critical`,
                                                                                                'aria-label': `حذف السطر ${r + 1}`,
                                                                                                onClick:
                                                                                                    (
                                                                                                        e,
                                                                                                    ) =>
                                                                                                        Be(
                                                                                                            r,
                                                                                                        ),
                                                                                            },
                                                                                            [
                                                                                                d(
                                                                                                    g(
                                                                                                        ie,
                                                                                                    ),
                                                                                                    {
                                                                                                        class: `size-4`,
                                                                                                    },
                                                                                                ),
                                                                                            ],
                                                                                            8,
                                                                                            ge,
                                                                                        ),
                                                                                    ],
                                                                                ),
                                                                            ],
                                                                        )
                                                                    ),
                                                                ),
                                                                128,
                                                            )),
                                                        ]),
                                                    ]),
                                                ]),
                                                d(
                                                    S,
                                                    {
                                                        class: `px-5 pb-3`,
                                                        message:
                                                            g(G).errors.items,
                                                    },
                                                    null,
                                                    8,
                                                    [`message`],
                                                ),
                                            ]),
                                            _: 1,
                                        },
                                    ),
                                ]),
                                l(`div`, _e, [
                                    d(
                                        C,
                                        {
                                            title: `مصاريف الاستيراد`,
                                            description: `بالجنيه المصري — بتتوزّع على الأصناف بالتناسب مع قيمة كل بند`,
                                        },
                                        {
                                            default: c(() => [
                                                l(`div`, ve, [
                                                    l(`div`, ye, [
                                                        d(
                                                            g(y),
                                                            {
                                                                for: `shipping_cost`,
                                                            },
                                                            {
                                                                default: c(
                                                                    () => [
                                                                        ...(f[26] ||=
                                                                            [
                                                                                m(
                                                                                    `الشحن`,
                                                                                    -1,
                                                                                ),
                                                                            ]),
                                                                    ],
                                                                ),
                                                                _: 1,
                                                            },
                                                        ),
                                                        d(
                                                            g(b),
                                                            {
                                                                id: `shipping_cost`,
                                                                modelValue:
                                                                    g(G)
                                                                        .shipping_cost,
                                                                'onUpdate:modelValue':
                                                                    (f[6] ||= (
                                                                        e,
                                                                    ) =>
                                                                        (g(
                                                                            G,
                                                                        ).shipping_cost =
                                                                            e)),
                                                                type: `number`,
                                                                step: `0.01`,
                                                                min: `0`,
                                                                dir: `ltr`,
                                                                class: `text-start`,
                                                                'data-test': `purchase-shipping`,
                                                            },
                                                            null,
                                                            8,
                                                            [`modelValue`],
                                                        ),
                                                        d(
                                                            S,
                                                            {
                                                                message:
                                                                    g(G).errors
                                                                        .shipping_cost,
                                                            },
                                                            null,
                                                            8,
                                                            [`message`],
                                                        ),
                                                    ]),
                                                    l(`div`, be, [
                                                        d(
                                                            g(y),
                                                            {
                                                                for: `customs_cost`,
                                                            },
                                                            {
                                                                default: c(
                                                                    () => [
                                                                        ...(f[27] ||=
                                                                            [
                                                                                m(
                                                                                    `الجمارك`,
                                                                                    -1,
                                                                                ),
                                                                            ]),
                                                                    ],
                                                                ),
                                                                _: 1,
                                                            },
                                                        ),
                                                        d(
                                                            g(b),
                                                            {
                                                                id: `customs_cost`,
                                                                modelValue:
                                                                    g(G)
                                                                        .customs_cost,
                                                                'onUpdate:modelValue':
                                                                    (f[7] ||= (
                                                                        e,
                                                                    ) =>
                                                                        (g(
                                                                            G,
                                                                        ).customs_cost =
                                                                            e)),
                                                                type: `number`,
                                                                step: `0.01`,
                                                                min: `0`,
                                                                dir: `ltr`,
                                                                class: `text-start`,
                                                                'data-test': `purchase-customs`,
                                                            },
                                                            null,
                                                            8,
                                                            [`modelValue`],
                                                        ),
                                                        d(
                                                            S,
                                                            {
                                                                message:
                                                                    g(G).errors
                                                                        .customs_cost,
                                                            },
                                                            null,
                                                            8,
                                                            [`message`],
                                                        ),
                                                    ]),
                                                    l(`div`, xe, [
                                                        d(
                                                            g(y),
                                                            {
                                                                for: `clearance_cost`,
                                                            },
                                                            {
                                                                default: c(
                                                                    () => [
                                                                        ...(f[28] ||=
                                                                            [
                                                                                m(
                                                                                    `التخليص`,
                                                                                    -1,
                                                                                ),
                                                                            ]),
                                                                    ],
                                                                ),
                                                                _: 1,
                                                            },
                                                        ),
                                                        d(
                                                            g(b),
                                                            {
                                                                id: `clearance_cost`,
                                                                modelValue:
                                                                    g(G)
                                                                        .clearance_cost,
                                                                'onUpdate:modelValue':
                                                                    (f[8] ||= (
                                                                        e,
                                                                    ) =>
                                                                        (g(
                                                                            G,
                                                                        ).clearance_cost =
                                                                            e)),
                                                                type: `number`,
                                                                step: `0.01`,
                                                                min: `0`,
                                                                dir: `ltr`,
                                                                class: `text-start`,
                                                                'data-test': `purchase-clearance`,
                                                            },
                                                            null,
                                                            8,
                                                            [`modelValue`],
                                                        ),
                                                        d(
                                                            S,
                                                            {
                                                                message:
                                                                    g(G).errors
                                                                        .clearance_cost,
                                                            },
                                                            null,
                                                            8,
                                                            [`message`],
                                                        ),
                                                    ]),
                                                ]),
                                                l(`p`, Se, [
                                                    (f[29] ||= m(
                                                        ` إجمالي المصاريف `,
                                                        -1,
                                                    )),
                                                    l(
                                                        `span`,
                                                        Ce,
                                                        p(g(x)($.value)),
                                                        1,
                                                    ),
                                                ]),
                                            ]),
                                            _: 1,
                                        },
                                    ),
                                    d(
                                        C,
                                        { title: `الإجمالي` },
                                        {
                                            default: c(() => [
                                                l(`div`, H, [
                                                    l(`div`, we, [
                                                        d(
                                                            g(y),
                                                            { for: `discount` },
                                                            {
                                                                default: c(
                                                                    () => [
                                                                        m(
                                                                            `خصم (` +
                                                                                p(
                                                                                    g(
                                                                                        G,
                                                                                    )
                                                                                        .currency,
                                                                                ) +
                                                                                `)`,
                                                                            1,
                                                                        ),
                                                                    ],
                                                                ),
                                                                _: 1,
                                                            },
                                                        ),
                                                        d(
                                                            g(b),
                                                            {
                                                                id: `discount`,
                                                                modelValue:
                                                                    g(G)
                                                                        .discount,
                                                                'onUpdate:modelValue':
                                                                    (f[9] ||= (
                                                                        e,
                                                                    ) =>
                                                                        (g(
                                                                            G,
                                                                        ).discount =
                                                                            e)),
                                                                type: `number`,
                                                                step: `0.01`,
                                                                min: `0`,
                                                                dir: `ltr`,
                                                                class: `text-start`,
                                                            },
                                                            null,
                                                            8,
                                                            [`modelValue`],
                                                        ),
                                                        d(
                                                            S,
                                                            {
                                                                message:
                                                                    g(G).errors
                                                                        .discount,
                                                            },
                                                            null,
                                                            8,
                                                            [`message`],
                                                        ),
                                                        d(
                                                            g(y),
                                                            {
                                                                for: `notes`,
                                                                class: `mt-3 block`,
                                                            },
                                                            {
                                                                default: c(
                                                                    () => [
                                                                        ...(f[30] ||=
                                                                            [
                                                                                m(
                                                                                    `ملاحظات`,
                                                                                    -1,
                                                                                ),
                                                                            ]),
                                                                    ],
                                                                ),
                                                                _: 1,
                                                            },
                                                        ),
                                                        d(
                                                            g(b),
                                                            {
                                                                id: `notes`,
                                                                modelValue:
                                                                    g(G).notes,
                                                                'onUpdate:modelValue':
                                                                    (f[10] ||= (
                                                                        e,
                                                                    ) =>
                                                                        (g(
                                                                            G,
                                                                        ).notes =
                                                                            e)),
                                                            },
                                                            null,
                                                            8,
                                                            [`modelValue`],
                                                        ),
                                                    ]),
                                                    l(`dl`, Te, [
                                                        l(`div`, Ee, [
                                                            (f[31] ||= l(
                                                                `dt`,
                                                                {
                                                                    class: `text-muted-foreground`,
                                                                },
                                                                `إجمالي البنود`,
                                                                -1,
                                                            )),
                                                            l(
                                                                `dd`,
                                                                De,
                                                                p(
                                                                    g(x)(
                                                                        Y.value,
                                                                        g(G)
                                                                            .currency,
                                                                        2,
                                                                    ),
                                                                ),
                                                                1,
                                                            ),
                                                        ]),
                                                        l(`div`, Oe, [
                                                            (f[32] ||= l(
                                                                `dt`,
                                                                {
                                                                    class: `text-muted-foreground`,
                                                                },
                                                                `سعر الصرف`,
                                                                -1,
                                                            )),
                                                            l(
                                                                `dd`,
                                                                ke,
                                                                p(
                                                                    g(ae)(
                                                                        Z.value,
                                                                        4,
                                                                    ),
                                                                ),
                                                                1,
                                                            ),
                                                        ]),
                                                        l(`div`, Ae, [
                                                            l(
                                                                `dt`,
                                                                null,
                                                                `الإجمالي (` +
                                                                    p(
                                                                        g(G)
                                                                            .currency,
                                                                    ) +
                                                                    `)`,
                                                                1,
                                                            ),
                                                            l(
                                                                `dd`,
                                                                je,
                                                                p(
                                                                    g(x)(
                                                                        X.value,
                                                                        g(G)
                                                                            .currency,
                                                                        2,
                                                                    ),
                                                                ),
                                                                1,
                                                            ),
                                                        ]),
                                                        l(`div`, Me, [
                                                            (f[33] ||= l(
                                                                `dt`,
                                                                null,
                                                                `بالجنيه المصري`,
                                                                -1,
                                                            )),
                                                            l(
                                                                `dd`,
                                                                Ne,
                                                                p(
                                                                    g(x)(
                                                                        Q.value,
                                                                    ),
                                                                ),
                                                                1,
                                                            ),
                                                        ]),
                                                        l(`div`, Pe, [
                                                            (f[34] ||= l(
                                                                `dt`,
                                                                null,
                                                                `+ مصاريف الاستيراد`,
                                                                -1,
                                                            )),
                                                            l(
                                                                `dd`,
                                                                Fe,
                                                                p(
                                                                    g(x)(
                                                                        $.value,
                                                                    ),
                                                                ),
                                                                1,
                                                            ),
                                                        ]),
                                                        l(`div`, Ie, [
                                                            (f[35] ||= l(
                                                                `dt`,
                                                                null,
                                                                `التكلفة الكلية الواصلة`,
                                                                -1,
                                                            )),
                                                            l(
                                                                `dd`,
                                                                Le,
                                                                p(
                                                                    g(x)(
                                                                        Q.value +
                                                                            $.value,
                                                                    ),
                                                                ),
                                                                1,
                                                            ),
                                                        ]),
                                                    ]),
                                                ]),
                                            ]),
                                            _: 1,
                                        },
                                    ),
                                ]),
                                l(`div`, Re, [
                                    d(
                                        g(v),
                                        {
                                            type: `submit`,
                                            disabled: g(G).processing,
                                            'data-test': `save-purchase`,
                                        },
                                        {
                                            default: c(() => [
                                                g(G).processing
                                                    ? (n(),
                                                      ee(g(se), { key: 0 }))
                                                    : u(``, !0),
                                                (f[36] ||= m(
                                                    ` حفظ الفاتورة `,
                                                    -1,
                                                )),
                                            ]),
                                            _: 1,
                                        },
                                        8,
                                        [`disabled`],
                                    ),
                                ]),
                            ],
                            32,
                        ),
                    ],
                    64,
                )
            );
        },
    });
export { W as default };
