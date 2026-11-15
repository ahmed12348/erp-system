import {
    Kt as e,
    Q as t,
    Qt as n,
    R as r,
    Tn as i,
    X as a,
    Xn as o,
    Y as s,
    Z as c,
    at as l,
    fr as u,
    it as d,
    mr as f,
    o as p,
    or as m,
    st as h,
} from './dist-C0kHilBU.js';
import { t as g } from './button-BRBVuDNP.js';
import { t as _ } from './createLucideIcon-C6NZ5pDL.js';
import { t as v } from './file-text-Bf77k8xe.js';
import { t as y } from './trash-DJlV-Hhi.js';
import { h as b, v as x } from './app-DxgtVyfV.js';
import { t as S } from './InputError-_9qlcKk2.js';
import { t as ee } from './Spinner-S_eIiGCn.js';
import { t as C } from './SectionCard-sO5uXxKO.js';
import { a as w, i as T, n as E, o as D, t as O } from './dialog-DoW27EUD.js';
var k = _({
        name: `download`,
        size: 24,
        node: [
            [`path`, { d: `M12 15V3`, key: `m9g1x1` }],
            [
                `path`,
                {
                    d: `M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4`,
                    key: `ih7n3h`,
                },
            ],
            [`path`, { d: `m7 10 5 5 5-5`, key: `brsn70` }],
        ],
    }),
    A = _({
        name: `image`,
        size: 24,
        node: [
            [
                `rect`,
                {
                    width: `18`,
                    height: `18`,
                    x: `3`,
                    y: `3`,
                    rx: `2`,
                    ry: `2`,
                    key: `1m3agn`,
                },
            ],
            [`circle`, { cx: `9`, cy: `9`, r: `2`, key: `af1f0g` }],
            [
                `path`,
                {
                    d: `m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21`,
                    key: `1xmnt7`,
                },
            ],
        ],
    }),
    j = _({
        name: `upload`,
        size: 24,
        node: [
            [`path`, { d: `M12 3v12`, key: `1x0j5s` }],
            [`path`, { d: `m17 8-5-5-5 5`, key: `7q97r8` }],
            [
                `path`,
                {
                    d: `M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4`,
                    key: `ih7n3h`,
                },
            ],
        ],
    }),
    M = { key: 0, class: `@container/list` },
    N = { class: `grid gap-3 @md/list:grid-cols-2` },
    P = [`aria-label`, `onClick`],
    F = [`src`, `alt`],
    I = { class: `min-w-0 flex-1` },
    L = { class: `truncate text-sm font-medium` },
    R = { class: `mt-0.5 text-xs text-muted-foreground` },
    te = { class: `flex shrink-0 items-center gap-1` },
    z = [`href`, `aria-label`],
    B = [`aria-label`, `onClick`],
    V = { key: 1, class: `text-sm text-muted-foreground` },
    H = { class: `max-h-[70vh] overflow-auto rounded-lg bg-muted` },
    U = [`src`, `alt`],
    W = [`src`, `title`],
    G = {
        key: 2,
        class: `flex flex-col items-center gap-2 p-10 text-center text-sm text-muted-foreground`,
    },
    K = { class: `flex justify-end` },
    q = [`href`],
    J = h({
        __name: `AttachmentPanel`,
        props: {
            attachments: {},
            uploadUrl: {},
            heading: { default: `المرفقات` },
            subheading: {
                default: `صور أو ملفات PDF — فاتورة المورد، إيصال التحويل، بوليصة الشحن`,
            },
        },
        setup(h) {
            let _ = h,
                J = o(null),
                Y = o(null),
                X = p({ file: null, notes: `` });
            function Z() {
                J.value?.click();
            }
            function Q(e) {
                let t = e.target,
                    n = t.files?.[0];
                n &&
                    ((X.file = n),
                    X.post(_.uploadUrl, {
                        preserveScroll: !0,
                        forceFormData: !0,
                        onFinish: () => {
                            (X.reset(), (t.value = ``));
                        },
                    }));
            }
            function ne(e) {
                window.confirm(`متأكد إنك عايز تحذف «${e.name}»؟`) &&
                    f.delete(`/attachments/${e.id}`, { preserveScroll: !0 });
            }
            function $(e) {
                return e >= 1048576
                    ? `${x(e / 1048576, 1)} ميجا`
                    : `${x(e / 1024)} كيلو`;
            }
            return (o, f) => (
                e(),
                a(
                    C,
                    {
                        title: h.heading ?? void 0,
                        description: h.subheading ?? void 0,
                    },
                    {
                        actions: i(() => [
                            l(
                                m(g),
                                {
                                    variant: `outline`,
                                    size: `sm`,
                                    disabled: m(X).processing,
                                    'data-test': `attach-button`,
                                    onClick: Z,
                                },
                                {
                                    default: i(() => [
                                        m(X).processing
                                            ? (e(), a(m(ee), { key: 0 }))
                                            : (e(),
                                              a(m(j), {
                                                  key: 1,
                                                  class: `size-4`,
                                              })),
                                        (f[1] ||= d(` رفع مرفق `, -1)),
                                    ]),
                                    _: 1,
                                },
                                8,
                                [`disabled`],
                            ),
                            s(
                                `input`,
                                {
                                    ref_key: `fileInput`,
                                    ref: J,
                                    type: `file`,
                                    class: `hidden`,
                                    accept: `image/jpeg,image/png,image/webp,application/pdf`,
                                    'data-test': `attach-input`,
                                    onChange: Q,
                                },
                                null,
                                544,
                            ),
                        ]),
                        default: i(() => [
                            l(
                                S,
                                { class: `mb-3`, message: m(X).errors.file },
                                null,
                                8,
                                [`message`],
                            ),
                            h.attachments.length
                                ? (e(),
                                  t(`div`, M, [
                                      s(`ul`, N, [
                                          (e(!0),
                                          t(
                                              r,
                                              null,
                                              n(
                                                  h.attachments,
                                                  (n) => (
                                                      e(),
                                                      t(
                                                          `li`,
                                                          {
                                                              key: n.id,
                                                              class: `flex items-center gap-3 rounded-lg border border-border p-3`,
                                                          },
                                                          [
                                                              s(
                                                                  `button`,
                                                                  {
                                                                      type: `button`,
                                                                      class: `flex size-12 shrink-0 items-center justify-center overflow-hidden rounded-md bg-muted text-muted-foreground`,
                                                                      'aria-label': `معاينة ${n.name}`,
                                                                      onClick: (
                                                                          e,
                                                                      ) =>
                                                                          (Y.value =
                                                                              n),
                                                                  },
                                                                  [
                                                                      n.isImage
                                                                          ? (e(),
                                                                            t(
                                                                                `img`,
                                                                                {
                                                                                    key: 0,
                                                                                    src: n.previewUrl,
                                                                                    alt: n.name,
                                                                                    class: `size-full object-cover`,
                                                                                },
                                                                                null,
                                                                                8,
                                                                                F,
                                                                            ))
                                                                          : (e(),
                                                                            a(
                                                                                m(
                                                                                    v,
                                                                                ),
                                                                                {
                                                                                    key: 1,
                                                                                    class: `size-5`,
                                                                                },
                                                                            )),
                                                                  ],
                                                                  8,
                                                                  P,
                                                              ),
                                                              s(`div`, I, [
                                                                  s(
                                                                      `p`,
                                                                      L,
                                                                      u(n.name),
                                                                      1,
                                                                  ),
                                                                  s(`p`, R, [
                                                                      d(
                                                                          u(
                                                                              $(
                                                                                  n.size,
                                                                              ),
                                                                          ) +
                                                                              ` `,
                                                                          1,
                                                                      ),
                                                                      n.uploadedAt
                                                                          ? (e(),
                                                                            t(
                                                                                r,
                                                                                {
                                                                                    key: 0,
                                                                                },
                                                                                [
                                                                                    d(
                                                                                        ` · ` +
                                                                                            u(
                                                                                                m(
                                                                                                    b,
                                                                                                )(
                                                                                                    n.uploadedAt,
                                                                                                ),
                                                                                            ),
                                                                                        1,
                                                                                    ),
                                                                                ],
                                                                                64,
                                                                            ))
                                                                          : c(
                                                                                ``,
                                                                                !0,
                                                                            ),
                                                                      n.uploadedBy
                                                                          ? (e(),
                                                                            t(
                                                                                r,
                                                                                {
                                                                                    key: 1,
                                                                                },
                                                                                [
                                                                                    d(
                                                                                        ` · ` +
                                                                                            u(
                                                                                                n.uploadedBy,
                                                                                            ),
                                                                                        1,
                                                                                    ),
                                                                                ],
                                                                                64,
                                                                            ))
                                                                          : c(
                                                                                ``,
                                                                                !0,
                                                                            ),
                                                                  ]),
                                                              ]),
                                                              s(`div`, te, [
                                                                  s(
                                                                      `a`,
                                                                      {
                                                                          href: n.downloadUrl,
                                                                          class: `flex size-8 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-accent hover:text-foreground`,
                                                                          'aria-label': `تحميل ${n.name}`,
                                                                      },
                                                                      [
                                                                          l(
                                                                              m(
                                                                                  k,
                                                                              ),
                                                                              {
                                                                                  class: `size-4`,
                                                                              },
                                                                          ),
                                                                      ],
                                                                      8,
                                                                      z,
                                                                  ),
                                                                  s(
                                                                      `button`,
                                                                      {
                                                                          type: `button`,
                                                                          class: `flex size-8 items-center justify-center rounded-md text-muted-foreground transition-colors hover:bg-destructive/10 hover:text-destructive`,
                                                                          'aria-label': `حذف ${n.name}`,
                                                                          onClick:
                                                                              (
                                                                                  e,
                                                                              ) =>
                                                                                  ne(
                                                                                      n,
                                                                                  ),
                                                                      },
                                                                      [
                                                                          l(
                                                                              m(
                                                                                  y,
                                                                              ),
                                                                              {
                                                                                  class: `size-4`,
                                                                              },
                                                                          ),
                                                                      ],
                                                                      8,
                                                                      B,
                                                                  ),
                                                              ]),
                                                          ],
                                                      )
                                                  ),
                                              ),
                                              128,
                                          )),
                                      ]),
                                  ]))
                                : (e(),
                                  t(
                                      `p`,
                                      V,
                                      ` مفيش مرفقات. ارفع صورة أو PDF للفاتورة أو إيصال التحويل. `,
                                  )),
                            l(
                                m(D),
                                {
                                    open: Y.value !== null,
                                    'onUpdate:open': (f[0] ||= (e) =>
                                        (Y.value = null)),
                                },
                                {
                                    default: i(() => [
                                        l(
                                            m(w),
                                            { class: `max-w-3xl` },
                                            {
                                                default: i(() => [
                                                    l(m(E), null, {
                                                        default: i(() => [
                                                            l(
                                                                m(O),
                                                                {
                                                                    class: `truncate`,
                                                                },
                                                                {
                                                                    default: i(
                                                                        () => [
                                                                            d(
                                                                                u(
                                                                                    Y
                                                                                        .value
                                                                                        ?.name,
                                                                                ),
                                                                                1,
                                                                            ),
                                                                        ],
                                                                    ),
                                                                    _: 1,
                                                                },
                                                            ),
                                                            l(m(T), null, {
                                                                default: i(
                                                                    () => [
                                                                        d(
                                                                            u(
                                                                                Y.value
                                                                                    ? $(
                                                                                          Y
                                                                                              .value
                                                                                              .size,
                                                                                      )
                                                                                    : ``,
                                                                            ) +
                                                                                ` `,
                                                                            1,
                                                                        ),
                                                                        Y.value
                                                                            ?.notes
                                                                            ? (e(),
                                                                              t(
                                                                                  r,
                                                                                  {
                                                                                      key: 0,
                                                                                  },
                                                                                  [
                                                                                      d(
                                                                                          ` · ` +
                                                                                              u(
                                                                                                  Y
                                                                                                      .value
                                                                                                      .notes,
                                                                                              ),
                                                                                          1,
                                                                                      ),
                                                                                  ],
                                                                                  64,
                                                                              ))
                                                                            : c(
                                                                                  ``,
                                                                                  !0,
                                                                              ),
                                                                    ],
                                                                ),
                                                                _: 1,
                                                            }),
                                                        ]),
                                                        _: 1,
                                                    }),
                                                    s(`div`, H, [
                                                        Y.value?.isImage
                                                            ? (e(),
                                                              t(
                                                                  `img`,
                                                                  {
                                                                      key: 0,
                                                                      src: Y
                                                                          .value
                                                                          .previewUrl,
                                                                      alt: Y
                                                                          .value
                                                                          .name,
                                                                      class: `mx-auto block`,
                                                                  },
                                                                  null,
                                                                  8,
                                                                  U,
                                                              ))
                                                            : Y.value?.isPdf
                                                              ? (e(),
                                                                t(
                                                                    `iframe`,
                                                                    {
                                                                        key: 1,
                                                                        src: Y
                                                                            .value
                                                                            .previewUrl,
                                                                        class: `h-[70vh] w-full`,
                                                                        title: Y
                                                                            .value
                                                                            .name,
                                                                    },
                                                                    null,
                                                                    8,
                                                                    W,
                                                                ))
                                                              : (e(),
                                                                t(`div`, G, [
                                                                    l(m(A), {
                                                                        class: `size-6`,
                                                                    }),
                                                                    (f[2] ||= d(
                                                                        ` مش قادرين نعرض الملف ده — حمّله عشان تفتحه. `,
                                                                        -1,
                                                                    )),
                                                                ])),
                                                    ]),
                                                    s(`div`, K, [
                                                        Y.value
                                                            ? (e(),
                                                              a(
                                                                  m(g),
                                                                  {
                                                                      key: 0,
                                                                      'as-child': ``,
                                                                      variant: `outline`,
                                                                  },
                                                                  {
                                                                      default:
                                                                          i(
                                                                              () => [
                                                                                  s(
                                                                                      `a`,
                                                                                      {
                                                                                          href: Y
                                                                                              .value
                                                                                              .downloadUrl,
                                                                                      },
                                                                                      [
                                                                                          l(
                                                                                              m(
                                                                                                  k,
                                                                                              ),
                                                                                              {
                                                                                                  class: `size-4`,
                                                                                              },
                                                                                          ),
                                                                                          (f[3] ||=
                                                                                              d(
                                                                                                  ` تحميل `,
                                                                                                  -1,
                                                                                              )),
                                                                                      ],
                                                                                      8,
                                                                                      q,
                                                                                  ),
                                                                              ],
                                                                          ),
                                                                      _: 1,
                                                                  },
                                                              ))
                                                            : c(``, !0),
                                                    ]),
                                                ]),
                                                _: 1,
                                            },
                                        ),
                                    ]),
                                    _: 1,
                                },
                                8,
                                [`open`],
                            ),
                        ]),
                        _: 1,
                    },
                    8,
                    [`title`, `description`],
                )
            );
        },
    });
export { J as t };
