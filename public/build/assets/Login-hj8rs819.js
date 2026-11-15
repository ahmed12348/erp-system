import { n as e, o as t, t as n } from './Presence-BI6XYFRa.js';
import {
    $n as r,
    $t as i,
    Bt as a,
    Dn as o,
    It as s,
    J as c,
    Kt as l,
    M as u,
    Mt as d,
    Nt as f,
    Q as p,
    Qt as m,
    R as h,
    Tn as g,
    Wt as _,
    X as v,
    Y as y,
    Z as b,
    _t as x,
    at as S,
    bn as C,
    dn as w,
    fr as T,
    ht as E,
    it as D,
    j as ee,
    n as O,
    nn as te,
    or as k,
    r as A,
    st as j,
    tn as M,
    ur as N,
} from './dist-C0kHilBU.js';
import { a as P, n as F, t as I } from './button-BRBVuDNP.js';
import { i as L, n as ne, r as R, t as re } from './FieldRoot-_RlB5iPB.js';
import { a as z, c as ie, t as B } from './useForwardExpose-CL6aaO9U.js';
import { t as V } from './VisuallyHidden-DwTNoksw.js';
import { t as H } from './useForwardPropsEmits-re_7TsxN.js';
import { t as U } from './Label-BhHH3wmI.js';
import { t as W } from './check-DKhmhOm5.js';
import { t as G } from './PasswordInput-Dmemg5EW.js';
import { s as ae } from './shared-DeHk4G-N.js';
import { t as oe } from './Input-8-HQLciI.js';
import { n as se } from './wayfinder-Bh70ToPC.js';
import {
    dt as ce,
    ft as le,
    ht as ue,
    mt as de,
    pt as fe,
    rt as pe,
    st as me,
} from './app-DxgtVyfV.js';
import { t as K } from './InputError-_9qlcKk2.js';
import { t as he } from './Spinner-S_eIiGCn.js';
import { t as ge } from './PasskeyVerify-Bn08SgTR.js';
import { t as _e } from './TextLink-IywSBKYP.js';
import { n as ve } from './password-u5mFHSI2.js';
import { n as ye, t as be } from './alert-DV-aNR-6.js';
function q(e, t) {
    return R(e) ? !1 : Array.isArray(e) ? e.some((e) => L(e, t)) : L(e, t);
}
function xe() {
    let e = E()?.vnode?.scopeId;
    return e ? { [e]: `` } : {};
}
var J = j({
        inheritAttrs: !1,
        __name: `VisuallyHiddenInputBubble`,
        props: {
            name: { type: String, required: !0 },
            value: { type: null, required: !0 },
            checked: { type: Boolean, required: !1, default: void 0 },
            required: { type: Boolean, required: !1 },
            disabled: { type: Boolean, required: !1 },
            feature: { type: String, required: !1, default: `fully-hidden` },
        },
        setup(e) {
            let t = e,
                { primitiveElement: n, currentElement: r } = ue(),
                i = c(() => t.checked ?? t.value);
            return (
                C(i, (e, t) => {
                    if (!r.value) return;
                    let n = r.value,
                        i = window.HTMLInputElement.prototype,
                        a = Object.getOwnPropertyDescriptor(i, `value`).set;
                    if (a && e !== t) {
                        let t = new Event(`input`, { bubbles: !0 }),
                            r = new Event(`change`, { bubbles: !0 });
                        (a.call(n, e), n.dispatchEvent(t), n.dispatchEvent(r));
                    }
                }),
                (e, r) => (
                    l(),
                    v(
                        V,
                        d(
                            { ref_key: `primitiveElement`, ref: n },
                            { ...t, ...e.$attrs },
                            { as: `input` },
                        ),
                        null,
                        16,
                    )
                )
            );
        },
    }),
    Se = j({
        inheritAttrs: !1,
        __name: `VisuallyHiddenInput`,
        props: {
            name: { type: String, required: !0 },
            value: { type: null, required: !0 },
            checked: { type: Boolean, required: !1, default: void 0 },
            required: { type: Boolean, required: !1 },
            disabled: { type: Boolean, required: !1 },
            feature: { type: String, required: !1, default: `fully-hidden` },
        },
        setup(e) {
            let t = e,
                n = c(
                    () =>
                        typeof t.value == `object` &&
                        Array.isArray(t.value) &&
                        t.value.length === 0 &&
                        t.required,
                ),
                r = c(() =>
                    typeof t.value == `string` ||
                    typeof t.value == `number` ||
                    typeof t.value == `boolean` ||
                    t.value === null ||
                    t.value === void 0
                        ? [{ name: t.name, value: t.value }]
                        : typeof t.value == `object` && Array.isArray(t.value)
                          ? t.value.flatMap((e, n) =>
                                typeof e == `object`
                                    ? Object.entries(e).map(([e, r]) => ({
                                          name: `${t.name}[${n}][${e}]`,
                                          value: r,
                                      }))
                                    : { name: `${t.name}[${n}]`, value: e },
                            )
                          : t.value !== null &&
                              typeof t.value == `object` &&
                              !Array.isArray(t.value)
                            ? Object.entries(t.value).map(([e, n]) => ({
                                  name: `${t.name}[${e}]`,
                                  value: n,
                              }))
                            : [],
                );
            return (i, a) => (
                l(),
                p(
                    h,
                    null,
                    [
                        b(` We render single input if it's required `),
                        n.value
                            ? (l(),
                              v(
                                  J,
                                  d(
                                      { key: e.name },
                                      { ...t, ...i.$attrs },
                                      { name: e.name, value: e.value },
                                  ),
                                  null,
                                  16,
                                  [`name`, `value`],
                              ))
                            : (l(!0),
                              p(
                                  h,
                                  { key: 1 },
                                  m(
                                      r.value,
                                      (e) => (
                                          l(),
                                          v(
                                              J,
                                              d(
                                                  { key: e.name },
                                                  { ref_for: !0 },
                                                  { ...t, ...i.$attrs },
                                                  {
                                                      name: e.name,
                                                      value: e.value,
                                                  },
                                              ),
                                              null,
                                              16,
                                              [`name`, `value`],
                                          )
                                      ),
                                  ),
                                  128,
                              )),
                    ],
                    2112,
                )
            );
        },
    }),
    Ce = j({
        __name: `RovingFocusItem`,
        props: {
            tabStopId: { type: String, required: !1 },
            focusable: { type: Boolean, required: !1, default: !0 },
            active: { type: Boolean, required: !1 },
            allowShiftKey: { type: Boolean, required: !1 },
            asChild: { type: Boolean, required: !1 },
            as: { type: null, required: !1, default: `span` },
        },
        setup(t) {
            let n = t,
                o = me(),
                s = e(),
                u = c(() => n.tabStopId || s),
                d = c(() => o.currentTabStopId.value === u.value),
                { getItems: p, CollectionItem: m } = de();
            (a(() => {
                n.focusable && o.onFocusableItemAdd();
            }),
                _(() => {
                    n.focusable && o.onFocusableItemRemove();
                }),
                C(
                    () => n.focusable,
                    (e, t) => {
                        e !== t &&
                            (e
                                ? o.onFocusableItemAdd()
                                : o.onFocusableItemRemove());
                    },
                ));
            function h(e) {
                if (e.defaultPrevented) return;
                if (e.key === `Tab` && e.shiftKey) {
                    o.onItemShiftTab();
                    return;
                }
                if (e.target !== e.currentTarget) return;
                let t = le(e, o.orientation.value, o.dir.value);
                if (t !== void 0) {
                    if (
                        e.metaKey ||
                        e.ctrlKey ||
                        e.altKey ||
                        (!n.allowShiftKey && e.shiftKey)
                    )
                        return;
                    e.preventDefault();
                    let r = [
                        ...p()
                            .map((e) => e.ref)
                            .filter((e) => e.dataset.disabled !== ``),
                    ];
                    if (t === `last`) r.reverse();
                    else if (t === `prev` || t === `next`) {
                        t === `prev` && r.reverse();
                        let n = r.indexOf(e.currentTarget);
                        r = o.loop.value ? fe(r, n + 1) : r.slice(n + 1);
                    }
                    f(() => ce(r));
                }
            }
            let { forwardRef: y, currentElement: b } = B(),
                x = r();
            (C(
                b,
                (e) => {
                    x.value = e;
                },
                { immediate: !0, flush: `post` },
            ),
                z(x, `keydown`, h));
            function w(e) {
                x.value = e.currentTarget;
            }
            return (e, n) => (
                l(),
                v(k(m), null, {
                    default: g(() => [
                        S(
                            k(F),
                            {
                                ref: k(y),
                                tabindex: d.value ? 0 : -1,
                                'data-orientation': k(o).orientation.value,
                                'data-active': t.active ? `` : void 0,
                                'data-disabled': t.focusable ? void 0 : ``,
                                as: t.as,
                                'as-child': t.asChild,
                                onMousedown: (n[0] ||= (e) => {
                                    t.focusable
                                        ? k(o).onItemFocus(u.value)
                                        : e.preventDefault();
                                }),
                                onFocus: (n[1] ||= (e) =>
                                    k(o).onItemFocus(u.value)),
                                onFocusin: w,
                            },
                            {
                                default: g(() => [i(e.$slots, `default`)]),
                                _: 3,
                            },
                            8,
                            [
                                `tabindex`,
                                `data-orientation`,
                                `data-active`,
                                `data-disabled`,
                                `as`,
                                `as-child`,
                            ],
                        ),
                    ]),
                    _: 3,
                })
            );
        },
    }),
    [we, Te] = t(`CheckboxGroupRoot`);
