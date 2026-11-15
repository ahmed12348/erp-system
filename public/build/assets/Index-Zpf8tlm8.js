import {
    J as e,
    Jn as t,
    Kt as n,
    Q as r,
    Qt as i,
    R as a,
    Tn as o,
    X as s,
    Y as c,
    at as l,
    fr as u,
    i as d,
    it as f,
    lr as p,
    mr as m,
    or as h,
    r as g,
    st as _,
} from './dist-C0kHilBU.js';
import { t as v } from './EmptyState-Cuz8CAbt.js';
import { t as y } from './Input-8-HQLciI.js';
import { J as b, _ as x, v as S } from './app-DxgtVyfV.js';
import { t as C } from './PageHeader-FN2NW817.js';
import { t as w } from './SectionCard-sO5uXxKO.js';
var T = { class: `flex flex-1 flex-col gap-5 p-4 md:p-6` },
    E = { class: `flex flex-wrap items-center gap-2` },
    D = { key: 0, class: `scrollbar-thin overflow-x-auto` },
    O = { class: `w-full text-sm` },
    k = { class: `divide-y divide-border` },
    A = { class: `px-5 py-3` },
    j = { class: `px-5 py-3 text-muted-foreground` },
    M = { class: `px-5 py-3 tabular-nums` },
    N = { class: `px-5 py-3 tabular-nums` },
    P = { class: `px-5 py-3 font-semibold whitespace-nowrap tabular-nums` },
    F = { class: `px-5 py-3 whitespace-nowrap text-status-good tabular-nums` },
    I = { class: `px-5 py-3 tabular-nums` },
    L = { key: 1, class: `text-muted-foreground` },
    R = { class: `px-5 py-3 whitespace-nowrap tabular-nums` },
    z = { class: `px-5 py-3 whitespace-nowrap tabular-nums` },
    B = { class: `font-semibold` },
    V = { class: `ms-1 text-xs text-muted-foreground` },
    H = { class: `border-t-2 border-border bg-muted/40` },
    U = { class: `font-bold` },
    W = { class: `px-5 py-3 tabular-nums` },
    G = { class: `px-5 py-3 tabular-nums` },
    K = { class: `px-5 py-3 tabular-nums`, 'data-test': `reps-outstanding` },
    q = { class: `px-5 py-3 tabular-nums` },
    J = _({
        layout: (e) => ({ breadcrumbs: e.breadcrumbs }),
        __name: `Index`,
        props: { reps: {}, filters: {}, breadcrumbs: {} },
        setup(_) {
            let J = _,
                Y = t({ from: J.filters.from ?? ``, to: J.filters.to ?? `` });
            function X() {
                m.get(
                    `/reps`,
                    Object.fromEntries(
                        Object.entries(Y).filter(([, e]) => e !== ``),
                    ),
                    { preserveState: !0, replace: !0, preserveScroll: !0 },
                );
            }
            let Z = e(() => ({
                netSales: J.reps.reduce((e, t) => e + t.netSales, 0),
                collected: J.reps.reduce((e, t) => e + t.collected, 0),
                outstanding: J.reps.reduce((e, t) => e + t.outstanding, 0),
                commission: J.reps.reduce((e, t) => e + t.commission, 0),
            }));
            return (e, t) => (
                n(),
                r(
                    a,
                    null,
                    [
                        l(h(g), { title: `المندوبين` }),
                        c(`div`, T, [
                            l(C, {
                                title: `مندوبي المبيعات`,
                                description: `العمولة بتتحسب على المحصّل مش على المبيعات`,
                            }),
                            l(
                                w,
                                { flush: `` },
                                {
                                    actions: o(() => [
                                        c(`div`, E, [
                                            l(
                                                h(y),
                                                {
                                                    modelValue: Y.from,
                                                    'onUpdate:modelValue':
                                                        (t[0] ||= (e) =>
                                                            (Y.from = e)),
                                                    type: `date`,
                                                    class: `h-9 w-38`,
                                                    'aria-label': `من تاريخ`,
                                                    'data-test': `reps-from`,
                                                    onChange: X,
                                                },
                                                null,
                                                8,
                                                [`modelValue`],
                                            ),
                                            l(
                                                h(y),
                                                {
                                                    modelValue: Y.to,
                                                    'onUpdate:modelValue':
                                                        (t[1] ||= (e) =>
                                                            (Y.to = e)),
                                                    type: `date`,
                                                    class: `h-9 w-38`,
                                                    'aria-label': `إلى تاريخ`,
                                                    onChange: X,
                                                },
                                                null,
                                                8,
                                                [`modelValue`],
                                            ),
                                        ]),
                                    ]),
                                    default: o(() => [
                                        _.reps.length
                                            ? (n(),
                                              r(`div`, D, [
                                                  c(`table`, O, [
                                                      (t[4] ||= c(
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
                                                                      ` المندوب `,
                                                                  ),
                                                                  c(
                                                                      `th`,
                                                                      {
                                                                          class: `px-5 py-2.5 text-start font-medium`,
                                                                      },
                                                                      ` المنطقة `,
                                                                  ),
                                                                  c(
                                                                      `th`,
                                                                      {
                                                                          class: `px-5 py-2.5 text-start font-medium`,
                                                                      },
                                                                      ` التجار `,
                                                                  ),
                                                                  c(
                                                                      `th`,
                                                                      {
                                                                          class: `px-5 py-2.5 text-start font-medium`,
                                                                      },
                                                                      ` الفواتير `,
                                                                  ),
                                                                  c(
                                                                      `th`,
                                                                      {
                                                                          class: `px-5 py-2.5 text-start font-medium`,
                                                                      },
                                                                      ` صافي المبيعات `,
                                                                  ),
                                                                  c(
                                                                      `th`,
                                                                      {
                                                                          class: `px-5 py-2.5 text-start font-medium`,
                                                                      },
                                                                      ` المحصّل `,
                                                                  ),
                                                                  c(
                                                                      `th`,
                                                                      {
                                                                          class: `px-5 py-2.5 text-start font-medium`,
                                                                      },
                                                                      ` نسبة التحصيل `,
                                                                  ),
                                                                  c(
                                                                      `th`,
                                                                      {
                                                                          class: `px-5 py-2.5 text-start font-medium`,
                                                                      },
                                                                      ` المستحق على تجاره `,
                                                                  ),
                                                                  c(
                                                                      `th`,
                                                                      {
                                                                          class: `px-5 py-2.5 text-start font-medium`,
                                                                      },
                                                                      ` العمولة `,
                                                                  ),
                                                              ]),
                                                          ],
                                                          -1,
                                                      )),
                                                      c(`tbody`, k, [
                                                          (n(!0),
                                                          r(
                                                              a,
                                                              null,
                                                              i(
                                                                  _.reps,
                                                                  (e) => (
                                                                      n(),
                                                                      r(
                                                                          `tr`,
                                                                          {
                                                                              key: e.id,
                                                                              class: `transition-colors hover:bg-accent/60`,
                                                                          },
                                                                          [
                                                                              c(
                                                                                  `td`,
                                                                                  A,
                                                                                  [
                                                                                      l(
                                                                                          h(
                                                                                              d,
                                                                                          ),
                                                                                          {
                                                                                              href: `/reps/${e.id}`,
                                                                                              class: `font-medium hover:text-primary hover:underline`,
                                                                                          },
                                                                                          {
                                                                                              default:
                                                                                                  o(
                                                                                                      () => [
                                                                                                          f(
                                                                                                              u(
                                                                                                                  e.name,
                                                                                                              ),
                                                                                                              1,
                                                                                                          ),
                                                                                                      ],
                                                                                                  ),
                                                                                              _: 2,
                                                                                          },
                                                                                          1032,
                                                                                          [
                                                                                              `href`,
                                                                                          ],
                                                                                      ),
                                                                                  ],
                                                                              ),
                                                                              c(
                                                                                  `td`,
                                                                                  j,
                                                                                  u(
                                                                                      e.region ??
                                                                                          `—`,
                                                                                  ),
                                                                                  1,
                                                                              ),
                                                                              c(
                                                                                  `td`,
                                                                                  M,
                                                                                  u(
                                                                                      h(
                                                                                          S,
                                                                                      )(
                                                                                          e.customers,
                                                                                      ),
                                                                                  ),
                                                                                  1,
                                                                              ),
                                                                              c(
                                                                                  `td`,
                                                                                  N,
                                                                                  u(
                                                                                      h(
                                                                                          S,
                                                                                      )(
                                                                                          e.invoices,
                                                                                      ),
                                                                                  ),
                                                                                  1,
                                                                              ),
                                                                              c(
                                                                                  `td`,
                                                                                  P,
                                                                                  u(
                                                                                      h(
                                                                                          x,
                                                                                      )(
                                                                                          e.netSales,
                                                                                      ),
                                                                                  ),
                                                                                  1,
                                                                              ),
                                                                              c(
                                                                                  `td`,
                                                                                  F,
                                                                                  u(
                                                                                      h(
                                                                                          x,
                                                                                      )(
                                                                                          e.collected,
                                                                                      ),
                                                                                  ),
                                                                                  1,
                                                                              ),
                                                                              c(
                                                                                  `td`,
                                                                                  I,
                                                                                  [
                                                                                      e.collectionRate ===
                                                                                      null
                                                                                          ? (n(),
                                                                                            r(
                                                                                                `span`,
                                                                                                L,
                                                                                                `—`,
                                                                                            ))
                                                                                          : (n(),
                                                                                            r(
                                                                                                `span`,
                                                                                                {
                                                                                                    key: 0,
                                                                                                    class: p(
                                                                                                        e.collectionRate >=
                                                                                                            70
                                                                                                            ? `text-status-good`
                                                                                                            : e.collectionRate >=
                                                                                                                45
                                                                                                              ? `text-status-warning`
                                                                                                              : `text-status-critical`,
                                                                                                    ),
                                                                                                },
                                                                                                u(
                                                                                                    h(
                                                                                                        S,
                                                                                                    )(
                                                                                                        e.collectionRate,
                                                                                                        1,
                                                                                                    ),
                                                                                                ) +
                                                                                                    `% `,
                                                                                                3,
                                                                                            )),
                                                                                  ],
                                                                              ),
                                                                              c(
                                                                                  `td`,
                                                                                  R,
                                                                                  u(
                                                                                      h(
                                                                                          x,
                                                                                      )(
                                                                                          e.outstanding,
                                                                                      ),
                                                                                  ),
                                                                                  1,
                                                                              ),
                                                                              c(
                                                                                  `td`,
                                                                                  z,
                                                                                  [
                                                                                      c(
                                                                                          `span`,
                                                                                          B,
                                                                                          u(
                                                                                              h(
                                                                                                  x,
                                                                                              )(
                                                                                                  e.commission,
                                                                                              ),
                                                                                          ),
                                                                                          1,
                                                                                      ),
                                                                                      c(
                                                                                          `span`,
                                                                                          V,
                                                                                          ` (` +
                                                                                              u(
                                                                                                  h(
                                                                                                      S,
                                                                                                  )(
                                                                                                      e.commissionRate,
                                                                                                      2,
                                                                                                  ),
                                                                                              ) +
                                                                                              `%) `,
                                                                                          1,
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
                                                      c(`tfoot`, H, [
                                                          c(`tr`, U, [
                                                              (t[2] ||= c(
                                                                  `td`,
                                                                  {
                                                                      colspan: `4`,
                                                                      class: `px-5 py-3`,
                                                                  },
                                                                  `الإجمالي`,
                                                                  -1,
                                                              )),
                                                              c(
                                                                  `td`,
                                                                  W,
                                                                  u(
                                                                      h(x)(
                                                                          Z
                                                                              .value
                                                                              .netSales,
                                                                      ),
                                                                  ),
                                                                  1,
                                                              ),
                                                              c(
                                                                  `td`,
                                                                  G,
                                                                  u(
                                                                      h(x)(
                                                                          Z
                                                                              .value
                                                                              .collected,
                                                                      ),
                                                                  ),
                                                                  1,
                                                              ),
                                                              (t[3] ||= c(
                                                                  `td`,
                                                                  {
                                                                      class: `px-5 py-3`,
                                                                  },
                                                                  null,
                                                                  -1,
                                                              )),
                                                              c(
                                                                  `td`,
                                                                  K,
                                                                  u(
                                                                      h(x)(
                                                                          Z
                                                                              .value
                                                                              .outstanding,
                                                                      ),
                                                                  ),
                                                                  1,
                                                              ),
                                                              c(
                                                                  `td`,
                                                                  q,
                                                                  u(
                                                                      h(x)(
                                                                          Z
                                                                              .value
                                                                              .commission,
                                                                      ),
                                                                  ),
                                                                  1,
                                                              ),
                                                          ]),
                                                      ]),
                                                  ]),
                                              ]))
                                            : (n(),
                                              s(
                                                  v,
                                                  {
                                                      key: 1,
                                                      large: ``,
                                                      icon: h(b),
                                                      title: `لسه مافيش مندوبين`,
                                                      description: `أول ما تضيف مندوبين وتربطهم بالتجار، هتشوف هنا مبيعات كل مندوب وتحصيلاته وعمولته — العمولة بتتحسب على المحصّل مش على المبيعات.`,
                                                  },
                                                  null,
                                                  8,
                                                  [`icon`],
                                              )),
                                    ]),
                                    _: 1,
                                },
                            ),
                        ]),
                    ],
                    64,
                )
            );
        },
    });
export { J as default };
