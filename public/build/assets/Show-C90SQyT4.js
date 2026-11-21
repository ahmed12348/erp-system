import {
    J as e,
    Kt as t,
    Q as n,
    Qt as r,
    R as i,
    Tn as a,
    X as o,
    Xn as s,
    Y as c,
    Z as l,
    at as u,
    fr as d,
    i as f,
    it as p,
    lr as m,
    mr as ee,
    or as h,
    r as g,
    st as _,
} from './dist-C0kHilBU.js';
import { t as v } from './button-BRBVuDNP.js';
import { t as te } from './EtaBadge-7wfaEWIh.js';
import { t as ne } from './AttachmentPanel-DGCqic29.js';
import { t as y } from './printer-CzjJqg98.js';
import { _ as b, b as x, g as S, h as C, ot as w } from './app-C5Hu6jle.js';
import { t as T } from './PageHeader-FN2NW817.js';
import { t as E } from './Spinner-g7aT_6xf.js';
import { t as D } from './SectionCard-sO5uXxKO.js';
var O = { class: `flex flex-1 flex-col gap-5 p-4 md:p-6` },
    k = [`href`],
    A = { class: `grid gap-5 lg:grid-cols-3` },
    j = { class: `space-y-2.5 text-sm` },
    M = { class: `flex justify-between` },
    N = { class: `font-medium` },
    P = { key: 1 },
    F = { class: `flex justify-between` },
    I = { class: `font-medium` },
    L = { class: `flex justify-between` },
    R = { class: `font-medium tabular-nums` },
    z = { class: `flex justify-between` },
    B = { class: `font-medium tabular-nums` },
    V = { class: `flex justify-between` },
    H = { class: `font-medium` },
    U = { class: `space-y-3 text-sm` },
    W = { key: 0, class: `text-xs break-all text-muted-foreground` },
    G = { dir: `ltr`, class: `font-mono` },
    K = { key: 1, class: `text-xs text-muted-foreground` },
    re = { class: `space-y-2.5 text-sm` },
    q = { class: `flex justify-between` },
    J = { class: `font-semibold tabular-nums` },
    Y = { class: `flex justify-between` },
    X = { class: `font-semibold text-status-good tabular-nums` },
    ie = { class: `flex justify-between border-t border-border pt-2` },
    ae = { class: `scrollbar-thin overflow-x-auto` },
    oe = { class: `w-full text-sm` },
    se = { class: `divide-y divide-border` },
    ce = { class: `px-5 py-3 font-medium` },
    le = {
        class: `px-5 py-3 whitespace-nowrap text-muted-foreground tabular-nums`,
    },
    ue = { class: `px-5 py-3 whitespace-nowrap tabular-nums` },
    de = { class: `px-5 py-3 whitespace-nowrap tabular-nums` },
    fe = {
        class: `px-5 py-3 whitespace-nowrap text-muted-foreground tabular-nums`,
    },
    pe = { class: `px-5 py-3 font-semibold whitespace-nowrap tabular-nums` },
    me = { class: `border-t-2 border-border bg-muted/40 text-sm` },
    he = { class: `px-5 py-2 tabular-nums` },
    ge = { key: 0 },
    _e = { class: `px-5 py-2 tabular-nums` },
    ve = { class: `px-5 py-2 tabular-nums` },
    ye = { class: `text-base font-bold` },
    be = { class: `px-5 py-3 tabular-nums`, 'data-test': `invoice-total` },
    xe = { class: `scrollbar-thin overflow-x-auto` },
    Se = { class: `w-full text-sm` },
    Z = { class: `divide-y divide-border` },
    Ce = { class: `px-5 py-3 whitespace-nowrap tabular-nums` },
    we = { class: `px-5 py-3` },
    Te = { class: `px-5 py-3 text-muted-foreground tabular-nums` },
    Ee = { class: `px-5 py-3 font-semibold text-status-good tabular-nums` },
    De = { class: `text-sm text-muted-foreground` },
    Q = _({
        layout: (e) => ({ breadcrumbs: e.breadcrumbs }),
        __name: `Show`,
        props: { invoice: {}, breadcrumbs: {} },
        setup(_) {
            let Q = _,
                $ = s(!1),
                Oe = e(
                    () =>
                        Q.invoice.type === `sale` &&
                        [null, `pending`, `rejected`].includes(
                            Q.invoice.etaStatus,
                        ),
                );
            function ke() {
                (($.value = !0),
                    ee.post(
                        `/sales/${Q.invoice.id}/eta`,
                        {},
                        {
                            preserveScroll: !0,
                            onFinish: () => {
                                $.value = !1;
                            },
                        },
                    ));
            }
            let Ae = { cash: `نقدي`, bank: `تحويل بنكي`, check: `شيك` };
            return (e, s) => (
                t(),
                n(
                    i,
                    null,
                    [
                        u(h(g), { title: _.invoice.number }, null, 8, [
                            `title`,
                        ]),
                        c(`div`, O, [
                            u(
                                T,
                                {
                                    title: _.invoice.number,
                                    description: `${_.invoice.type === `sale` ? `فاتورة بيع` : `مرتجع مبيعات`} — ${h(C)(_.invoice.date)}`,
                                },
                                {
                                    actions: a(() => [
                                        u(
                                            h(v),
                                            {
                                                variant: `outline`,
                                                'as-child': ``,
                                            },
                                            {
                                                default: a(() => [
                                                    c(
                                                        `a`,
                                                        {
                                                            href: `/sales/${_.invoice.id}/print`,
                                                            target: `_blank`,
                                                        },
                                                        [
                                                            u(h(y), {
                                                                class: `size-4`,
                                                            }),
                                                            (s[0] ||= p(
                                                                ` طباعة `,
                                                                -1,
                                                            )),
                                                        ],
                                                        8,
                                                        k,
                                                    ),
                                                ]),
                                                _: 1,
                                            },
                                        ),
                                        Oe.value
                                            ? (t(),
                                              o(
                                                  h(v),
                                                  {
                                                      key: 0,
                                                      disabled: $.value,
                                                      'data-test': `send-eta`,
                                                      onClick: ke,
                                                  },
                                                  {
                                                      default: a(() => [
                                                          $.value
                                                              ? (t(),
                                                                o(h(E), {
                                                                    key: 0,
                                                                }))
                                                              : (t(),
                                                                o(h(w), {
                                                                    key: 1,
                                                                    class: `size-4`,
                                                                })),
                                                          (s[1] ||= p(
                                                              ` إرسال للفاتورة الإلكترونية `,
                                                              -1,
                                                          )),
                                                      ]),
                                                      _: 1,
                                                  },
                                                  8,
                                                  [`disabled`],
                                              ))
                                            : l(``, !0),
                                    ]),
                                    _: 1,
                                },
                                8,
                                [`title`, `description`],
                            ),
                            c(`div`, A, [
                                u(
                                    D,
                                    { title: `بيانات الفاتورة` },
                                    {
                                        default: a(() => [
                                            c(`dl`, j, [
                                                c(`div`, M, [
                                                    (s[2] ||= c(
                                                        `dt`,
                                                        {
                                                            class: `text-muted-foreground`,
                                                        },
                                                        `التاجر`,
                                                        -1,
                                                    )),
                                                    c(`dd`, N, [
                                                        _.invoice.customer
                                                            ? (t(),
                                                              o(
                                                                  h(f),
                                                                  {
                                                                      key: 0,
                                                                      href: `/customers/${_.invoice.customer.id}`,
                                                                      class: `hover:text-primary hover:underline`,
                                                                  },
                                                                  {
                                                                      default:
                                                                          a(
                                                                              () => [
                                                                                  p(
                                                                                      d(
                                                                                          _
                                                                                              .invoice
                                                                                              .customer
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
                                                            : (t(),
                                                              n(
                                                                  `span`,
                                                                  P,
                                                                  `—`,
                                                              )),
                                                    ]),
                                                ]),
                                                c(`div`, F, [
                                                    (s[3] ||= c(
                                                        `dt`,
                                                        {
                                                            class: `text-muted-foreground`,
                                                        },
                                                        `المخزن`,
                                                        -1,
                                                    )),
                                                    c(
                                                        `dd`,
                                                        I,
                                                        d(
                                                            _.invoice
                                                                .warehouse ??
                                                                `—`,
                                                        ),
                                                        1,
                                                    ),
                                                ]),
                                                c(`div`, L, [
                                                    (s[4] ||= c(
                                                        `dt`,
                                                        {
                                                            class: `text-muted-foreground`,
                                                        },
                                                        `التاريخ`,
                                                        -1,
                                                    )),
                                                    c(
                                                        `dd`,
                                                        R,
                                                        d(h(S)(_.invoice.date)),
                                                        1,
                                                    ),
                                                ]),
                                                c(`div`, z, [
                                                    (s[5] ||= c(
                                                        `dt`,
                                                        {
                                                            class: `text-muted-foreground`,
                                                        },
                                                        `الاستحقاق`,
                                                        -1,
                                                    )),
                                                    c(
                                                        `dd`,
                                                        B,
                                                        d(
                                                            _.invoice.dueDate
                                                                ? h(S)(
                                                                      _.invoice
                                                                          .dueDate,
                                                                  )
                                                                : `—`,
                                                        ),
                                                        1,
                                                    ),
                                                ]),
                                                c(`div`, V, [
                                                    (s[6] ||= c(
                                                        `dt`,
                                                        {
                                                            class: `text-muted-foreground`,
                                                        },
                                                        `أنشأها`,
                                                        -1,
                                                    )),
                                                    c(
                                                        `dd`,
                                                        H,
                                                        d(
                                                            _.invoice
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
                                u(
                                    D,
                                    { title: `الفاتورة الإلكترونية` },
                                    {
                                        default: a(() => [
                                            c(`div`, U, [
                                                u(
                                                    te,
                                                    {
                                                        status: _.invoice
                                                            .etaStatus,
                                                    },
                                                    null,
                                                    8,
                                                    [`status`],
                                                ),
                                                _.invoice.etaUuid
                                                    ? (t(),
                                                      n(`p`, W, [
                                                          (s[7] ||= p(
                                                              ` الرقم المرجعي: `,
                                                              -1,
                                                          )),
                                                          c(
                                                              `span`,
                                                              G,
                                                              d(
                                                                  _.invoice
                                                                      .etaUuid,
                                                              ),
                                                              1,
                                                          ),
                                                      ]))
                                                    : (t(),
                                                      n(
                                                          `p`,
                                                          K,
                                                          ` الفاتورة لسه مااتبعتتش لمنظومة مصلحة الضرائب. `,
                                                      )),
                                                (s[8] ||= c(
                                                    `p`,
                                                    {
                                                        class: `rounded-md bg-muted px-3 py-2 text-xs text-muted-foreground`,
                                                    },
                                                    ` ملاحظة: الربط بالمنظومة محاكاة للعرض التوضيحي — مفيش إرسال فعلي. `,
                                                    -1,
                                                )),
                                            ]),
                                        ]),
                                        _: 1,
                                    },
                                ),
                                u(
                                    D,
                                    { title: `السداد` },
                                    {
                                        default: a(() => [
                                            c(`dl`, re, [
                                                c(`div`, q, [
                                                    (s[9] ||= c(
                                                        `dt`,
                                                        {
                                                            class: `text-muted-foreground`,
                                                        },
                                                        `إجمالي الفاتورة`,
                                                        -1,
                                                    )),
                                                    c(
                                                        `dd`,
                                                        J,
                                                        d(
                                                            h(b)(
                                                                _.invoice.total,
                                                            ),
                                                        ),
                                                        1,
                                                    ),
                                                ]),
                                                c(`div`, Y, [
                                                    (s[10] ||= c(
                                                        `dt`,
                                                        {
                                                            class: `text-muted-foreground`,
                                                        },
                                                        `المدفوع`,
                                                        -1,
                                                    )),
                                                    c(
                                                        `dd`,
                                                        X,
                                                        d(h(b)(_.invoice.paid)),
                                                        1,
                                                    ),
                                                ]),
                                                c(`div`, ie, [
                                                    (s[11] ||= c(
                                                        `dt`,
                                                        {
                                                            class: `font-medium`,
                                                        },
                                                        `المتبقي`,
                                                        -1,
                                                    )),
                                                    c(
                                                        `dd`,
                                                        {
                                                            class: m([
                                                                `text-base font-bold tabular-nums`,
                                                                _.invoice
                                                                    .remaining >
                                                                0
                                                                    ? `text-status-critical`
                                                                    : `text-status-good`,
                                                            ]),
                                                        },
                                                        d(
                                                            h(b)(
                                                                _.invoice
                                                                    .remaining,
                                                            ),
                                                        ),
                                                        3,
                                                    ),
                                                ]),
                                            ]),
                                        ]),
                                        _: 1,
                                    },
                                ),
                            ]),
                            u(
                                D,
                                { title: `بنود الفاتورة`, flush: `` },
                                {
                                    default: a(() => [
                                        c(`div`, ae, [
                                            c(`table`, oe, [
                                                (s[16] ||= c(
                                                    `thead`,
                                                    {
                                                        class: `border-b border-border text-xs text-muted-foreground`,
                                                    },
                                                    [
                                                        c(`tr`, null, [
                                                            c(
                                                                `th`,
                                                                {
                                                                    class: `px-5 py-2.5 text-start font-medium`,
                                                                },
                                                                ` الصنف `,
                                                            ),
                                                            c(
                                                                `th`,
                                                                {
                                                                    class: `px-5 py-2.5 text-start font-medium`,
                                                                },
                                                                ` الكود `,
                                                            ),
                                                            c(
                                                                `th`,
                                                                {
                                                                    class: `px-5 py-2.5 text-start font-medium`,
                                                                },
                                                                ` الكمية `,
                                                            ),
                                                            c(
                                                                `th`,
                                                                {
                                                                    class: `px-5 py-2.5 text-start font-medium`,
                                                                },
                                                                ` السعر `,
                                                            ),
                                                            c(
                                                                `th`,
                                                                {
                                                                    class: `px-5 py-2.5 text-start font-medium`,
                                                                },
                                                                ` الخصم `,
                                                            ),
                                                            c(
                                                                `th`,
                                                                {
                                                                    class: `px-5 py-2.5 text-start font-medium`,
                                                                },
                                                                ` الإجمالي `,
                                                            ),
                                                        ]),
                                                    ],
                                                    -1,
                                                )),
                                                c(`tbody`, se, [
                                                    (t(!0),
                                                    n(
                                                        i,
                                                        null,
                                                        r(
                                                            _.invoice.items,
                                                            (e) => (
                                                                t(),
                                                                n(
                                                                    `tr`,
                                                                    {
                                                                        key: e.id,
                                                                    },
                                                                    [
                                                                        c(
                                                                            `td`,
                                                                            ce,
                                                                            d(
                                                                                e.name,
                                                                            ),
                                                                            1,
                                                                        ),
                                                                        c(
                                                                            `td`,
                                                                            le,
                                                                            d(
                                                                                e.sku,
                                                                            ),
                                                                            1,
                                                                        ),
                                                                        c(
                                                                            `td`,
                                                                            ue,
                                                                            d(
                                                                                h(
                                                                                    x,
                                                                                )(
                                                                                    e.quantity,
                                                                                    e.unit,
                                                                                ),
                                                                            ),
                                                                            1,
                                                                        ),
                                                                        c(
                                                                            `td`,
                                                                            de,
                                                                            d(
                                                                                h(
                                                                                    b,
                                                                                )(
                                                                                    e.unitPrice,
                                                                                ),
                                                                            ),
                                                                            1,
                                                                        ),
                                                                        c(
                                                                            `td`,
                                                                            fe,
                                                                            d(
                                                                                e.discount >
                                                                                    0
                                                                                    ? h(
                                                                                          b,
                                                                                      )(
                                                                                          e.discount,
                                                                                      )
                                                                                    : `—`,
                                                                            ),
                                                                            1,
                                                                        ),
                                                                        c(
                                                                            `td`,
                                                                            pe,
                                                                            d(
                                                                                h(
                                                                                    b,
                                                                                )(
                                                                                    e.total,
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
                                                c(`tfoot`, me, [
                                                    c(`tr`, null, [
                                                        (s[12] ||= c(
                                                            `td`,
                                                            {
                                                                colspan: `5`,
                                                                class: `px-5 py-2 text-muted-foreground`,
                                                            },
                                                            ` إجمالي البنود `,
                                                            -1,
                                                        )),
                                                        c(
                                                            `td`,
                                                            he,
                                                            d(
                                                                h(b)(
                                                                    _.invoice
                                                                        .subtotal,
                                                                ),
                                                            ),
                                                            1,
                                                        ),
                                                    ]),
                                                    _.invoice.discount > 0
                                                        ? (t(),
                                                          n(`tr`, ge, [
                                                              (s[13] ||= c(
                                                                  `td`,
                                                                  {
                                                                      colspan: `5`,
                                                                      class: `px-5 py-2 text-muted-foreground`,
                                                                  },
                                                                  ` خصم على الفاتورة `,
                                                                  -1,
                                                              )),
                                                              c(
                                                                  `td`,
                                                                  _e,
                                                                  ` − ` +
                                                                      d(
                                                                          h(b)(
                                                                              _
                                                                                  .invoice
                                                                                  .discount,
                                                                          ),
                                                                      ),
                                                                  1,
                                                              ),
                                                          ]))
                                                        : l(``, !0),
                                                    c(`tr`, null, [
                                                        (s[14] ||= c(
                                                            `td`,
                                                            {
                                                                colspan: `5`,
                                                                class: `px-5 py-2 text-muted-foreground`,
                                                            },
                                                            ` ضريبة القيمة المضافة `,
                                                            -1,
                                                        )),
                                                        c(
                                                            `td`,
                                                            ve,
                                                            d(
                                                                h(b)(
                                                                    _.invoice
                                                                        .tax,
                                                                ),
                                                            ),
                                                            1,
                                                        ),
                                                    ]),
                                                    c(`tr`, ye, [
                                                        (s[15] ||= c(
                                                            `td`,
                                                            {
                                                                colspan: `5`,
                                                                class: `px-5 py-3`,
                                                            },
                                                            `الإجمالي`,
                                                            -1,
                                                        )),
                                                        c(
                                                            `td`,
                                                            be,
                                                            d(
                                                                h(b)(
                                                                    _.invoice
                                                                        .total,
                                                                ),
                                                            ),
                                                            1,
                                                        ),
                                                    ]),
                                                ]),
                                            ]),
                                        ]),
                                    ]),
                                    _: 1,
                                },
                            ),
                            _.invoice.payments.length
                                ? (t(),
                                  o(
                                      D,
                                      {
                                          key: 0,
                                          title: `التحصيلات على الفاتورة`,
                                          flush: ``,
                                      },
                                      {
                                          default: a(() => [
                                              c(`div`, xe, [
                                                  c(`table`, Se, [
                                                      (s[17] ||= c(
                                                          `thead`,
                                                          {
                                                              class: `border-b border-border text-xs text-muted-foreground`,
                                                          },
                                                          [
                                                              c(`tr`, null, [
                                                                  c(
                                                                      `th`,
                                                                      {
                                                                          class: `px-5 py-2.5 text-start font-medium`,
                                                                      },
                                                                      ` التاريخ `,
                                                                  ),
                                                                  c(
                                                                      `th`,
                                                                      {
                                                                          class: `px-5 py-2.5 text-start font-medium`,
                                                                      },
                                                                      ` الطريقة `,
                                                                  ),
                                                                  c(
                                                                      `th`,
                                                                      {
                                                                          class: `px-5 py-2.5 text-start font-medium`,
                                                                      },
                                                                      ` المرجع `,
                                                                  ),
                                                                  c(
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
                                                      c(`tbody`, Z, [
                                                          (t(!0),
                                                          n(
                                                              i,
                                                              null,
                                                              r(
                                                                  _.invoice
                                                                      .payments,
                                                                  (e) => (
                                                                      t(),
                                                                      n(
                                                                          `tr`,
                                                                          {
                                                                              key: e.id,
                                                                          },
                                                                          [
                                                                              c(
                                                                                  `td`,
                                                                                  Ce,
                                                                                  d(
                                                                                      h(
                                                                                          S,
                                                                                      )(
                                                                                          e.date,
                                                                                      ),
                                                                                  ),
                                                                                  1,
                                                                              ),
                                                                              c(
                                                                                  `td`,
                                                                                  we,
                                                                                  d(
                                                                                      Ae[
                                                                                          e
                                                                                              .method
                                                                                      ] ??
                                                                                          e.method,
                                                                                  ),
                                                                                  1,
                                                                              ),
                                                                              c(
                                                                                  `td`,
                                                                                  Te,
                                                                                  d(
                                                                                      e.reference ??
                                                                                          `—`,
                                                                                  ),
                                                                                  1,
                                                                              ),
                                                                              c(
                                                                                  `td`,
                                                                                  Ee,
                                                                                  d(
                                                                                      h(
                                                                                          b,
                                                                                      )(
                                                                                          e.amount,
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
                                : l(``, !0),
                            u(
                                ne,
                                {
                                    attachments: _.invoice.attachments,
                                    'upload-url': `/invoices/${_.invoice.id}/attachments`,
                                },
                                null,
                                8,
                                [`attachments`, `upload-url`],
                            ),
                            _.invoice.notes
                                ? (t(),
                                  o(
                                      D,
                                      { key: 1, title: `ملاحظات` },
                                      {
                                          default: a(() => [
                                              c(`p`, De, d(_.invoice.notes), 1),
                                          ]),
                                          _: 1,
                                      },
                                  ))
                                : l(``, !0),
                        ]),
                    ],
                    64,
                )
            );
        },
    });
export { Q as default };