function Y(e) {
    return e === `indeterminate`;
}
function X(e) {
    return Y(e) ? `indeterminate` : e ? `checked` : `unchecked`;
}
var [Ee, De] = t(`CheckboxRoot`),
    Oe = j({
        inheritAttrs: !1,
        __name: `CheckboxRoot`,
        props: {
            defaultValue: { type: null, required: !1 },
            modelValue: { type: null, required: !1, default: void 0 },
            disabled: { type: Boolean, required: !1 },
            value: { type: null, required: !1, default: `on` },
            id: { type: String, required: !1 },
            trueValue: { type: null, required: !1, default: () => !0 },
            falseValue: { type: null, required: !1, default: () => !1 },
            asChild: { type: Boolean, required: !1 },
            as: { type: null, required: !1, default: `button` },
            name: { type: String, required: !1 },
            required: { type: Boolean, required: !1 },
        },
        emits: [`update:modelValue`],
        setup(e, { emit: t }) {
            let n = e,
                r = t,
                { forwardRef: o, currentElement: f } = B(),
                m = we(null),
                _ = re(null),
                y = ie(n, `modelValue`, r, {
                    defaultValue: n.defaultValue ?? n.falseValue,
                    passive: n.modelValue === void 0,
                }),
                x = c(() => {
                    let e = m?.max.value;
                    if (R(e)) return !1;
                    let t = m.modelValue.value ?? [];
                    return !q(t, n.value) && t.length >= e;
                }),
                S = c(
                    () =>
                        !!(
                            m?.disabled.value ||
                            n.disabled ||
                            _?.disabled.value
                        ),
                ),
                T = c(() => S.value || x.value),
                E = c(() => !!_ && !m),
                D = c(() => n.id ?? (E.value ? _?.fieldId.value : void 0)),
                O = c(() => n.name ?? _?.name.value),
                A = c(() => (_ ? n.required || _.required.value : n.required)),
                j = c(() => L(y.value, n.trueValue)),
                M = c(() =>
                    R(m?.modelValue.value)
                        ? y.value === `indeterminate`
                            ? `indeterminate`
                            : j.value
                        : q(m.modelValue.value, n.value),
                );
            function N() {
                if (T.value) return;
                let e = M.value !== !0;
                if (R(m?.modelValue.value))
                    y.value === `indeterminate`
                        ? (y.value = n.trueValue)
                        : (y.value = j.value ? n.falseValue : n.trueValue);
                else {
                    let e = [...(m.modelValue.value || [])];
                    if (q(e, n.value)) {
                        let t = e.findIndex((e) => L(e, n.value));
                        e.splice(t, 1);
                    } else e.push(n.value);
                    m.modelValue.value = e;
                }
                E.value && _?.handleControlInput({ value: e });
            }
            C(M, (e) => {
                E.value && _?.reportControlState({ filled: e === !0 });
            });
            let P = ne(f),
                I = xe(),
                z = w(),
                V = c(() => {
                    if (!z[`aria-label`])
                        return D.value && f.value
                            ? document.querySelector(`[for="${D.value}"]`)
                                  ?.innerText
                            : void 0;
                });
            function H() {
                let e = (e, t) => [e, t].filter(Boolean).join(` `) || void 0;
                return {
                    'aria-labelledby': e(
                        z[`aria-labelledby`],
                        E.value ? _?.labelId.value : void 0,
                    ),
                    'aria-describedby': e(
                        z[`aria-describedby`],
                        _?.describedBy.value,
                    ),
                    'aria-invalid':
                        z[`aria-invalid`] ?? (_?.invalid.value || void 0),
                };
            }
            function U() {
                _?.handleControlFocus();
            }
            function W() {
                E.value
                    ? _?.handleControlBlur()
                    : _?.reportControlState({ focused: !1, touched: !0 });
            }
            let G;
            return (
                a(() => {
                    E.value &&
                        (G = _?.registerControl({
                            id: () => D.value,
                            element: () => f.value,
                            getValue: () => M.value,
                            required: () => n.required,
                            isFilled: (e) => e === !0,
                        }));
                }),
                s(() => G?.()),
                De({ disabled: S, state: M }),
                (t, n) => (
                    l(),
                    p(
                        h,
                        null,
                        [
                            (l(),
                            v(
                                te(k(m)?.rovingFocus.value ? k(Ce) : k(F)),
                                d(
                                    {
                                        ...k(_)?.dataAttributes.value,
                                        ...t.$attrs,
                                        ...k(I),
                                        ...H(),
                                    },
                                    {
                                        id: D.value,
                                        ref: k(o),
                                        role: `checkbox`,
                                        'as-child': e.asChild,
                                        as: e.as,
                                        type:
                                            e.as === `button`
                                                ? `button`
                                                : void 0,
                                        'aria-checked': k(Y)(M.value)
                                            ? `mixed`
                                            : M.value,
                                        'aria-required': A.value,
                                        'aria-label':
                                            t.$attrs[`aria-label`] || V.value,
                                        'data-state': k(X)(M.value),
                                        'aria-disabled': T.value
                                            ? `true`
                                            : void 0,
                                        'data-disabled': S.value ? `` : void 0,
                                        disabled: S.value,
                                        focusable: k(m)?.rovingFocus.value
                                            ? !S.value
                                            : void 0,
                                        onKeydown: ee(
                                            u(() => {}, [`prevent`]),
                                            [`enter`],
                                        ),
                                        onClick: N,
                                        onFocus: U,
                                        onBlur: W,
                                    },
                                ),
                                {
                                    default: g(() => [
                                        i(t.$slots, `default`, {
                                            modelValue: k(y),
                                            state: M.value,
                                        }),
                                    ]),
                                    _: 3,
                                },
                                16,
                                [
                                    `id`,
                                    `as-child`,
                                    `as`,
                                    `type`,
                                    `aria-checked`,
                                    `aria-required`,
                                    `aria-label`,
                                    `data-state`,
                                    `aria-disabled`,
                                    `data-disabled`,
                                    `disabled`,
                                    `focusable`,
                                    `onKeydown`,
                                ],
                            )),
                            k(P) && O.value && !k(m)
                                ? (l(),
                                  v(
                                      k(Se),
                                      d(
                                          {
                                              key: 0,
                                              type: `checkbox`,
                                              checked: !!M.value,
                                              name: O.value,
                                              value: e.value,
                                              disabled: S.value,
                                              required: A.value,
                                          },
                                          k(I),
                                      ),
                                      null,
                                      16,
                                      [
                                          `checked`,
                                          `name`,
                                          `value`,
                                          `disabled`,
                                          `required`,
                                      ],
                                  ))
                                : b(`v-if`, !0),
                        ],
                        64,
                    )
                )
            );
        },
    }),
    ke = j({
        __name: `CheckboxIndicator`,
        props: {
            forceMount: { type: Boolean, required: !1 },
            asChild: { type: Boolean, required: !1 },
            as: { type: null, required: !1, default: `span` },
        },
        setup(e) {
            let { forwardRef: t } = B(),
                r = Ee();
            return (a, o) => (
                l(),
                v(
                    k(n),
                    {
                        present:
                            e.forceMount ||
                            k(Y)(k(r).state.value) ||
                            k(r).state.value === !0,
                    },
                    {
                        default: g(() => [
                            S(
                                k(F),
                                d(
                                    {
                                        ref: k(t),
                                        'data-state': k(X)(k(r).state.value),
                                        'data-disabled': k(r).disabled.value
                                            ? ``
                                            : void 0,
                                        style: { pointerEvents: `none` },
                                        'as-child': e.asChild,
                                        as: e.as,
                                    },
                                    a.$attrs,
                                ),
                                {
                                    default: g(() => [i(a.$slots, `default`)]),
                                    _: 3,
                                },
                                16,
                                [
                                    `data-state`,
                                    `data-disabled`,
                                    `as-child`,
                                    `as`,
                                ],
                            ),
                        ]),
                        _: 3,
                    },
                    8,
                    [`present`],
                )
            );
        },
    }),
    Z = { 'data-test': `team-invitation-alert` },
    Ae = j({
        __name: `TeamInvitationAlert`,
        props: { invitation: {}, action: {} },
        setup(e) {
            let t = e,
                n = c(() =>
                    t.action === `login`
                        ? `سجّل الدخول للانضمام إلى فريق "${t.invitation.teamName}".`
                        : `أنشئ حسابًا للانضمام إلى فريق "${t.invitation.teamName}".`,
                );
            return (e, t) => (
                l(),
                p(`div`, Z, [
                    S(
                        k(ye),
                        {
                            class: `border-primary/25 bg-accent text-accent-foreground [&>svg]:text-primary`,
                        },
                        {
                            default: g(() => [
                                S(k(pe), { class: `size-4` }),
                                S(
                                    k(be),
                                    { class: `text-accent-foreground` },
                                    {
                                        default: g(() => [D(T(n.value), 1)]),
                                        _: 1,
                                    },
                                ),
                            ]),
                            _: 1,
                        },
                    ),
                ])
            );
        },
    }),
    je = j({
        __name: `Checkbox`,
        props: {
            defaultValue: {},
            modelValue: {},
            disabled: { type: Boolean },
            value: {},
            id: {},
            trueValue: {},
            falseValue: {},
            asChild: { type: Boolean },
            as: {},
            name: {},
            required: { type: Boolean },
            class: { type: [Boolean, null, String, Object, Array] },
        },
        emits: [`update:modelValue`],
        setup(e, { emit: t }) {
            let n = e,
                r = t,
                a = ae(n, `class`),
                o = H(a, r);
            return (e, t) => (
                l(),
                v(
                    k(Oe),
                    d({ 'data-slot': `checkbox` }, k(o), {
                        class: k(P)(
                            `peer border-input data-[state=checked]:bg-primary data-[state=checked]:text-primary-foreground data-[state=checked]:border-primary focus-visible:border-ring focus-visible:ring-ring/50 aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive size-4 shrink-0 rounded-[4px] border shadow-xs transition-shadow outline-none focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50`,
                            n.class,
                        ),
                    }),
                    {
                        default: g((t) => [
                            S(
                                k(ke),
                                {
                                    'data-slot': `checkbox-indicator`,
                                    class: `grid place-content-center text-current transition-none`,
                                },
                                {
                                    default: g(() => [
                                        i(e.$slots, `default`, N(x(t)), () => [
                                            S(k(W), { class: `size-3.5` }),
                                        ]),
                                    ]),
                                    _: 2,
                                },
                                1024,
                            ),
                        ]),
                        _: 3,
                    },
                    16,
                    [`class`],
                )
            );
        },
    }),
    Q = (e) => ({ url: Q.url(e), method: `post` });
