import { n as e, o as t, t as n } from './Presence-BI6XYFRa.js';
import {
    $n as r,
    $t as i,
    Bt as a,
    It as o,
    J as s,
    Kt as c,
    M as l,
    Mt as u,
    Nt as d,
    Q as f,
    Qt as p,
    R as m,
    Tn as h,
    Wt as g,
    X as _,
    Z as v,
    _t as y,
    at as b,
    bn as x,
    dn as S,
    ht as C,
    j as w,
    nn as ee,
    or as T,
    st as E,
    ur as D,
} from './dist-C0kHilBU.js';
import { a as O, n as k } from './button-BRBVuDNP.js';
import { i as A, n as j, r as M, t as te } from './FieldRoot-_RlB5iPB.js';
import { a as N, c as P, t as F } from './useForwardExpose-CL6aaO9U.js';
import { t as I } from './VisuallyHidden-DwTNoksw.js';
import { t as L } from './useForwardPropsEmits-re_7TsxN.js';
import { t as R } from './check-DKhmhOm5.js';
import { s as z } from './shared-DeHk4G-N.js';
import {
    Ct as B,
    St as V,
    bt as H,
    gt as U,
    wt as W,
    xt as G,
} from './app-DkL2j24G.js';
function K(e, t) {
    return M(e) ? !1 : Array.isArray(e) ? e.some((e) => A(e, t)) : A(e, t);
}
function q() {
    let e = C()?.vnode?.scopeId;
    return e ? { [e]: `` } : {};
}
var J = E({
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
                { primitiveElement: n, currentElement: r } = W(),
                i = s(() => t.checked ?? t.value);
            return (
                x(i, (e, t) => {
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
                    c(),
                    _(
                        I,
                        u(
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
    Y = E({
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
                n = s(
                    () =>
                        typeof t.value == `object` &&
                        Array.isArray(t.value) &&
                        t.value.length === 0 &&
                        t.required,
                ),
                r = s(() =>
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
                c(),
                f(
                    m,
                    null,
                    [
                        v(` We render single input if it's required `),
                        n.value
                            ? (c(),
                              _(
                                  J,
                                  u(
                                      { key: e.name },
                                      { ...t, ...i.$attrs },
                                      { name: e.name, value: e.value },
                                  ),
                                  null,
                                  16,
                                  [`name`, `value`],
                              ))
                            : (c(!0),
                              f(
                                  m,
                                  { key: 1 },
                                  p(
                                      r.value,
                                      (e) => (
                                          c(),
                                          _(
                                              J,
                                              u(
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
    X = E({
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
                o = U(),
                l = e(),
                u = s(() => n.tabStopId || l),
                f = s(() => o.currentTabStopId.value === u.value),
                { getItems: p, CollectionItem: m } = B();
            (a(() => {
                n.focusable && o.onFocusableItemAdd();
            }),
                g(() => {
                    n.focusable && o.onFocusableItemRemove();
                }),
                x(
                    () => n.focusable,
                    (e, t) => {
                        e !== t &&
                            (e
                                ? o.onFocusableItemAdd()
                                : o.onFocusableItemRemove());
                    },
                ));
            function v(e) {
                if (e.defaultPrevented) return;
                if (e.key === `Tab` && e.shiftKey) {
                    o.onItemShiftTab();
                    return;
                }
                if (e.target !== e.currentTarget) return;
                let t = G(e, o.orientation.value, o.dir.value);
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
                        r = o.loop.value ? V(r, n + 1) : r.slice(n + 1);
                    }
                    d(() => H(r));
                }
            }
            let { forwardRef: y, currentElement: S } = F(),
                C = r();
            (x(
                S,
                (e) => {
                    C.value = e;
                },
                { immediate: !0, flush: `post` },
            ),
                N(C, `keydown`, v));
            function w(e) {
                C.value = e.currentTarget;
            }
            return (e, n) => (
                c(),
                _(T(m), null, {
                    default: h(() => [
                        b(
                            T(k),
                            {
                                ref: T(y),
                                tabindex: f.value ? 0 : -1,
                                'data-orientation': T(o).orientation.value,
                                'data-active': t.active ? `` : void 0,
                                'data-disabled': t.focusable ? void 0 : ``,
                                as: t.as,
                                'as-child': t.asChild,
                                onMousedown: (n[0] ||= (e) => {
                                    t.focusable
                                        ? T(o).onItemFocus(u.value)
                                        : e.preventDefault();
                                }),
                                onFocus: (n[1] ||= (e) =>
                                    T(o).onItemFocus(u.value)),
                                onFocusin: w,
                            },
                            {
                                default: h(() => [i(e.$slots, `default`)]),
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
    [ne, re] = t(`CheckboxGroupRoot`);
function Z(e) {
    return e === `indeterminate`;
}
function Q(e) {
    return Z(e) ? `indeterminate` : e ? `checked` : `unchecked`;
}
var [$, ie] = t(`CheckboxRoot`),
    ae = E({
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
                { forwardRef: d, currentElement: p } = F(),
                g = ne(null),
                y = te(null),
                b = P(n, `modelValue`, r, {
                    defaultValue: n.defaultValue ?? n.falseValue,
                    passive: n.modelValue === void 0,
                }),
                C = s(() => {
                    let e = g?.max.value;
                    if (M(e)) return !1;
                    let t = g.modelValue.value ?? [];
                    return !K(t, n.value) && t.length >= e;
                }),
                E = s(
                    () =>
                        !!(
                            g?.disabled.value ||
                            n.disabled ||
                            y?.disabled.value
                        ),
                ),
                D = s(() => E.value || C.value),
                O = s(() => !!y && !g),
                N = s(() => n.id ?? (O.value ? y?.fieldId.value : void 0)),
                I = s(() => n.name ?? y?.name.value),
                L = s(() => (y ? n.required || y.required.value : n.required)),
                R = s(() => A(b.value, n.trueValue)),
                z = s(() =>
                    M(g?.modelValue.value)
                        ? b.value === `indeterminate`
                            ? `indeterminate`
                            : R.value
                        : K(g.modelValue.value, n.value),
                );
            function B() {
                if (D.value) return;
                let e = z.value !== !0;
                if (M(g?.modelValue.value))
                    b.value === `indeterminate`
                        ? (b.value = n.trueValue)
                        : (b.value = R.value ? n.falseValue : n.trueValue);
                else {
                    let e = [...(g.modelValue.value || [])];
                    if (K(e, n.value)) {
                        let t = e.findIndex((e) => A(e, n.value));
                        e.splice(t, 1);
                    } else e.push(n.value);
                    g.modelValue.value = e;
                }
                O.value && y?.handleControlInput({ value: e });
            }
            x(z, (e) => {
                O.value && y?.reportControlState({ filled: e === !0 });
            });
            let V = j(p),
                H = q(),
                U = S(),
                W = s(() => {
                    if (!U[`aria-label`])
                        return N.value && p.value
                            ? document.querySelector(`[for="${N.value}"]`)
                                  ?.innerText
                            : void 0;
                });
            function G() {
                let e = (e, t) => [e, t].filter(Boolean).join(` `) || void 0;
                return {
                    'aria-labelledby': e(
                        U[`aria-labelledby`],
                        O.value ? y?.labelId.value : void 0,
                    ),
                    'aria-describedby': e(
                        U[`aria-describedby`],
                        y?.describedBy.value,
                    ),
                    'aria-invalid':
                        U[`aria-invalid`] ?? (y?.invalid.value || void 0),
                };
            }
            function J() {
                y?.handleControlFocus();
            }
            function re() {
                O.value
                    ? y?.handleControlBlur()
                    : y?.reportControlState({ focused: !1, touched: !0 });
            }
            let $;
            return (
                a(() => {
                    O.value &&
                        ($ = y?.registerControl({
                            id: () => N.value,
                            element: () => p.value,
                            getValue: () => z.value,
                            required: () => n.required,
                            isFilled: (e) => e === !0,
                        }));
                }),
                o(() => $?.()),
                ie({ disabled: E, state: z }),
                (t, n) => (
                    c(),
                    f(
                        m,
                        null,
                        [
                            (c(),
                            _(
                                ee(T(g)?.rovingFocus.value ? T(X) : T(k)),
                                u(
                                    {
                                        ...T(y)?.dataAttributes.value,
                                        ...t.$attrs,
                                        ...T(H),
                                        ...G(),
                                    },
                                    {
                                        id: N.value,
                                        ref: T(d),
                                        role: `checkbox`,
                                        'as-child': e.asChild,
                                        as: e.as,
                                        type:
                                            e.as === `button`
                                                ? `button`
                                                : void 0,
                                        'aria-checked': T(Z)(z.value)
                                            ? `mixed`
                                            : z.value,
                                        'aria-required': L.value,
                                        'aria-label':
                                            t.$attrs[`aria-label`] || W.value,
                                        'data-state': T(Q)(z.value),
                                        'aria-disabled': D.value
                                            ? `true`
                                            : void 0,
                                        'data-disabled': E.value ? `` : void 0,
                                        disabled: E.value,
                                        focusable: T(g)?.rovingFocus.value
                                            ? !E.value
                                            : void 0,
                                        onKeydown: w(
                                            l(() => {}, [`prevent`]),
                                            [`enter`],
                                        ),
                                        onClick: B,
                                        onFocus: J,
                                        onBlur: re,
                                    },
                                ),
                                {
                                    default: h(() => [
                                        i(t.$slots, `default`, {
                                            modelValue: T(b),
                                            state: z.value,
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
                            T(V) && I.value && !T(g)
                                ? (c(),
                                  _(
                                      T(Y),
                                      u(
                                          {
                                              key: 0,
                                              type: `checkbox`,
                                              checked: !!z.value,
                                              name: I.value,
                                              value: e.value,
                                              disabled: E.value,
                                              required: L.value,
                                          },
                                          T(H),
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
                                : v(`v-if`, !0),
                        ],
                        64,
                    )
                )
            );
        },
    }),
    oe = E({
        __name: `CheckboxIndicator`,
        props: {
            forceMount: { type: Boolean, required: !1 },
            asChild: { type: Boolean, required: !1 },
            as: { type: null, required: !1, default: `span` },
        },
        setup(e) {
            let { forwardRef: t } = F(),
                r = $();
            return (a, o) => (
                c(),
                _(
                    T(n),
                    {
                        present:
                            e.forceMount ||
                            T(Z)(T(r).state.value) ||
                            T(r).state.value === !0,
                    },
                    {
                        default: h(() => [
                            b(
                                T(k),
                                u(
                                    {
                                        ref: T(t),
                                        'data-state': T(Q)(T(r).state.value),
                                        'data-disabled': T(r).disabled.value
                                            ? ``
                                            : void 0,
                                        style: { pointerEvents: `none` },
                                        'as-child': e.asChild,
                                        as: e.as,
                                    },
                                    a.$attrs,
                                ),
                                {
                                    default: h(() => [i(a.$slots, `default`)]),
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
    se = E({
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
                a = z(n, `class`),
                o = L(a, r);
            return (e, t) => (
                c(),
                _(
                    T(ae),
                    u({ 'data-slot': `checkbox` }, T(o), {
                        class: T(O)(
                            `peer border-input data-[state=checked]:bg-primary data-[state=checked]:text-primary-foreground data-[state=checked]:border-primary focus-visible:border-ring focus-visible:ring-ring/50 aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive size-4 shrink-0 rounded-[4px] border shadow-xs transition-shadow outline-none focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50`,
                            n.class,
                        ),
                    }),
                    {
                        default: h((t) => [
                            b(
                                T(oe),
                                {
                                    'data-slot': `checkbox-indicator`,
                                    class: `grid place-content-center text-current transition-none`,
                                },
                                {
                                    default: h(() => [
                                        i(e.$slots, `default`, D(y(t)), () => [
                                            b(T(R), { class: `size-3.5` }),
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
    });
export { se as t };
