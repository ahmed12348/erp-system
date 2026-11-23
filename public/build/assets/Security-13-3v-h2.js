import {
    $t as e,
    Bt as t,
    Dn as n,
    J as r,
    Kt as i,
    Mt as a,
    Nt as o,
    Q as s,
    Qt as c,
    R as l,
    Tn as u,
    Wt as d,
    X as f,
    Xn as p,
    Y as m,
    Z as h,
    _t as g,
    at as _,
    bn as v,
    dr as ee,
    fr as y,
    gn as te,
    it as b,
    jt as x,
    lr as S,
    mr as C,
    n as w,
    nn as ne,
    or as T,
    pn as re,
    r as ie,
    s as E,
    st as D,
    tn as ae,
    ur as O,
} from './dist-C0kHilBU.js';
import { a as k, t as A } from './button-BRBVuDNP.js';
import { t as oe } from './DialogTrigger-BSfqUQoo.js';
import { t as j } from './Label-BhHH3wmI.js';
import { t as M } from './createLucideIcon-C6NZ5pDL.js';
import { n as se, t as ce } from './PasswordInput-DxXAjafG.js';
import { t as le } from './eye-BHrWNse5.js';
import { r as ue, t as de } from './vue-cU7sZfTa.js';
import { t as fe } from './scan-line-B0xV_6Oy.js';
import { t as pe } from './trash-DJlV-Hhi.js';
import { t as me } from './Input-iZAg09j4.js';
import { n as N, t as he } from './wayfinder-Bh70ToPC.js';
import {
    Bt as ge,
    U as _e,
    _t as ve,
    at as ye,
    d as be,
    p as xe,
} from './app-CZp4sxGj.js';
import { t as P } from './InputError-_9qlcKk2.js';
import { t as Se } from './Spinner-KEXvnTRx.js';
import { n as Ce, t as we } from './alert-DV-aNR-6.js';
import { a as Te, i as Ee, r as De, t as Oe } from './login-qRzzv-bN.js';
import {
    a as ke,
    i as Ae,
    n as je,
    o as Me,
    r as Ne,
    t as Pe,
} from './dialog-B-IA8Lzj.js';
import { t as Fe } from './DialogClose-DEdGo09K.js';
var Ie = M({
        name: `circle-exclamation-point`,
        size: 24,
        node: [
            [`circle`, { cx: `12`, cy: `12`, r: `10`, key: `1mglay` }],
            [`line`, { x1: `12`, x2: `12`, y1: `8`, y2: `12`, key: `1pkeuh` }],
            [
                `line`,
                { x1: `12`, x2: `12.01`, y1: `16`, y2: `16`, key: `4dfq90` },
            ],
        ],
        aliases: [`alert-circle`, `circle-alert`],
    }),
    Le = M({
        name: `copy`,
        size: 24,
        node: [
            [
                `rect`,
                {
                    width: `14`,
                    height: `14`,
                    x: `8`,
                    y: `8`,
                    rx: `2`,
                    ry: `2`,
                    key: `17jyea`,
                },
            ],
            [
                `path`,
                {
                    d: `M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2`,
                    key: `zix9uf`,
                },
            ],
        ],
    }),
    Re = M({
        name: `lock-keyhole`,
        size: 24,
        node: [
            [`circle`, { cx: `12`, cy: `16`, r: `1`, key: `1au0dj` }],
            [
                `rect`,
                {
                    x: `3`,
                    y: `10`,
                    width: `18`,
                    height: `12`,
                    rx: `2`,
                    key: `6s8ecr`,
                },
            ],
            [`path`, { d: `M7 10V7a5 5 0 0 1 10 0v3`, key: `1pqi11` }],
        ],
    }),
    ze = M({
        name: `refresh-cw`,
        size: 24,
        node: [
            [
                `path`,
                {
                    d: `M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8`,
                    key: `v9h5vc`,
                },
            ],
            [`path`, { d: `M21 3v5h-5`, key: `1q7to0` }],
            [
                `path`,
                {
                    d: `M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16`,
                    key: `3uifl3`,
                },
            ],
            [`path`, { d: `M8 16H3v5`, key: `1cv678` }],
        ],
    }),
    Be = D({
        __name: `AlertTitle`,
        props: { class: { type: [Boolean, null, String, Object, Array] } },
        setup(t) {
            let n = t;
            return (t, r) => (
                i(),
                s(
                    `div`,
                    {
                        'data-slot': `alert-title`,
                        class: S(
                            T(k)(
                                `col-start-2 line-clamp-1 min-h-4 font-medium tracking-tight`,
                                n.class,
                            ),
                        ),
                    },
                    [e(t.$slots, `default`)],
                    2,
                )
            );
        },
    }),
    F = (e) => ({ url: F.url(e), method: `get` });
((F.definition = { methods: [`get`, `head`], url: `/settings/security` }),
    (F.url = (e) => F.definition.url + N(e)),
    (F.get = (e) => ({ url: F.url(e), method: `get` })),
    (F.head = (e) => ({ url: F.url(e), method: `head` })));
var Ve = (e) => ({ action: F.url(e), method: `get` });
((Ve.get = (e) => ({ action: F.url(e), method: `get` })),
    (Ve.head = (e) => ({
        action: F.url({
            [e?.mergeQuery ? `mergeQuery` : `query`]: {
                _method: `HEAD`,
                ...(e?.query ?? e?.mergeQuery ?? {}),
            },
        }),
        method: `get`,
    })),
    (F.form = Ve));
var I = (e) => ({ url: I.url(e), method: `put` });
((I.definition = { methods: [`put`], url: `/settings/password` }),
    (I.url = (e) => I.definition.url + N(e)),
    (I.put = (e) => ({ url: I.url(e), method: `put` })));
var He = (e) => ({
    action: I.url({
        [e?.mergeQuery ? `mergeQuery` : `query`]: {
            _method: `PUT`,
            ...(e?.query ?? e?.mergeQuery ?? {}),
        },
    }),
    method: `post`,
});
((He.put = (e) => ({
    action: I.url({
        [e?.mergeQuery ? `mergeQuery` : `query`]: {
            _method: `PUT`,
            ...(e?.query ?? e?.mergeQuery ?? {}),
        },
    }),
    method: `post`,
})),
    (I.form = He));