((Q.definition = { methods: [`post`], url: `/login` }),
    (Q.url = (e) => Q.definition.url + se(e)),
    (Q.post = (e) => ({ url: Q.url(e), method: `post` })));
var $ = (e) => ({ action: Q.url(e), method: `post` });
(($.post = (e) => ({ action: Q.url(e), method: `post` })),
    (Q.form = $),
    Object.assign(Q, Q));
var Me = {
        key: 0,
        class: `mb-4 text-center text-sm font-medium text-green-600`,
    },
    Ne = { class: `grid gap-6` },
    Pe = { class: `grid gap-2` },
    Fe = { class: `grid gap-2` },
    Ie = { class: `flex items-center justify-between` },
    Le = { class: `flex items-center justify-between` },
    Re = j({
        layout: {
            title: `تسجيل الدخول`,
            description: `ادخل البريد الإلكتروني وكلمة المرور للدخول على النظام`,
        },
        __name: `Login`,
        props: {
            status: {},
            canResetPassword: { type: Boolean },
            teamInvitation: {},
        },
        setup(e) {
            return (t, n) => {
                let r = M(`focus`);
                return (
                    l(),
                    p(
                        h,
                        null,
                        [
                            S(k(A), { title: `تسجيل الدخول` }),
                            e.status
                                ? (l(), p(`div`, Me, T(e.status), 1))
                                : b(``, !0),
                            e.teamInvitation
                                ? (l(),
                                  v(
                                      Ae,
                                      {
                                          key: 1,
                                          invitation: e.teamInvitation,
                                          action: `login`,
                                      },
                                      null,
                                      8,
                                      [`invitation`],
                                  ))
                                : b(``, !0),
                            S(ge),
                            S(
                                k(O),
                                d(k(Q).form(), {
                                    'reset-on-success': [`password`],
                                    class: `flex flex-col gap-6`,
                                }),
                                {
                                    default: g(
                                        ({ errors: t, processing: i }) => [
                                            y(`div`, Ne, [
                                                y(`div`, Pe, [
                                                    S(
                                                        k(U),
                                                        { for: `email` },
                                                        {
                                                            default: g(() => [
                                                                ...(n[0] ||= [
                                                                    D(
                                                                        `البريد الإلكتروني`,
                                                                        -1,
                                                                    ),
                                                                ]),
                                                            ]),
                                                            _: 1,
                                                        },
                                                    ),
                                                    o(
                                                        S(
                                                            k(oe),
                                                            {
                                                                id: `email`,
                                                                type: `email`,
                                                                name: `email`,
                                                                required: ``,
                                                                tabindex: 1,
                                                                autocomplete: `email`,
                                                                placeholder: `email@example.com`,
                                                            },
                                                            null,
                                                            512,
                                                        ),
                                                        [[r]],
                                                    ),
                                                    S(
                                                        K,
                                                        { message: t.email },
                                                        null,
                                                        8,
                                                        [`message`],
                                                    ),
                                                ]),
                                                y(`div`, Fe, [
                                                    y(`div`, Ie, [
                                                        S(
                                                            k(U),
                                                            { for: `password` },
                                                            {
                                                                default: g(
                                                                    () => [
                                                                        ...(n[1] ||=
                                                                            [
                                                                                D(
                                                                                    `كلمة المرور`,
                                                                                    -1,
                                                                                ),
                                                                            ]),
                                                                    ],
                                                                ),
                                                                _: 1,
                                                            },
                                                        ),
                                                        e.canResetPassword
                                                            ? (l(),
                                                              v(
                                                                  _e,
                                                                  {
                                                                      key: 0,
                                                                      href: k(
                                                                          ve,
                                                                      )(),
                                                                      class: `text-sm`,
                                                                      tabindex: 5,
                                                                  },
                                                                  {
                                                                      default:
                                                                          g(
                                                                              () => [
                                                                                  ...(n[2] ||=
                                                                                      [
                                                                                          D(
                                                                                              ` نسيت كلمة المرور؟ `,
                                                                                              -1,
                                                                                          ),
                                                                                      ]),
                                                                              ],
                                                                          ),
                                                                      _: 1,
                                                                  },
                                                                  8,
                                                                  [`href`],
                                                              ))
                                                            : b(``, !0),
                                                    ]),
                                                    S(G, {
                                                        id: `password`,
                                                        name: `password`,
                                                        required: ``,
                                                        tabindex: 2,
                                                        autocomplete: `current-password`,
                                                        placeholder: `كلمة المرور`,
                                                    }),
                                                    S(
                                                        K,
                                                        { message: t.password },
                                                        null,
                                                        8,
                                                        [`message`],
                                                    ),
                                                ]),
                                                y(`div`, Le, [
                                                    S(
                                                        k(U),
                                                        {
                                                            for: `remember`,
                                                            class: `flex items-center gap-3`,
                                                        },
                                                        {
                                                            default: g(() => [
                                                                S(k(je), {
                                                                    id: `remember`,
                                                                    name: `remember`,
                                                                    tabindex: 3,
                                                                }),
                                                                (n[3] ||= y(
                                                                    `span`,
                                                                    null,
                                                                    `تذكّرني`,
                                                                    -1,
                                                                )),
                                                            ]),
                                                            _: 1,
                                                        },
                                                    ),
                                                ]),
                                                S(
                                                    k(I),
                                                    {
                                                        type: `submit`,
                                                        class: `mt-4 w-full`,
                                                        tabindex: 4,
                                                        disabled: i,
                                                        'data-test': `login-button`,
                                                    },
                                                    {
                                                        default: g(() => [
                                                            i
                                                                ? (l(),
                                                                  v(k(he), {
                                                                      key: 0,
                                                                  }))
                                                                : b(``, !0),
                                                            (n[4] ||= D(
                                                                ` دخول `,
                                                                -1,
                                                            )),
                                                        ]),
                                                        _: 2,
                                                    },
                                                    1032,
                                                    [`disabled`],
                                                ),
                                            ]),
                                            (n[5] ||= y(
                                                `p`,
                                                {
                                                    class: `text-center text-sm text-muted-foreground`,
                                                },
                                                ` الحسابات بتتعمل عن طريق إدارة النظام. لو محتاج صلاحية دخول كلّم المسؤول. `,
                                                -1,
                                            )),
                                        ],
                                    ),
                                    _: 1,
                                },
                                16,
                            ),
                        ],
                        64,
                    )
                );
            };
        },
    });
export { Re as default };
