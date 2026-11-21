import {
    Dn as e,
    Kt as t,
    M as n,
    O as r,
    Q as i,
    Qt as a,
    R as o,
    Tn as s,
    X as c,
    Xn as l,
    Y as u,
    Z as d,
    at as f,
    fr as p,
    i as m,
    it as h,
    lr as g,
    mr as _,
    o as v,
    or as y,
    r as b,
    st as x,
} from './dist-C0kHilBU.js';
import { t as S } from './button-BRBVuDNP.js';
import { t as C } from './createLucideIcon-C6NZ5pDL.js';
import { t as w } from './EmptyState-Cuz8CAbt.js';
import { t as T } from './plus-DGsV77u5.js';
import { t as ee } from './Input-CsBjGzWv.js';
import {
    J as E,
    _ as D,
    b as O,
    g as k,
    h as A,
    rt as j,
    w as M,
} from './app-DkL2j24G.js';
import { t as N } from './PageHeader-FN2NW817.js';
import { t as P } from './InputError-_9qlcKk2.js';
import { t as F } from './SectionCard-sO5uXxKO.js';
var I = C({
        name: `rotate-ccw-clock`,
        size: 24,
        node: [
            [
                `path`,
                {
                    d: `M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8`,
                    key: `1357e3`,
                },
            ],
            [`path`, { d: `M3 3v5h5`, key: `1xhq8a` }],
            [`path`, { d: `M12 7v5l4 2`, key: `1fdv2h` }],
        ],
        aliases: [`history`],
    }),
    L = { class: `space-y-4` },
    R = { key: 0, class: `flex flex-wrap gap-2` },
    z = { key: 0, class: `text-[10px] text-muted-foreground` },
    B = [`aria-label`, `onClick`],
    V = { key: 1, class: `text-sm text-muted-foreground` },
    H = { class: `flex-1 space-y-1` },
    U = x({
        __name: `ProductAliases`,
        props: { productId: {}, aliases: {} },
        setup(e) {
            let r = e,
                { manageMasterData: l } = M(),
                m = v({ alias: `` });
            function g() {
                m.post(`/products/${r.productId}/aliases`, {
                    preserveScroll: !0,
                    onSuccess: () => m.reset(),
                });
            }
            function b(e) {
                _.delete(`/product-aliases/${e.id}`, { preserveScroll: !0 });
            }
            return (r, _) => (
                t(),
                c(
                    F,
                    { title: `أسماء بديلة` },
                    {
                        default: s(() => [
                            u(`div`, L, [
                                (_[2] ||= u(
                                    `p`,
                                    { class: `text-xs text-muted-foreground` },
                                    ` الأسماء اللي بيتكتب بيها الصنف على فواتير العملاء. النظام بيتعلّمها لوحده أول ما تصحّح مطابقة في فاتورة ممسوحة، وبيستعملها قبل أي مقارنة تانية. `,
                                    -1,
                                )),
                                e.aliases.length
                                    ? (t(),
                                      i(`ul`, R, [
                                          (t(!0),
                                          i(
                                              o,
                                              null,
                                              a(
                                                  e.aliases,
                                                  (e) => (
                                                      t(),
                                                      i(
                                                          `li`,
                                                          {
                                                              key: e.id,
                                                              class: `group flex items-center gap-1.5 rounded-full border border-border bg-muted/40 py-1 ps-3 pe-1.5 text-sm`,
                                                              'data-test': `product-alias`,
                                                          },
                                                          [
                                                              f(y(j), {
                                                                  class: `size-3 text-muted-foreground`,
                                                              }),
                                                              u(
                                                                  `span`,
                                                                  null,
                                                                  p(e.alias),
                                                                  1,
                                                              ),
                                                              e.addedAt
                                                                  ? (t(),
                                                                    i(
                                                                        `span`,
                                                                        z,
                                                                        p(
                                                                            y(
                                                                                A,
                                                                            )(
                                                                                e.addedAt,
                                                                            ),
                                                                        ),
                                                                        1,
                                                                    ))
                                                                  : d(``, !0),
                                                              y(l)
                                                                  ? (t(),
                                                                    i(
                                                                        `button`,
                                                                        {
                                                                            key: 1,
                                                                            type: `button`,
                                                                            class: `flex size-5 items-center justify-center rounded-full text-muted-foreground transition-colors hover:bg-destructive/10 hover:text-destructive`,
                                                                            'aria-label': `شيل الاسم البديل ${e.alias}`,
                                                                            'data-test': `product-alias-remove`,
                                                                            onClick:
                                                                                (
                                                                                    t,
                                                                                ) =>
                                                                                    b(
                                                                                        e,
                                                                                    ),
                                                                        },
                                                                        [
                                                                            f(
                                                                                y(
                                                                                    E,
                                                                                ),
                                                                                {
                                                                                    class: `size-3`,
                                                                                },
                                                                            ),
                                                                        ],
                                                                        8,
                                                                        B,
                                                                    ))
                                                                  : d(``, !0),
                                                          ],
                                                      )
                                                  ),
                                              ),
                                              128,
                                          )),
                                      ]))
                                    : (t(),
                                      i(
                                          `p`,
                                          V,
                                          ` لسه مافيش أسماء بديلة للصنف ده. `,
                                      )),
                                y(l)
                                    ? (t(),
                                      i(
                                          `form`,
                                          {
                                              key: 2,
                                              class: `flex items-start gap-2`,
                                              onSubmit: n(g, [`prevent`]),
                                          },
                                          [
                                              u(`div`, H, [
                                                  f(
                                                      y(ee),
                                                      {
                                                          modelValue:
                                                              y(m).alias,
                                                          'onUpdate:modelValue':
                                                              (_[0] ||= (e) =>
                                                                  (y(m).alias =
                                                                      e)),
                                                          placeholder: `اكتب اسم زي ما بيتكتب على الفاتورة`,
                                                          maxlength: `191`,
                                                          'data-test': `product-alias-input`,
                                                      },
                                                      null,
                                                      8,
                                                      [`modelValue`],
                                                  ),
                                                  f(
                                                      P,
                                                      {
                                                          message:
                                                              y(m).errors.alias,
                                                      },
                                                      null,
                                                      8,
                                                      [`message`],
                                                  ),
                                              ]),
                                              f(
                                                  y(S),
                                                  {
                                                      type: `submit`,
                                                      variant: `outline`,
                                                      disabled:
                                                          y(m).processing ||
                                                          y(m).alias.trim() ===
                                                              ``,
                                                      'data-test': `product-alias-add`,
                                                  },
                                                  {
                                                      default: s(() => [
                                                          f(y(T), {
                                                              class: `size-4`,
                                                          }),
                                                          (_[1] ||= h(
                                                              ` إضافة `,
                                                              -1,
                                                          )),
                                                      ]),
                                                      _: 1,
                                                  },
                                                  8,
                                                  [`disabled`],
                                              ),
                                          ],
                                          32,
                                      ))
                                    : d(``, !0),
                            ]),
                        ]),
                        _: 1,
                    },
                )
            );
        },
    }),
    W = { class: `flex flex-1 flex-col gap-5 p-4 md:p-6` },
    G = { class: `grid gap-4 sm:grid-cols-2 xl:grid-cols-4` },
    te = { class: `rounded-xl border border-border bg-card p-5 shadow-soft` },
    K = { class: `rounded-xl border border-border bg-card p-5 shadow-soft` },
    q = { class: `mt-2 text-xl font-bold tabular-nums` },
    J = { class: `rounded-xl border border-border bg-card p-5 shadow-soft` },
    Y = { class: `mt-2 text-xl font-bold tabular-nums` },
    X = { class: `rounded-xl border border-border bg-card p-5 shadow-soft` },
    Z = { class: `mt-2 text-xl font-bold tabular-nums` },
    Q = [`value`],
    ne = { key: 0, class: `scrollbar-thin overflow-x-auto` },
    re = { class: `w-full text-sm` },
    ie = { class: `divide-y divide-border` },
    ae = {
        class: `px-5 py-3 whitespace-nowrap text-muted-foreground tabular-nums`,
    },
    oe = { class: `px-5 py-3` },
    $ = { class: `px-5 py-3 text-muted-foreground` },
    se = { class: `px-5 py-3 whitespace-nowrap tabular-nums` },
    ce = { key: 1, class: `text-muted-foreground` },
    le = { dir: `ltr` },
    ue = {
        class: `px-5 py-3 whitespace-nowrap text-muted-foreground tabular-nums`,
    },
    de = { class: `px-5 py-3 whitespace-nowrap tabular-nums` },
    fe = x({
        layout: (e) => ({ breadcrumbs: e.breadcrumbs }),
        __name: `Show`,
        props: {
            product: {},
            rows: {},
            aliases: {},
            warehouses: {},
            filters: {},
            breadcrumbs: {},
        },
        setup(n) {
            let d = n,
                v = l(String(d.filters.warehouse_id ?? ``));
            function x() {
                _.get(
                    `/inventory/${d.product.id}`,
                    v.value ? { warehouse_id: v.value } : {},
                    { preserveState: !0, replace: !0, preserveScroll: !0 },
                );
            }
            let S = {
                in: `وارد`,
                out: `منصرف`,
                adjust: `تسوية`,
                transfer: `تحويل`,
            };
            return (l, d) => (
                t(),
                i(
                    o,
                    null,
                    [
                        f(y(b), { title: n.product.name }, null, 8, [`title`]),
                        u(`div`, W, [
                            f(
                                N,
                                {
                                    title: n.product.name,
                                    description: `كود الصنف ${n.product.sku}`,
                                },
                                null,
                                8,
                                [`title`, `description`],
                            ),
                            u(`div`, G, [
                                u(`div`, te, [
                                    (d[1] ||= u(
                                        `p`,
                                        {
                                            class: `text-sm text-muted-foreground`,
                                        },
                                        `الرصيد الحالي`,
                                        -1,
                                    )),
                                    u(
                                        `p`,
                                        {
                                            class: g([
                                                `mt-2 text-xl font-bold tabular-nums`,
                                                n.product.balance <
                                                n.product.minStock
                                                    ? `text-status-critical`
                                                    : ``,
                                            ]),
                                            'data-test': `product-balance`,
                                        },
                                        p(
                                            y(O)(
                                                n.product.balance,
                                                n.product.unit,
                                            ),
                                        ),
                                        3,
                                    ),
                                ]),
                                u(`div`, K, [
                                    (d[2] ||= u(
                                        `p`,
                                        {
                                            class: `text-sm text-muted-foreground`,
                                        },
                                        `حد الطلب`,
                                        -1,
                                    )),
                                    u(
                                        `p`,
                                        q,
                                        p(
                                            y(O)(
                                                n.product.minStock,
                                                n.product.unit,
                                            ),
                                        ),
                                        1,
                                    ),
                                ]),
                                u(`div`, J, [
                                    (d[3] ||= u(
                                        `p`,
                                        {
                                            class: `text-sm text-muted-foreground`,
                                        },
                                        `آخر تكلفة واصلة`,
                                        -1,
                                    )),
                                    u(`p`, Y, p(y(D)(n.product.cost)), 1),
                                ]),
                                u(`div`, X, [
                                    (d[4] ||= u(
                                        `p`,
                                        {
                                            class: `text-sm text-muted-foreground`,
                                        },
                                        `قيمة الرصيد`,
                                        -1,
                                    )),
                                    u(
                                        `p`,
                                        Z,
                                        p(
                                            y(D)(
                                                n.product.balance *
                                                    n.product.cost,
                                            ),
                                        ),
                                        1,
                                    ),
                                ]),
                            ]),
                            f(
                                U,
                                {
                                    'product-id': n.product.id,
                                    aliases: n.aliases,
                                },
                                null,
                                8,
                                [`product-id`, `aliases`],
                            ),
                            f(
                                F,
                                { title: `حركة الصنف`, flush: `` },
                                {
                                    actions: s(() => [
                                        e(
                                            u(
                                                `select`,
                                                {
                                                    'onUpdate:modelValue':
                                                        (d[0] ||= (e) =>
                                                            (v.value = e)),
                                                    class: `h-9 rounded-md border border-input bg-transparent px-3 text-sm`,
                                                    'data-test': `movement-warehouse`,
                                                    onChange: x,
                                                },
                                                [
                                                    (d[5] ||= u(
                                                        `option`,
                                                        { value: `` },
                                                        `كل المخازن`,
                                                        -1,
                                                    )),
                                                    (t(!0),
                                                    i(
                                                        o,
                                                        null,
                                                        a(
                                                            n.warehouses,
                                                            (e) => (
                                                                t(),
                                                                i(
                                                                    `option`,
                                                                    {
                                                                        key: e.id,
                                                                        value: e.id,
                                                                    },
                                                                    p(e.name),
                                                                    9,
                                                                    Q,
                                                                )
                                                            ),
                                                        ),
                                                        128,
                                                    )),
                                                ],
                                                544,
                                            ),
                                            [[r, v.value]],
                                        ),
                                    ]),
                                    default: s(() => [
                                        n.rows.length
                                            ? (t(),
                                              i(`div`, ne, [
                                                  u(`table`, re, [
                                                      (d[6] ||= u(
                                                          `thead`,
                                                          {
                                                              class: `border-b border-border text-xs text-muted-foreground`,
                                                          },
                                                          [
                                                              u(`tr`, null, [
                                                                  u(
                                                                      `th`,
                                                                      {
                                                                          class: `px-5 py-2.5 text-start font-medium`,
                                                                      },
                                                                      ` التاريخ `,
                                                                  ),
                                                                  u(
                                                                      `th`,
                                                                      {
                                                                          class: `px-5 py-2.5 text-start font-medium`,
                                                                      },
                                                                      ` النوع `,
                                                                  ),
                                                                  u(
                                                                      `th`,
                                                                      {
                                                                          class: `px-5 py-2.5 text-start font-medium`,
                                                                      },
                                                                      ` المخزن `,
                                                                  ),
                                                                  u(
                                                                      `th`,
                                                                      {
                                                                          class: `px-5 py-2.5 text-start font-medium`,
                                                                      },
                                                                      ` المرجع `,
                                                                  ),
                                                                  u(
                                                                      `th`,
                                                                      {
                                                                          class: `px-5 py-2.5 text-start font-medium`,
                                                                      },
                                                                      ` الكمية `,
                                                                  ),
                                                                  u(
                                                                      `th`,
                                                                      {
                                                                          class: `px-5 py-2.5 text-start font-medium`,
                                                                      },
                                                                      ` التكلفة `,
                                                                  ),
                                                                  u(
                                                                      `th`,
                                                                      {
                                                                          class: `px-5 py-2.5 text-start font-medium`,
                                                                      },
                                                                      ` الرصيد بعدها `,
                                                                  ),
                                                              ]),
                                                          ],
                                                          -1,
                                                      )),
                                                      u(`tbody`, ie, [
                                                          (t(!0),
                                                          i(
                                                              o,
                                                              null,
                                                              a(
                                                                  n.rows,
                                                                  (e) => (
                                                                      t(),
                                                                      i(
                                                                          `tr`,
                                                                          {
                                                                              key: e.id,
                                                                              class: `transition-colors hover:bg-accent/60`,
                                                                          },
                                                                          [
                                                                              u(
                                                                                  `td`,
                                                                                  ae,
                                                                                  p(
                                                                                      y(
                                                                                          k,
                                                                                      )(
                                                                                          e.date,
                                                                                      ),
                                                                                  ),
                                                                                  1,
                                                                              ),
                                                                              u(
                                                                                  `td`,
                                                                                  oe,
                                                                                  [
                                                                                      u(
                                                                                          `span`,
                                                                                          {
                                                                                              class: g(
                                                                                                  [
                                                                                                      `rounded-full px-2 py-0.5 text-xs font-medium`,
                                                                                                      e.quantity >=
                                                                                                      0
                                                                                                          ? `bg-status-good/10 text-status-good`
                                                                                                          : `bg-status-warning/10 text-status-warning`,
                                                                                                  ],
                                                                                              ),
                                                                                          },
                                                                                          p(
                                                                                              S[
                                                                                                  e
                                                                                                      .type
                                                                                              ] ??
                                                                                                  e.type,
                                                                                          ),
                                                                                          3,
                                                                                      ),
                                                                                  ],
                                                                              ),
                                                                              u(
                                                                                  `td`,
                                                                                  $,
                                                                                  p(
                                                                                      e.warehouse ??
                                                                                          `—`,
                                                                                  ),
                                                                                  1,
                                                                              ),
                                                                              u(
                                                                                  `td`,
                                                                                  se,
                                                                                  [
                                                                                      e.link
                                                                                          ? (t(),
                                                                                            c(
                                                                                                y(
                                                                                                    m,
                                                                                                ),
                                                                                                {
                                                                                                    key: 0,
                                                                                                    href: e.link,
                                                                                                    class: `hover:text-primary hover:underline`,
                                                                                                },
                                                                                                {
                                                                                                    default:
                                                                                                        s(
                                                                                                            () => [
                                                                                                                h(
                                                                                                                    p(
                                                                                                                        e.reference,
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
                                                                                            ))
                                                                                          : (t(),
                                                                                            i(
                                                                                                `span`,
                                                                                                ce,
                                                                                                p(
                                                                                                    e.reference ??
                                                                                                        `—`,
                                                                                                ),
                                                                                                1,
                                                                                            )),
                                                                                  ],
                                                                              ),
                                                                              u(
                                                                                  `td`,
                                                                                  {
                                                                                      class: g(
                                                                                          [
                                                                                              `px-5 py-3 font-semibold whitespace-nowrap tabular-nums`,
                                                                                              e.quantity >=
                                                                                              0
                                                                                                  ? `text-status-good`
                                                                                                  : `text-status-warning`,
                                                                                          ],
                                                                                      ),
                                                                                  },
                                                                                  [
                                                                                      u(
                                                                                          `span`,
                                                                                          le,
                                                                                          p(
                                                                                              e.quantity >=
                                                                                                  0
                                                                                                  ? `+`
                                                                                                  : `−`,
                                                                                          ) +
                                                                                              p(
                                                                                                  y(
                                                                                                      O,
                                                                                                  )(
                                                                                                      Math.abs(
                                                                                                          e.quantity,
                                                                                                      ),
                                                                                                  ),
                                                                                              ),
                                                                                          1,
                                                                                      ),
                                                                                  ],
                                                                                  2,
                                                                              ),
                                                                              u(
                                                                                  `td`,
                                                                                  ue,
                                                                                  p(
                                                                                      e.unitCost
                                                                                          ? y(
                                                                                                D,
                                                                                            )(
                                                                                                e.unitCost,
                                                                                            )
                                                                                          : `—`,
                                                                                  ),
                                                                                  1,
                                                                              ),
                                                                              u(
                                                                                  `td`,
                                                                                  de,
                                                                                  p(
                                                                                      y(
                                                                                          O,
                                                                                      )(
                                                                                          e.balance,
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
                                              ]))
                                            : (t(),
                                              c(
                                                  w,
                                                  {
                                                      key: 1,
                                                      icon: y(I),
                                                      title: `مفيش حركة على الصنف ده`,
                                                      description: `الصنف لسه مادخلش ولا خرج من أي مخزن.`,
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
export { fe as default };