var Ue = { edit: F, update: I },
    We = {
        class: `flex items-center justify-between border-b p-4 last:border-b-0`,
    },
    Ge = { class: `flex items-center gap-4` },
    Ke = {
        class: `flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-muted`,
    },
    qe = { class: `space-y-1` },
    Je = { class: `flex items-center gap-2.5` },
    Ye = { class: `font-medium tracking-tight` },
    Xe = {
        key: 0,
        class: `inline-flex items-center gap-1 rounded-md bg-muted px-2 py-0.5 text-[11px] font-medium tracking-wide text-muted-foreground uppercase ring-1 ring-border ring-inset`,
    },
    Ze = { class: `text-sm text-muted-foreground` },
    Qe = D({
        __name: `PasskeyItem`,
        props: { passkey: {} },
        emits: [`remove`],
        setup(e, { emit: t }) {
            let n = e,
                r = t,
                a = p(!1),
                o = () => {
                    ((a.value = !0),
                        r(`remove`, n.passkey.id, () => {
                            a.value = !1;
                        }));
                };
            return (t, n) => (
                i(),
                s(`div`, We, [
                    m(`div`, Ge, [
                        m(`div`, Ke, [
                            _(T(ue), {
                                class: `h-5 w-5 text-muted-foreground`,
                            }),
                        ]),
                        m(`div`, qe, [
                            m(`div`, Je, [
                                m(`p`, Ye, y(e.passkey.name), 1),
                                e.passkey.authenticator
                                    ? (i(),
                                      s(
                                          `span`,
                                          Xe,
                                          y(e.passkey.authenticator),
                                          1,
                                      ))
                                    : h(``, !0),
                            ]),
                            m(`p`, Ze, [
                                b(
                                    ` Added ` +
                                        y(e.passkey.created_at_diff) +
                                        ` `,
                                    1,
                                ),
                                e.passkey.last_used_at_diff
                                    ? (i(),
                                      s(
                                          l,
                                          { key: 0 },
                                          [
                                              (n[0] ||= m(
                                                  `span`,
                                                  {
                                                      class: `mx-1 text-muted-foreground/50`,
                                                  },
                                                  `/`,
                                                  -1,
                                              )),
                                              b(
                                                  ` Last used ` +
                                                      y(
                                                          e.passkey
                                                              .last_used_at_diff,
                                                      ),
                                                  1,
                                              ),
                                          ],
                                          64,
                                      ))
                                    : h(``, !0),
                            ]),
                        ]),
                    ]),
                    _(T(Me), null, {
                        default: u(() => [
                            _(
                                T(oe),
                                { 'as-child': `` },
                                {
                                    default: u(() => [
                                        _(
                                            T(A),
                                            {
                                                variant: `ghost`,
                                                size: `sm`,
                                                class: `text-destructive hover:bg-destructive/10 hover:text-destructive`,
                                            },
                                            {
                                                default: u(() => [
                                                    _(T(pe), {
                                                        class: `h-4 w-4`,
                                                    }),
                                                    (n[1] ||= m(
                                                        `span`,
                                                        { class: `sr-only` },
                                                        `Remove`,
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
                            _(T(ke), null, {
                                default: u(() => [
                                    _(T(Pe), null, {
                                        default: u(() => [
                                            ...(n[2] ||= [
                                                b(`Remove passkey`, -1),
                                            ]),
                                        ]),
                                        _: 1,
                                    }),
                                    _(T(Ae), null, {
                                        default: u(() => [
                                            b(
                                                ` Are you sure you want to remove the "` +
                                                    y(e.passkey.name) +
                                                    `" passkey? You will no longer be able to use it to sign in. `,
                                                1,
                                            ),
                                        ]),
                                        _: 1,
                                    }),
                                    _(
                                        T(Ne),
                                        { class: `gap-2` },
                                        {
                                            default: u(() => [
                                                _(
                                                    T(Fe),
                                                    { 'as-child': `` },
                                                    {
                                                        default: u(() => [
                                                            _(
                                                                T(A),
                                                                {
                                                                    variant: `secondary`,
                                                                },
                                                                {
                                                                    default: u(
                                                                        () => [
                                                                            ...(n[3] ||=
                                                                                [
                                                                                    b(
                                                                                        `Cancel`,
                                                                                        -1,
                                                                                    ),
                                                                                ]),
                                                                        ],
                                                                    ),
                                                                    _: 1,
                                                                },
                                                            ),
                                                        ]),
                                                        _: 1,
                                                    },
                                                ),
                                                _(
                                                    T(A),
                                                    {
                                                        variant: `destructive`,
                                                        disabled: a.value,
                                                        onClick: o,
                                                    },
                                                    {
                                                        default: u(() => [
                                                            b(
                                                                y(
                                                                    a.value
                                                                        ? `Removing...`
                                                                        : `Remove passkey`,
                                                                ),
                                                                1,
                                                            ),
                                                        ]),
                                                        _: 1,
                                                    },
                                                    8,
                                                    [`disabled`],
                                                ),
                                            ]),
                                            _: 1,
                                        },
                                    ),
                                ]),
                                _: 1,
                            }),
                        ]),
                        _: 1,
                    }),
                ])
            );
        },
    }),
    $e = { key: 0, class: `text-sm text-muted-foreground` },
    et = { class: `grid gap-2` },
    tt = { class: `flex gap-2` },
    nt = D({
        __name: `PasskeyRegister`,
        emits: [`success`],
        setup(e, { emit: t }) {
            let r = t,
                a = p(
                    (() => {
                        let e = navigator.userAgent;
                        return (
                            [
                                [
                                    { pattern: /Edg|Edge/, name: `Edge` },
                                    {
                                        pattern: /OPR|Opera|OPiOS/,
                                        name: `Opera`,
                                    },
                                    {
                                        pattern: /Firefox|FxiOS/,
                                        name: `Firefox`,
                                    },
                                    { pattern: /Chrome|CriOS/, name: `Chrome` },
                                    { pattern: /Safari/, name: `Safari` },
                                ].find(({ pattern: t }) => t.test(e))?.name,
                                [
                                    { pattern: /iPhone/, name: `iPhone` },
                                    {
                                        pattern: /iPad|Macintosh(?=.*Mobile)/,
                                        name: `iPad`,
                                    },
                                    { pattern: /Android/, name: `Android` },
                                    { pattern: /Mac/, name: `Mac` },
                                    { pattern: /Windows/, name: `Windows` },
                                ].find(({ pattern: t }) => t.test(e))?.name,
                            ]
                                .filter(Boolean)
                                .join(` on `) || ``
                        );
                    })(),
                ),
                o = p(!1),
                {
                    register: c,
                    isLoading: l,
                    error: d,
                    isSupported: g,
                } = de({
                    onSuccess: () => {
                        ((a.value = ``), (o.value = !1), r(`success`));
                    },
                }),
                v = async (e) => {
                    (e.preventDefault(), a.value.trim() && (await c(a.value)));
                },
                ee = () => {
                    ((o.value = !1), (a.value = ``));
                };
            return (e, t) => {
                let r = ae(`focus`);
                return T(g)
                    ? o.value
                        ? (i(),
                          s(
                              `form`,
                              {
                                  key: 2,
                                  onSubmit: v,
                                  class: `space-y-4 rounded-lg border border-border bg-muted/50 p-4`,
                              },
                              [
                                  m(`div`, et, [
                                      _(
                                          T(j),
                                          { for: `passkey-name` },
                                          {
                                              default: u(() => [
                                                  ...(t[3] ||= [
                                                      b(`Passkey name`, -1),
                                                  ]),
                                              ]),
                                              _: 1,
                                          },
                                      ),
                                      n(
                                          _(
                                              T(me),
                                              {
                                                  id: `passkey-name`,
                                                  type: `text`,
                                                  modelValue: a.value,
                                                  'onUpdate:modelValue':
                                                      (t[1] ||= (e) =>
                                                          (a.value = e)),
                                                  placeholder: `e.g., MacBook Pro, iPhone`,
                                                  class: `mt-1 block w-full border-foreground/20`,
                                              },
                                              null,
                                              8,
                                              [`modelValue`],
                                          ),
                                          [[r]],
                                      ),
                                      (t[4] ||= m(
                                          `p`,
                                          {
                                              class: `text-xs text-muted-foreground`,
                                          },
                                          ` A name helps you identify this passkey later. `,
                                          -1,
                                      )),
                                  ]),
                                  T(d)
                                      ? (i(),
                                        f(
                                            P,
                                            { key: 0, message: T(d) },
                                            null,
                                            8,
                                            [`message`],
                                        ))
                                      : h(``, !0),
                                  m(`div`, tt, [
                                      _(
                                          T(A),
                                          {
                                              type: `submit`,
                                              disabled: T(l) || !a.value.trim(),
                                          },
                                          {
                                              default: u(() => [
                                                  b(
                                                      y(
                                                          T(l)
                                                              ? `Registering...`
                                                              : `Register passkey`,
                                                      ),
                                                      1,
                                                  ),
                                              ]),
                                              _: 1,
                                          },
                                          8,
                                          [`disabled`],
                                      ),
                                      _(
                                          T(A),
                                          {
                                              type: `button`,
                                              variant: `ghost`,
                                              onClick: ee,
                                          },
                                          {
                                              default: u(() => [
                                                  ...(t[5] ||= [
                                                      b(` Cancel `, -1),
                                                  ]),
                                              ]),
                                              _: 1,
                                          },
                                      ),
                                  ]),
                              ],
                              32,
                          ))
                        : (i(),
                          f(
                              T(A),
                              {
                                  key: 1,
                                  variant: `outline`,
                                  onClick: (t[0] ||= (e) => (o.value = !0)),
                              },
                              {
                                  default: u(() => [
                                      ...(t[2] ||= [b(` Add passkey `, -1)]),
                                  ]),
                                  _: 1,
                              },
                          ))
                    : (i(),
                      s(
                          `div`,
                          $e,
                          ` Passkeys are not supported in this browser. `,
                      ));
            };
        },
    }),
    L = (e) => ({ url: L.url(e), method: `get` });
((L.definition = { methods: [`get`, `head`], url: `/user/passkeys/options` }),
    (L.url = (e) => L.definition.url + N(e)),
    (L.get = (e) => ({ url: L.url(e), method: `get` })),
    (L.head = (e) => ({ url: L.url(e), method: `head` })));
var R = (e) => ({ action: L.url(e), method: `get` });
((R.get = (e) => ({ action: L.url(e), method: `get` })),
    (R.head = (e) => ({
        action: L.url({
            [e?.mergeQuery ? `mergeQuery` : `query`]: {
                _method: `HEAD`,
                ...(e?.query ?? e?.mergeQuery ?? {}),
            },
        }),
        method: `get`,
    })),
    (L.form = R));
var z = (e) => ({ url: z.url(e), method: `post` });
((z.definition = { methods: [`post`], url: `/user/passkeys` }),
    (z.url = (e) => z.definition.url + N(e)),
    (z.post = (e) => ({ url: z.url(e), method: `post` })));
var rt = (e) => ({ action: z.url(e), method: `post` });
((rt.post = (e) => ({ action: z.url(e), method: `post` })), (z.form = rt));
var B = (e, t) => ({ url: B.url(e, t), method: `delete` });
((B.definition = { methods: [`delete`], url: `/user/passkeys/{passkey}` }),
    (B.url = (e, t) => {
        ((typeof e == `string` || typeof e == `number`) && (e = { passkey: e }),
            typeof e == `object` &&
                !Array.isArray(e) &&
                `id` in e &&
                (e = { passkey: e.id }),
            Array.isArray(e) && (e = { passkey: e[0] }),
            (e = he(e)));
        let n = {
            passkey: typeof e.passkey == `object` ? e.passkey.id : e.passkey,
        };
        return (
            B.definition.url
                .replace(`{passkey}`, n.passkey.toString())
                .replace(/\/+$/, ``) + N(t)
        );
    }),
    (B.delete = (e, t) => ({ url: B.url(e, t), method: `delete` })));
var it = (e, t) => ({
    action: B.url(e, {
        [t?.mergeQuery ? `mergeQuery` : `query`]: {
            _method: `DELETE`,
            ...(t?.query ?? t?.mergeQuery ?? {}),
        },
    }),
    method: `post`,
});
((it.delete = (e, t) => ({
    action: B.url(e, {
        [t?.mergeQuery ? `mergeQuery` : `query`]: {
            _method: `DELETE`,
            ...(t?.query ?? t?.mergeQuery ?? {}),
        },
    }),
    method: `post`,
})),
    (B.form = it));
var at = { key: 0, class: `space-y-6` },
    ot = { class: `overflow-hidden rounded-lg border border-border` },
    st = { key: 1, class: `p-8 text-center` },
    ct = {
        class: `mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-muted`,
    },
    lt = D({
        __name: `ManagePasskeys`,
        props: {
            canManagePasskeys: { type: Boolean, default: !1 },
            passkeys: { default: () => [] },
        },
        setup(e) {
            let t = (e, t) => {
                    C.delete(B.url(e), { preserveScroll: !0, onError: t });
                },
                n = () => {
                    C.reload();
                };
            return (r, a) =>
                e.canManagePasskeys
                    ? (i(),
                      s(`div`, at, [
                          _(xe, {
                              variant: `small`,
                              title: `Passkeys`,
                              description: `Manage your passkeys for passwordless sign-in`,
                          }),
                          m(`div`, ot, [
                              e.passkeys.length
                                  ? (i(!0),
                                    s(
                                        l,
                                        { key: 0 },
                                        c(
                                            e.passkeys,
                                            (e) => (
                                                i(),
                                                f(
                                                    Qe,
                                                    {
                                                        key: e.id,
                                                        passkey: e,
                                                        onRemove: t,
                                                    },
                                                    null,
                                                    8,
                                                    [`passkey`],
                                                )
                                            ),
                                        ),
                                        128,
                                    ))
                                  : (i(),
                                    s(`div`, st, [
                                        m(`div`, ct, [
                                            _(T(ue), {
                                                class: `h-7 w-7 text-muted-foreground`,
                                            }),
                                        ]),
                                        (a[0] ||= m(
                                            `p`,
                                            { class: `font-medium` },
                                            `No passkeys yet`,
                                            -1,
                                        )),
                                        (a[1] ||= m(
                                            `p`,
                                            {
                                                class: `mt-1 text-sm text-muted-foreground`,
                                            },
                                            ` Add a passkey to sign in without a password `,
                                            -1,
                                        )),
                                    ])),
                          ]),
                          _(nt, { onSuccess: n }),
                      ]))
                    : h(``, !0);
        },
    }),
    ut = { class: `list-inside list-disc text-sm` },
    dt = D({
        __name: `AlertError`,
        props: { errors: {}, title: { default: `Something went wrong.` } },
        setup(e) {
            let t = e,
                n = r(() => Array.from(new Set(t.errors)));
            return (t, r) => (
                i(),
                f(
                    T(Ce),
                    { variant: `destructive` },
                    {
                        default: u(() => [
                            _(T(Ie), { class: `size-4` }),
                            _(T(Be), null, {
                                default: u(() => [b(y(e.title), 1)]),
                                _: 1,
                            }),
                            _(T(we), null, {
                                default: u(() => [
                                    m(`ul`, ut, [
                                        (i(!0),
                                        s(
                                            l,
                                            null,
                                            c(
                                                n.value,
                                                (e, t) => (
                                                    i(),
                                                    s(`li`, { key: t }, y(e), 1)
                                                ),
                                            ),
                                            128,
                                        )),
                                    ]),
                                ]),
                                _: 1,
                            }),
                        ]),
                        _: 1,
                    },
                )
            );
        },
    }),
    ft = D({
        __name: `Card`,
        props: { class: { type: [Boolean, null, String, Object, Array] } },
        setup(t) {
            let n = t;
            return (t, r) => (
                i(),
                s(
                    `div`,
                    {
                        'data-slot': `card`,
                        class: S(
                            T(k)(
                                `bg-card text-card-foreground flex flex-col gap-6 rounded-xl border py-6 shadow-sm`,
                                n.class,
                            ),
                        ),
                    },
                    [e(t.$slots, `default`)],
                    2,
                )
            );
        },
    }),
    pt = D({
        __name: `CardContent`,
        props: { class: { type: [Boolean, null, String, Object, Array] } },
        setup(t) {
            let n = t;
            return (t, r) => (
                i(),
                s(
                    `div`,
                    {
                        'data-slot': `card-content`,
                        class: S(T(k)(`px-6`, n.class)),
                    },
                    [e(t.$slots, `default`)],
                    2,
                )
            );
        },
    }),
    mt = D({
        __name: `CardDescription`,
        props: { class: { type: [Boolean, null, String, Object, Array] } },
        setup(t) {
            let n = t;
            return (t, r) => (
                i(),
                s(
                    `p`,
                    {
                        'data-slot': `card-description`,
                        class: S(
                            T(k)(`text-muted-foreground text-sm`, n.class),
                        ),
                    },
                    [e(t.$slots, `default`)],
                    2,
                )
            );
        },
    }),
    ht = D({
        __name: `CardHeader`,
        props: { class: { type: [Boolean, null, String, Object, Array] } },
        setup(t) {
            let n = t;
            return (t, r) => (
                i(),
                s(
                    `div`,
                    {
                        'data-slot': `card-header`,
                        class: S(
                            T(k)(
                                `@container/card-header grid auto-rows-min grid-rows-[auto_auto] items-start gap-1.5 px-6 has-data-[slot=card-action]:grid-cols-[1fr_auto] [.border-b]:pb-6`,
                                n.class,
                            ),
                        ),
                    },
                    [e(t.$slots, `default`)],
                    2,
                )
            );
        },
    }),
    gt = D({
        __name: `CardTitle`,
        props: { class: { type: [Boolean, null, String, Object, Array] } },
        setup(t) {
            let n = t;
            return (t, r) => (
                i(),
                s(
                    `h3`,
                    {
                        'data-slot': `card-title`,
                        class: S(T(k)(`leading-none font-semibold`, n.class)),
                    },
                    [e(t.$slots, `default`)],
                    2,
                )
            );
        },
    }),
    V = (e) => ({ url: V.url(e), method: `get` });
((V.definition = { methods: [`get`, `head`], url: `/two-factor-challenge` }),
    (V.url = (e) => V.definition.url + N(e)),
    (V.get = (e) => ({ url: V.url(e), method: `get` })),
    (V.head = (e) => ({ url: V.url(e), method: `head` })));
var _t = (e) => ({ action: V.url(e), method: `get` });
((_t.get = (e) => ({ action: V.url(e), method: `get` })),
    (_t.head = (e) => ({
        action: V.url({
            [e?.mergeQuery ? `mergeQuery` : `query`]: {
                _method: `HEAD`,
                ...(e?.query ?? e?.mergeQuery ?? {}),
            },
        }),
        method: `get`,
    })),
    (V.form = _t));
var H = (e) => ({ url: H.url(e), method: `post` });
((H.definition = { methods: [`post`], url: `/user/two-factor-authentication` }),
    (H.url = (e) => H.definition.url + N(e)),
    (H.post = (e) => ({ url: H.url(e), method: `post` })));
var vt = (e) => ({ action: H.url(e), method: `post` });
((vt.post = (e) => ({ action: H.url(e), method: `post` })), (H.form = vt));
var U = (e) => ({ url: U.url(e), method: `post` });
((U.definition = {
    methods: [`post`],
    url: `/user/confirmed-two-factor-authentication`,
}),
    (U.url = (e) => U.definition.url + N(e)),
    (U.post = (e) => ({ url: U.url(e), method: `post` })));
var yt = (e) => ({ action: U.url(e), method: `post` });
((yt.post = (e) => ({ action: U.url(e), method: `post` })), (U.form = yt));
var W = (e) => ({ url: W.url(e), method: `delete` });
((W.definition = {
    methods: [`delete`],
    url: `/user/two-factor-authentication`,
}),
    (W.url = (e) => W.definition.url + N(e)),
    (W.delete = (e) => ({ url: W.url(e), method: `delete` })));
var bt = (e) => ({
    action: W.url({
        [e?.mergeQuery ? `mergeQuery` : `query`]: {
            _method: `DELETE`,
            ...(e?.query ?? e?.mergeQuery ?? {}),
        },
    }),
    method: `post`,
});
((bt.delete = (e) => ({
    action: W.url({
        [e?.mergeQuery ? `mergeQuery` : `query`]: {
            _method: `DELETE`,
            ...(e?.query ?? e?.mergeQuery ?? {}),
        },
    }),
    method: `post`,
})),
    (W.form = bt));
var G = (e) => ({ url: G.url(e), method: `get` });
((G.definition = { methods: [`get`, `head`], url: `/user/two-factor-qr-code` }),
    (G.url = (e) => G.definition.url + N(e)),
    (G.get = (e) => ({ url: G.url(e), method: `get` })),
    (G.head = (e) => ({ url: G.url(e), method: `head` })));
var xt = (e) => ({ action: G.url(e), method: `get` });
((xt.get = (e) => ({ action: G.url(e), method: `get` })),
    (xt.head = (e) => ({
        action: G.url({
            [e?.mergeQuery ? `mergeQuery` : `query`]: {
                _method: `HEAD`,
                ...(e?.query ?? e?.mergeQuery ?? {}),
            },
        }),
        method: `get`,
    })),
    (G.form = xt));
var K = (e) => ({ url: K.url(e), method: `get` });
((K.definition = {
    methods: [`get`, `head`],
    url: `/user/two-factor-secret-key`,
}),
    (K.url = (e) => K.definition.url + N(e)),
    (K.get = (e) => ({ url: K.url(e), method: `get` })),
    (K.head = (e) => ({ url: K.url(e), method: `head` })));
var q = (e) => ({ action: K.url(e), method: `get` });
((q.get = (e) => ({ action: K.url(e), method: `get` })),
    (q.head = (e) => ({
        action: K.url({
            [e?.mergeQuery ? `mergeQuery` : `query`]: {
                _method: `HEAD`,
                ...(e?.query ?? e?.mergeQuery ?? {}),
            },
        }),
        method: `get`,
    })),
    (K.form = q));
var J = (e) => ({ url: J.url(e), method: `get` });
((J.definition = {
    methods: [`get`, `head`],
    url: `/user/two-factor-recovery-codes`,
}),
    (J.url = (e) => J.definition.url + N(e)),
    (J.get = (e) => ({ url: J.url(e), method: `get` })),
    (J.head = (e) => ({ url: J.url(e), method: `head` })));
var St = (e) => ({ action: J.url(e), method: `get` });
((St.get = (e) => ({ action: J.url(e), method: `get` })),
    (St.head = (e) => ({
        action: J.url({
            [e?.mergeQuery ? `mergeQuery` : `query`]: {
                _method: `HEAD`,
                ...(e?.query ?? e?.mergeQuery ?? {}),
            },
        }),
        method: `get`,
    })),
    (J.form = St));
var Y = (e) => ({ url: Y.url(e), method: `post` });
((Y.definition = { methods: [`post`], url: `/user/two-factor-recovery-codes` }),
    (Y.url = (e) => Y.definition.url + N(e)),
    (Y.post = (e) => ({ url: Y.url(e), method: `post` })));
var Ct = (e) => ({ action: Y.url(e), method: `post` });
((Ct.post = (e) => ({ action: Y.url(e), method: `post` })),
    (Y.form = Ct),
    Object.assign(V, Oe),
    Object.assign(H, H),
    Object.assign(U, U),
    Object.assign(W, W),
    Object.assign(G, G),
    Object.assign(K, K),
    Object.assign(J, J),
    Object.assign(Y, Y));
var X = p([]),
    Z = p(null),
    Q = p(null),
    $ = p([]),
    wt = r(() => Q.value !== null && Z.value !== null),
    Tt = () => {
        let e = E(),
            t = async () => {
                try {
                    let { svg: t } = await e.submit(G());
                    Q.value = t;
                } catch {
                    (X.value.push(`Failed to fetch QR code`), (Q.value = null));
                }
            },
            n = async () => {
                try {
                    let { secretKey: t } = await e.submit(K());
                    Z.value = t;
                } catch {
                    (X.value.push(`Failed to fetch a setup key`),
                        (Z.value = null));
                }
            },
            r = () => {
                ((Z.value = null), (Q.value = null), i());
            },
            i = () => {
                X.value = [];
            };
        return {
            qrCodeSvg: Q,
            manualSetupKey: Z,
            recoveryCodesList: $,
            errors: X,
            hasSetupData: wt,
            clearSetupData: r,
            clearErrors: i,
            clearTwoFactorAuthData: () => {
                (r(), i(), ($.value = []));
            },
            fetchQrCode: t,
            fetchSetupKey: n,
            fetchSetupData: async () => {
                try {
                    (i(), await Promise.all([t(), n()]));
                } catch {
                    ((Q.value = null), (Z.value = null));
                }
            },
            fetchRecoveryCodes: async () => {
                try {
                    (i(), ($.value = await e.submit(J())));
                } catch {
                    (X.value.push(`Failed to fetch recovery codes`),
                        ($.value = []));
                }
            },
        };
    },
    Et = {
        class: `flex flex-col gap-3 select-none sm:flex-row sm:items-center sm:justify-between`,
    },
    Dt = { key: 0, class: `mt-6` },
    Ot = { key: 1, class: `mt-3 space-y-3` },
    kt = { key: 0, class: `space-y-2` },
    At = D({
        __name: `TwoFactorRecoveryCodes`,
        setup(e) {
            let {
                    recoveryCodesList: n,
                    fetchRecoveryCodes: r,
                    errors: d,
                } = Tt(),
                g = p(!1),
                v = te(`recoveryCodeSectionRef`),
                ee = async () => {
                    (!g.value && !n.value.length && (await r()),
                        (g.value = !g.value),
                        g.value &&
                            (await o(),
                            v.value?.scrollIntoView({ behavior: `smooth` })));
                };
            return (
                t(async () => {
                    n.value.length || (await r());
                }),
                (e, t) => (
                    i(),
                    f(
                        T(ft),
                        { class: `w-full` },
                        {
                            default: u(() => [
                                _(T(ht), null, {
                                    default: u(() => [
                                        _(
                                            T(gt),
                                            { class: `flex gap-3` },
                                            {
                                                default: u(() => [
                                                    _(T(Re), {
                                                        class: `size-4`,
                                                    }),
                                                    (t[0] ||= b(
                                                        `2FA recovery codes `,
                                                        -1,
                                                    )),
                                                ]),
                                                _: 1,
                                            },
                                        ),
                                        _(T(mt), null, {
                                            default: u(() => [
                                                ...(t[1] ||= [
                                                    b(
                                                        ` Recovery codes let you regain access if you lose your 2FA device. Store them in a secure password manager. `,
                                                        -1,
                                                    ),
                                                ]),
                                            ]),
                                            _: 1,
                                        }),
                                    ]),
                                    _: 1,
                                }),
                                _(T(pt), null, {
                                    default: u(() => [
                                        m(`div`, Et, [
                                            _(
                                                T(A),
                                                { onClick: ee, class: `w-fit` },
                                                {
                                                    default: u(() => [
                                                        (i(),
                                                        f(
                                                            ne(
                                                                g.value
                                                                    ? T(se)
                                                                    : T(le),
                                                            ),
                                                            { class: `size-4` },
                                                        )),
                                                        b(
                                                            ` ` +
                                                                y(
                                                                    g.value
                                                                        ? `Hide`
                                                                        : `View`,
                                                                ) +
                                                                ` recovery codes `,
                                                            1,
                                                        ),
                                                    ]),
                                                    _: 1,
                                                },
                                            ),
                                            g.value && T(n).length
                                                ? (i(),
                                                  f(
                                                      T(w),
                                                      a(
                                                          { key: 0 },
                                                          T(Y).form(),
                                                          {
                                                              method: `post`,
                                                              options: {
                                                                  preserveScroll:
                                                                      !0,
                                                              },
                                                              onSuccess: T(r),
                                                          },
                                                      ),
                                                      {
                                                          default: u(
                                                              ({
                                                                  processing: e,
                                                              }) => [
                                                                  _(
                                                                      T(A),
                                                                      {
                                                                          variant: `secondary`,
                                                                          type: `submit`,
                                                                          disabled:
                                                                              e,
                                                                      },
                                                                      {
                                                                          default:
                                                                              u(
                                                                                  () => [
                                                                                      _(
                                                                                          T(
                                                                                              ze,
                                                                                          ),
                                                                                      ),
                                                                                      (t[2] ||=
                                                                                          b(
                                                                                              ` Regenerate codes `,
                                                                                              -1,
                                                                                          )),
                                                                                  ],
                                                                              ),
                                                                          _: 1,
                                                                      },
                                                                      8,
                                                                      [
                                                                          `disabled`,
                                                                      ],
                                                                  ),
                                                              ],
                                                          ),
                                                          _: 1,
                                                      },
                                                      16,
                                                      [`onSuccess`],
                                                  ))
                                                : h(``, !0),
                                        ]),
                                        m(
                                            `div`,
                                            {
                                                class: S([
                                                    `relative overflow-hidden transition-all duration-300`,
                                                    g.value
                                                        ? `h-auto opacity-100`
                                                        : `h-0 opacity-0`,
                                                ]),
                                            },
                                            [
                                                T(d)?.length
                                                    ? (i(),
                                                      s(`div`, Dt, [
                                                          _(
                                                              dt,
                                                              { errors: T(d) },
                                                              null,
                                                              8,
                                                              [`errors`],
                                                          ),
                                                      ]))
                                                    : (i(),
                                                      s(`div`, Ot, [
                                                          m(
                                                              `div`,
                                                              {
                                                                  ref_key: `recoveryCodeSectionRef`,
                                                                  ref: v,
                                                                  class: `grid gap-1 rounded-lg bg-muted p-4 font-mono text-sm`,
                                                              },
                                                              [
                                                                  T(n).length
                                                                      ? (i(!0),
                                                                        s(
                                                                            l,
                                                                            {
                                                                                key: 1,
                                                                            },
                                                                            c(
                                                                                T(
                                                                                    n,
                                                                                ),
                                                                                (
                                                                                    e,
                                                                                    t,
                                                                                ) => (
                                                                                    i(),
                                                                                    s(
                                                                                        `div`,
                                                                                        {
                                                                                            key: t,
                                                                                        },
                                                                                        y(
                                                                                            e,
                                                                                        ),
                                                                                        1,
                                                                                    )
                                                                                ),
                                                                            ),
                                                                            128,
                                                                        ))
                                                                      : (i(),
                                                                        s(
                                                                            `div`,
                                                                            kt,
                                                                            [
                                                                                (i(),
                                                                                s(
                                                                                    l,
                                                                                    null,
                                                                                    c(
                                                                                        8,
                                                                                        (
                                                                                            e,
                                                                                        ) =>
                                                                                            m(
                                                                                                `div`,
                                                                                                {
                                                                                                    key: e,
                                                                                                    class: `h-4 animate-pulse rounded bg-muted-foreground/20`,
                                                                                                },
                                                                                            ),
                                                                                    ),
                                                                                    64,
                                                                                )),
                                                                            ],
                                                                        )),
                                                              ],
                                                              512,
                                                          ),
                                                          (t[3] ||= m(
                                                              `p`,
                                                              {
                                                                  class: `text-xs text-muted-foreground select-none`,
                                                              },
                                                              [
                                                                  b(
                                                                      ` Each recovery code can be used once to access your account and will be removed after use. If you need more, click `,
                                                                  ),
                                                                  m(
                                                                      `span`,
                                                                      {
                                                                          class: `font-bold`,
                                                                      },
                                                                      `Regenerate codes`,
                                                                  ),
                                                                  b(` above. `),
                                                              ],
                                                              -1,
                                                          )),
                                                      ])),
                                            ],
                                            2,
                                        ),
                                    ]),
                                    _: 1,
                                }),
                            ]),
                            _: 1,
                        },
                    )
                )
            );
        },
    }),
    jt = {
        class: `mb-3 w-auto rounded-full border border-border bg-card p-0.5 shadow-sm`,
    },
    Mt = {
        class: `relative overflow-hidden rounded-full border border-border bg-muted p-2.5`,
    },
    Nt = { class: `absolute inset-0 grid grid-cols-5 opacity-50` },
    Pt = { class: `absolute inset-0 grid grid-rows-5 opacity-50` },
    Ft = {
        class: `relative flex w-auto flex-col items-center justify-center space-y-5`,
    },
    It = {
        class: `relative mx-auto flex max-w-md items-center overflow-hidden`,
    },
    Lt = {
        class: `relative mx-auto aspect-square w-64 overflow-hidden rounded-lg border border-border`,
    },
    Rt = {
        key: 0,
        class: `absolute inset-0 z-10 flex aspect-square h-auto w-full animate-pulse items-center justify-center bg-background`,
    },
    zt = { key: 1, class: `relative z-10 overflow-hidden border p-5` },
    Bt = [`innerHTML`],
    Vt = { class: `flex w-full items-center space-x-5` },
    Ht = { class: `flex w-full items-center justify-center space-x-2` },
    Ut = {
        class: `flex w-full items-stretch overflow-hidden rounded-xl border border-border`,
    },
    Wt = {
        key: 0,
        class: `flex h-full w-full items-center justify-center bg-muted p-3`,
    },
    Gt = [`value`],
    Kt = [`value`],
    qt = {
        class: `flex w-full flex-col items-center justify-center space-y-3 py-2`,
    },
    Jt = { class: `flex w-full items-center space-x-5` },
    Yt = D({
        __name: `TwoFactorSetupModal`,
        props: x(
            {
                requiresConfirmation: { type: Boolean },
                twoFactorEnabled: { type: Boolean },
            },
            { isOpen: { type: Boolean }, isOpenModifiers: {} },
        ),
        emits: [`update:isOpen`],
        setup(e) {
            let { resolvedAppearance: t } = ge(),
                n = e,
                d = re(e, `isOpen`),
                { copy: h, copied: g } = _e(),
                {
                    qrCodeSvg: x,
                    manualSetupKey: S,
                    clearSetupData: C,
                    fetchSetupData: ne,
                    errors: ie,
                } = Tt(),
                E = p(!1),
                D = p(``),
                ae = te(`pinInputContainerRef`),
                O = r(() =>
                    n.twoFactorEnabled
                        ? {
                              title: `Two-factor authentication enabled`,
                              description: `Two-factor authentication is now enabled. Scan the QR code or enter the setup key in your authenticator app.`,
                              buttonText: `Close`,
                          }
                        : E.value
                          ? {
                                title: `Verify authentication code`,
                                description: `Enter the 6-digit code from your authenticator app`,
                                buttonText: `Continue`,
                            }
                          : {
                                title: `Enable two-factor authentication`,
                                description: `To finish enabling two-factor authentication, scan the QR code or enter the setup key in your authenticator app`,
                                buttonText: `Continue`,
                            },
                ),
                k = () => {
                    n.requiresConfirmation
                        ? ((E.value = !0),
                          o(() => {
                              ae.value?.querySelector(`input`)?.focus();
                          }))
                        : (C(), (d.value = !1));
                },
                oe = () => {
                    (n.twoFactorEnabled && C(), (E.value = !1), (D.value = ``));
                };
            return (
                v(
                    () => d.value,
                    async (e) => {
                        e ? x.value || (await ne()) : oe();
                    },
                ),
                (e, n) => (
                    i(),
                    f(
                        T(Me),
                        {
                            open: d.value,
                            'onUpdate:open': (n[5] ||= (e) => (d.value = e)),
                        },
                        {
                            default: u(() => [
                                _(
                                    T(ke),
                                    { class: `sm:max-w-md` },
                                    {
                                        default: u(() => [
                                            _(
                                                T(je),
                                                {
                                                    class: `flex items-center justify-center`,
                                                },
                                                {
                                                    default: u(() => [
                                                        m(`div`, jt, [
                                                            m(`div`, Mt, [
                                                                m(`div`, Nt, [
                                                                    (i(),
                                                                    s(
                                                                        l,
                                                                        null,
                                                                        c(
                                                                            5,
                                                                            (
                                                                                e,
                                                                            ) =>
                                                                                m(
                                                                                    `div`,
                                                                                    {
                                                                                        key: `col-${e}`,
                                                                                        class: `border-r border-border last:border-r-0`,
                                                                                    },
                                                                                ),
                                                                        ),
                                                                        64,
                                                                    )),
                                                                ]),
                                                                m(`div`, Pt, [
                                                                    (i(),
                                                                    s(
                                                                        l,
                                                                        null,
                                                                        c(
                                                                            5,
                                                                            (
                                                                                e,
                                                                            ) =>
                                                                                m(
                                                                                    `div`,
                                                                                    {
                                                                                        key: `row-${e}`,
                                                                                        class: `border-b border-border last:border-b-0`,
                                                                                    },
                                                                                ),
                                                                        ),
                                                                        64,
                                                                    )),
                                                                ]),
                                                                _(T(fe), {
                                                                    class: `relative z-20 size-6 text-foreground`,
                                                                }),
                                                            ]),
                                                        ]),
                                                        _(T(Pe), null, {
                                                            default: u(() => [
                                                                b(
                                                                    y(
                                                                        O.value
                                                                            .title,
                                                                    ),
                                                                    1,
                                                                ),
                                                            ]),
                                                            _: 1,
                                                        }),
                                                        _(
                                                            T(Ae),
                                                            {
                                                                class: `text-center`,
                                                            },
                                                            {
                                                                default: u(
                                                                    () => [
                                                                        b(
                                                                            y(
                                                                                O
                                                                                    .value
                                                                                    .description,
                                                                            ),
                                                                            1,
                                                                        ),
                                                                    ],
                                                                ),
                                                                _: 1,
                                                            },
                                                        ),
                                                    ]),
                                                    _: 1,
                                                },
                                            ),
                                            m(`div`, Ft, [
                                                E.value
                                                    ? (i(),
                                                      f(
                                                          T(w),
                                                          a(
                                                              { key: 1 },
                                                              T(U).form(),
                                                              {
                                                                  'error-bag': `confirmTwoFactorAuthentication`,
                                                                  'reset-on-error': ``,
                                                                  onFinish:
                                                                      (n[3] ||=
                                                                          (e) =>
                                                                              (D.value = ``)),
                                                                  onSuccess:
                                                                      (n[4] ||=
                                                                          (e) =>
                                                                              (d.value =
                                                                                  !1)),
                                                              },
                                                          ),
                                                          {
                                                              default: u(
                                                                  ({
                                                                      errors: e,
                                                                      processing:
                                                                          t,
                                                                  }) => [
                                                                      m(
                                                                          `input`,
                                                                          {
                                                                              type: `hidden`,
                                                                              name: `code`,
                                                                              value: D.value,
                                                                          },
                                                                          null,
                                                                          8,
                                                                          Kt,
                                                                      ),
                                                                      m(
                                                                          `div`,
                                                                          {
                                                                              ref_key: `pinInputContainerRef`,
                                                                              ref: ae,
                                                                              class: `relative w-full space-y-3`,
                                                                          },
                                                                          [
                                                                              m(
                                                                                  `div`,
                                                                                  qt,
                                                                                  [
                                                                                      _(
                                                                                          T(
                                                                                              Te,
                                                                                          ),
                                                                                          {
                                                                                              id: `otp`,
                                                                                              modelValue:
                                                                                                  D.value,
                                                                                              'onUpdate:modelValue':
                                                                                                  (n[1] ||=
                                                                                                      (
                                                                                                          e,
                                                                                                      ) =>
                                                                                                          (D.value =
                                                                                                              e)),
                                                                                              maxlength: 6,
                                                                                              disabled:
                                                                                                  t,
                                                                                              autofocus: ``,
                                                                                          },
                                                                                          {
                                                                                              default:
                                                                                                  u(
                                                                                                      () => [
                                                                                                          _(
                                                                                                              T(
                                                                                                                  Ee,
                                                                                                              ),
                                                                                                              null,
                                                                                                              {
                                                                                                                  default:
                                                                                                                      u(
                                                                                                                          () => [
                                                                                                                              (i(),
                                                                                                                              s(
                                                                                                                                  l,
                                                                                                                                  null,
                                                                                                                                  c(
                                                                                                                                      6,
                                                                                                                                      (
                                                                                                                                          e,
                                                                                                                                      ) =>
                                                                                                                                          _(
                                                                                                                                              T(
                                                                                                                                                  De,
                                                                                                                                              ),
                                                                                                                                              {
                                                                                                                                                  key: e,
                                                                                                                                                  index:
                                                                                                                                                      e -
                                                                                                                                                      1,
                                                                                                                                              },
                                                                                                                                              null,
                                                                                                                                              8,
                                                                                                                                              [
                                                                                                                                                  `index`,
                                                                                                                                              ],
                                                                                                                                          ),
                                                                                                                                  ),
                                                                                                                                  64,
                                                                                                                              )),
                                                                                                                          ],
                                                                                                                      ),
                                                                                                                  _: 1,
                                                                                                              },
                                                                                                          ),
                                                                                                      ],
                                                                                                  ),
                                                                                              _: 1,
                                                                                          },
                                                                                          8,
                                                                                          [
                                                                                              `modelValue`,
                                                                                              `disabled`,
                                                                                          ],
                                                                                      ),
                                                                                      _(
                                                                                          P,
                                                                                          {
                                                                                              message:
                                                                                                  e?.code,
                                                                                          },
                                                                                          null,
                                                                                          8,
                                                                                          [
                                                                                              `message`,
                                                                                          ],
                                                                                      ),
                                                                                  ],
                                                                              ),
                                                                              m(
                                                                                  `div`,
                                                                                  Jt,
                                                                                  [
                                                                                      _(
                                                                                          T(
                                                                                              A,
                                                                                          ),
                                                                                          {
                                                                                              type: `button`,
                                                                                              variant: `outline`,
                                                                                              class: `w-auto flex-1`,
                                                                                              onClick:
                                                                                                  (n[2] ||=
                                                                                                      (
                                                                                                          e,
                                                                                                      ) =>
                                                                                                          (E.value =
                                                                                                              !1)),
                                                                                              disabled:
                                                                                                  t,
                                                                                          },
                                                                                          {
                                                                                              default:
                                                                                                  u(
                                                                                                      () => [
                                                                                                          ...(n[7] ||=
                                                                                                              [
                                                                                                                  b(
                                                                                                                      ` Back `,
                                                                                                                      -1,
                                                                                                                  ),
                                                                                                              ]),
                                                                                                      ],
                                                                                                  ),
                                                                                              _: 1,
                                                                                          },
                                                                                          8,
                                                                                          [
                                                                                              `disabled`,
                                                                                          ],
                                                                                      ),
                                                                                      _(
                                                                                          T(
                                                                                              A,
                                                                                          ),
                                                                                          {
                                                                                              type: `submit`,
                                                                                              class: `w-auto flex-1`,
                                                                                              disabled:
                                                                                                  t ||
                                                                                                  D
                                                                                                      .value
                                                                                                      .length <
                                                                                                      6,
                                                                                          },
                                                                                          {
                                                                                              default:
                                                                                                  u(
                                                                                                      () => [
                                                                                                          ...(n[8] ||=
                                                                                                              [
                                                                                                                  b(
                                                                                                                      ` Confirm `,
                                                                                                                      -1,
                                                                                                                  ),
                                                                                                              ]),
                                                                                                      ],
                                                                                                  ),
                                                                                              _: 1,
                                                                                          },
                                                                                          8,
                                                                                          [
                                                                                              `disabled`,
                                                                                          ],
                                                                                      ),
                                                                                  ],
                                                                              ),
                                                                          ],
                                                                          512,
                                                                      ),
                                                                  ],
                                                              ),
                                                              _: 1,
                                                          },
                                                          16,
                                                      ))
                                                    : (i(),
                                                      s(
                                                          l,
                                                          { key: 0 },
                                                          [
                                                              T(ie)?.length
                                                                  ? (i(),
                                                                    f(
                                                                        dt,
                                                                        {
                                                                            key: 0,
                                                                            errors: T(
                                                                                ie,
                                                                            ),
                                                                        },
                                                                        null,
                                                                        8,
                                                                        [
                                                                            `errors`,
                                                                        ],
                                                                    ))
                                                                  : (i(),
                                                                    s(
                                                                        l,
                                                                        {
                                                                            key: 1,
                                                                        },
                                                                        [
                                                                            m(
                                                                                `div`,
                                                                                It,
                                                                                [
                                                                                    m(
                                                                                        `div`,
                                                                                        Lt,
                                                                                        [
                                                                                            T(
                                                                                                x,
                                                                                            )
                                                                                                ? (i(),
                                                                                                  s(
                                                                                                      `div`,
                                                                                                      zt,
                                                                                                      [
                                                                                                          m(
                                                                                                              `div`,
                                                                                                              {
                                                                                                                  innerHTML:
                                                                                                                      T(
                                                                                                                          x,
                                                                                                                      ),
                                                                                                                  class: `flex aspect-square size-full items-center justify-center`,
                                                                                                                  style: ee(
                                                                                                                      {
                                                                                                                          filter:
                                                                                                                              T(
                                                                                                                                  t,
                                                                                                                              ) ===
                                                                                                                              `dark`
                                                                                                                                  ? `invert(1) brightness(1.5)`
                                                                                                                                  : void 0,
                                                                                                                      },
                                                                                                                  ),
                                                                                                              },
                                                                                                              null,
                                                                                                              12,
                                                                                                              Bt,
                                                                                                          ),
                                                                                                      ],
                                                                                                  ))
                                                                                                : (i(),
                                                                                                  s(
                                                                                                      `div`,
                                                                                                      Rt,
                                                                                                      [
                                                                                                          _(
                                                                                                              T(
                                                                                                                  Se,
                                                                                                              ),
                                                                                                              {
                                                                                                                  class: `size-6`,
                                                                                                              },
                                                                                                          ),
                                                                                                      ],
                                                                                                  )),
                                                                                        ],
                                                                                    ),
                                                                                ],
                                                                            ),
                                                                            m(
                                                                                `div`,
                                                                                Vt,
                                                                                [
                                                                                    _(
                                                                                        T(
                                                                                            A,
                                                                                        ),
                                                                                        {
                                                                                            class: `w-full`,
                                                                                            onClick:
                                                                                                k,
                                                                                        },
                                                                                        {
                                                                                            default:
                                                                                                u(
                                                                                                    () => [
                                                                                                        b(
                                                                                                            y(
                                                                                                                O
                                                                                                                    .value
                                                                                                                    .buttonText,
                                                                                                            ),
                                                                                                            1,
                                                                                                        ),
                                                                                                    ],
                                                                                                ),
                                                                                            _: 1,
                                                                                        },
                                                                                    ),
                                                                                ],
                                                                            ),
                                                                            (n[6] ||=
                                                                                m(
                                                                                    `div`,
                                                                                    {
                                                                                        class: `relative flex w-full items-center justify-center`,
                                                                                    },
                                                                                    [
                                                                                        m(
                                                                                            `div`,
                                                                                            {
                                                                                                class: `absolute inset-0 top-1/2 h-px w-full bg-border`,
                                                                                            },
                                                                                        ),
                                                                                        m(
                                                                                            `span`,
                                                                                            {
                                                                                                class: `relative bg-card px-2 py-1`,
                                                                                            },
                                                                                            `or, enter the code manually`,
                                                                                        ),
                                                                                    ],
                                                                                    -1,
                                                                                )),
                                                                            m(
                                                                                `div`,
                                                                                Ht,
                                                                                [
                                                                                    m(
                                                                                        `div`,
                                                                                        Ut,
                                                                                        [
                                                                                            T(
                                                                                                S,
                                                                                            )
                                                                                                ? (i(),
                                                                                                  s(
                                                                                                      l,
                                                                                                      {
                                                                                                          key: 1,
                                                                                                      },
                                                                                                      [
                                                                                                          m(
                                                                                                              `input`,
                                                                                                              {
                                                                                                                  type: `text`,
                                                                                                                  readonly: ``,
                                                                                                                  value: T(
                                                                                                                      S,
                                                                                                                  ),
                                                                                                                  class: `h-full w-full bg-background p-3 text-foreground`,
                                                                                                              },
                                                                                                              null,
                                                                                                              8,
                                                                                                              Gt,
                                                                                                          ),
                                                                                                          m(
                                                                                                              `button`,
                                                                                                              {
                                                                                                                  onClick:
                                                                                                                      (n[0] ||=
                                                                                                                          (
                                                                                                                              e,
                                                                                                                          ) =>
                                                                                                                              T(
                                                                                                                                  h,
                                                                                                                              )(
                                                                                                                                  T(
                                                                                                                                      S,
                                                                                                                                  ) ||
                                                                                                                                      ``,
                                                                                                                              )),
                                                                                                                  class: `relative block h-auto border-l border-border px-3 hover:bg-muted`,
                                                                                                              },
                                                                                                              [
                                                                                                                  T(
                                                                                                                      g,
                                                                                                                  )
                                                                                                                      ? (i(),
                                                                                                                        f(
                                                                                                                            T(
                                                                                                                                ve,
                                                                                                                            ),
                                                                                                                            {
                                                                                                                                key: 0,
                                                                                                                                class: `w-4 text-green-500`,
                                                                                                                            },
                                                                                                                        ))
                                                                                                                      : (i(),
                                                                                                                        f(
                                                                                                                            T(
                                                                                                                                Le,
                                                                                                                            ),
                                                                                                                            {
                                                                                                                                key: 1,
                                                                                                                                class: `w-4`,
                                                                                                                            },
                                                                                                                        )),
                                                                                                              ],
                                                                                                          ),
                                                                                                      ],
                                                                                                      64,
                                                                                                  ))
                                                                                                : (i(),
                                                                                                  s(
                                                                                                      `div`,
                                                                                                      Wt,
                                                                                                      [
                                                                                                          _(
                                                                                                              T(
                                                                                                                  Se,
                                                                                                              ),
                                                                                                          ),
                                                                                                      ],
                                                                                                  )),
                                                                                        ],
                                                                                    ),
                                                                                ],
                                                                            ),
                                                                        ],
                                                                        64,
                                                                    )),
                                                          ],
                                                          64,
                                                      )),
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
                    )
                )
            );
        },
    }),
    Xt = { key: 0, class: `space-y-6` },
    Zt = { key: 0, class: `flex flex-col items-start justify-start space-y-4` },
    Qt = { key: 1, class: `flex flex-col items-start justify-start space-y-4` },
    $t = { class: `relative inline` },
    en = D({
        __name: `ManageTwoFactor`,
        props: {
            canManageTwoFactor: { type: Boolean, default: !1 },
            requiresConfirmation: { type: Boolean, default: !1 },
            twoFactorEnabled: { type: Boolean, default: !1 },
        },
        setup(e) {
            let { hasSetupData: t, clearTwoFactorAuthData: n } = Tt(),
                r = p(!1);
            return (
                d(() => n()),
                (n, o) =>
                    e.canManageTwoFactor
                        ? (i(),
                          s(`div`, Xt, [
                              _(xe, {
                                  variant: `small`,
                                  title: `Two-factor authentication`,
                                  description: `Manage your two-factor authentication settings`,
                              }),
                              e.twoFactorEnabled
                                  ? (i(),
                                    s(`div`, Qt, [
                                        (o[7] ||= m(
                                            `p`,
                                            {
                                                class: `text-sm text-muted-foreground`,
                                            },
                                            ` You will be prompted for a secure, random pin during login, which you can retrieve from the TOTP-supported application on your phone. `,
                                            -1,
                                        )),
                                        m(`div`, $t, [
                                            _(
                                                T(w),
                                                O(g(T(W).form())),
                                                {
                                                    default: u(
                                                        ({ processing: e }) => [
                                                            _(
                                                                T(A),
                                                                {
                                                                    variant: `destructive`,
                                                                    type: `submit`,
                                                                    disabled: e,
                                                                },
                                                                {
                                                                    default: u(
                                                                        () => [
                                                                            ...(o[6] ||=
                                                                                [
                                                                                    b(
                                                                                        ` Disable 2FA `,
                                                                                        -1,
                                                                                    ),
                                                                                ]),
                                                                        ],
                                                                    ),
                                                                    _: 1,
                                                                },
                                                                8,
                                                                [`disabled`],
                                                            ),
                                                        ],
                                                    ),
                                                    _: 1,
                                                },
                                                16,
                                            ),
                                        ]),
                                        _(At),
                                    ]))
                                  : (i(),
                                    s(`div`, Zt, [
                                        (o[5] ||= m(
                                            `p`,
                                            {
                                                class: `text-sm text-muted-foreground`,
                                            },
                                            ` When you enable two-factor authentication, you will be prompted for a secure pin during login. This pin can be retrieved from a TOTP-supported application on your phone. `,
                                            -1,
                                        )),
                                        m(`div`, null, [
                                            T(t)
                                                ? (i(),
                                                  f(
                                                      T(A),
                                                      {
                                                          key: 0,
                                                          onClick: (o[0] ||= (
                                                              e,
                                                          ) => (r.value = !0)),
                                                      },
                                                      {
                                                          default: u(() => [
                                                              _(T(ye)),
                                                              (o[3] ||= b(
                                                                  `Continue setup `,
                                                                  -1,
                                                              )),
                                                          ]),
                                                          _: 1,
                                                      },
                                                  ))
                                                : (i(),
                                                  f(
                                                      T(w),
                                                      a(
                                                          { key: 1 },
                                                          T(H).form(),
                                                          {
                                                              onSuccess:
                                                                  (o[1] ||= (
                                                                      e,
                                                                  ) =>
                                                                      (r.value =
                                                                          !0)),
                                                          },
                                                      ),
                                                      {
                                                          default: u(
                                                              ({
                                                                  processing: e,
                                                              }) => [
                                                                  _(
                                                                      T(A),
                                                                      {
                                                                          type: `submit`,
                                                                          disabled:
                                                                              e,
                                                                      },
                                                                      {
                                                                          default:
                                                                              u(
                                                                                  () => [
                                                                                      ...(o[4] ||=
                                                                                          [
                                                                                              b(
                                                                                                  ` Enable 2FA `,
                                                                                                  -1,
                                                                                              ),
                                                                                          ]),
                                                                                  ],
                                                                              ),
                                                                          _: 1,
                                                                      },
                                                                      8,
                                                                      [
                                                                          `disabled`,
                                                                      ],
                                                                  ),
                                                              ],
                                                          ),
                                                          _: 1,
                                                      },
                                                      16,
                                                  )),
                                        ]),
                                    ])),
                              _(
                                  Yt,
                                  {
                                      isOpen: r.value,
                                      'onUpdate:isOpen': (o[2] ||= (e) =>
                                          (r.value = e)),
                                      requiresConfirmation:
                                          e.requiresConfirmation,
                                      twoFactorEnabled: e.twoFactorEnabled,
                                  },
                                  null,
                                  8,
                                  [
                                      `isOpen`,
                                      `requiresConfirmation`,
                                      `twoFactorEnabled`,
                                  ],
                              ),
                          ]))
                        : h(``, !0)
            );
        },
    }),
    tn = { class: `space-y-6` },
    nn = { class: `grid gap-2` },
    rn = { class: `grid gap-2` },
    an = { class: `grid gap-2` },
    on = { class: `flex items-center gap-4` },
    sn = D({
        layout: { breadcrumbs: [{ title: `Security settings`, href: be() }] },
        __name: `Security`,
        props: {
            passwordRules: {},
            canManagePasskeys: { type: Boolean },
            passkeys: {},
            canManageTwoFactor: { type: Boolean },
            requiresConfirmation: { type: Boolean },
            twoFactorEnabled: { type: Boolean },
        },
        setup(e) {
            let t = e;
            return (n, r) => (
                i(),
                s(
                    l,
                    null,
                    [
                        _(T(ie), { title: `Security settings` }),
                        (r[4] ||= m(
                            `h1`,
                            { class: `sr-only` },
                            `Security settings`,
                            -1,
                        )),
                        m(`div`, tn, [
                            _(xe, {
                                variant: `small`,
                                title: `Update password`,
                                description: `Ensure your account is using a long, random password to stay secure`,
                            }),
                            _(
                                T(w),
                                a(T(Ue).update.form(), {
                                    options: { preserveScroll: !0 },
                                    'reset-on-success': ``,
                                    'reset-on-error': [
                                        `password`,
                                        `password_confirmation`,
                                        `current_password`,
                                    ],
                                    class: `space-y-6`,
                                }),
                                {
                                    default: u(
                                        ({ errors: e, processing: n }) => [
                                            m(`div`, nn, [
                                                _(
                                                    T(j),
                                                    { for: `current_password` },
                                                    {
                                                        default: u(() => [
                                                            ...(r[0] ||= [
                                                                b(
                                                                    `Current password`,
                                                                    -1,
                                                                ),
                                                            ]),
                                                        ]),
                                                        _: 1,
                                                    },
                                                ),
                                                _(ce, {
                                                    id: `current_password`,
                                                    name: `current_password`,
                                                    class: `mt-1 block w-full`,
                                                    autocomplete: `current-password`,
                                                    placeholder: `Current password`,
                                                }),
                                                _(
                                                    P,
                                                    {
                                                        message:
                                                            e.current_password,
                                                    },
                                                    null,
                                                    8,
                                                    [`message`],
                                                ),
                                            ]),
                                            m(`div`, rn, [
                                                _(
                                                    T(j),
                                                    { for: `password` },
                                                    {
                                                        default: u(() => [
                                                            ...(r[1] ||= [
                                                                b(
                                                                    `New password`,
                                                                    -1,
                                                                ),
                                                            ]),
                                                        ]),
                                                        _: 1,
                                                    },
                                                ),
                                                _(
                                                    ce,
                                                    {
                                                        id: `password`,
                                                        name: `password`,
                                                        class: `mt-1 block w-full`,
                                                        autocomplete: `new-password`,
                                                        placeholder: `New password`,
                                                        passwordrules:
                                                            t.passwordRules,
                                                    },
                                                    null,
                                                    8,
                                                    [`passwordrules`],
                                                ),
                                                _(
                                                    P,
                                                    { message: e.password },
                                                    null,
                                                    8,
                                                    [`message`],
                                                ),
                                            ]),
                                            m(`div`, an, [
                                                _(
                                                    T(j),
                                                    {
                                                        for: `password_confirmation`,
                                                    },
                                                    {
                                                        default: u(() => [
                                                            ...(r[2] ||= [
                                                                b(
                                                                    `Confirm password`,
                                                                    -1,
                                                                ),
                                                            ]),
                                                        ]),
                                                        _: 1,
                                                    },
                                                ),
                                                _(
                                                    ce,
                                                    {
                                                        id: `password_confirmation`,
                                                        name: `password_confirmation`,
                                                        class: `mt-1 block w-full`,
                                                        autocomplete: `new-password`,
                                                        placeholder: `Confirm password`,
                                                        passwordrules:
                                                            t.passwordRules,
                                                    },
                                                    null,
                                                    8,
                                                    [`passwordrules`],
                                                ),
                                                _(
                                                    P,
                                                    {
                                                        message:
                                                            e.password_confirmation,
                                                    },
                                                    null,
                                                    8,
                                                    [`message`],
                                                ),
                                            ]),
                                            m(`div`, on, [
                                                _(
                                                    T(A),
                                                    {
                                                        disabled: n,
                                                        'data-test': `update-password-button`,
                                                    },
                                                    {
                                                        default: u(() => [
                                                            ...(r[3] ||= [
                                                                b(` Save `, -1),
                                                            ]),
                                                        ]),
                                                        _: 1,
                                                    },
                                                    8,
                                                    [`disabled`],
                                                ),
                                            ]),
                                        ],
                                    ),
                                    _: 1,
                                },
                                16,
                            ),
                        ]),
                        _(
                            en,
                            {
                                canManageTwoFactor: e.canManageTwoFactor,
                                requiresConfirmation: e.requiresConfirmation,
                                twoFactorEnabled: e.twoFactorEnabled,
                            },
                            null,
                            8,
                            [
                                `canManageTwoFactor`,
                                `requiresConfirmation`,
                                `twoFactorEnabled`,
                            ],
                        ),
                        _(
                            lt,
                            {
                                canManagePasskeys: e.canManagePasskeys,
                                passkeys: e.passkeys,
                            },
                            null,
                            8,
                            [`canManagePasskeys`, `passkeys`],
                        ),
                    ],
                    64,
                )
            );
        },
    });
export { sn as default };
