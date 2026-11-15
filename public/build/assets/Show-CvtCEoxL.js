import {
    Kt as e,
    Q as t,
    Qt as n,
    R as r,
    Tn as i,
    X as a,
    Y as o,
    Z as s,
    at as c,
    fr as l,
    i as ee,
    it as u,
    or as d,
    r as f,
    st as p,
} from './dist-C0kHilBU.js';
import { t as te } from './AttachmentPanel-DHc7Scsh.js';
import { _ as m, b as ne, g as h, h as g, v as _ } from './app-DxgtVyfV.js';
import { t as v } from './PageHeader-FN2NW817.js';
import { t as y } from './SectionCard-sO5uXxKO.js';
var b = { class: `flex flex-1 flex-col gap-5 p-4 md:p-6` },
    x = { class: `grid gap-5 lg:grid-cols-3` },
    S = { class: `space-y-2.5 text-sm` },
    C = { class: `flex justify-between` },
    w = { class: `font-medium` },
    T = { key: 1 },
    E = { class: `flex justify-between` },
    D = { class: `font-medium` },
    O = { class: `flex justify-between` },
    k = { class: `font-medium` },
    A = { class: `flex justify-between` },
    j = { class: `font-medium tabular-nums` },
    M = { class: `flex justify-between` },
    N = { class: `font-medium` },
    P = { class: `space-y-2.5 text-sm` },
    F = { class: `flex justify-between` },
    I = { class: `font-medium` },
    L = { class: `flex justify-between` },
    R = { class: `font-medium tabular-nums` },
    z = { class: `flex justify-between` },
    B = { class: `font-semibold tabular-nums` },
    V = { class: `flex justify-between border-t border-border pt-2` },
    H = { class: `font-bold tabular-nums`, 'data-test': `purchase-egp` },
    re = { class: `space-y-2.5 text-sm` },
    U = { class: `flex justify-between` },
    W = { class: `tabular-nums` },
    G = { class: `flex justify-between` },
    K = { class: `tabular-nums` },
    q = { class: `flex justify-between` },
    J = { class: `tabular-nums` },
    Y = {
        class: `flex justify-between border-t border-border pt-2 font-semibold`,
    },
    X = { class: `tabular-nums` },
    ie = { class: `flex justify-between text-base font-bold` },
    ae = { class: `tabular-nums`, 'data-test': `purchase-landed` },
    oe = { class: `scrollbar-thin overflow-x-auto` },
    se = { class: `w-full text-sm` },
    ce = { class: `border-b border-border text-xs text-muted-foreground` },
    le = { class: `px-5 py-2.5 text-start font-medium` },
    ue = { class: `divide-y divide-border` },
    de = { class: `px-5 py-3 font-medium` },
    fe = {
        class: `px-5 py-3 whitespace-nowrap text-muted-foreground tabular-nums`,
    },
    pe = { class: `px-5 py-3 whitespace-nowrap tabular-nums` },
    me = { class: `px-5 py-3 whitespace-nowrap tabular-nums` },
    he = { class: `px-5 py-3 font-semibold whitespace-nowrap tabular-nums` },
    Z = { class: `px-5 py-3 whitespace-nowrap text-chart-1 tabular-nums` },
    ge = { class: `scrollbar-thin overflow-x-auto` },
    _e = { class: `w-full text-sm` },
    ve = { class: `divide-y divide-border` },
    ye = { class: `px-5 py-3 whitespace-nowrap tabular-nums` },
    be = { class: `px-5 py-3` },
    xe = { class: `px-5 py-3 text-muted-foreground tabular-nums` },
    Se = { class: `px-5 py-3 font-semibold tabular-nums` },
    Ce = { class: `text-sm text-muted-foreground` },
    Q = p({
        layout: (e) => ({ breadcrumbs: e.breadcrumbs }),
        __name: `Show`,
        props: { invoice: {}, breadcrumbs: {} },
        setup(p) {
            let Q = { cash: `نقدي`, bank: `تحويل بنكي`, check: `شيك` };
            return (we, $) => (
                e(),
                t(
                    r,
                    null,
                    [
                        c(d(f), { title: p.invoice.number }, null, 8, [
                            `title`,
                        ]),
                        o(`div`, b, [
                            c(
                                v,
                                {
                                    title: p.invoice.number,
                                    description: `فاتورة شراء — ${d(g)(p.invoice.date)}`,
                                },
                                null,
                                8,
                                [`title`, `description`],
                            ),
                            o(`div`, x, [
                                c(
                                    y,
                                    { title: `بيانات المورد` },
                                    {
                                        default: i(() => [
                                            o(`dl`, S, [
                                                o(`div`, C, [
                                                    ($[0] ||= o(
                                                        `dt`,
                                                        {
                                                            class: `text-muted-foreground`,
                                                        },
                                                        `المورد`,
                                                        -1,
                                                    )),
                                                    o(`dd`, w, [
                                                        p.invoice.supplier
                                                            ? (e(),
                                                              a(
                                                                  d(ee),
                                                                  {
                                                                      key: 0,
                                                                      href: `/suppliers/${p.invoice.supplier.id}`,
                                                                      class: `hover:text-primary hover:underline`,
                                                                  },
                                                                  {
                                                                      default:
                                                                          i(
                                                                              () => [
                                                                                  u(
                                                                                      l(
                                                                                          p
                                                                                              .invoice
                                                                                              .supplier
                                                                                              .name,
                                                                                      ),
                                                                                      1,
                                                                                  ),
                                                                              ],
                                                                          ),
                                                                      _: 1,
                                                                  },
                                                                  8,
                                                                  [`href`],
                                                              ))
                                                            : (e(),
                                                              t(
                                                                  `span`,
                                                                  T,
                                                                  `—`,
                                                              )),
                                                    ]),
                                                ]),
                                                o(`div`, E, [
                                                    ($[1] ||= o(
                                                        `dt`,
                                                        {
                                                            class: `text-muted-foreground`,
                                                        },
                                                        `بلد الاستيراد`,
                                                        -1,
                                                    )),
                                                    o(
                                                        `dd`,
                                                        D,
                                                        l(
                                                            p.invoice.supplier
                                                                ?.country ??
                                                                `—`,
                                                        ),
                                                        1,
                                                    ),
                                                ]),
                                                o(`div`, O, [
                                                    ($[2] ||= o(
                                                        `dt`,
                                                        {
                                                            class: `text-muted-foreground`,
                                                        },
                                                        `المخزن`,
                                                        -1,
                                                    )),
                                                    o(
                                                        `dd`,
                                                        k,
                                                        l(
                                                            p.invoice
                                                                .warehouse ??
                                                                `—`,
                                                        ),
                                                        1,
                                                    ),
                                                ]),
                                                o(`div`, A, [
                                                    ($[3] ||= o(
                                                        `dt`,
                                                        {
                                                            class: `text-muted-foreground`,
                                                        },
                                                        `التاريخ`,
                                                        -1,
                                                    )),
                                                    o(
                                                        `dd`,
                                                        j,
                                                        l(d(h)(p.invoice.date)),
                                                        1,
                                                    ),
                                                ]),
                                                o(`div`, M, [
                                                    ($[4] ||= o(
                                                        `dt`,
                                                        {
                                                            class: `text-muted-foreground`,
                                                        },
                                                        `أنشأها`,
                                                        -1,
                                                    )),
                                                    o(
                                                        `dd`,
                                                        N,
                                                        l(
                                                            p.invoice
                                                                .createdBy ??
                                                                `—`,
                                                        ),
                                                        1,
                                                    ),
                                                ]),
                                            ]),
                                        ]),
                                        _: 1,
                                    },
                                ),
                                c(
                                    y,
                                    { title: `العملة والتحويل` },
                                    {
                                        default: i(() => [
                                            o(`dl`, P, [
                                                o(`div`, F, [
                                                    ($[5] ||= o(
                                                        `dt`,
                                                        {
                                                            class: `text-muted-foreground`,
                                                        },
                                                        `عملة الفاتورة`,
                                                        -1,
                                                    )),
                                                    o(
                                                        `dd`,
                                                        I,
                                                        l(p.invoice.currency),
                                                        1,
                                                    ),
                                                ]),
                                                o(`div`, L, [
                                                    ($[6] ||= o(
                                                        `dt`,
                                                        {
                                                            class: `text-muted-foreground`,
                                                        },
                                                        `سعر الصرف`,
                                                        -1,
                                                    )),
                                                    o(
                                                        `dd`,
                                                        R,
                                                        l(
                                                            d(_)(
                                                                p.invoice
                                                                    .exchangeRate,
                                                                4,
                                                            ),
                                                        ),
                                                        1,
                                                    ),
                                                ]),
                                                o(`div`, z, [
                                                    ($[7] ||= o(
                                                        `dt`,
                                                        {
                                                            class: `text-muted-foreground`,
                                                        },
                                                        ` الإجمالي بعملة الفاتورة `,
                                                        -1,
                                                    )),
                                                    o(
                                                        `dd`,
                                                        B,
                                                        l(
                                                            d(m)(
                                                                p.invoice.total,
                                                                p.invoice
                                                                    .currency,
                                                                2,
                                                            ),
                                                        ),
                                                        1,
                                                    ),
                                                ]),
                                                o(`div`, V, [
                                                    ($[8] ||= o(
                                                        `dt`,
                                                        {
                                                            class: `font-medium`,
                                                        },
                                                        `الإجمالي بالجنيه`,
                                                        -1,
                                                    )),
                                                    o(
                                                        `dd`,
                                                        H,
                                                        l(
                                                            d(m)(
                                                                p.invoice
                                                                    .totalEgp,
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
                                c(
                                    y,
                                    { title: `مصاريف الاستيراد` },
                                    {
                                        default: i(() => [
                                            o(`dl`, re, [
                                                o(`div`, U, [
                                                    ($[9] ||= o(
                                                        `dt`,
                                                        {
                                                            class: `text-muted-foreground`,
                                                        },
                                                        `الشحن`,
                                                        -1,
                                                    )),
                                                    o(
                                                        `dd`,
                                                        W,
                                                        l(
                                                            d(m)(
                                                                p.invoice
                                                                    .shipping,
                                                            ),
                                                        ),
                                                        1,
                                                    ),
                                                ]),
                                                o(`div`, G, [
                                                    ($[10] ||= o(
                                                        `dt`,
                                                        {
                                                            class: `text-muted-foreground`,
                                                        },
                                                        `الجمارك`,
                                                        -1,
                                                    )),
                                                    o(
                                                        `dd`,
                                                        K,
                                                        l(
                                                            d(m)(
                                                                p.invoice
                                                                    .customs,
                                                            ),
                                                        ),
                                                        1,
                                                    ),
                                                ]),
                                                o(`div`, q, [
                                                    ($[11] ||= o(
                                                        `dt`,
                                                        {
                                                            class: `text-muted-foreground`,
                                                        },
                                                        `التخليص`,
                                                        -1,
                                                    )),
                                                    o(
                                                        `dd`,
                                                        J,
                                                        l(
                                                            d(m)(
                                                                p.invoice
                                                                    .clearance,
                                                            ),
                                                        ),
                                                        1,
                                                    ),
                                                ]),
                                                o(`div`, Y, [
                                                    ($[12] ||= o(
                                                        `dt`,
                                                        null,
                                                        `إجمالي المصاريف`,
                                                        -1,
                                                    )),
                                                    o(
                                                        `dd`,
                                                        X,
                                                        l(
                                                            d(m)(
                                                                p.invoice
                                                                    .landedTotal,
                                                            ),
                                                        ),
                                                        1,
                                                    ),
                                                ]),
                                                o(`div`, ie, [
                                                    ($[13] ||= o(
                                                        `dt`,
                                                        null,
                                                        `التكلفة الكلية الواصلة`,
                                                        -1,
                                                    )),
                                                    o(
                                                        `dd`,
                                                        ae,
                                                        l(
                                                            d(m)(
                                                                p.invoice
                                                                    .totalEgp +
                                                                    p.invoice
                                                                        .landedTotal,
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
                            ]),
                            c(
                                y,
                                {
                                    title: `بنود الفاتورة`,
                                    description: `التكلفة الواصلة بالجنيه شاملة نصيب البند من مصاريف الاستيراد`,
                                    flush: ``,
                                },
                                {
                                    default: i(() => [
                                        o(`div`, oe, [
                                            o(`table`, se, [
                                                o(`thead`, ce, [
                                                    o(`tr`, null, [
                                                        ($[14] ||= o(
                                                            `th`,
                                                            {
                                                                class: `px-5 py-2.5 text-start font-medium`,
                                                            },
                                                            ` الصنف `,
                                                            -1,
                                                        )),
                                                        ($[15] ||= o(
                                                            `th`,
                                                            {
                                                                class: `px-5 py-2.5 text-start font-medium`,
                                                            },
                                                            ` الكود `,
                                                            -1,
                                                        )),
                                                        ($[16] ||= o(
                                                            `th`,
                                                            {
                                                                class: `px-5 py-2.5 text-start font-medium`,
                                                            },
                                                            ` الكمية `,
                                                            -1,
                                                        )),
                                                        o(
                                                            `th`,
                                                            le,
                                                            ` السعر (` +
                                                                l(
                                                                    p.invoice
                                                                        .currency,
                                                                ) +
                                                                `) `,
                                                            1,
                                                        ),
                                                        ($[17] ||= o(
                                                            `th`,
                                                            {
                                                                class: `px-5 py-2.5 text-start font-medium`,
                                                            },
                                                            ` الإجمالي `,
                                                            -1,
                                                        )),
                                                        ($[18] ||= o(
                                                            `th`,
                                                            {
                                                                class: `px-5 py-2.5 text-start font-medium`,
                                                            },
                                                            ` التكلفة الواصلة للوحدة `,
                                                            -1,
                                                        )),
                                                    ]),
                                                ]),
                                                o(`tbody`, ue, [
                                                    (e(!0),
                                                    t(
                                                        r,
                                                        null,
                                                        n(
                                                            p.invoice.items,
                                                            (n) => (
                                                                e(),
                                                                t(
                                                                    `tr`,
                                                                    {
                                                                        key: n.id,
                                                                    },
                                                                    [
                                                                        o(
                                                                            `td`,
                                                                            de,
                                                                            l(
                                                                                n.name,
                                                                            ),
                                                                            1,
                                                                        ),
                                                                        o(
                                                                            `td`,
                                                                            fe,
                                                                            l(
                                                                                n.sku,
                                                                            ),
                                                                            1,
                                                                        ),
                                                                        o(
                                                                            `td`,
                                                                            pe,
                                                                            l(
                                                                                d(
                                                                                    ne,
                                                                                )(
                                                                                    n.quantity,
                                                                                    n.unit,
                                                                                ),
                                                                            ),
                                                                            1,
                                                                        ),
                                                                        o(
                                                                            `td`,
                                                                            me,
                                                                            l(
                                                                                d(
                                                                                    m,
                                                                                )(
                                                                                    n.unitPrice,
                                                                                    p
                                                                                        .invoice
                                                                                        .currency,
                                                                                    2,
                                                                                ),
                                                                            ),
                                                                            1,
                                                                        ),
                                                                        o(
                                                                            `td`,
                                                                            he,
                                                                            l(
                                                                                d(
                                                                                    m,
                                                                                )(
                                                                                    n.total,
                                                                                    p
                                                                                        .invoice
                                                                                        .currency,
                                                                                    2,
                                                                                ),
                                                                            ),
                                                                            1,
                                                                        ),
                                                                        o(
                                                                            `td`,
                                                                            Z,
                                                                            l(
                                                                                d(
                                                                                    m,
                                                                                )(
                                                                                    n.landedUnitCost,
                                                                                ),
                                                                            ),
                                                                            1,
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
                                    ]),
                                    _: 1,
                                },
                            ),
                            p.invoice.payments.length
                                ? (e(),
                                  a(
                                      y,
                                      {
                                          key: 0,
                                          title: `المدفوعات للمورد`,
                                          flush: ``,
                                      },
                                      {
                                          default: i(() => [
                                              o(`div`, ge, [
                                                  o(`table`, _e, [
                                                      ($[19] ||= o(
                                                          `thead`,
                                                          {
                                                              class: `border-b border-border text-xs text-muted-foreground`,
                                                          },
                                                          [
                                                              o(`tr`, null, [
                                                                  o(
                                                                      `th`,
                                                                      {
                                                                          class: `px-5 py-2.5 text-start font-medium`,
                                                                      },
                                                                      ` التاريخ `,
                                                                  ),
                                                                  o(
                                                                      `th`,
                                                                      {
                                                                          class: `px-5 py-2.5 text-start font-medium`,
                                                                      },
                                                                      ` الطريقة `,
                                                                  ),
                                                                  o(
                                                                      `th`,
                                                                      {
                                                                          class: `px-5 py-2.5 text-start font-medium`,
                                                                      },
                                                                      ` المرجع `,
                                                                  ),
                                                                  o(
                                                                      `th`,
                                                                      {
                                                                          class: `px-5 py-2.5 text-start font-medium`,
                                                                      },
                                                                      ` المبلغ `,
                                                                  ),
                                                              ]),
                                                          ],
                                                          -1,
                                                      )),
                                                      o(`tbody`, ve, [
                                                          (e(!0),
                                                          t(
                                                              r,
                                                              null,
                                                              n(
                                                                  p.invoice
                                                                      .payments,
                                                                  (n) => (
                                                                      e(),
                                                                      t(
                                                                          `tr`,
                                                                          {
                                                                              key: n.id,
                                                                          },
                                                                          [
                                                                              o(
                                                                                  `td`,
                                                                                  ye,
                                                                                  l(
                                                                                      d(
                                                                                          h,
                                                                                      )(
                                                                                          n.date,
                                                                                      ),
                                                                                  ),
                                                                                  1,
                                                                              ),
                                                                              o(
                                                                                  `td`,
                                                                                  be,
                                                                                  l(
                                                                                      Q[
                                                                                          n
                                                                                              .method
                                                                                      ] ??
                                                                                          n.method,
                                                                                  ),
                                                                                  1,
                                                                              ),
                                                                              o(
                                                                                  `td`,
                                                                                  xe,
                                                                                  l(
                                                                                      n.reference ??
                                                                                          `—`,
                                                                                  ),
                                                                                  1,
                                                                              ),
                                                                              o(
                                                                                  `td`,
                                                                                  Se,
                                                                                  l(
                                                                                      d(
                                                                                          m,
                                                                                      )(
                                                                                          n.amount,
                                                                                          n.currency,
                                                                                          2,
                                                                                      ),
                                                                                  ),
                                                                                  1,
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
                                          ]),
                                          _: 1,
                                      },
                                  ))
                                : s(``, !0),
                            c(
                                te,
                                {
                                    attachments: p.invoice.attachments,
                                    'upload-url': `/invoices/${p.invoice.id}/attachments`,
                                },
                                null,
                                8,
                                [`attachments`, `upload-url`],
                            ),
                            p.invoice.notes
                                ? (e(),
                                  a(
                                      y,
                                      { key: 1, title: `ملاحظات` },
                                      {
                                          default: i(() => [
                                              o(`p`, Ce, l(p.invoice.notes), 1),
                                          ]),
                                          _: 1,
                                      },
                                  ))
                                : s(``, !0),
                        ]),
                    ],
                    64,
                )
            );
        },
    });
export { Q as default };
