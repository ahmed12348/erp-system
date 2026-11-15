import {
    Kt as e,
    Q as t,
    Qt as n,
    R as r,
    Tn as i,
    X as a,
    Y as o,
    Z as s,
    fr as c,
    it as l,
    or as u,
    st as d,
} from './dist-C0kHilBU.js';
import { _ as f, g as p, h as m } from './app-DxgtVyfV.js';
import { t as h } from './PrintSheet-DmPvwsZK.js';
var g = { class: `mt-6 grid grid-cols-2 gap-6 text-sm` },
    _ = { class: `text-base font-bold` },
    v = { class: `mt-0.5 text-neutral-600` },
    y = { key: 0, class: `mt-0.5 text-neutral-600` },
    b = { dir: `ltr` },
    x = { class: `space-y-1 text-sm` },
    S = { class: `flex justify-between border-b border-neutral-200 pb-1` },
    C = { class: `font-semibold tabular-nums` },
    w = { class: `flex justify-between border-b border-neutral-200 pb-1` },
    T = { class: `font-semibold tabular-nums` },
    E = { class: `flex justify-between pt-1` },
    D = { class: `text-base font-bold tabular-nums` },
    O = { class: `mt-7 w-full border-collapse text-[13px]` },
    k = { class: `bg-neutral-50` },
    A = {
        class: `border border-neutral-300 px-2 py-1.5 font-semibold tabular-nums`,
    },
    j = {
        class: `border border-neutral-300 px-2 py-1.5 whitespace-nowrap tabular-nums`,
    },
    M = { class: `border border-neutral-300 px-2 py-1.5` },
    N = { class: `border border-neutral-300 px-2 py-1.5 tabular-nums` },
    P = {
        class: `border border-neutral-300 px-2 py-1.5 whitespace-nowrap tabular-nums`,
    },
    F = {
        class: `border border-neutral-300 px-2 py-1.5 whitespace-nowrap tabular-nums`,
    },
    I = {
        class: `border border-neutral-300 px-2 py-1.5 font-semibold whitespace-nowrap tabular-nums`,
    },
    L = { key: 0 },
    R = { class: `bg-neutral-100 font-bold` },
    z = { class: `border border-neutral-300 px-2 py-2 tabular-nums` },
    B = { class: `border border-neutral-300 px-2 py-2 tabular-nums` },
    V = { class: `border border-neutral-300 px-2 py-2 tabular-nums` },
    H = d({
        __name: `CustomerStatement`,
        props: {
            company: {},
            customer: {},
            summary: {},
            statement: {},
            filters: {},
            printedAt: {},
        },
        setup(d) {
            let H = d,
                U = (() => {
                    let { from: e, to: t } = H.filters;
                    return e && t
                        ? `الفترة من ${m(e)} إلى ${m(t)}`
                        : e
                          ? `من ${m(e)}`
                          : t
                            ? `حتى ${m(t)}`
                            : `كل الحركة حتى تاريخه`;
                })();
            return (m, H) => (
                e(),
                a(
                    h,
                    {
                        company: d.company,
                        title: `كشف حساب تاجر`,
                        subtitle: u(U),
                        'printed-at': d.printedAt,
                    },
                    {
                        default: i(() => [
                            o(`section`, g, [
                                o(`div`, null, [
                                    (H[1] ||= o(
                                        `p`,
                                        {
                                            class: `mb-1 text-xs font-semibold text-neutral-500`,
                                        },
                                        ` التاجر `,
                                        -1,
                                    )),
                                    o(`p`, _, c(d.customer.name), 1),
                                    o(`p`, v, [
                                        l(c(d.customer.city ?? `—`) + ` `, 1),
                                        d.customer.address
                                            ? (e(),
                                              t(
                                                  r,
                                                  { key: 0 },
                                                  [
                                                      l(
                                                          ` — ` +
                                                              c(
                                                                  d.customer
                                                                      .address,
                                                              ),
                                                          1,
                                                      ),
                                                  ],
                                                  64,
                                              ))
                                            : s(``, !0),
                                    ]),
                                    d.customer.phone
                                        ? (e(),
                                          t(`p`, y, [
                                              (H[0] ||= l(` تليفون: `, -1)),
                                              o(
                                                  `span`,
                                                  b,
                                                  c(d.customer.phone),
                                                  1,
                                              ),
                                          ]))
                                        : s(``, !0),
                                ]),
                                o(`div`, x, [
                                    o(`div`, S, [
                                        (H[2] ||= o(
                                            `span`,
                                            { class: `text-neutral-500` },
                                            `إجمالي المبيعات`,
                                            -1,
                                        )),
                                        o(
                                            `span`,
                                            C,
                                            c(u(f)(d.summary.sales)),
                                            1,
                                        ),
                                    ]),
                                    o(`div`, w, [
                                        (H[3] ||= o(
                                            `span`,
                                            { class: `text-neutral-500` },
                                            `إجمالي التحصيلات`,
                                            -1,
                                        )),
                                        o(
                                            `span`,
                                            T,
                                            c(u(f)(d.summary.paid)),
                                            1,
                                        ),
                                    ]),
                                    o(`div`, E, [
                                        (H[4] ||= o(
                                            `span`,
                                            { class: `font-semibold` },
                                            `الرصيد المستحق`,
                                            -1,
                                        )),
                                        o(
                                            `span`,
                                            D,
                                            c(u(f)(d.summary.balance)),
                                            1,
                                        ),
                                    ]),
                                ]),
                            ]),
                            o(`table`, O, [
                                (H[8] ||= o(
                                    `thead`,
                                    null,
                                    [
                                        o(
                                            `tr`,
                                            { class: `bg-neutral-100 text-xs` },
                                            [
                                                o(
                                                    `th`,
                                                    {
                                                        class: `border border-neutral-300 px-2 py-2 text-start font-semibold`,
                                                    },
                                                    ` التاريخ `,
                                                ),
                                                o(
                                                    `th`,
                                                    {
                                                        class: `border border-neutral-300 px-2 py-2 text-start font-semibold`,
                                                    },
                                                    ` البيان `,
                                                ),
                                                o(
                                                    `th`,
                                                    {
                                                        class: `border border-neutral-300 px-2 py-2 text-start font-semibold`,
                                                    },
                                                    ` المرجع `,
                                                ),
                                                o(
                                                    `th`,
                                                    {
                                                        class: `border border-neutral-300 px-2 py-2 text-start font-semibold`,
                                                    },
                                                    ` مدين `,
                                                ),
                                                o(
                                                    `th`,
                                                    {
                                                        class: `border border-neutral-300 px-2 py-2 text-start font-semibold`,
                                                    },
                                                    ` دائن `,
                                                ),
                                                o(
                                                    `th`,
                                                    {
                                                        class: `border border-neutral-300 px-2 py-2 text-start font-semibold`,
                                                    },
                                                    ` الرصيد `,
                                                ),
                                            ],
                                        ),
                                    ],
                                    -1,
                                )),
                                o(`tbody`, null, [
                                    o(`tr`, k, [
                                        (H[5] ||= o(
                                            `td`,
                                            {
                                                class: `border border-neutral-300 px-2 py-1.5 text-neutral-600`,
                                                colspan: `5`,
                                            },
                                            ` رصيد ما قبل الفترة `,
                                            -1,
                                        )),
                                        o(
                                            `td`,
                                            A,
                                            c(u(f)(d.statement.opening)),
                                            1,
                                        ),
                                    ]),
                                    (e(!0),
                                    t(
                                        r,
                                        null,
                                        n(
                                            d.statement.rows,
                                            (n) => (
                                                e(),
                                                t(
                                                    `tr`,
                                                    {
                                                        key: `${n.kind}-${n.id}`,
                                                    },
                                                    [
                                                        o(
                                                            `td`,
                                                            j,
                                                            c(u(p)(n.date)),
                                                            1,
                                                        ),
                                                        o(
                                                            `td`,
                                                            M,
                                                            c(n.description),
                                                            1,
                                                        ),
                                                        o(
                                                            `td`,
                                                            N,
                                                            c(n.reference),
                                                            1,
                                                        ),
                                                        o(
                                                            `td`,
                                                            P,
                                                            c(
                                                                n.debit > 0
                                                                    ? u(f)(
                                                                          n.debit,
                                                                      )
                                                                    : `—`,
                                                            ),
                                                            1,
                                                        ),
                                                        o(
                                                            `td`,
                                                            F,
                                                            c(
                                                                n.credit > 0
                                                                    ? u(f)(
                                                                          n.credit,
                                                                      )
                                                                    : `—`,
                                                            ),
                                                            1,
                                                        ),
                                                        o(
                                                            `td`,
                                                            I,
                                                            c(u(f)(n.balance)),
                                                            1,
                                                        ),
                                                    ],
                                                )
                                            ),
                                        ),
                                        128,
                                    )),
                                    d.statement.rows.length
                                        ? s(``, !0)
                                        : (e(),
                                          t(`tr`, L, [
                                              ...(H[6] ||= [
                                                  o(
                                                      `td`,
                                                      {
                                                          colspan: `6`,
                                                          class: `border border-neutral-300 px-2 py-6 text-center text-neutral-500`,
                                                      },
                                                      ` مفيش حركة في الفترة المحددة `,
                                                      -1,
                                                  ),
                                              ]),
                                          ])),
                                ]),
                                o(`tfoot`, null, [
                                    o(`tr`, R, [
                                        (H[7] ||= o(
                                            `td`,
                                            {
                                                class: `border border-neutral-300 px-2 py-2`,
                                                colspan: `3`,
                                            },
                                            ` الإجمالي `,
                                            -1,
                                        )),
                                        o(
                                            `td`,
                                            z,
                                            c(u(f)(d.statement.totals.debit)),
                                            1,
                                        ),
                                        o(
                                            `td`,
                                            B,
                                            c(u(f)(d.statement.totals.credit)),
                                            1,
                                        ),
                                        o(
                                            `td`,
                                            V,
                                            c(u(f)(d.statement.closing)),
                                            1,
                                        ),
                                    ]),
                                ]),
                            ]),
                            (H[9] ||= o(
                                `section`,
                                {
                                    class: `mt-12 grid grid-cols-2 gap-10 text-xs text-neutral-600`,
                                },
                                [
                                    o(
                                        `div`,
                                        {
                                            class: `border-t border-neutral-400 pt-2 text-center`,
                                        },
                                        ` توقيع المحاسب `,
                                    ),
                                    o(
                                        `div`,
                                        {
                                            class: `border-t border-neutral-400 pt-2 text-center`,
                                        },
                                        ` توقيع التاجر `,
                                    ),
                                ],
                                -1,
                            )),
                        ]),
                        _: 1,
                    },
                    8,
                    [`company`, `subtitle`, `printed-at`],
                )
            );
        },
    });
export { H as default };
