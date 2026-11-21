import {
    Bt as e,
    Dn as t,
    J as n,
    Kt as r,
    M as i,
    O as a,
    Q as o,
    Qt as s,
    R as c,
    Tn as l,
    X as u,
    Y as d,
    Z as f,
    at as p,
    fr as m,
    it as h,
    lr as g,
    mr as ee,
    o as te,
    or as _,
    r as ne,
    st as v,
} from './dist-C0kHilBU.js';
import { t as y } from './button-BRBVuDNP.js';
import { t as b } from './Label-BhHH3wmI.js';
import {
    a as re,
    i as ie,
    n as ae,
    o as oe,
    r as se,
    t as x,
} from './useInvoiceScan-Bdmuse2I.js';
import { t as ce } from './plus-DGsV77u5.js';
import { t as S } from './trash-DJlV-Hhi.js';
import { t as C } from './Input-D2HWfRkZ.js';
import { _ as w } from './app-C5Hu6jle.js';
import { t as le } from './PageHeader-FN2NW817.js';
import { t as T } from './InputError-_9qlcKk2.js';
import { t as ue } from './Spinner-g7aT_6xf.js';
import { t as E } from './SectionCard-sO5uXxKO.js';
var D = { class: `flex flex-1 flex-col gap-5 p-4 md:p-6` },
    O = {
        class: `rounded-lg border border-yellow-400/60 bg-yellow-50 px-4 py-3 text-sm dark:border-yellow-500/40 dark:bg-yellow-500/10`,
        'data-test': `draft-notice`,
    },
    k = { class: `mt-1 text-xs text-muted-foreground` },
    A = { class: `font-medium text-foreground` },
    j = { class: `grid gap-5 lg:grid-cols-5` },
    M = { class: `space-y-4` },
    N = { class: `space-y-2` },
    P = [`value`],
    F = { key: 0, class: `text-xs text-muted-foreground` },
    I = { class: `grid grid-cols-2 gap-3` },
    L = { class: `space-y-2` },
    R = [`value`],
    z = { class: `space-y-2` },
    B = { class: `grid grid-cols-2 gap-3` },
    V = { class: `space-y-2` },
    H = { class: `space-y-2` },
    U = { class: `grid grid-cols-3 gap-3` },
    de = { class: `space-y-2` },
    fe = { class: `space-y-2` },
    pe = { class: `space-y-2` },
    me = { class: `scrollbar-thin overflow-x-auto` },
    he = { class: `w-full text-sm` },
    ge = { class: `border-b border-border text-xs text-muted-foreground` },
    _e = { class: `w-28 px-3 py-2.5 text-start font-medium` },
    ve = { class: `divide-y divide-border` },
    ye = { class: `px-3 py-2` },
    be = [`onUpdate:modelValue`, `data-test`, `onChange`],
    xe = [`value`],
    Se = [`data-test`],
    W = { class: `px-3 py-2` },
    Ce = { class: `px-3 py-2` },
    we = { class: `px-3 py-2 font-semibold tabular-nums` },
    Te = { class: `px-3 py-2` },
    Ee = [`onClick`],
    De = { class: `space-y-2 text-sm` },
    Oe = { class: `flex justify-between` },
    ke = { class: `tabular-nums` },
    Ae = { class: `flex items-center justify-between gap-3` },
    je = { class: `w-32` },
    Me = { class: `flex justify-between` },
    Ne = { class: `tabular-nums` },
    Pe = {
        class: `flex justify-between border-t border-border pt-2 text-base font-semibold`,
    },
    Fe = { class: `tabular-nums` },
    Ie = {
        class: `sticky bottom-0 flex flex-wrap items-center justify-end gap-2 border-t border-border bg-background/90 py-3 backdrop-blur-sm`,
    },
    Le = { class: `lg:col-span-2` },
    Re = `purchase`,
    G = `h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm`,
    K = v({
        layout: (e) => ({ breadcrumbs: e.breadcrumbs }),
        __name: `Purchase`,
        props: {
            draft: {},
            suppliers: {},
            warehouses: {},
            products: {},
            breadcrumbs: {},
        },
        setup(v) {
            let K = v,
                q = ae();
            e(() => q.seed(K.draft.review, `purchase`));
            let J = te({
                supplier_id: K.draft.form.supplier_id ?? ``,
                warehouse_id: K.draft.warehouseId,
                date:
                    K.draft.form.date ?? new Date().toISOString().slice(0, 10),
                due_date: ``,
                currency: K.draft.form.currency ?? `EGP`,
                exchange_rate: K.draft.form.exchange_rate ?? 1,
                shipping_cost: K.draft.form.shipping_cost ?? 0,
                customs_cost: K.draft.form.customs_cost ?? 0,
                clearance_cost: K.draft.form.clearance_cost ?? 0,
                discount: K.draft.form.discount ?? 0,
                notes: K.draft.form.notes ?? ``,
                items: (K.draft.form.items ?? []).map((e) => ({
                    product_id: e.product_id ?? ``,
                    quantity: e.quantity ?? 1,
                    unit_price: e.unit_price ?? ``,
                })),
            });
            J.items.length === 0 &&
                J.items.push({ product_id: ``, quantity: 1, unit_price: `` });
            function Y() {
                J.items.push({ product_id: ``, quantity: 1, unit_price: `` });
            }
            function ze(e) {
                (J.items.splice(e, 1), J.items.length === 0 && Y());
            }
            function X(e) {
                return Number(e.quantity || 0) * Number(e.unit_price || 0);
            }
            let Z = n(() => J.items.reduce((e, t) => e + X(t), 0)),
                Be = n(() => Math.max(Z.value - Number(J.discount || 0), 0)),
                Ve = n(
                    () =>
                        Number(J.shipping_cost || 0) +
                        Number(J.customs_cost || 0) +
                        Number(J.clearance_cost || 0),
                ),
                Q = n(() => q.pending.value.size);
            function $() {
                return (e) => ({
                    ...e,
                    due_date: null,
                    items: e.items.filter((e) => e.product_id !== ``),
                });
            }
            function He() {
                J.transform($()).put(`/purchases/drafts/${K.draft.id}`, {
                    preserveScroll: !0,
                });
            }
            function Ue() {
                J.transform($()).post(
                    `/purchases/drafts/${K.draft.id}/confirm`,
                    { preserveScroll: !0 },
                );
            }
            function We() {
                window.confirm(`متأكد إنك عايز تلغي المسودة دي؟`) &&
                    ee.delete(`/drafts/${K.draft.id}`);
            }
            return (e, n) => (
                r(),
                o(
                    c,
                    null,
                    [
                        p(_(ne), { title: `مراجعة فاتورة شراء ممسوحة` }),
                        d(`div`, D, [
                            p(
                                le,
                                {
                                    title: `مراجعة فاتورة شراء ممسوحة`,
                                    description: `البيانات اتقرت من الصورة — راجعها وبعدين أكّد الفاتورة`,
                                },
                                {
                                    actions: l(() => [
                                        p(
                                            _(y),
                                            {
                                                type: `button`,
                                                variant: `ghost`,
                                                size: `sm`,
                                                class: `text-destructive hover:text-destructive`,
                                                'data-test': `draft-discard`,
                                                onClick: We,
                                            },
                                            {
                                                default: l(() => [
                                                    p(_(S), {
                                                        class: `size-4`,
                                                    }),
                                                    (n[13] ||= h(
                                                        ` إلغاء المسودة `,
                                                        -1,
                                                    )),
                                                ]),
                                                _: 1,
                                            },
                                        ),
                                    ]),
                                    _: 1,
                                },
                            ),
                            v.draft.suggestedType
                                ? (r(),
                                  u(
                                      se,
                                      {
                                          key: 0,
                                          'draft-id': v.draft.id,
                                          current: Re,
                                          suggested: v.draft.suggestedType,
                                          'party-name':
                                              _(q).review.value?.party
                                                  .extracted ?? null,
                                      },
                                      null,
                                      8,
                                      [`draft-id`, `suggested`, `party-name`],
                                  ))
                                : f(``, !0),
                            d(`div`, O, [
                                (n[16] ||= d(
                                    `p`,
                                    { class: `font-medium` },
                                    ` دي مسودة — المخزون وأرصدة الموردين ما اتحركوش لسه. `,
                                    -1,
                                )),
                                d(`p`, k, [
                                    Q.value > 0
                                        ? (r(),
                                          o(
                                              c,
                                              { key: 0 },
                                              [
                                                  (n[14] ||= h(` فيه `, -1)),
                                                  d(`span`, A, m(Q.value), 1),
                                                  (n[15] ||= h(
                                                      ` خانة ملوّنة بالأصفر محتاجة تراجعها. `,
                                                      -1,
                                                  )),
                                              ],
                                              64,
                                          ))
                                        : (r(),
                                          o(
                                              c,
                                              { key: 1 },
                                              [
                                                  h(
                                                      ` كل الخانات اتطابقت. راجعها بسرعة واضغط «تأكيد الفاتورة». `,
                                                  ),
                                              ],
                                              64,
                                          )),
                                ]),
                            ]),
                            d(`div`, j, [
                                d(
                                    `form`,
                                    {
                                        class: `flex flex-col gap-5 lg:col-span-3`,
                                        onSubmit: i(Ue, [`prevent`]),
                                    },
                                    [
                                        p(
                                            E,
                                            { title: `بيانات الفاتورة` },
                                            {
                                                default: l(() => [
                                                    d(`div`, M, [
                                                        d(`div`, N, [
                                                            p(
                                                                _(b),
                                                                {
                                                                    for: `supplier_id`,
                                                                },
                                                                {
                                                                    default: l(
                                                                        () => [
                                                                            ...(n[17] ||=
                                                                                [
                                                                                    h(
                                                                                        `المورد`,
                                                                                        -1,
                                                                                    ),
                                                                                ]),
                                                                        ],
                                                                    ),
                                                                    _: 1,
                                                                },
                                                            ),
                                                            t(
                                                                d(
                                                                    `select`,
                                                                    {
                                                                        id: `supplier_id`,
                                                                        'onUpdate:modelValue':
                                                                            (n[0] ||=
                                                                                (
                                                                                    e,
                                                                                ) =>
                                                                                    (_(
                                                                                        J,
                                                                                    ).supplier_id =
                                                                                        e)),
                                                                        class: g(
                                                                            [
                                                                                G,
                                                                                _(
                                                                                    q,
                                                                                ).reviewClass(
                                                                                    `supplier_id`,
                                                                                ),
                                                                            ],
                                                                        ),
                                                                        required: ``,
                                                                        'data-test': `draft-supplier`,
                                                                        onChange:
                                                                            (n[1] ||=
                                                                                (
                                                                                    e,
                                                                                ) =>
                                                                                    _(
                                                                                        q,
                                                                                    ).confirm(
                                                                                        `supplier_id`,
                                                                                    )),
                                                                    },
                                                                    [
                                                                        (n[18] ||=
                                                                            d(
                                                                                `option`,
                                                                                {
                                                                                    value: ``,
                                                                                    disabled: ``,
                                                                                },
                                                                                `اختر المورد`,
                                                                                -1,
                                                                            )),
                                                                        (r(!0),
                                                                        o(
                                                                            c,
                                                                            null,
                                                                            s(
                                                                                v.suppliers,
                                                                                (
                                                                                    e,
                                                                                ) => (
                                                                                    r(),
                                                                                    o(
                                                                                        `option`,
                                                                                        {
                                                                                            key: e.id,
                                                                                            value: e.id,
                                                                                        },
                                                                                        m(
                                                                                            e.name,
                                                                                        ) +
                                                                                            m(
                                                                                                e.country
                                                                                                    ? ` — ${e.country}`
                                                                                                    : ``,
                                                                                            ),
                                                                                        9,
                                                                                        P,
                                                                                    )
                                                                                ),
                                                                            ),
                                                                            128,
                                                                        )),
                                                                    ],
                                                                    34,
                                                                ),
                                                                [
                                                                    [
                                                                        a,
                                                                        _(J)
                                                                            .supplier_id,
                                                                    ],
                                                                ],
                                                            ),
                                                            _(q).needsReview(
                                                                `supplier_id`,
                                                            ) &&
                                                            _(q).review.value
                                                                ?.party
                                                                .extracted
                                                                ? (r(),
                                                                  o(
                                                                      `p`,
                                                                      F,
                                                                      ` مكتوب على الفاتورة: «` +
                                                                          m(
                                                                              _(
                                                                                  q,
                                                                              )
                                                                                  .review
                                                                                  .value
                                                                                  .party
                                                                                  .extracted,
                                                                          ) +
                                                                          `» `,
                                                                      1,
                                                                  ))
                                                                : f(``, !0),
                                                            p(
                                                                T,
                                                                {
                                                                    message:
                                                                        _(J)
                                                                            .errors
                                                                            .supplier_id,
                                                                },
                                                                null,
                                                                8,
                                                                [`message`],
                                                            ),
                                                        ]),
                                                        d(`div`, I, [
                                                            d(`div`, L, [
                                                                p(
                                                                    _(b),
                                                                    {
                                                                        for: `warehouse_id`,
                                                                    },
                                                                    {
                                                                        default:
                                                                            l(
                                                                                () => [
                                                                                    ...(n[19] ||=
                                                                                        [
                                                                                            h(
                                                                                                `المخزن`,
                                                                                                -1,
                                                                                            ),
                                                                                        ]),
                                                                                ],
                                                                            ),
                                                                        _: 1,
                                                                    },
                                                                ),
                                                                t(
                                                                    d(
                                                                        `select`,
                                                                        {
                                                                            id: `warehouse_id`,
                                                                            'onUpdate:modelValue':
                                                                                (n[2] ||=
                                                                                    (
                                                                                        e,
                                                                                    ) =>
                                                                                        (_(
                                                                                            J,
                                                                                        ).warehouse_id =
                                                                                            e)),
                                                                            class: g(
                                                                                G,
                                                                            ),
                                                                            required: ``,
                                                                        },
                                                                        [
                                                                            (r(
                                                                                !0,
                                                                            ),
                                                                            o(
                                                                                c,
                                                                                null,
                                                                                s(
                                                                                    v.warehouses,
                                                                                    (
                                                                                        e,
                                                                                    ) => (
                                                                                        r(),
                                                                                        o(
                                                                                            `option`,
                                                                                            {
                                                                                                key: e.id,
                                                                                                value: e.id,
                                                                                            },
                                                                                            m(
                                                                                                e.name,
                                                                                            ),
                                                                                            9,
                                                                                            R,
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
                                                                            a,
                                                                            _(J)
                                                                                .warehouse_id,
                                                                        ],
                                                                    ],
                                                                ),
                                                            ]),
                                                            d(`div`, z, [
                                                                p(
                                                                    _(b),
                                                                    {
                                                                        for: `date`,
                                                                    },
                                                                    {
                                                                        default:
                                                                            l(
                                                                                () => [
                                                                                    ...(n[20] ||=
                                                                                        [
                                                                                            h(
                                                                                                `تاريخ الفاتورة`,
                                                                                                -1,
                                                                                            ),
                                                                                        ]),
                                                                                ],
                                                                            ),
                                                                        _: 1,
                                                                    },
                                                                ),
                                                                p(
                                                                    _(C),
                                                                    {
                                                                        id: `date`,
                                                                        modelValue:
                                                                            _(J)
                                                                                .date,
                                                                        'onUpdate:modelValue':
                                                                            (n[3] ||=
                                                                                (
                                                                                    e,
                                                                                ) =>
                                                                                    (_(
                                                                                        J,
                                                                                    ).date =
                                                                                        e)),
                                                                        type: `date`,
                                                                        required: ``,
                                                                        class: g(
                                                                            _(
                                                                                q,
                                                                            ).reviewClass(
                                                                                `date`,
                                                                            ),
                                                                        ),
                                                                        onInput:
                                                                            (n[4] ||=
                                                                                (
                                                                                    e,
                                                                                ) =>
                                                                                    _(
                                                                                        q,
                                                                                    ).confirm(
                                                                                        `date`,
                                                                                    )),
                                                                    },
                                                                    null,
                                                                    8,
                                                                    [
                                                                        `modelValue`,
                                                                        `class`,
                                                                    ],
                                                                ),
                                                                p(
                                                                    T,
                                                                    {
                                                                        message:
                                                                            _(J)
                                                                                .errors
                                                                                .date,
                                                                    },
                                                                    null,
                                                                    8,
                                                                    [`message`],
                                                                ),
                                                            ]),
                                                        ]),
                                                        d(`div`, B, [
                                                            d(`div`, V, [
                                                                p(
                                                                    _(b),
                                                                    {
                                                                        for: `currency`,
                                                                    },
                                                                    {
                                                                        default:
                                                                            l(
                                                                                () => [
                                                                                    ...(n[21] ||=
                                                                                        [
                                                                                            h(
                                                                                                `العملة`,
                                                                                                -1,
                                                                                            ),
                                                                                        ]),
                                                                                ],
                                                                            ),
                                                                        _: 1,
                                                                    },
                                                                ),
                                                                t(
                                                                    d(
                                                                        `select`,
                                                                        {
                                                                            id: `currency`,
                                                                            'onUpdate:modelValue':
                                                                                (n[5] ||=
                                                                                    (
                                                                                        e,
                                                                                    ) =>
                                                                                        (_(
                                                                                            J,
                                                                                        ).currency =
                                                                                            e)),
                                                                            class: g(
                                                                                [
                                                                                    G,
                                                                                    _(
                                                                                        q,
                                                                                    ).reviewClass(
                                                                                        `currency`,
                                                                                    ),
                                                                                ],
                                                                            ),
                                                                            onChange:
                                                                                (n[6] ||=
                                                                                    (
                                                                                        e,
                                                                                    ) =>
                                                                                        _(
                                                                                            q,
                                                                                        ).confirm(
                                                                                            `currency`,
                                                                                        )),
                                                                        },
                                                                        [
                                                                            ...(n[22] ||=
                                                                                [
                                                                                    d(
                                                                                        `option`,
                                                                                        {
                                                                                            value: `USD`,
                                                                                        },
                                                                                        `دولار أمريكي`,
                                                                                        -1,
                                                                                    ),
                                                                                    d(
                                                                                        `option`,
                                                                                        {
                                                                                            value: `EUR`,
                                                                                        },
                                                                                        `يورو`,
                                                                                        -1,
                                                                                    ),
                                                                                    d(
                                                                                        `option`,
                                                                                        {
                                                                                            value: `TRY`,
                                                                                        },
                                                                                        `ليرة تركية`,
                                                                                        -1,
                                                                                    ),
                                                                                    d(
                                                                                        `option`,
                                                                                        {
                                                                                            value: `EGP`,
                                                                                        },
                                                                                        `جنيه مصري`,
                                                                                        -1,
                                                                                    ),
                                                                                ]),
                                                                        ],
                                                                        34,
                                                                    ),
                                                                    [
                                                                        [
                                                                            a,
                                                                            _(J)
                                                                                .currency,
                                                                        ],
                                                                    ],
                                                                ),
                                                            ]),
                                                            d(`div`, H, [
                                                                p(
                                                                    _(b),
                                                                    {
                                                                        for: `exchange_rate`,
                                                                    },
                                                                    {
                                                                        default:
                                                                            l(
                                                                                () => [
                                                                                    ...(n[23] ||=
                                                                                        [
                                                                                            h(
                                                                                                `سعر الصرف`,
                                                                                                -1,
                                                                                            ),
                                                                                        ]),
                                                                                ],
                                                                            ),
                                                                        _: 1,
                                                                    },
                                                                ),
                                                                p(
                                                                    _(C),
                                                                    {
                                                                        id: `exchange_rate`,
                                                                        modelValue:
                                                                            _(J)
                                                                                .exchange_rate,
                                                                        'onUpdate:modelValue':
                                                                            (n[7] ||=
                                                                                (
                                                                                    e,
                                                                                ) =>
                                                                                    (_(
                                                                                        J,
                                                                                    ).exchange_rate =
                                                                                        e)),
                                                                        type: `number`,
                                                                        step: `0.0001`,
                                                                        min: `0.0001`,
                                                                        dir: `ltr`,
                                                                        class: g(
                                                                            [
                                                                                `text-start`,
                                                                                _(
                                                                                    q,
                                                                                ).reviewClass(
                                                                                    `exchange_rate`,
                                                                                ),
                                                                            ],
                                                                        ),
                                                                        onInput:
                                                                            (n[8] ||=
                                                                                (
                                                                                    e,
                                                                                ) =>
                                                                                    _(
                                                                                        q,
                                                                                    ).confirm(
                                                                                        `exchange_rate`,
                                                                                    )),
                                                                    },
                                                                    null,
                                                                    8,
                                                                    [
                                                                        `modelValue`,
                                                                        `class`,
                                                                    ],
                                                                ),
                                                                p(
                                                                    T,
                                                                    {
                                                                        message:
                                                                            _(J)
                                                                                .errors
                                                                                .exchange_rate,
                                                                    },
                                                                    null,
                                                                    8,
                                                                    [`message`],
                                                                ),
                                                            ]),
                                                        ]),
                                                        d(`div`, U, [
                                                            d(`div`, de, [
                                                                p(
                                                                    _(b),
                                                                    {
                                                                        for: `shipping_cost`,
                                                                    },
                                                                    {
                                                                        default:
                                                                            l(
                                                                                () => [
                                                                                    ...(n[24] ||=
                                                                                        [
                                                                                            h(
                                                                                                `الشحن`,
                                                                                                -1,
                                                                                            ),
                                                                                        ]),
                                                                                ],
                                                                            ),
                                                                        _: 1,
                                                                    },
                                                                ),
                                                                p(
                                                                    _(C),
                                                                    {
                                                                        id: `shipping_cost`,
                                                                        modelValue:
                                                                            _(J)
                                                                                .shipping_cost,
                                                                        'onUpdate:modelValue':
                                                                            (n[9] ||=
                                                                                (
                                                                                    e,
                                                                                ) =>
                                                                                    (_(
                                                                                        J,
                                                                                    ).shipping_cost =
                                                                                        e)),
                                                                        type: `number`,
                                                                        step: `0.01`,
                                                                        min: `0`,
                                                                        dir: `ltr`,
                                                                        class: `text-start`,
                                                                    },
                                                                    null,
                                                                    8,
                                                                    [
                                                                        `modelValue`,
                                                                    ],
                                                                ),
                                                            ]),
                                                            d(`div`, fe, [
                                                                p(
                                                                    _(b),
                                                                    {
                                                                        for: `customs_cost`,
                                                                    },
                                                                    {
                                                                        default:
                                                                            l(
                                                                                () => [
                                                                                    ...(n[25] ||=
                                                                                        [
                                                                                            h(
                                                                                                `الجمارك`,
                                                                                                -1,
                                                                                            ),
                                                                                        ]),
                                                                                ],
                                                                            ),
                                                                        _: 1,
                                                                    },
                                                                ),
                                                                p(
                                                                    _(C),
                                                                    {
                                                                        id: `customs_cost`,
                                                                        modelValue:
                                                                            _(J)
                                                                                .customs_cost,
                                                                        'onUpdate:modelValue':
                                                                            (n[10] ||=
                                                                                (
                                                                                    e,
                                                                                ) =>
                                                                                    (_(
                                                                                        J,
                                                                                    ).customs_cost =
                                                                                        e)),
                                                                        type: `number`,
                                                                        step: `0.01`,
                                                                        min: `0`,
                                                                        dir: `ltr`,
                                                                        class: `text-start`,
                                                                    },
                                                                    null,
                                                                    8,
                                                                    [
                                                                        `modelValue`,
                                                                    ],
                                                                ),
                                                            ]),
                                                            d(`div`, pe, [
                                                                p(
                                                                    _(b),
                                                                    {
                                                                        for: `clearance_cost`,
                                                                    },
                                                                    {
                                                                        default:
                                                                            l(
                                                                                () => [
                                                                                    ...(n[26] ||=
                                                                                        [
                                                                                            h(
                                                                                                `التخليص`,
                                                                                                -1,
                                                                                            ),
                                                                                        ]),
                                                                                ],
                                                                            ),
                                                                        _: 1,
                                                                    },
                                                                ),
                                                                p(
                                                                    _(C),
                                                                    {
                                                                        id: `clearance_cost`,
                                                                        modelValue:
                                                                            _(J)
                                                                                .clearance_cost,
                                                                        'onUpdate:modelValue':
                                                                            (n[11] ||=
                                                                                (
                                                                                    e,
                                                                                ) =>
                                                                                    (_(
                                                                                        J,
                                                                                    ).clearance_cost =
                                                                                        e)),
                                                                        type: `number`,
                                                                        step: `0.01`,
                                                                        min: `0`,
                                                                        dir: `ltr`,
                                                                        class: `text-start`,
                                                                    },
                                                                    null,
                                                                    8,
                                                                    [
                                                                        `modelValue`,
                                                                    ],
                                                                ),
                                                            ]),
                                                        ]),
                                                    ]),
                                                ]),
                                                _: 1,
                                            },
                                        ),
                                        p(
                                            E,
                                            { title: `البنود`, flush: `` },
                                            {
                                                actions: l(() => [
                                                    p(
                                                        _(y),
                                                        {
                                                            type: `button`,
                                                            variant: `outline`,
                                                            size: `sm`,
                                                            onClick: Y,
                                                        },
                                                        {
                                                            default: l(() => [
                                                                p(_(ce), {
                                                                    class: `size-4`,
                                                                }),
                                                                (n[27] ||= h(
                                                                    ` إضافة سطر `,
                                                                    -1,
                                                                )),
                                                            ]),
                                                            _: 1,
                                                        },
                                                    ),
                                                ]),
                                                default: l(() => [
                                                    d(`div`, me, [
                                                        d(`table`, he, [
                                                            d(`thead`, ge, [
                                                                d(`tr`, null, [
                                                                    (n[28] ||=
                                                                        d(
                                                                            `th`,
                                                                            {
                                                                                class: `px-3 py-2.5 text-start font-medium`,
                                                                            },
                                                                            ` الصنف `,
                                                                            -1,
                                                                        )),
                                                                    (n[29] ||=
                                                                        d(
                                                                            `th`,
                                                                            {
                                                                                class: `w-20 px-3 py-2.5 text-start font-medium`,
                                                                            },
                                                                            ` الكمية `,
                                                                            -1,
                                                                        )),
                                                                    d(
                                                                        `th`,
                                                                        _e,
                                                                        ` السعر (` +
                                                                            m(
                                                                                _(
                                                                                    J,
                                                                                )
                                                                                    .currency,
                                                                            ) +
                                                                            `) `,
                                                                        1,
                                                                    ),
                                                                    (n[30] ||=
                                                                        d(
                                                                            `th`,
                                                                            {
                                                                                class: `w-28 px-3 py-2.5 text-start font-medium`,
                                                                            },
                                                                            ` الإجمالي `,
                                                                            -1,
                                                                        )),
                                                                    (n[31] ||=
                                                                        d(
                                                                            `th`,
                                                                            {
                                                                                class: `w-10 px-3 py-2.5`,
                                                                            },
                                                                            null,
                                                                            -1,
                                                                        )),
                                                                ]),
                                                            ]),
                                                            d(`tbody`, ve, [
                                                                (r(!0),
                                                                o(
                                                                    c,
                                                                    null,
                                                                    s(
                                                                        _(J)
                                                                            .items,
                                                                        (
                                                                            e,
                                                                            i,
                                                                        ) => (
                                                                            r(),
                                                                            o(
                                                                                `tr`,
                                                                                {
                                                                                    key: i,
                                                                                },
                                                                                [
                                                                                    d(
                                                                                        `td`,
                                                                                        ye,
                                                                                        [
                                                                                            t(
                                                                                                d(
                                                                                                    `select`,
                                                                                                    {
                                                                                                        'onUpdate:modelValue':
                                                                                                            (
                                                                                                                t,
                                                                                                            ) =>
                                                                                                                (e.product_id =
                                                                                                                    t),
                                                                                                        class: g(
                                                                                                            [
                                                                                                                G,
                                                                                                                _(
                                                                                                                    q,
                                                                                                                ).reviewClass(
                                                                                                                    _(
                                                                                                                        x,
                                                                                                                    )(
                                                                                                                        i,
                                                                                                                        `product_id`,
                                                                                                                    ),
                                                                                                                ),
                                                                                                            ],
                                                                                                        ),
                                                                                                        'data-test': `draft-line-product-${i}`,
                                                                                                        onChange:
                                                                                                            (
                                                                                                                e,
                                                                                                            ) =>
                                                                                                                _(
                                                                                                                    q,
                                                                                                                ).confirm(
                                                                                                                    _(
                                                                                                                        x,
                                                                                                                    )(
                                                                                                                        i,
                                                                                                                        `product_id`,
                                                                                                                    ),
                                                                                                                ),
                                                                                                    },
                                                                                                    [
                                                                                                        (n[32] ||=
                                                                                                            d(
                                                                                                                `option`,
                                                                                                                {
                                                                                                                    value: ``,
                                                                                                                    disabled: ``,
                                                                                                                },
                                                                                                                ` اختر الصنف `,
                                                                                                                -1,
                                                                                                            )),
                                                                                                        (r(
                                                                                                            !0,
                                                                                                        ),
                                                                                                        o(
                                                                                                            c,
                                                                                                            null,
                                                                                                            s(
                                                                                                                v.products,
                                                                                                                (
                                                                                                                    e,
                                                                                                                ) => (
                                                                                                                    r(),
                                                                                                                    o(
                                                                                                                        `option`,
                                                                                                                        {
                                                                                                                            key: e.id,
                                                                                                                            value: e.id,
                                                                                                                        },
                                                                                                                        m(
                                                                                                                            e.name,
                                                                                                                        ) +
                                                                                                                            ` (` +
                                                                                                                            m(
                                                                                                                                e.sku,
                                                                                                                            ) +
                                                                                                                            `) `,
                                                                                                                        9,
                                                                                                                        xe,
                                                                                                                    )
                                                                                                                ),
                                                                                                            ),
                                                                                                            128,
                                                                                                        )),
                                                                                                    ],
                                                                                                    42,
                                                                                                    be,
                                                                                                ),
                                                                                                [
                                                                                                    [
                                                                                                        a,
                                                                                                        e.product_id,
                                                                                                    ],
                                                                                                ],
                                                                                            ),
                                                                                            _(
                                                                                                q,
                                                                                            ).needsReview(
                                                                                                _(
                                                                                                    x,
                                                                                                )(
                                                                                                    i,
                                                                                                    `product_id`,
                                                                                                ),
                                                                                            ) &&
                                                                                            _(
                                                                                                q,
                                                                                            ).rawItem(
                                                                                                i,
                                                                                            )
                                                                                                ? (r(),
                                                                                                  o(
                                                                                                      `p`,
                                                                                                      {
                                                                                                          key: 0,
                                                                                                          class: `mt-1 text-xs text-muted-foreground`,
                                                                                                          'data-test': `draft-line-hint-${i}`,
                                                                                                      },
                                                                                                      ` على الفاتورة: «` +
                                                                                                          m(
                                                                                                              _(
                                                                                                                  q,
                                                                                                              ).rawItem(
                                                                                                                  i,
                                                                                                              )
                                                                                                                  .extracted
                                                                                                                  .name ??
                                                                                                                  _(
                                                                                                                      q,
                                                                                                                  ).rawItem(
                                                                                                                      i,
                                                                                                                  )
                                                                                                                      .extracted
                                                                                                                      .code,
                                                                                                          ) +
                                                                                                          `» `,
                                                                                                      9,
                                                                                                      Se,
                                                                                                  ))
                                                                                                : f(
                                                                                                      ``,
                                                                                                      !0,
                                                                                                  ),
                                                                                        ],
                                                                                    ),
                                                                                    d(
                                                                                        `td`,
                                                                                        W,
                                                                                        [
                                                                                            p(
                                                                                                _(
                                                                                                    C,
                                                                                                ),
                                                                                                {
                                                                                                    modelValue:
                                                                                                        e.quantity,
                                                                                                    'onUpdate:modelValue':
                                                                                                        (
                                                                                                            t,
                                                                                                        ) =>
                                                                                                            (e.quantity =
                                                                                                                t),
                                                                                                    type: `number`,
                                                                                                    step: `0.001`,
                                                                                                    min: `0.001`,
                                                                                                    dir: `ltr`,
                                                                                                    class: `text-start`,
                                                                                                },
                                                                                                null,
                                                                                                8,
                                                                                                [
                                                                                                    `modelValue`,
                                                                                                    `onUpdate:modelValue`,
                                                                                                ],
                                                                                            ),
                                                                                        ],
                                                                                    ),
                                                                                    d(
                                                                                        `td`,
                                                                                        Ce,
                                                                                        [
                                                                                            p(
                                                                                                _(
                                                                                                    C,
                                                                                                ),
                                                                                                {
                                                                                                    modelValue:
                                                                                                        e.unit_price,
                                                                                                    'onUpdate:modelValue':
                                                                                                        (
                                                                                                            t,
                                                                                                        ) =>
                                                                                                            (e.unit_price =
                                                                                                                t),
                                                                                                    type: `number`,
                                                                                                    step: `0.01`,
                                                                                                    min: `0`,
                                                                                                    dir: `ltr`,
                                                                                                    class: g(
                                                                                                        [
                                                                                                            `text-start`,
                                                                                                            _(
                                                                                                                q,
                                                                                                            ).reviewClass(
                                                                                                                _(
                                                                                                                    x,
                                                                                                                )(
                                                                                                                    i,
                                                                                                                    `unit_price`,
                                                                                                                ),
                                                                                                            ),
                                                                                                        ],
                                                                                                    ),
                                                                                                    onInput:
                                                                                                        (
                                                                                                            e,
                                                                                                        ) =>
                                                                                                            _(
                                                                                                                q,
                                                                                                            ).confirm(
                                                                                                                _(
                                                                                                                    x,
                                                                                                                )(
                                                                                                                    i,
                                                                                                                    `unit_price`,
                                                                                                                ),
                                                                                                            ),
                                                                                                },
                                                                                                null,
                                                                                                8,
                                                                                                [
                                                                                                    `modelValue`,
                                                                                                    `onUpdate:modelValue`,
                                                                                                    `class`,
                                                                                                    `onInput`,
                                                                                                ],
                                                                                            ),
                                                                                        ],
                                                                                    ),
                                                                                    d(
                                                                                        `td`,
                                                                                        we,
                                                                                        m(
                                                                                            _(
                                                                                                w,
                                                                                            )(
                                                                                                X(
                                                                                                    e,
                                                                                                ),
                                                                                                _(
                                                                                                    J,
                                                                                                )
                                                                                                    .currency,
                                                                                            ),
                                                                                        ),
                                                                                        1,
                                                                                    ),
                                                                                    d(
                                                                                        `td`,
                                                                                        Te,
                                                                                        [
                                                                                            d(
                                                                                                `button`,
                                                                                                {
                                                                                                    type: `button`,
                                                                                                    class: `text-muted-foreground transition-colors hover:text-destructive`,
                                                                                                    'aria-label': `حذف السطر`,
                                                                                                    onClick:
                                                                                                        (
                                                                                                            e,
                                                                                                        ) =>
                                                                                                            ze(
                                                                                                                i,
                                                                                                            ),
                                                                                                },
                                                                                                [
                                                                                                    p(
                                                                                                        _(
                                                                                                            S,
                                                                                                        ),
                                                                                                        {
                                                                                                            class: `size-4`,
                                                                                                        },
                                                                                                    ),
                                                                                                ],
                                                                                                8,
                                                                                                Ee,
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
                                                    p(
                                                        T,
                                                        {
                                                            class: `px-5 pb-3`,
                                                            message:
                                                                _(J).errors
                                                                    .items,
                                                        },
                                                        null,
                                                        8,
                                                        [`message`],
                                                    ),
                                                ]),
                                                _: 1,
                                            },
                                        ),
                                        p(
                                            E,
                                            { title: `الإجمالي` },
                                            {
                                                default: l(() => [
                                                    d(`dl`, De, [
                                                        d(`div`, Oe, [
                                                            (n[33] ||= d(
                                                                `dt`,
                                                                {
                                                                    class: `text-muted-foreground`,
                                                                },
                                                                `إجمالي البنود`,
                                                                -1,
                                                            )),
                                                            d(
                                                                `dd`,
                                                                ke,
                                                                m(
                                                                    _(w)(
                                                                        Z.value,
                                                                        _(J)
                                                                            .currency,
                                                                    ),
                                                                ),
                                                                1,
                                                            ),
                                                        ]),
                                                        d(`div`, Ae, [
                                                            (n[34] ||= d(
                                                                `dt`,
                                                                {
                                                                    class: `text-muted-foreground`,
                                                                },
                                                                `خصم`,
                                                                -1,
                                                            )),
                                                            d(`dd`, je, [
                                                                p(
                                                                    _(C),
                                                                    {
                                                                        modelValue:
                                                                            _(J)
                                                                                .discount,
                                                                        'onUpdate:modelValue':
                                                                            (n[12] ||=
                                                                                (
                                                                                    e,
                                                                                ) =>
                                                                                    (_(
                                                                                        J,
                                                                                    ).discount =
                                                                                        e)),
                                                                        type: `number`,
                                                                        step: `0.01`,
                                                                        min: `0`,
                                                                        dir: `ltr`,
                                                                        class: `h-8 text-start`,
                                                                    },
                                                                    null,
                                                                    8,
                                                                    [
                                                                        `modelValue`,
                                                                    ],
                                                                ),
                                                            ]),
                                                        ]),
                                                        d(`div`, Me, [
                                                            (n[35] ||= d(
                                                                `dt`,
                                                                {
                                                                    class: `text-muted-foreground`,
                                                                },
                                                                ` مصاريف الاستيراد `,
                                                                -1,
                                                            )),
                                                            d(
                                                                `dd`,
                                                                Ne,
                                                                m(
                                                                    _(w)(
                                                                        Ve.value,
                                                                    ),
                                                                ),
                                                                1,
                                                            ),
                                                        ]),
                                                        d(`div`, Pe, [
                                                            (n[36] ||= d(
                                                                `dt`,
                                                                null,
                                                                `الإجمالي`,
                                                                -1,
                                                            )),
                                                            d(
                                                                `dd`,
                                                                Fe,
                                                                m(
                                                                    _(w)(
                                                                        Be.value,
                                                                        _(J)
                                                                            .currency,
                                                                    ),
                                                                ),
                                                                1,
                                                            ),
                                                        ]),
                                                    ]),
                                                ]),
                                                _: 1,
                                            },
                                        ),
                                        d(`div`, Ie, [
                                            p(
                                                _(y),
                                                {
                                                    type: `button`,
                                                    variant: `outline`,
                                                    disabled: _(J).processing,
                                                    'data-test': `draft-save`,
                                                    onClick: He,
                                                },
                                                {
                                                    default: l(() => [
                                                        p(_(re), {
                                                            class: `size-4`,
                                                        }),
                                                        (n[37] ||= h(
                                                            ` حفظ كمسودة `,
                                                            -1,
                                                        )),
                                                    ]),
                                                    _: 1,
                                                },
                                                8,
                                                [`disabled`],
                                            ),
                                            p(
                                                _(y),
                                                {
                                                    type: `submit`,
                                                    disabled: _(J).processing,
                                                    'data-test': `draft-confirm`,
                                                },
                                                {
                                                    default: l(() => [
                                                        _(J).processing
                                                            ? (r(),
                                                              u(_(ue), {
                                                                  key: 0,
                                                                  class: `size-4`,
                                                              }))
                                                            : (r(),
                                                              u(_(oe), {
                                                                  key: 1,
                                                                  class: `size-4`,
                                                              })),
                                                        (n[38] ||= h(
                                                            ` تأكيد الفاتورة `,
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
                                d(`div`, Le, [
                                    p(ie, { image: v.draft.image }, null, 8, [
                                        `image`,
                                    ]),
                                ]),
                            ]),
                        ]),
                    ],
                    64,
                )
            );
        },
    });
export { K as default };
