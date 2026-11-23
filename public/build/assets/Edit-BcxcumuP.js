import { a as e, i as t, n, o as r, t as i } from './Presence-BI6XYFRa.js';
import {
    $t as a,
    Bt as o,
    H as s,
    It as c,
    J as l,
    Kt as u,
    M as d,
    Mt as f,
    Nt as p,
    Q as m,
    Qt as h,
    R as g,
    Tn as _,
    Wt as v,
    X as y,
    Xn as b,
    Y as x,
    Z as S,
    _t as C,
    at as w,
    bn as T,
    dn as E,
    dr as D,
    fr as O,
    it as k,
    lr as A,
    mr as j,
    n as M,
    nn as N,
    or as P,
    r as ee,
    rr as F,
    st as I,
    ur as te,
    xn as ne,
} from './dist-C0kHilBU.js';
import { a as L, n as R, t as z } from './button-BRBVuDNP.js';
import { i as re, n as ie, r as ae, t as oe } from './FieldRoot-_RlB5iPB.js';
import {
    d as se,
    f as ce,
    g as le,
    m as ue,
    o as de,
    p as fe,
    t as pe,
} from './Teleport-DOuIax1n.js';
import {
    c as me,
    i as he,
    m as ge,
    s as _e,
    t as B,
} from './useForwardExpose-CL6aaO9U.js';
import { t as ve } from './useDirection-zK8JAAvw.js';
import { n as V, t as ye } from './useForwardPropsEmits-re_7TsxN.js';
import { t as be } from './Label-BhHH3wmI.js';
import { t as xe } from './createLucideIcon-C6NZ5pDL.js';
import { s as H } from './shared-DeHk4G-N.js';
import { t as Se } from './Input-iZAg09j4.js';
import { n as Ce, t as we } from './badge-CuI5vV9Y.js';
import {
    A as Te,
    B as Ee,
    Dt as U,
    Et as De,
    F as Oe,
    J as ke,
    Lt as Ae,
    M as je,
    N as Me,
    O as Ne,
    P as Pe,
    R as Fe,
    Rt as Ie,
    St as Le,
    _t as Re,
    bt as ze,
    c as Be,
    j as Ve,
    k as He,
    l as Ue,
    n as We,
    o as Ge,
    p as W,
    r as Ke,
    s as qe,
    t as Je,
    u as Ye,
    xt as Xe,
    z as Ze,
    zt as Qe,
} from './app-CZp4sxGj.js';
import { t as G } from './InputError-_9qlcKk2.js';
import {
    a as K,
    i as q,
    n as J,
    o as Y,
    r as X,
    t as $e,
} from './dialog-B-IA8Lzj.js';
import { t as et } from './DialogClose-DEdGo09K.js';
function tt(e, t = -1 / 0, n = 1 / 0) {
    return Math.min(n, Math.max(t, e));
}
function nt(e) {
    let n = t({ nonce: b() });
    return l(() => e?.value || n.nonce?.value);
}
var rt = [` `, `Enter`, `ArrowUp`, `ArrowDown`],
    it = [` `, `Enter`];
function Z(e, t, n) {
    return e === void 0
        ? !1
        : Array.isArray(e)
          ? e.some((e) => at(e, t, n))
          : at(e, t, n);
}
function at(e, t, n) {
    return e === void 0 || t === void 0
        ? !1
        : typeof e == `string`
          ? e === t
          : typeof n == `function`
            ? n(e, t)
            : typeof n == `string`
              ? e?.[n] === t?.[n]
              : re(e, t);
}
function ot(e) {
    return e == null || e === `` || (Array.isArray(e) && e.length === 0);
}
var st = [`value`],
    [Q, ct] = r(`SelectRoot`),
    lt = I({
        inheritAttrs: !1,
        __name: `SelectRoot`,
        props: {
            open: { type: Boolean, required: !1, default: void 0 },
            defaultOpen: { type: Boolean, required: !1 },
            defaultValue: { type: null, required: !1 },
            modelValue: { type: null, required: !1, default: void 0 },
            nullableValue: { type: String, required: !1, default: `` },
            by: { type: [String, Function], required: !1 },
            dir: { type: String, required: !1 },
            multiple: { type: Boolean, required: !1 },
            autocomplete: { type: String, required: !1 },
            disabled: { type: Boolean, required: !1 },
            name: { type: String, required: !1 },
            required: { type: Boolean, required: !1 },
        },
        emits: [`update:modelValue`, `update:open`],
        setup(e, { emit: t }) {
            let n = e,
                r = t,
                { multiple: i, dir: o } = F(n),
                s = oe(null),
                c = l(() => n.name ?? s?.name.value),
                d = l(() => (s ? n.required || s.required.value : n.required)),
                p = l(() => !!(n.disabled || s?.disabled.value)),
                v = me(n, `modelValue`, r, {
                    defaultValue: n.defaultValue ?? (i.value ? [] : void 0),
                    passive: n.modelValue === void 0,
                    deep: !0,
                }),
                x = me(n, `open`, r, {
                    defaultValue: n.defaultOpen,
                    passive: n.open === void 0,
                }),
                C = b(),
                w = b(),
                E = b({ x: 0, y: 0 }),
                D = l(() =>
                    i.value && Array.isArray(v.value)
                        ? v.value?.length === 0
                        : ae(v.value),
                );
            (T(v, () => {
                s?.reportControlState({ filled: !D.value });
            }),
                U({ isProvider: !0 }));
            let O = ve(o),
                k = ie(C),
                A = b(new Set()),
                j = l(() =>
                    Array.from(A.value)
                        .map((e) => e.value)
                        .join(`;`),
                );
            function M(e) {
                let t;
                if (i.value) {
                    let r = Array.isArray(v.value) ? [...v.value] : [],
                        i = r.findIndex((t) => at(t, e, n.by));
                    (i === -1 ? r.push(e) : r.splice(i, 1), (t = [...r]));
                } else t = e;
                ((v.value = t), s?.handleControlInput({ value: t }));
            }
            function N(e) {
                return Array.from(A.value).find((t) => Z(e, t.value, n.by));
            }
            return (
                ct({
                    triggerElement: C,
                    onTriggerChange: (e) => {
                        C.value = e;
                    },
                    valueElement: w,
                    onValueElementChange: (e) => {
                        w.value = e;
                    },
                    contentId: ``,
                    modelValue: v,
                    onValueChange: M,
                    by: n.by,
                    open: x,
                    multiple: i,
                    required: d,
                    onOpenChange: (e) => {
                        x.value = e;
                    },
                    dir: O,
                    triggerPointerDownPosRef: E,
                    disabled: p,
                    isEmptyModelValue: D,
                    optionsSet: A,
                    onOptionAdd: (e) => {
                        let t = N(e.value);
                        (t && A.value.delete(t), A.value.add(e));
                    },
                    onOptionRemove: (e) => {
                        let t = N(e.value);
                        t && A.value.delete(t);
                    },
                }),
                (t, n) => (
                    u(),
                    y(P(Le), null, {
                        default: _(() => [
                            a(t.$slots, `default`, {
                                modelValue: P(v),
                                open: P(x),
                            }),
                            P(k) && c.value
                                ? (u(),
                                  y(
                                      ut,
                                      {
                                          key: j.value,
                                          'aria-hidden': `true`,
                                          tabindex: `-1`,
                                          multiple: P(i),
                                          required: d.value,
                                          name: c.value,
                                          autocomplete: e.autocomplete,
                                          disabled: p.value,
                                          value: P(v),
                                      },
                                      {
                                          default: _(() => [
                                              P(ae)(P(v))
                                                  ? (u(),
                                                    m(
                                                        `option`,
                                                        {
                                                            key: 0,
                                                            value: e.nullableValue,
                                                        },
                                                        null,
                                                        8,
                                                        st,
                                                    ))
                                                  : S(`v-if`, !0),
                                              (u(!0),
                                              m(
                                                  g,
                                                  null,
                                                  h(
                                                      Array.from(A.value),
                                                      (e) => (
                                                          u(),
                                                          m(
                                                              `option`,
                                                              f(
                                                                  {
                                                                      key:
                                                                          e.value ??
                                                                          ``,
                                                                  },
                                                                  {
                                                                      ref_for:
                                                                          !0,
                                                                  },
                                                                  e,
                                                              ),
                                                              null,
                                                              16,
                                                          )
                                                      ),
                                                  ),
                                                  128,
                                              )),
                                          ]),
                                          _: 1,
                                      },
                                      8,
                                      [
                                          `multiple`,
                                          `required`,
                                          `name`,
                                          `autocomplete`,
                                          `disabled`,
                                          `value`,
                                      ],
                                  ))
                                : S(`v-if`, !0),
                        ]),
                        _: 3,
                    })
                )
            );
        },
    }),
    ut = I({
        __name: `BubbleSelect`,
        props: {
            autocomplete: { type: String, required: !1 },
            autofocus: { type: Boolean, required: !1 },
            disabled: { type: Boolean, required: !1 },
            form: { type: String, required: !1 },
            multiple: { type: Boolean, required: !1 },
            name: { type: String, required: !1 },
            required: { type: Boolean, required: !1 },
            size: { type: Number, required: !1 },
            value: { type: null, required: !1 },
        },
        setup(e) {
            let t = e,
                n = b(),
                r = Q();
            T(
                () => t.value,
                (e, t) => {
                    let r = window.HTMLSelectElement.prototype,
                        i = Object.getOwnPropertyDescriptor(r, `value`).set;
                    if (e !== t && i && n.value) {
                        let t = new Event(`change`, { bubbles: !0 });
                        (i.call(n.value, e), n.value.dispatchEvent(t));
                    }
                },
            );
            function i(e) {
                r.onValueChange(e.target.value);
            }
            return (e, r) => (
                u(),
                y(
                    P(De),
                    { 'as-child': `` },
                    {
                        default: _(() => [
                            x(
                                `select`,
                                f({ ref_key: `selectElement`, ref: n }, t, {
                                    onInput: i,
                                }),
                                [a(e.$slots, `default`)],
                                16,
                            ),
                        ]),
                        _: 3,
                    },
                )
            );
        },
    }),
    dt = I({
        __name: `SelectPopperPosition`,
        props: {
            memoDependencies: { type: Array, required: !1 },
            side: { type: null, required: !1 },
            sideOffset: { type: Number, required: !1 },
            sideFlip: { type: Boolean, required: !1 },
            align: { type: null, required: !1, default: `start` },
            alignOffset: { type: Number, required: !1 },
            alignFlip: { type: Boolean, required: !1 },
            avoidCollisions: { type: Boolean, required: !1 },
            collisionBoundary: { type: null, required: !1 },
            collisionPadding: {
                type: [Number, Object],
                required: !1,
                default: 10,
            },
            arrowPadding: { type: Number, required: !1 },
            hideShiftedArrow: { type: Boolean, required: !1 },
            sticky: { type: String, required: !1 },
            hideWhenDetached: { type: Boolean, required: !1 },
            positionStrategy: { type: String, required: !1 },
            updatePositionStrategy: { type: String, required: !1 },
            disableUpdateOnLayoutShift: { type: Boolean, required: !1 },
            prioritizePosition: { type: Boolean, required: !1 },
            reference: { type: null, required: !1 },
            dir: { type: String, required: !1 },
            asChild: { type: Boolean, required: !1 },
            as: { type: null, required: !1 },
        },
        setup(e) {
            let t = V(e);
            return (e, n) => (
                u(),
                y(
                    P(ze),
                    f(P(t), {
                        style: {
                            boxSizing: `border-box`,
                            '--reka-select-content-transform-origin': `var(--reka-popper-transform-origin)`,
                            '--reka-select-content-available-width': `var(--reka-popper-available-width)`,
                            '--reka-select-content-available-height': `var(--reka-popper-available-height)`,
                            '--reka-select-trigger-width': `var(--reka-popper-anchor-width)`,
                            '--reka-select-trigger-height': `var(--reka-popper-anchor-height)`,
                        },
                    }),
                    { default: _(() => [a(e.$slots, `default`)]), _: 3 },
                    16,
                )
            );
        },
    }),
    ft = {
        onViewportChange: () => {},
        itemTextRefCallback: () => {},
        itemRefCallback: () => {},
    },
    [$, pt] = r(`SelectContent`),
    mt = I({
        __name: `SelectContentImpl`,
        props: {
            position: { type: String, required: !1, default: `item-aligned` },
            bodyLock: { type: Boolean, required: !1, default: !0 },
            loop: { type: Boolean, required: !1 },
            memoDependencies: { type: Array, required: !1 },
            side: { type: null, required: !1 },
            sideOffset: { type: Number, required: !1 },
            sideFlip: { type: Boolean, required: !1 },
            align: { type: null, required: !1, default: `start` },
            alignOffset: { type: Number, required: !1 },
            alignFlip: { type: Boolean, required: !1 },
            avoidCollisions: { type: Boolean, required: !1 },
            collisionBoundary: { type: null, required: !1 },
            collisionPadding: { type: [Number, Object], required: !1 },
            arrowPadding: { type: Number, required: !1 },
            hideShiftedArrow: { type: Boolean, required: !1 },
            sticky: { type: String, required: !1 },
            hideWhenDetached: { type: Boolean, required: !1 },
            positionStrategy: { type: String, required: !1 },
            updatePositionStrategy: { type: String, required: !1 },
            disableUpdateOnLayoutShift: { type: Boolean, required: !1 },
            prioritizePosition: { type: Boolean, required: !1 },
            reference: { type: null, required: !1 },
            dir: { type: String, required: !1 },
            asChild: { type: Boolean, required: !1 },
            as: { type: null, required: !1 },
            disableOutsidePointerEvents: {
                type: Boolean,
                required: !1,
                default: !0,
            },
        },
        emits: [`closeAutoFocus`, `escapeKeyDown`, `pointerDownOutside`],
        setup(e, { emit: t }) {
            let n = e,
                r = t,
                i = Q();
            (Qe(), ue(n.bodyLock));
            let { CollectionSlot: o, getItems: s } = U(),
                c = b();
            fe(c);
            let { search: p, handleTypeaheadSearch: m } = Ae(),
                h = b(),
                g = b(),
                v = b(),
                x = b(!1),
                S = b(!1),
                C = b(!1);
            function E() {
                g.value && c.value && de([g.value, c.value]);
            }
            T(x, () => {
                E();
            });
            let { onOpenChange: D, triggerPointerDownPosRef: O } = i;
            ne((e) => {
                if (!c.value) return;
                let t = { x: 0, y: 0 },
                    n = (e) => {
                        t = {
                            x: Math.abs(
                                Math.round(e.pageX) - (O.value?.x ?? 0),
                            ),
                            y: Math.abs(
                                Math.round(e.pageY) - (O.value?.y ?? 0),
                            ),
                        };
                    },
                    r = (e) => {
                        e.pointerType !== `touch` &&
                            (t.x <= 10 && t.y <= 10
                                ? e.preventDefault()
                                : c.value?.contains(e.target) || D(!1),
                            document.removeEventListener(`pointermove`, n),
                            (O.value = null));
                    };
                (O.value !== null &&
                    (document.addEventListener(`pointermove`, n),
                    document.addEventListener(`pointerup`, r, {
                        capture: !0,
                        once: !0,
                    })),
                    e(() => {
                        (document.removeEventListener(`pointermove`, n),
                            document.removeEventListener(`pointerup`, r, {
                                capture: !0,
                            }));
                    }));
            });
            function k(e) {
                let t = e.ctrlKey || e.altKey || e.metaKey;
                if (
                    (e.key === `Tab` && e.preventDefault(), e.code === `Space`)
                ) {
                    if (p.value === ``) return;
                    e.preventDefault();
                }
                if (
                    (!t && e.key.length === 1 && m(e.key, s()),
                    [`ArrowUp`, `ArrowDown`, `Home`, `End`].includes(e.key))
                ) {
                    let t = [...s().map((e) => e.ref)];
                    if (
                        ([`ArrowUp`, `End`].includes(e.key) &&
                            (t = t.slice().reverse()),
                        [`ArrowUp`, `ArrowDown`].includes(e.key))
                    ) {
                        let r = e.target,
                            i = t.indexOf(r);
                        t = n.loop ? Ie(t, i + 1) : t.slice(i + 1);
                    }
                    (setTimeout(() => de(t)), e.preventDefault());
                }
            }
            let A = ge(n, `loop`),
                j = l(() => (n.position === `popper` ? A : {})),
                M = V(j.value);
            return (
                pt({
                    content: c,
                    viewport: h,
                    onViewportChange: (e) => {
                        h.value = e;
                    },
                    itemRefCallback: (e, t, n) => {
                        let r = !S.value && !n,
                            a = Z(i.modelValue.value, t, i.by);
                        if (i.multiple.value) {
                            if (C.value) return;
                            (a || r) && ((g.value = e), a && (C.value = !0));
                        } else (a || r) && (g.value = e);
                        r && (S.value = !0);
                    },
                    selectedItem: g,
                    selectedItemText: v,
                    onItemLeave: () => {
                        c.value?.focus();
                    },
                    itemTextRefCallback: (e, t, n) => {
                        let r = !S.value && !n;
                        (Z(i.modelValue.value, t, i.by) || r) && (v.value = e);
                    },
                    focusSelectedItem: E,
                    position: n.position,
                    isPositioned: x,
                    searchRef: p,
                }),
                (t, n) => (
                    u(),
                    y(P(o), null, {
                        default: _(() => [
                            w(
                                P(se),
                                {
                                    'as-child': ``,
                                    onMountAutoFocus: (n[6] ||= d(() => {}, [
                                        `prevent`,
                                    ])),
                                    onUnmountAutoFocus: (n[7] ||= (e) => {
                                        (r(`closeAutoFocus`, e),
                                            !e.defaultPrevented &&
                                                (P(
                                                    i,
                                                ).triggerElement.value?.focus({
                                                    preventScroll: !0,
                                                }),
                                                e.preventDefault()));
                                    }),
                                },
                                {
                                    default: _(() => [
                                        w(
                                            P(ce),
                                            {
                                                'as-child': ``,
                                                'disable-outside-pointer-events':
                                                    e.disableOutsidePointerEvents,
                                                onFocusOutside: (n[2] ||=
                                                    d(() => {}, [`prevent`])),
                                                onDismiss: (n[3] ||= (e) =>
                                                    P(i).onOpenChange(!1)),
                                                onEscapeKeyDown: (n[4] ||= (
                                                    e,
                                                ) => r(`escapeKeyDown`, e)),
                                                onPointerDownOutside: (n[5] ||=
                                                    (e) =>
                                                        r(
                                                            `pointerDownOutside`,
                                                            e,
                                                        )),
                                            },
                                            {
                                                default: _(() => [
                                                    (u(),
                                                    y(
                                                        N(
                                                            e.position ===
                                                                `popper`
                                                                ? dt
                                                                : _t,
                                                        ),
                                                        f(
                                                            {
                                                                ...t.$attrs,
                                                                ...P(M),
                                                            },
                                                            {
                                                                id: P(i)
                                                                    .contentId,
                                                                ref: (e) => {
                                                                    if (!e)
                                                                        return;
                                                                    let t =
                                                                        P(he)(
                                                                            e,
                                                                        );
                                                                    t?.hasAttribute(
                                                                        `data-reka-popper-content-wrapper`,
                                                                    )
                                                                        ? (c.value =
                                                                              t.firstElementChild)
                                                                        : (c.value =
                                                                              t);
                                                                },
                                                                role: `listbox`,
                                                                'data-state': P(
                                                                    i,
                                                                ).open.value
                                                                    ? `open`
                                                                    : `closed`,
                                                                dir: P(i).dir
                                                                    .value,
                                                                style: {
                                                                    display: `flex`,
                                                                    flexDirection: `column`,
                                                                    outline: `none`,
                                                                },
                                                                onContextmenu:
                                                                    (n[0] ||=
                                                                        d(() => {}, [
                                                                            `prevent`,
                                                                        ])),
                                                                onPlaced:
                                                                    (n[1] ||= (
                                                                        e,
                                                                    ) =>
                                                                        (x.value =
                                                                            !0)),
                                                                onKeydown: k,
                                                            },
                                                        ),
                                                        {
                                                            default: _(() => [
                                                                a(
                                                                    t.$slots,
                                                                    `default`,
                                                                ),
                                                            ]),
                                                            _: 3,
                                                        },
                                                        16,
                                                        [
                                                            `id`,
                                                            `data-state`,
                                                            `dir`,
                                                            `onKeydown`,
                                                        ],
                                                    )),
                                                ]),
                                                _: 3,
                                            },
                                            8,
                                            [`disable-outside-pointer-events`],
                                        ),
                                    ]),
                                    _: 3,
                                },
                            ),
                        ]),
                        _: 3,
                    })
                )
            );
        },
    }),
    [ht, gt] = r(`SelectItemAlignedPosition`),
    _t = I({
        inheritAttrs: !1,
        __name: `SelectItemAlignedPosition`,
        props: {
            asChild: { type: Boolean, required: !1 },
            as: { type: null, required: !1 },
        },
        emits: [`placed`],
        setup(e, { emit: t }) {
            let n = e,
                r = t,
                { getItems: i } = U(),
                s = Q(),
                c = $(),
                l = b(!1),
                d = b(!0),
                h = b(),
                { forwardRef: g, currentElement: v } = B(),
                {
                    viewport: y,
                    selectedItem: x,
                    selectedItemText: S,
                    focusSelectedItem: C,
                } = c;
            function T() {
                if (
                    s.triggerElement.value &&
                    s.valueElement.value &&
                    h.value &&
                    v.value &&
                    y?.value &&
                    x?.value &&
                    S?.value
                ) {
                    let e = s.triggerElement.value.getBoundingClientRect(),
                        t = v.value.getBoundingClientRect(),
                        n = s.valueElement.value.getBoundingClientRect(),
                        a = S.value.getBoundingClientRect();
                    if (s.dir.value !== `rtl`) {
                        let r = a.left - t.left,
                            i = n.left - r,
                            o = e.left - i,
                            s = e.width + o,
                            c = Math.max(s, t.width),
                            l = window.innerWidth - 10,
                            u = tt(i, 10, Math.max(10, l - c));
                        ((h.value.style.minWidth = `${s}px`),
                            (h.value.style.left = `${u}px`));
                    } else {
                        let r = t.right - a.right,
                            i = window.innerWidth - n.right - r,
                            o = window.innerWidth - e.right - i,
                            s = e.width + o,
                            c = Math.max(s, t.width),
                            l = window.innerWidth - 10,
                            u = tt(i, 10, Math.max(10, l - c));
                        ((h.value.style.minWidth = `${s}px`),
                            (h.value.style.right = `${u}px`));
                    }
                    let o = i().map((e) => e.ref),
                        c = window.innerHeight - 20,
                        u = y.value.scrollHeight,
                        d = window.getComputedStyle(v.value),
                        f = Number.parseInt(d.borderTopWidth, 10),
                        p = Number.parseInt(d.paddingTop, 10),
                        m = Number.parseInt(d.borderBottomWidth, 10),
                        g = Number.parseInt(d.paddingBottom, 10),
                        _ = f + p + u + g + m,
                        b = Math.min(x.value.offsetHeight * 5, _),
                        C = window.getComputedStyle(y.value),
                        w = Number.parseInt(C.paddingTop, 10),
                        T = Number.parseInt(C.paddingBottom, 10),
                        E = e.top + e.height / 2 - 10,
                        D = c - E,
                        O = x.value.offsetHeight / 2,
                        k = x.value.offsetTop + O,
                        A = f + p + k,
                        j = _ - A;
                    if (A <= E) {
                        let e = x.value === o.at(-1);
                        h.value.style.bottom = `0px`;
                        let t =
                                v.value.clientHeight -
                                y.value.offsetTop -
                                y.value.offsetHeight,
                            n = A + Math.max(D, O + (e ? T : 0) + t + m);
                        h.value.style.height = `${n}px`;
                    } else {
                        let e = x.value === o[0];
                        h.value.style.top = `0px`;
                        let t =
                            Math.max(
                                E,
                                f + y.value.offsetTop + (e ? w : 0) + O,
                            ) + j;
                        ((h.value.style.height = `${t}px`),
                            (y.value.scrollTop = A - E + y.value.offsetTop));
                    }
                    ((h.value.style.margin = `10px 0`),
                        (h.value.style.minHeight = `${b}px`),
                        (h.value.style.maxHeight = `${c}px`),
                        r(`placed`),
                        requestAnimationFrame(() => (l.value = !0)));
                }
            }
            let E = b(``);
            o(async () => {
                (await p(),
                    T(),
                    v.value &&
                        (E.value = window.getComputedStyle(v.value).zIndex));
            });
            function O(e) {
                e && d.value === !0 && (T(), C?.(), (d.value = !1));
            }
            return (
                _e(s.triggerElement, () => {
                    T();
                }),
                gt({
                    contentWrapper: h,
                    shouldExpandOnScrollRef: l,
                    onScrollButtonChange: O,
                }),
                (e, t) => (
                    u(),
                    m(
                        `div`,
                        {
                            ref_key: `contentWrapperElement`,
                            ref: h,
                            style: D({
                                display: `flex`,
                                flexDirection: `column`,
                                position: `fixed`,
                                zIndex: E.value,
                            }),
                        },
                        [
                            w(
                                P(R),
                                f(
                                    {
                                        ref: P(g),
                                        style: {
                                            boxSizing: `border-box`,
                                            maxHeight: `100%`,
                                        },
                                    },
                                    { ...e.$attrs, ...n },
                                ),
                                {
                                    default: _(() => [a(e.$slots, `default`)]),
                                    _: 3,
                                },
                                16,
                            ),
                        ],
                        4,
                    )
                )
            );
        },
    }),
    vt = I({
        inheritAttrs: !1,
        __name: `SelectProvider`,
        props: { context: { type: Object, required: !0 } },
        setup(e) {
            return (ct(e.context), pt(ft), (e, t) => a(e.$slots, `default`));
        },
    }),
    yt = { key: 1 },
    bt = I({
        inheritAttrs: !1,
        __name: `SelectContent`,
        props: {
            forceMount: { type: Boolean, required: !1 },
            position: { type: String, required: !1 },
            bodyLock: { type: Boolean, required: !1 },
            loop: { type: Boolean, required: !1 },
            memoDependencies: { type: Array, required: !1 },
            side: { type: null, required: !1 },
            sideOffset: { type: Number, required: !1 },
            sideFlip: { type: Boolean, required: !1 },
            align: { type: null, required: !1 },
            alignOffset: { type: Number, required: !1 },
            alignFlip: { type: Boolean, required: !1 },
            avoidCollisions: { type: Boolean, required: !1 },
            collisionBoundary: { type: null, required: !1 },
            collisionPadding: { type: [Number, Object], required: !1 },
            arrowPadding: { type: Number, required: !1 },
            hideShiftedArrow: { type: Boolean, required: !1 },
            sticky: { type: String, required: !1 },
            hideWhenDetached: { type: Boolean, required: !1 },
            positionStrategy: { type: String, required: !1 },
            updatePositionStrategy: { type: String, required: !1 },
            disableUpdateOnLayoutShift: { type: Boolean, required: !1 },
            prioritizePosition: { type: Boolean, required: !1 },
            reference: { type: null, required: !1 },
            dir: { type: String, required: !1 },
            asChild: { type: Boolean, required: !1 },
            as: { type: null, required: !1 },
            disableOutsidePointerEvents: { type: Boolean, required: !1 },
        },
        emits: [`closeAutoFocus`, `escapeKeyDown`, `pointerDownOutside`],
        setup(e, { emit: t }) {
            let n = e,
                r = ye(n, t),
                c = Q(),
                d = b();
            o(() => {
                d.value = new DocumentFragment();
            });
            let f = b(),
                p = l(() => n.forceMount || c.open.value),
                h = b(p.value),
                g;
            function x() {
                g &&= (clearTimeout(g), void 0);
            }
            return (
                T(p, (e, t, n) => {
                    (x(),
                        (g = setTimeout(() => {
                            ((h.value = p.value), (g = void 0));
                        })),
                        n(x));
                }),
                v(x),
                (e, t) =>
                    p.value || h.value || f.value?.present
                        ? (u(),
                          y(
                              P(i),
                              {
                                  key: 0,
                                  ref_key: `presenceRef`,
                                  ref: f,
                                  present: p.value,
                              },
                              {
                                  default: _(() => [
                                      w(
                                          mt,
                                          te(C({ ...P(r), ...e.$attrs })),
                                          {
                                              default: _(() => [
                                                  a(e.$slots, `default`),
                                              ]),
                                              _: 3,
                                          },
                                          16,
                                      ),
                                  ]),
                                  _: 3,
                              },
                              8,
                              [`present`],
                          ))
                        : d.value
                          ? (u(),
                            m(`div`, yt, [
                                (u(),
                                y(
                                    s,
                                    { to: d.value },
                                    [
                                        w(
                                            vt,
                                            { context: P(c) },
                                            {
                                                default: _(() => [
                                                    a(e.$slots, `default`),
                                                ]),
                                                _: 3,
                                            },
                                            8,
                                            [`context`],
                                        ),
                                    ],
                                    8,
                                    [`to`],
                                )),
                            ]))
                          : S(`v-if`, !0)
            );
        },
    }),
    xt = I({
        __name: `SelectIcon`,
        props: {
            asChild: { type: Boolean, required: !1 },
            as: { type: null, required: !1, default: `span` },
        },
        setup(e) {
            return (t, n) => (
                u(),
                y(
                    P(R),
                    { 'aria-hidden': `true`, as: e.as, 'as-child': e.asChild },
                    {
                        default: _(() => [
                            a(t.$slots, `default`, {}, () => [
                                (n[0] ||= k(`▼`, -1)),
                            ]),
                        ]),
                        _: 3,
                    },
                    8,
                    [`as`, `as-child`],
                )
            );
        },
    }),
    [St, Ct] = r(`SelectItem`),
    wt = I({
        __name: `SelectItem`,
        props: {
            value: { type: null, required: !0 },
            disabled: { type: Boolean, required: !1 },
            textValue: { type: String, required: !1 },
            asChild: { type: Boolean, required: !1 },
            as: { type: null, required: !1 },
        },
        emits: [`select`],
        setup(t, { emit: r }) {
            let i = t,
                s = r,
                { disabled: c } = F(i),
                f = Q(),
                m = $(),
                { forwardRef: h, currentElement: g } = B(),
                { CollectionItem: v } = U(),
                x = l(() => Z(f.modelValue?.value, i.value, f.by)),
                S = b(!1),
                C = b(i.textValue ?? ``),
                T = n(void 0, `reka-select-item-text`);
            async function E(e) {
                if (e.defaultPrevented) return;
                let t = { originalEvent: e, value: i.value };
                le(`select.select`, D, t);
            }
            async function D(e) {
                (await p(),
                    s(`select`, e),
                    e.defaultPrevented ||
                        c.value ||
                        (f.onValueChange(i.value),
                        f.multiple.value || f.onOpenChange(!1)));
            }
            async function O(e) {
                (await p(),
                    !e.defaultPrevented &&
                        (c.value
                            ? m.onItemLeave?.()
                            : e.currentTarget?.focus({ preventScroll: !0 })));
            }
            async function k(t) {
                (await p(),
                    !t.defaultPrevented &&
                        t.currentTarget === e() &&
                        m.onItemLeave?.());
            }
            async function A(e) {
                (await p(),
                    e.defaultPrevented ||
                        (m.searchRef?.value !== `` && e.key === ` `) ||
                        (it.includes(e.key) && E(e),
                        e.key === ` ` && e.preventDefault()));
            }
            if (i.value === ``)
                throw Error(
                    `A <SelectItem /> must have a value prop that is not an empty string. This is because the Select value can be set to an empty string to clear the selection and show the placeholder.`,
                );
            return (
                o(() => {
                    g.value && m.itemRefCallback(g.value, i.value, i.disabled);
                }),
                Ct({
                    value: i.value,
                    disabled: c,
                    textId: T,
                    isSelected: x,
                    onItemTextChange: (e) => {
                        C.value = ((C.value || e?.textContent) ?? ``).trim();
                    },
                }),
                (e, n) => (
                    u(),
                    y(
                        P(v),
                        { value: { textValue: C.value } },
                        {
                            default: _(() => [
                                w(
                                    P(R),
                                    {
                                        ref: P(h),
                                        role: `option`,
                                        'aria-labelledby': P(T),
                                        'data-highlighted': S.value
                                            ? ``
                                            : void 0,
                                        'aria-selected': x.value,
                                        'data-state': x.value
                                            ? `checked`
                                            : `unchecked`,
                                        'aria-disabled': P(c) || void 0,
                                        'data-disabled': P(c) ? `` : void 0,
                                        tabindex: P(c) ? void 0 : -1,
                                        as: t.as,
                                        'as-child': t.asChild,
                                        onFocus: (n[0] ||= (e) =>
                                            (S.value = !0)),
                                        onBlur: (n[1] ||= (e) =>
                                            (S.value = !1)),
                                        onPointerup: E,
                                        onPointerdown: (n[2] ||= (e) => {
                                            e.currentTarget.focus({
                                                preventScroll: !0,
                                            });
                                        }),
                                        onTouchend: (n[3] ||= d(() => {}, [
                                            `prevent`,
                                            `stop`,
                                        ])),
                                        onPointermove: O,
                                        onPointerleave: k,
                                        onKeydown: A,
                                    },
                                    {
                                        default: _(() => [
                                            a(e.$slots, `default`, {
                                                selected: x.value,
                                            }),
                                        ]),
                                        _: 3,
                                    },
                                    8,
                                    [
                                        `aria-labelledby`,
                                        `data-highlighted`,
                                        `aria-selected`,
                                        `data-state`,
                                        `aria-disabled`,
                                        `data-disabled`,
                                        `tabindex`,
                                        `as`,
                                        `as-child`,
                                    ],
                                ),
                            ]),
                            _: 3,
                        },
                        8,
                        [`value`],
                    )
                )
            );
        },
    }),
    Tt = I({
        __name: `SelectItemIndicator`,
        props: {
            forceMount: { type: Boolean, required: !1 },
            asChild: { type: Boolean, required: !1 },
            as: { type: null, required: !1, default: `span` },
        },
        setup(e) {
            let { forwardRef: t } = B(),
                n = St();
            return (r, o) => (
                u(),
                y(
                    P(i),
                    { present: e.forceMount || P(n).isSelected.value },
                    {
                        default: _(() => [
                            w(
                                P(R),
                                {
                                    ref: P(t),
                                    'aria-hidden': `true`,
                                    'data-state': P(n).isSelected.value
                                        ? `checked`
                                        : `unchecked`,
                                    as: e.as,
                                    'as-child': e.asChild,
                                },
                                {
                                    default: _(() => [a(r.$slots, `default`)]),
                                    _: 3,
                                },
                                8,
                                [`data-state`, `as`, `as-child`],
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
    Et = I({
        inheritAttrs: !1,
        __name: `SelectItemText`,
        props: {
            asChild: { type: Boolean, required: !1 },
            as: { type: null, required: !1, default: `span` },
        },
        setup(e) {
            let t = e,
                n = Q(),
                r = $(),
                i = St(),
                { forwardRef: s, currentElement: c } = B(),
                d = l(() => ({
                    value: i.value,
                    disabled: i.disabled.value,
                    textContent:
                        c.value?.textContent ?? i.value?.toString() ?? ``,
                }));
            return (
                o(() => {
                    c.value &&
                        (i.onItemTextChange(c.value),
                        r.itemTextRefCallback(
                            c.value,
                            i.value,
                            i.disabled.value,
                        ),
                        n.onOptionAdd(d.value));
                }),
                v(() => {
                    n.onOptionRemove(d.value);
                }),
                (e, n) => (
                    u(),
                    y(
                        P(R),
                        f(
                            { id: P(i).textId, ref: P(s) },
                            { ...t, ...e.$attrs },
                        ),
                        { default: _(() => [a(e.$slots, `default`)]), _: 3 },
                        16,
                        [`id`],
                    )
                )
            );
        },
    }),
    Dt = I({
        __name: `SelectPortal`,
        props: {
            to: { type: null, required: !1 },
            disabled: { type: Boolean, required: !1 },
            defer: { type: Boolean, required: !1 },
            forceMount: { type: Boolean, required: !1 },
        },
        setup(e) {
            let t = e;
            return (e, n) => (
                u(),
                y(
                    P(pe),
                    te(C(t)),
                    { default: _(() => [a(e.$slots, `default`)]), _: 3 },
                    16,
                )
            );
        },
    }),
    Ot = I({
        __name: `SelectScrollButtonImpl`,
        emits: [`autoScroll`],
        setup(t, { emit: n }) {
            let r = n,
                { getItems: i } = U(),
                o = $(),
                s = b(null);
            function l() {
                s.value !== null &&
                    (window.clearInterval(s.value), (s.value = null));
            }
            ne(() => {
                i()
                    .map((e) => e.ref)
                    .find((t) => t === e())
                    ?.scrollIntoView({ block: `nearest` });
            });
            function d() {
                s.value === null &&
                    (s.value = window.setInterval(() => {
                        r(`autoScroll`);
                    }, 50));
            }
            function p() {
                (o.onItemLeave?.(),
                    s.value === null &&
                        (s.value = window.setInterval(() => {
                            r(`autoScroll`);
                        }, 50)));
            }
            return (
                c(() => l()),
                (e, t) => (
                    u(),
                    y(
                        P(R),
                        f(
                            { 'aria-hidden': `true`, style: { flexShrink: 0 } },
                            e.$parent?.$props,
                            {
                                onPointerdown: d,
                                onPointermove: p,
                                onPointerleave: (t[0] ||= () => {
                                    l();
                                }),
                            },
                        ),
                        { default: _(() => [a(e.$slots, `default`)]), _: 3 },
                        16,
                    )
                )
            );
        },
    }),
    kt = I({
        __name: `SelectScrollDownButton`,
        props: {
            asChild: { type: Boolean, required: !1 },
            as: { type: null, required: !1 },
        },
        setup(e) {
            let t = $(),
                n = t.position === `item-aligned` ? ht() : void 0,
                { forwardRef: r, currentElement: i } = B(),
                o = b(!1);
            return (
                ne((e) => {
                    if (t.viewport?.value && t.isPositioned?.value) {
                        let n = t.viewport.value;
                        function r() {
                            let e = n.scrollHeight - n.clientHeight;
                            o.value = Math.ceil(n.scrollTop) < e;
                        }
                        (r(),
                            n.addEventListener(`scroll`, r),
                            e(() => n.removeEventListener(`scroll`, r)));
                    }
                }),
                T(i, () => {
                    i.value && n?.onScrollButtonChange(i.value);
                }),
                (e, n) =>
                    o.value
                        ? (u(),
                          y(
                              Ot,
                              {
                                  key: 0,
                                  ref: P(r),
                                  onAutoScroll: (n[0] ||= () => {
                                      let { viewport: e, selectedItem: n } =
                                          P(t);
                                      e?.value &&
                                          n?.value &&
                                          (e.value.scrollTop =
                                              e.value.scrollTop +
                                              n.value.offsetHeight);
                                  }),
                              },
                              {
                                  default: _(() => [a(e.$slots, `default`)]),
                                  _: 3,
                              },
                              512,
                          ))
                        : S(`v-if`, !0)
            );
        },
    }),
    At = I({
        __name: `SelectScrollUpButton`,
        props: {
            asChild: { type: Boolean, required: !1 },
            as: { type: null, required: !1 },
        },
        setup(e) {
            let t = $(),
                n = t.position === `item-aligned` ? ht() : void 0,
                { forwardRef: r, currentElement: i } = B(),
                o = b(!1);
            return (
                ne((e) => {
                    if (t.viewport?.value && t.isPositioned?.value) {
                        let n = t.viewport.value;
                        function r() {
                            o.value = n.scrollTop > 0;
                        }
                        (r(),
                            n.addEventListener(`scroll`, r),
                            e(() => n.removeEventListener(`scroll`, r)));
                    }
                }),
                T(i, () => {
                    i.value && n?.onScrollButtonChange(i.value);
                }),
                (e, n) =>
                    o.value
                        ? (u(),
                          y(
                              Ot,
                              {
                                  key: 0,
                                  ref: P(r),
                                  onAutoScroll: (n[0] ||= () => {
                                      let { viewport: e, selectedItem: n } =
                                          P(t);
                                      e?.value &&
                                          n?.value &&
                                          (e.value.scrollTop =
                                              e.value.scrollTop -
                                              n.value.offsetHeight);
                                  }),
                              },
                              {
                                  default: _(() => [a(e.$slots, `default`)]),
                                  _: 3,
                              },
                              512,
                          ))
                        : S(`v-if`, !0)
            );
        },
    }),
    jt = I({
        __name: `SelectTrigger`,
        props: {
            disabled: { type: Boolean, required: !1 },
            reference: { type: null, required: !1 },
            asChild: { type: Boolean, required: !1 },
            as: { type: null, required: !1, default: `button` },
        },
        setup(e) {
            let t = e,
                r = Q(),
                { forwardRef: i, currentElement: s } = B(),
                d = l(() => r.disabled?.value || t.disabled);
            ((r.contentId ||= n(void 0, `reka-select-content`)),
                o(() => {
                    r.onTriggerChange(s.value);
                }));
            let p = oe(null),
                m = E();
            function h() {
                return m.id ?? p?.fieldId.value;
            }
            function g() {
                let e = (e, t) => [e, t].filter(Boolean).join(` `) || void 0;
                return {
                    ...p?.dataAttributes.value,
                    id: h(),
                    'aria-labelledby': e(
                        m[`aria-labelledby`],
                        p?.labelId.value,
                    ),
                    'aria-describedby': e(
                        m[`aria-describedby`],
                        p?.describedBy.value,
                    ),
                    'aria-invalid':
                        m[`aria-invalid`] ?? (p?.invalid.value || void 0),
                };
            }
            function v() {
                p?.handleControlFocus();
            }
            function b() {
                p?.handleControlBlur();
            }
            let x;
            (o(() => {
                x = p?.registerControl({
                    id: h,
                    element: () => s.value,
                    getValue: () => r.modelValue.value,
                    required: () => !!r.required?.value,
                });
            }),
                c(() => x?.()));
            let { getItems: S } = U(),
                {
                    search: C,
                    handleTypeaheadSearch: T,
                    resetTypeahead: D,
                } = Ae();
            function O() {
                d.value || (r.onOpenChange(!0), D());
            }
            function k(e) {
                (O(),
                    (r.triggerPointerDownPosRef.value = {
                        x: Math.round(e.pageX),
                        y: Math.round(e.pageY),
                    }));
            }
            function A(e) {
                return e.button === 0 && e.ctrlKey === !1;
            }
            let j = !1;
            function M(e) {
                if (e.defaultPrevented) return;
                if (e.pointerType === `touch`) return e.preventDefault();
                let t = e.target;
                (t.hasPointerCapture(e.pointerId) &&
                    t.releasePointerCapture(e.pointerId),
                    A(e) && (k(e), (j = !0)));
            }
            function N(e) {
                e.defaultPrevented || (A(e) && e.preventDefault());
            }
            function ee(e) {
                (e.defaultPrevented || j || e.currentTarget?.focus(), (j = !1));
            }
            function F(e) {
                e.defaultPrevented ||
                    (e.preventDefault(), e.pointerType === `touch` && k(e));
            }
            function I(e) {
                if (e.defaultPrevented) return;
                let t = C.value !== ``;
                (!(e.ctrlKey || e.altKey || e.metaKey) &&
                    e.key.length === 1 &&
                    T(e.key, S()),
                    !(t && e.key === ` `) &&
                        rt.includes(e.key) &&
                        (O(), e.preventDefault()));
            }
            return (t, n) => (
                u(),
                y(
                    P(Xe),
                    { 'as-child': ``, reference: e.reference },
                    {
                        default: _(() => [
                            w(
                                P(R),
                                f(g(), {
                                    ref: P(i),
                                    role: `combobox`,
                                    type: e.as === `button` ? `button` : void 0,
                                    'aria-controls': P(r).open.value
                                        ? P(r).contentId
                                        : void 0,
                                    'aria-expanded': P(r).open.value || !1,
                                    'aria-required': P(r).required?.value,
                                    'aria-autocomplete': `none`,
                                    disabled: d.value,
                                    dir: P(r)?.dir.value,
                                    'data-state': P(r)?.open.value
                                        ? `open`
                                        : `closed`,
                                    'data-disabled': d.value ? `` : void 0,
                                    'data-placeholder': P(ot)(
                                        P(r).modelValue?.value,
                                    )
                                        ? ``
                                        : void 0,
                                    'as-child': e.asChild,
                                    as: e.as,
                                    onClick: ee,
                                    onPointerdown: M,
                                    onMousedown: N,
                                    onFocus: v,
                                    onBlur: b,
                                    onPointerup: F,
                                    onKeydown: I,
                                }),
                                {
                                    default: _(() => [a(t.$slots, `default`)]),
                                    _: 3,
                                },
                                16,
                                [
                                    `type`,
                                    `aria-controls`,
                                    `aria-expanded`,
                                    `aria-required`,
                                    `disabled`,
                                    `dir`,
                                    `data-state`,
                                    `data-disabled`,
                                    `data-placeholder`,
                                    `as-child`,
                                    `as`,
                                ],
                            ),
                        ]),
                        _: 3,
                    },
                    8,
                    [`reference`],
                )
            );
        },
    }),
    Mt = I({
        __name: `SelectValue`,
        props: {
            placeholder: { type: String, required: !1, default: `` },
            asChild: { type: Boolean, required: !1 },
            as: { type: null, required: !1, default: `span` },
        },
        setup(e) {
            let t = e,
                { forwardRef: n, currentElement: r } = B(),
                i = Q();
            o(() => {
                i.valueElement = r;
            });
            let s = l(() => {
                    let e = [],
                        t = Array.from(i.optionsSet.value),
                        n = (e) => t.find((t) => Z(e, t.value, i.by));
                    return (
                        (e = Array.isArray(i.modelValue.value)
                            ? i.modelValue.value.map(
                                  (e) => n(e)?.textContent ?? ``,
                              )
                            : [n(i.modelValue.value)?.textContent ?? ``]),
                        e.filter(Boolean)
                    );
                }),
                c = l(() =>
                    s.value.length ? s.value.join(`, `) : t.placeholder,
                );
            return (r, o) => (
                u(),
                y(
                    P(R),
                    {
                        ref: P(n),
                        as: e.as,
                        'as-child': e.asChild,
                        style: { pointerEvents: `none` },
                        'data-placeholder': s.value.length
                            ? void 0
                            : t.placeholder,
                    },
                    {
                        default: _(() => [
                            a(
                                r.$slots,
                                `default`,
                                {
                                    selectedLabel: s.value,
                                    modelValue: P(i).modelValue.value,
                                },
                                () => [k(O(c.value), 1)],
                            ),
                        ]),
                        _: 3,
                    },
                    8,
                    [`as`, `as-child`, `data-placeholder`],
                )
            );
        },
    }),
    Nt = I({
        __name: `SelectViewport`,
        props: {
            nonce: { type: String, required: !1 },
            asChild: { type: Boolean, required: !1 },
            as: { type: null, required: !1 },
        },
        setup(e) {
            let t = e,
                { nonce: n } = F(t),
                r = nt(n),
                i = $(),
                s = i.position === `item-aligned` ? ht() : void 0,
                { forwardRef: c, currentElement: l } = B();
            o(() => {
                i?.onViewportChange(l.value);
            });
            let d = b(0);
            function p(e) {
                let t = e.currentTarget,
                    { shouldExpandOnScrollRef: n, contentWrapper: r } = s ?? {};
                if (n?.value && r?.value) {
                    let e = Math.abs(d.value - t.scrollTop);
                    if (e > 0) {
                        let n = window.innerHeight - 20,
                            i = Number.parseFloat(r.value.style.minHeight),
                            a = Number.parseFloat(r.value.style.height),
                            o = Math.max(i, a);
                        if (o < n) {
                            let i = o + e,
                                a = Math.min(n, i),
                                s = i - a;
                            ((r.value.style.height = `${a}px`),
                                r.value.style.bottom === `0px` &&
                                    ((t.scrollTop = s > 0 ? s : 0),
                                    (r.value.style.justifyContent = `flex-end`)));
                        }
                    }
                }
                d.value = t.scrollTop;
            }
            return (e, n) => (
                u(),
                m(
                    g,
                    null,
                    [
                        w(
                            P(R),
                            f(
                                {
                                    ref: P(c),
                                    'data-reka-select-viewport': ``,
                                    role: `presentation`,
                                },
                                { ...e.$attrs, ...t },
                                {
                                    style: {
                                        position: `relative`,
                                        flex: 1,
                                        overflow: `hidden auto`,
                                    },
                                    onScroll: p,
                                },
                            ),
                            {
                                default: _(() => [a(e.$slots, `default`)]),
                                _: 3,
                            },
                            16,
                        ),
                        w(
                            P(R),
                            { as: `style`, nonce: P(r) },
                            {
                                default: _(() => [
                                    ...(n[0] ||= [
                                        k(
                                            ` /* Hide scrollbars cross-browser and enable momentum scroll for touch devices */ [data-reka-select-viewport] { scrollbar-width:none; -ms-overflow-style: none; -webkit-overflow-scrolling: touch; } [data-reka-select-viewport]::-webkit-scrollbar { display: none; } `,
                                            -1,
                                        ),
                                    ]),
                                ]),
                                _: 1,
                            },
                            8,
                            [`nonce`],
                        ),
                    ],
                    64,
                )
            );
        },
    }),
    Pt = xe({
        name: `chevron-down`,
        size: 24,
        node: [[`path`, { d: `m6 9 6 6 6-6`, key: `qrunsl` }]],
    }),
    Ft = xe({
        name: `chevron-up`,
        size: 24,
        node: [[`path`, { d: `m18 15-6-6-6 6`, key: `153udz` }]],
    }),
    It = xe({
        name: `mail`,
        size: 24,
        node: [
            [
                `path`,
                { d: `m22 7-8.991 5.727a2 2 0 0 1-2.009 0L2 7`, key: `132q7q` },
            ],
            [
                `rect`,
                {
                    x: `2`,
                    y: `4`,
                    width: `20`,
                    height: `16`,
                    rx: `2`,
                    key: `izxlao`,
                },
            ],
        ],
    }),
    Lt = xe({
        name: `user-plus`,
        size: 24,
        node: [
            [
                `path`,
                {
                    d: `M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2`,
                    key: `1yyitq`,
                },
            ],
            [`circle`, { cx: `9`, cy: `7`, r: `4`, key: `nufk8` }],
            [`line`, { x1: `19`, x2: `19`, y1: `8`, y2: `14`, key: `1bvyxn` }],
            [`line`, { x1: `22`, x2: `16`, y1: `11`, y2: `11`, key: `1shjgl` }],
        ],
    }),
    Rt = I({
        __name: `CancelInvitationModal`,
        props: { team: {}, invitation: {}, open: { type: Boolean } },
        emits: [`update:open`],
        setup(e, { emit: t }) {
            let n = e,
                r = t,
                i = b(!1),
                a = () => {
                    n.invitation &&
                        j.visit(qe([n.team.slug, n.invitation.code]), {
                            onStart: () => (i.value = !0),
                            onFinish: () => (i.value = !1),
                            onSuccess: () => r(`update:open`, !1),
                        });
                };
            return (e, t) => (
                u(),
                y(
                    P(Y),
                    {
                        open: n.open,
                        'onUpdate:open': (t[0] ||= (e) => r(`update:open`, e)),
                    },
                    {
                        default: _(() => [
                            w(P(K), null, {
                                default: _(() => [
                                    w(P(J), null, {
                                        default: _(() => [
                                            w(P($e), null, {
                                                default: _(() => [
                                                    ...(t[1] ||= [
                                                        k(
                                                            `Cancel invitation`,
                                                            -1,
                                                        ),
                                                    ]),
                                                ]),
                                                _: 1,
                                            }),
                                            w(P(q), null, {
                                                default: _(() => [
                                                    (t[2] ||= k(
                                                        ` Are you sure you want to cancel the invitation for `,
                                                        -1,
                                                    )),
                                                    x(
                                                        `strong`,
                                                        null,
                                                        O(n.invitation?.email),
                                                        1,
                                                    ),
                                                    (t[3] ||= k(`? `, -1)),
                                                ]),
                                                _: 1,
                                            }),
                                        ]),
                                        _: 1,
                                    }),
                                    w(
                                        P(X),
                                        { class: `gap-2` },
                                        {
                                            default: _(() => [
                                                w(
                                                    P(et),
                                                    { 'as-child': `` },
                                                    {
                                                        default: _(() => [
                                                            w(
                                                                P(z),
                                                                {
                                                                    variant: `secondary`,
                                                                },
                                                                {
                                                                    default: _(
                                                                        () => [
                                                                            ...(t[4] ||=
                                                                                [
                                                                                    k(
                                                                                        ` Keep invitation `,
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
                                                w(
                                                    P(z),
                                                    {
                                                        'data-test': `cancel-invitation-confirm`,
                                                        variant: `destructive`,
                                                        disabled: i.value,
                                                        onClick: a,
                                                    },
                                                    {
                                                        default: _(() => [
                                                            ...(t[5] ||= [
                                                                k(
                                                                    ` Cancel invitation `,
                                                                    -1,
                                                                ),
                                                            ]),
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
                    },
                    8,
                    [`open`],
                )
            );
        },
    }),
    zt = { class: `space-y-4 py-4` },
    Bt = { class: `grid gap-2` },
    Vt = I({
        __name: `DeleteTeamModal`,
        props: { team: {}, open: { type: Boolean } },
        emits: [`update:open`],
        setup(e, { emit: t }) {
            let n = e,
                r = t,
                i = b(``),
                a = b(0),
                o = l(() => i.value === n.team.name),
                s = (e) => {
                    (r(`update:open`, e), e || ((i.value = ``), a.value++));
                };
            return (e, t) => (
                u(),
                y(
                    P(Y),
                    { open: n.open, 'onUpdate:open': s },
                    {
                        default: _(() => [
                            w(P(K), null, {
                                default: _(() => [
                                    (u(),
                                    y(
                                        P(M),
                                        f(
                                            { key: a.value },
                                            P(Je).form(n.team.slug),
                                            {
                                                class: `space-y-6`,
                                                onSuccess: (t[1] ||= (e) =>
                                                    s(!1)),
                                            },
                                        ),
                                        {
                                            default: _(
                                                ({
                                                    errors: e,
                                                    processing: r,
                                                }) => [
                                                    w(P(J), null, {
                                                        default: _(() => [
                                                            w(P($e), null, {
                                                                default: _(
                                                                    () => [
                                                                        ...(t[2] ||=
                                                                            [
                                                                                k(
                                                                                    `Are you sure?`,
                                                                                    -1,
                                                                                ),
                                                                            ]),
                                                                    ],
                                                                ),
                                                                _: 1,
                                                            }),
                                                            w(P(q), null, {
                                                                default: _(
                                                                    () => [
                                                                        (t[3] ||=
                                                                            k(
                                                                                ` This action cannot be undone. This will permanently delete the team `,
                                                                                -1,
                                                                            )),
                                                                        x(
                                                                            `strong`,
                                                                            null,
                                                                            `"` +
                                                                                O(
                                                                                    n
                                                                                        .team
                                                                                        .name,
                                                                                ) +
                                                                                `"`,
                                                                            1,
                                                                        ),
                                                                        (t[4] ||=
                                                                            k(
                                                                                `. `,
                                                                                -1,
                                                                            )),
                                                                    ],
                                                                ),
                                                                _: 1,
                                                            }),
                                                        ]),
                                                        _: 1,
                                                    }),
                                                    x(`div`, zt, [
                                                        x(`div`, Bt, [
                                                            w(
                                                                P(be),
                                                                {
                                                                    for: `confirmation-name`,
                                                                },
                                                                {
                                                                    default: _(
                                                                        () => [
                                                                            (t[5] ||=
                                                                                k(
                                                                                    ` Type `,
                                                                                    -1,
                                                                                )),
                                                                            x(
                                                                                `strong`,
                                                                                null,
                                                                                `"` +
                                                                                    O(
                                                                                        n
                                                                                            .team
                                                                                            .name,
                                                                                    ) +
                                                                                    `"`,
                                                                                1,
                                                                            ),
                                                                            (t[6] ||=
                                                                                k(
                                                                                    ` to confirm `,
                                                                                    -1,
                                                                                )),
                                                                        ],
                                                                    ),
                                                                    _: 1,
                                                                },
                                                            ),
                                                            w(
                                                                P(Se),
                                                                {
                                                                    id: `confirmation-name`,
                                                                    name: `name`,
                                                                    'data-test': `delete-team-name`,
                                                                    modelValue:
                                                                        i.value,
                                                                    'onUpdate:modelValue':
                                                                        (t[0] ||=
                                                                            (
                                                                                e,
                                                                            ) =>
                                                                                (i.value =
                                                                                    e)),
                                                                    placeholder: `Enter team name`,
                                                                    autocomplete: `off`,
                                                                },
                                                                null,
                                                                8,
                                                                [`modelValue`],
                                                            ),
                                                            w(
                                                                G,
                                                                {
                                                                    message:
                                                                        e.name,
                                                                },
                                                                null,
                                                                8,
                                                                [`message`],
                                                            ),
                                                        ]),
                                                    ]),
                                                    w(
                                                        P(X),
                                                        { class: `gap-2` },
                                                        {
                                                            default: _(() => [
                                                                w(
                                                                    P(et),
                                                                    {
                                                                        'as-child': ``,
                                                                    },
                                                                    {
                                                                        default:
                                                                            _(
                                                                                () => [
                                                                                    w(
                                                                                        P(
                                                                                            z,
                                                                                        ),
                                                                                        {
                                                                                            variant: `secondary`,
                                                                                        },
                                                                                        {
                                                                                            default:
                                                                                                _(
                                                                                                    () => [
                                                                                                        ...(t[7] ||=
                                                                                                            [
                                                                                                                k(
                                                                                                                    ` Cancel `,
                                                                                                                    -1,
                                                                                                                ),
                                                                                                            ]),
                                                                                                    ],
                                                                                                ),
                                                                                            _: 1,
                                                                                        },
                                                                                    ),
                                                                                ],
                                                                            ),
                                                                        _: 1,
                                                                    },
                                                                ),
                                                                w(
                                                                    P(z),
                                                                    {
                                                                        'data-test': `delete-team-confirm`,
                                                                        variant: `destructive`,
                                                                        type: `submit`,
                                                                        disabled:
                                                                            !o.value ||
                                                                            r,
                                                                    },
                                                                    {
                                                                        default:
                                                                            _(
                                                                                () => [
                                                                                    ...(t[8] ||=
                                                                                        [
                                                                                            k(
                                                                                                ` Delete team `,
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
                                                            ]),
                                                            _: 2,
                                                        },
                                                        1024,
                                                    ),
                                                ],
                                            ),
                                            _: 1,
                                        },
                                        16,
                                    )),
                                ]),
                                _: 1,
                            }),
                        ]),
                        _: 1,
                    },
                    8,
                    [`open`],
                )
            );
        },
    }),
    Ht = I({
        __name: `Select`,
        props: {
            open: { type: Boolean },
            defaultOpen: { type: Boolean },
            defaultValue: {},
            modelValue: {},
            nullableValue: {},
            by: { type: [String, Function] },
            dir: {},
            multiple: { type: Boolean },
            autocomplete: {},
            disabled: { type: Boolean },
            name: {},
            required: { type: Boolean },
        },
        emits: [`update:modelValue`, `update:open`],
        setup(e, { emit: t }) {
            let n = ye(e, t);
            return (e, t) => (
                u(),
                y(
                    P(lt),
                    f({ 'data-slot': `select` }, P(n)),
                    {
                        default: _((t) => [a(e.$slots, `default`, te(C(t)))]),
                        _: 3,
                    },
                    16,
                )
            );
        },
    }),
    Ut = I({
        inheritAttrs: !1,
        __name: `SelectContent`,
        props: {
            forceMount: { type: Boolean },
            position: { default: `popper` },
            bodyLock: { type: Boolean },
            loop: { type: Boolean },
            memoDependencies: {},
            side: {},
            sideOffset: {},
            sideFlip: { type: Boolean },
            align: {},
            alignOffset: {},
            alignFlip: { type: Boolean },
            avoidCollisions: { type: Boolean },
            collisionBoundary: {},
            collisionPadding: {},
            arrowPadding: {},
            hideShiftedArrow: { type: Boolean },
            sticky: {},
            hideWhenDetached: { type: Boolean },
            positionStrategy: {},
            updatePositionStrategy: {},
            disableUpdateOnLayoutShift: { type: Boolean },
            prioritizePosition: { type: Boolean },
            reference: {},
            dir: {},
            asChild: { type: Boolean },
            as: {},
            disableOutsidePointerEvents: { type: Boolean },
            class: { type: [Boolean, null, String, Object, Array] },
        },
        emits: [`closeAutoFocus`, `escapeKeyDown`, `pointerDownOutside`],
        setup(e, { emit: t }) {
            let n = e,
                r = t,
                i = H(n, `class`),
                o = ye(i, r);
            return (t, r) => (
                u(),
                y(P(Dt), null, {
                    default: _(() => [
                        w(
                            P(bt),
                            f(
                                { 'data-slot': `select-content` },
                                { ...t.$attrs, ...P(o) },
                                {
                                    class: P(L)(
                                        `bg-popover text-popover-foreground data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95 data-[side=bottom]:slide-in-from-top-2 data-[side=left]:slide-in-from-right-2 data-[side=right]:slide-in-from-left-2 data-[side=top]:slide-in-from-bottom-2 relative z-50 max-h-(--reka-select-content-available-height) min-w-[8rem] overflow-x-hidden overflow-y-auto rounded-md border shadow-md`,
                                        e.position === `popper` &&
                                            `data-[side=bottom]:translate-y-1 data-[side=left]:-translate-x-1 data-[side=right]:translate-x-1 data-[side=top]:-translate-y-1`,
                                        n.class,
                                    ),
                                },
                            ),
                            {
                                default: _(() => [
                                    w(P(qt)),
                                    w(
                                        P(Nt),
                                        {
                                            class: A(
                                                P(L)(
                                                    `p-1`,
                                                    e.position === `popper` &&
                                                        `h-[var(--reka-select-trigger-height)] w-full min-w-[var(--reka-select-trigger-width)] scroll-my-1`,
                                                ),
                                            ),
                                        },
                                        {
                                            default: _(() => [
                                                a(t.$slots, `default`),
                                            ]),
                                            _: 3,
                                        },
                                        8,
                                        [`class`],
                                    ),
                                    w(P(Kt)),
                                ]),
                                _: 3,
                            },
                            16,
                            [`class`],
                        ),
                    ]),
                    _: 3,
                })
            );
        },
    }),
    Wt = { class: `absolute end-2 flex size-3.5 items-center justify-center` },
    Gt = I({
        __name: `SelectItem`,
        props: {
            value: {},
            disabled: { type: Boolean },
            textValue: {},
            asChild: { type: Boolean },
            as: {},
            class: { type: [Boolean, null, String, Object, Array] },
        },
        setup(e) {
            let t = e,
                n = H(t, `class`),
                r = V(n);
            return (e, n) => (
                u(),
                y(
                    P(wt),
                    f({ 'data-slot': `select-item` }, P(r), {
                        class: P(L)(
                            `focus:bg-accent focus:text-accent-foreground [&_svg:not([class*='text-'])]:text-muted-foreground relative flex w-full cursor-default items-center gap-2 rounded-sm py-1.5 pe-8 ps-2 text-sm outline-hidden select-none data-[disabled]:pointer-events-none data-[disabled]:opacity-50 [&_svg]:pointer-events-none [&_svg]:shrink-0 [&_svg:not([class*='size-'])]:size-4 *:[span]:last:flex *:[span]:last:items-center *:[span]:last:gap-2`,
                            t.class,
                        ),
                    }),
                    {
                        default: _(() => [
                            x(`span`, Wt, [
                                w(P(Tt), null, {
                                    default: _(() => [
                                        a(
                                            e.$slots,
                                            `indicator-icon`,
                                            {},
                                            () => [
                                                w(P(Re), { class: `size-4` }),
                                            ],
                                        ),
                                    ]),
                                    _: 3,
                                }),
                            ]),
                            w(P(Et), null, {
                                default: _(() => [a(e.$slots, `default`)]),
                                _: 3,
                            }),
                        ]),
                        _: 3,
                    },
                    16,
                    [`class`],
                )
            );
        },
    }),
    Kt = I({
        __name: `SelectScrollDownButton`,
        props: {
            asChild: { type: Boolean },
            as: {},
            class: { type: [Boolean, null, String, Object, Array] },
        },
        setup(e) {
            let t = e,
                n = H(t, `class`),
                r = V(n);
            return (e, n) => (
                u(),
                y(
                    P(kt),
                    f({ 'data-slot': `select-scroll-down-button` }, P(r), {
                        class: P(L)(
                            `flex cursor-default items-center justify-center py-1`,
                            t.class,
                        ),
                    }),
                    {
                        default: _(() => [
                            a(e.$slots, `default`, {}, () => [
                                w(P(Pt), { class: `size-4` }),
                            ]),
                        ]),
                        _: 3,
                    },
                    16,
                    [`class`],
                )
            );
        },
    }),
    qt = I({
        __name: `SelectScrollUpButton`,
        props: {
            asChild: { type: Boolean },
            as: {},
            class: { type: [Boolean, null, String, Object, Array] },
        },
        setup(e) {
            let t = e,
                n = H(t, `class`),
                r = V(n);
            return (e, n) => (
                u(),
                y(
                    P(At),
                    f({ 'data-slot': `select-scroll-up-button` }, P(r), {
                        class: P(L)(
                            `flex cursor-default items-center justify-center py-1`,
                            t.class,
                        ),
                    }),
                    {
                        default: _(() => [
                            a(e.$slots, `default`, {}, () => [
                                w(P(Ft), { class: `size-4` }),
                            ]),
                        ]),
                        _: 3,
                    },
                    16,
                    [`class`],
                )
            );
        },
    }),
    Jt = I({
        __name: `SelectTrigger`,
        props: {
            disabled: { type: Boolean },
            reference: {},
            asChild: { type: Boolean },
            as: {},
            class: { type: [Boolean, null, String, Object, Array] },
            size: { default: `default` },
        },
        setup(e) {
            let t = e,
                n = H(t, `class`, `size`),
                r = V(n);
            return (n, i) => (
                u(),
                y(
                    P(jt),
                    f(
                        { 'data-slot': `select-trigger`, 'data-size': e.size },
                        P(r),
                        {
                            class: P(L)(
                                `border-input data-[placeholder]:text-muted-foreground [&_svg:not([class*='text-'])]:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive dark:bg-input/30 dark:hover:bg-input/50 flex w-fit items-center justify-between gap-2 rounded-md border bg-transparent px-3 py-2 text-sm whitespace-nowrap shadow-xs transition-[color,box-shadow] outline-none focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50 data-[size=default]:h-9 data-[size=sm]:h-8 *:data-[slot=select-value]:line-clamp-1 *:data-[slot=select-value]:flex *:data-[slot=select-value]:items-center *:data-[slot=select-value]:gap-2 [&_svg]:pointer-events-none [&_svg]:shrink-0 [&_svg:not([class*='size-'])]:size-4`,
                                t.class,
                            ),
                        },
                    ),
                    {
                        default: _(() => [
                            a(n.$slots, `default`),
                            w(
                                P(xt),
                                { 'as-child': `` },
                                {
                                    default: _(() => [
                                        w(P(Pt), {
                                            class: `size-4 opacity-50`,
                                        }),
                                    ]),
                                    _: 1,
                                },
                            ),
                        ]),
                        _: 3,
                    },
                    16,
                    [`data-size`, `class`],
                )
            );
        },
    }),
    Yt = I({
        __name: `SelectValue`,
        props: { placeholder: {}, asChild: { type: Boolean }, as: {} },
        setup(e) {
            let t = e;
            return (e, n) => (
                u(),
                y(
                    P(Mt),
                    f({ 'data-slot': `select-value` }, t),
                    { default: _(() => [a(e.$slots, `default`)]), _: 3 },
                    16,
                )
            );
        },
    }),
    Xt = { class: `grid gap-4` },
    Zt = { class: `grid gap-2` },
    Qt = { class: `grid gap-2` },
    $t = I({
        __name: `InviteMemberModal`,
        props: { team: {}, availableRoles: {}, open: { type: Boolean } },
        emits: [`update:open`],
        setup(e, { emit: t }) {
            let n = e,
                r = t,
                i = b(`member`),
                a = b(0);
            function o(e) {
                (r(`update:open`, e), e || ((i.value = `member`), a.value++));
            }
            return (e, t) => (
                u(),
                y(
                    P(Y),
                    { open: n.open, 'onUpdate:open': o },
                    {
                        default: _(() => [
                            w(P(K), null, {
                                default: _(() => [
                                    (u(),
                                    y(
                                        P(M),
                                        f(
                                            { key: a.value },
                                            P(Be).form(n.team.slug),
                                            {
                                                class: `space-y-6`,
                                                onSuccess: (t[1] ||= (e) =>
                                                    r(`update:open`, !1)),
                                            },
                                        ),
                                        {
                                            default: _(
                                                ({
                                                    errors: e,
                                                    processing: r,
                                                }) => [
                                                    w(P(J), null, {
                                                        default: _(() => [
                                                            w(P($e), null, {
                                                                default: _(
                                                                    () => [
                                                                        ...(t[2] ||=
                                                                            [
                                                                                k(
                                                                                    `Invite a team member`,
                                                                                    -1,
                                                                                ),
                                                                            ]),
                                                                    ],
                                                                ),
                                                                _: 1,
                                                            }),
                                                            w(P(q), null, {
                                                                default: _(
                                                                    () => [
                                                                        ...(t[3] ||=
                                                                            [
                                                                                k(
                                                                                    ` Send an invitation to join this team. `,
                                                                                    -1,
                                                                                ),
                                                                            ]),
                                                                    ],
                                                                ),
                                                                _: 1,
                                                            }),
                                                        ]),
                                                        _: 1,
                                                    }),
                                                    x(`div`, Xt, [
                                                        x(`div`, Zt, [
                                                            w(
                                                                P(be),
                                                                {
                                                                    for: `email`,
                                                                },
                                                                {
                                                                    default: _(
                                                                        () => [
                                                                            ...(t[4] ||=
                                                                                [
                                                                                    k(
                                                                                        `Email address`,
                                                                                        -1,
                                                                                    ),
                                                                                ]),
                                                                        ],
                                                                    ),
                                                                    _: 1,
                                                                },
                                                            ),
                                                            w(P(Se), {
                                                                id: `email`,
                                                                name: `email`,
                                                                'data-test': `invite-email`,
                                                                type: `email`,
                                                                placeholder: `colleague@example.com`,
                                                                required: ``,
                                                            }),
                                                            w(
                                                                G,
                                                                {
                                                                    message:
                                                                        e.email,
                                                                },
                                                                null,
                                                                8,
                                                                [`message`],
                                                            ),
                                                        ]),
                                                        x(`div`, Qt, [
                                                            w(
                                                                P(be),
                                                                { for: `role` },
                                                                {
                                                                    default: _(
                                                                        () => [
                                                                            ...(t[5] ||=
                                                                                [
                                                                                    k(
                                                                                        `Role`,
                                                                                        -1,
                                                                                    ),
                                                                                ]),
                                                                        ],
                                                                    ),
                                                                    _: 1,
                                                                },
                                                            ),
                                                            w(
                                                                P(Ht),
                                                                {
                                                                    modelValue:
                                                                        i.value,
                                                                    'onUpdate:modelValue':
                                                                        (t[0] ||=
                                                                            (
                                                                                e,
                                                                            ) =>
                                                                                (i.value =
                                                                                    e)),
                                                                    name: `role`,
                                                                    'data-test': `invite-role`,
                                                                },
                                                                {
                                                                    default: _(
                                                                        () => [
                                                                            w(
                                                                                P(
                                                                                    Jt,
                                                                                ),
                                                                                {
                                                                                    class: `w-full`,
                                                                                },
                                                                                {
                                                                                    default:
                                                                                        _(
                                                                                            () => [
                                                                                                w(
                                                                                                    P(
                                                                                                        Yt,
                                                                                                    ),
                                                                                                    {
                                                                                                        placeholder: `Select a role`,
                                                                                                    },
                                                                                                ),
                                                                                            ],
                                                                                        ),
                                                                                    _: 1,
                                                                                },
                                                                            ),
                                                                            w(
                                                                                P(
                                                                                    Ut,
                                                                                ),
                                                                                null,
                                                                                {
                                                                                    default:
                                                                                        _(
                                                                                            () => [
                                                                                                (u(
                                                                                                    !0,
                                                                                                ),
                                                                                                m(
                                                                                                    g,
                                                                                                    null,
                                                                                                    h(
                                                                                                        n.availableRoles,
                                                                                                        (
                                                                                                            e,
                                                                                                        ) => (
                                                                                                            u(),
                                                                                                            y(
                                                                                                                P(
                                                                                                                    Gt,
                                                                                                                ),
                                                                                                                {
                                                                                                                    key: e.value,
                                                                                                                    value: e.value,
                                                                                                                },
                                                                                                                {
                                                                                                                    default:
                                                                                                                        _(
                                                                                                                            () => [
                                                                                                                                k(
                                                                                                                                    O(
                                                                                                                                        e.label,
                                                                                                                                    ),
                                                                                                                                    1,
                                                                                                                                ),
                                                                                                                            ],
                                                                                                                        ),
                                                                                                                    _: 2,
                                                                                                                },
                                                                                                                1032,
                                                                                                                [
                                                                                                                    `value`,
                                                                                                                ],
                                                                                                            )
                                                                                                        ),
                                                                                                    ),
                                                                                                    128,
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
                                                                [`modelValue`],
                                                            ),
                                                            w(
                                                                G,
                                                                {
                                                                    message:
                                                                        e.role,
                                                                },
                                                                null,
                                                                8,
                                                                [`message`],
                                                            ),
                                                        ]),
                                                    ]),
                                                    w(
                                                        P(X),
                                                        { class: `gap-2` },
                                                        {
                                                            default: _(() => [
                                                                w(
                                                                    P(et),
                                                                    {
                                                                        'as-child': ``,
                                                                    },
                                                                    {
                                                                        default:
                                                                            _(
                                                                                () => [
                                                                                    w(
                                                                                        P(
                                                                                            z,
                                                                                        ),
                                                                                        {
                                                                                            variant: `secondary`,
                                                                                        },
                                                                                        {
                                                                                            default:
                                                                                                _(
                                                                                                    () => [
                                                                                                        ...(t[6] ||=
                                                                                                            [
                                                                                                                k(
                                                                                                                    ` Cancel `,
                                                                                                                    -1,
                                                                                                                ),
                                                                                                            ]),
                                                                                                    ],
                                                                                                ),
                                                                                            _: 1,
                                                                                        },
                                                                                    ),
                                                                                ],
                                                                            ),
                                                                        _: 1,
                                                                    },
                                                                ),
                                                                w(
                                                                    P(z),
                                                                    {
                                                                        type: `submit`,
                                                                        'data-test': `invite-submit`,
                                                                        disabled:
                                                                            r,
                                                                    },
                                                                    {
                                                                        default:
                                                                            _(
                                                                                () => [
                                                                                    ...(t[7] ||=
                                                                                        [
                                                                                            k(
                                                                                                ` Send invitation `,
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
                                                            ]),
                                                            _: 2,
                                                        },
                                                        1024,
                                                    ),
                                                ],
                                            ),
                                            _: 1,
                                        },
                                        16,
                                    )),
                                ]),
                                _: 1,
                            }),
                        ]),
                        _: 1,
                    },
                    8,
                    [`open`],
                )
            );
        },
    }),
    en = I({
        __name: `RemoveMemberModal`,
        props: { team: {}, member: {}, open: { type: Boolean } },
        emits: [`update:open`],
        setup(e, { emit: t }) {
            let n = e,
                r = t,
                i = b(!1),
                a = () => {
                    n.member &&
                        j.visit(Ue([n.team.slug, n.member.id]), {
                            onStart: () => (i.value = !0),
                            onFinish: () => (i.value = !1),
                            onSuccess: () => r(`update:open`, !1),
                        });
                };
            return (e, t) => (
                u(),
                y(
                    P(Y),
                    {
                        open: n.open,
                        'onUpdate:open': (t[0] ||= (e) => r(`update:open`, e)),
                    },
                    {
                        default: _(() => [
                            w(P(K), null, {
                                default: _(() => [
                                    w(P(J), null, {
                                        default: _(() => [
                                            w(P($e), null, {
                                                default: _(() => [
                                                    ...(t[1] ||= [
                                                        k(
                                                            `Remove team member`,
                                                            -1,
                                                        ),
                                                    ]),
                                                ]),
                                                _: 1,
                                            }),
                                            w(P(q), null, {
                                                default: _(() => [
                                                    (t[2] ||= k(
                                                        ` Are you sure you want to remove `,
                                                        -1,
                                                    )),
                                                    x(
                                                        `strong`,
                                                        null,
                                                        O(n.member?.name),
                                                        1,
                                                    ),
                                                    (t[3] ||= k(
                                                        ` from this team? `,
                                                        -1,
                                                    )),
                                                ]),
                                                _: 1,
                                            }),
                                        ]),
                                        _: 1,
                                    }),
                                    w(
                                        P(X),
                                        { class: `gap-2` },
                                        {
                                            default: _(() => [
                                                w(
                                                    P(et),
                                                    { 'as-child': `` },
                                                    {
                                                        default: _(() => [
                                                            w(
                                                                P(z),
                                                                {
                                                                    variant: `secondary`,
                                                                },
                                                                {
                                                                    default: _(
                                                                        () => [
                                                                            ...(t[4] ||=
                                                                                [
                                                                                    k(
                                                                                        ` Cancel `,
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
                                                w(
                                                    P(z),
                                                    {
                                                        'data-test': `remove-member-confirm`,
                                                        variant: `destructive`,
                                                        disabled: i.value,
                                                        onClick: a,
                                                    },
                                                    {
                                                        default: _(() => [
                                                            ...(t[5] ||= [
                                                                k(
                                                                    ` Remove member `,
                                                                    -1,
                                                                ),
                                                            ]),
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
                    },
                    8,
                    [`open`],
                )
            );
        },
    }),
    tn = { class: `sr-only` },
    nn = { class: `flex flex-col space-y-10` },
    rn = { key: 0, class: `space-y-6` },
    an = { class: `grid gap-2` },
    on = { class: `flex items-center gap-4` },
    sn = { key: 1, class: `space-y-6` },
    cn = { class: `space-y-6` },
    ln = { class: `flex items-center justify-between` },
    un = { class: `space-y-3` },
    dn = { class: `flex items-center gap-4` },
    fn = { class: `font-medium` },
    pn = { class: `text-sm text-muted-foreground` },
    mn = { class: `flex items-center gap-2` },
    hn = { key: 2, class: `space-y-6` },
    gn = { class: `space-y-3` },
    _n = { class: `flex items-center gap-4` },
    vn = {
        class: `flex h-10 w-10 items-center justify-center rounded-full bg-muted`,
    },
    yn = { class: `font-medium` },
    bn = { class: `text-sm text-muted-foreground` },
    xn = { key: 3, class: `space-y-6` },
    Sn = {
        class: `space-y-4 rounded-lg border border-red-100 bg-red-50 p-4 dark:border-red-200/10 dark:bg-red-700/10`,
    },
    Cn = I({
        layout: (e) => ({
            breadcrumbs: [
                { title: `Teams`, href: Ke() },
                { title: e.team.name, href: We(e.team.slug) },
            ],
        }),
        __name: `Edit`,
        props: {
            team: {},
            members: {},
            invitations: {},
            permissions: {},
            availableRoles: {},
        },
        setup(e) {
            let t = e,
                { getInitials: n } = Ne(),
                r = b(!1),
                i = b(!1),
                a = b(!1),
                o = b(null),
                s = b(!1),
                c = b(null),
                d = l(() =>
                    t.permissions.canUpdateTeam
                        ? `Edit ${t.team.name}`
                        : `View ${t.team.name}`,
                ),
                p = (e, n) => {
                    j.visit(Ye([t.team.slug, e.id]), {
                        data: { role: n },
                        preserveScroll: !0,
                    });
                },
                v = (e) => {
                    ((o.value = e), (a.value = !0));
                },
                C = (e) => {
                    ((c.value = e), (s.value = !0));
                };
            return (t, l) => (
                u(),
                m(
                    g,
                    null,
                    [
                        w(P(ee), { title: d.value }, null, 8, [`title`]),
                        x(`h1`, tn, O(d.value), 1),
                        x(`div`, nn, [
                            e.permissions.canUpdateTeam
                                ? (u(),
                                  m(`div`, rn, [
                                      w(W, {
                                          variant: `small`,
                                          title: `Team settings`,
                                          description: `Update your team name and settings`,
                                      }),
                                      w(
                                          P(M),
                                          f(P(Ge).form(e.team.slug), {
                                              class: `space-y-6`,
                                          }),
                                          {
                                              default: _(
                                                  ({
                                                      errors: t,
                                                      processing: n,
                                                  }) => [
                                                      x(`div`, an, [
                                                          w(
                                                              P(be),
                                                              { for: `name` },
                                                              {
                                                                  default: _(
                                                                      () => [
                                                                          ...(l[6] ||=
                                                                              [
                                                                                  k(
                                                                                      `Team name`,
                                                                                      -1,
                                                                                  ),
                                                                              ]),
                                                                      ],
                                                                  ),
                                                                  _: 1,
                                                              },
                                                          ),
                                                          w(
                                                              P(Se),
                                                              {
                                                                  id: `name`,
                                                                  name: `name`,
                                                                  'data-test': `team-name-input`,
                                                                  'default-value':
                                                                      e.team
                                                                          .name,
                                                                  required: ``,
                                                              },
                                                              null,
                                                              8,
                                                              [`default-value`],
                                                          ),
                                                          w(
                                                              G,
                                                              {
                                                                  message:
                                                                      t.name,
                                                              },
                                                              null,
                                                              8,
                                                              [`message`],
                                                          ),
                                                      ]),
                                                      x(`div`, on, [
                                                          w(
                                                              P(z),
                                                              {
                                                                  type: `submit`,
                                                                  'data-test': `team-save-button`,
                                                                  disabled: n,
                                                              },
                                                              {
                                                                  default: _(
                                                                      () => [
                                                                          ...(l[7] ||=
                                                                              [
                                                                                  k(
                                                                                      ` Save `,
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
                                                      ]),
                                                  ],
                                              ),
                                              _: 1,
                                          },
                                          16,
                                      ),
                                  ]))
                                : (u(),
                                  m(`div`, sn, [
                                      w(
                                          W,
                                          {
                                              variant: `small`,
                                              title: e.team.name,
                                          },
                                          null,
                                          8,
                                          [`title`],
                                      ),
                                  ])),
                            x(`div`, cn, [
                                x(`div`, ln, [
                                    w(
                                        W,
                                        {
                                            variant: `small`,
                                            title: `Team members`,
                                            description: e.permissions
                                                .canCreateInvitation
                                                ? `Manage who belongs to this team`
                                                : ``,
                                        },
                                        null,
                                        8,
                                        [`description`],
                                    ),
                                    e.permissions.canCreateInvitation
                                        ? (u(),
                                          y(
                                              P(z),
                                              {
                                                  key: 0,
                                                  'data-test': `invite-member-button`,
                                                  onClick: (l[0] ||= (e) =>
                                                      (r.value = !0)),
                                              },
                                              {
                                                  default: _(() => [
                                                      w(P(Lt)),
                                                      (l[8] ||= k(
                                                          ` Invite member `,
                                                          -1,
                                                      )),
                                                  ]),
                                                  _: 1,
                                              },
                                          ))
                                        : S(``, !0),
                                ]),
                                x(`div`, un, [
                                    (u(!0),
                                    m(
                                        g,
                                        null,
                                        h(
                                            e.members,
                                            (t) => (
                                                u(),
                                                m(
                                                    `div`,
                                                    {
                                                        key: t.id,
                                                        'data-test': `member-row`,
                                                        class: `flex items-center justify-between rounded-lg border p-4`,
                                                    },
                                                    [
                                                        x(`div`, dn, [
                                                            w(
                                                                P(Ve),
                                                                {
                                                                    class: `h-10 w-10`,
                                                                },
                                                                {
                                                                    default: _(
                                                                        () => [
                                                                            t.avatar
                                                                                ? (u(),
                                                                                  y(
                                                                                      P(
                                                                                          He,
                                                                                      ),
                                                                                      {
                                                                                          key: 0,
                                                                                          src: t.avatar,
                                                                                          alt: t.name,
                                                                                      },
                                                                                      null,
                                                                                      8,
                                                                                      [
                                                                                          `src`,
                                                                                          `alt`,
                                                                                      ],
                                                                                  ))
                                                                                : S(
                                                                                      ``,
                                                                                      !0,
                                                                                  ),
                                                                            w(
                                                                                P(
                                                                                    Te,
                                                                                ),
                                                                                null,
                                                                                {
                                                                                    default:
                                                                                        _(
                                                                                            () => [
                                                                                                k(
                                                                                                    O(
                                                                                                        P(
                                                                                                            n,
                                                                                                        )(
                                                                                                            t.name,
                                                                                                        ),
                                                                                                    ),
                                                                                                    1,
                                                                                                ),
                                                                                            ],
                                                                                        ),
                                                                                    _: 2,
                                                                                },
                                                                                1024,
                                                                            ),
                                                                        ],
                                                                    ),
                                                                    _: 2,
                                                                },
                                                                1024,
                                                            ),
                                                            x(`div`, null, [
                                                                x(
                                                                    `div`,
                                                                    fn,
                                                                    O(t.name),
                                                                    1,
                                                                ),
                                                                x(
                                                                    `div`,
                                                                    pn,
                                                                    O(t.email),
                                                                    1,
                                                                ),
                                                            ]),
                                                        ]),
                                                        x(`div`, mn, [
                                                            t.role !==
                                                                `owner` &&
                                                            e.permissions
                                                                .canUpdateMember
                                                                ? (u(),
                                                                  y(
                                                                      P(Oe),
                                                                      {
                                                                          key: 0,
                                                                      },
                                                                      {
                                                                          default:
                                                                              _(
                                                                                  () => [
                                                                                      w(
                                                                                          P(
                                                                                              je,
                                                                                          ),
                                                                                          {
                                                                                              'as-child': ``,
                                                                                          },
                                                                                          {
                                                                                              default:
                                                                                                  _(
                                                                                                      () => [
                                                                                                          w(
                                                                                                              P(
                                                                                                                  z,
                                                                                                              ),
                                                                                                              {
                                                                                                                  'data-test': `member-role-trigger`,
                                                                                                                  variant: `outline`,
                                                                                                                  size: `sm`,
                                                                                                              },
                                                                                                              {
                                                                                                                  default:
                                                                                                                      _(
                                                                                                                          () => [
                                                                                                                              k(
                                                                                                                                  O(
                                                                                                                                      t.role_label,
                                                                                                                                  ) +
                                                                                                                                      ` `,
                                                                                                                                  1,
                                                                                                                              ),
                                                                                                                              w(
                                                                                                                                  P(
                                                                                                                                      Pt,
                                                                                                                                  ),
                                                                                                                                  {
                                                                                                                                      class: `ml-2 h-4 w-4 opacity-50`,
                                                                                                                                  },
                                                                                                                              ),
                                                                                                                          ],
                                                                                                                      ),
                                                                                                                  _: 2,
                                                                                                              },
                                                                                                              1024,
                                                                                                          ),
                                                                                                      ],
                                                                                                  ),
                                                                                              _: 2,
                                                                                          },
                                                                                          1024,
                                                                                      ),
                                                                                      w(
                                                                                          P(
                                                                                              Pe,
                                                                                          ),
                                                                                          null,
                                                                                          {
                                                                                              default:
                                                                                                  _(
                                                                                                      () => [
                                                                                                          (u(
                                                                                                              !0,
                                                                                                          ),
                                                                                                          m(
                                                                                                              g,
                                                                                                              null,
                                                                                                              h(
                                                                                                                  e.availableRoles,
                                                                                                                  (
                                                                                                                      e,
                                                                                                                  ) => (
                                                                                                                      u(),
                                                                                                                      y(
                                                                                                                          P(
                                                                                                                              Me,
                                                                                                                          ),
                                                                                                                          {
                                                                                                                              key: e.value,
                                                                                                                              'data-test': `member-role-option`,
                                                                                                                              onClick:
                                                                                                                                  (
                                                                                                                                      n,
                                                                                                                                  ) =>
                                                                                                                                      p(
                                                                                                                                          t,
                                                                                                                                          e.value,
                                                                                                                                      ),
                                                                                                                          },
                                                                                                                          {
                                                                                                                              default:
                                                                                                                                  _(
                                                                                                                                      () => [
                                                                                                                                          k(
                                                                                                                                              O(
                                                                                                                                                  e.label,
                                                                                                                                              ),
                                                                                                                                              1,
                                                                                                                                          ),
                                                                                                                                      ],
                                                                                                                                  ),
                                                                                                                              _: 2,
                                                                                                                          },
                                                                                                                          1032,
                                                                                                                          [
                                                                                                                              `onClick`,
                                                                                                                          ],
                                                                                                                      )
                                                                                                                  ),
                                                                                                              ),
                                                                                                              128,
                                                                                                          )),
                                                                                                      ],
                                                                                                  ),
                                                                                              _: 2,
                                                                                          },
                                                                                          1024,
                                                                                      ),
                                                                                  ],
                                                                              ),
                                                                          _: 2,
                                                                      },
                                                                      1024,
                                                                  ))
                                                                : (u(),
                                                                  y(
                                                                      P(we),
                                                                      {
                                                                          key: 1,
                                                                          variant: `secondary`,
                                                                      },
                                                                      {
                                                                          default:
                                                                              _(
                                                                                  () => [
                                                                                      k(
                                                                                          O(
                                                                                              t.role_label,
                                                                                          ),
                                                                                          1,
                                                                                      ),
                                                                                  ],
                                                                              ),
                                                                          _: 2,
                                                                      },
                                                                      1024,
                                                                  )),
                                                            t.role !==
                                                                `owner` &&
                                                            e.permissions
                                                                .canRemoveMember
                                                                ? (u(),
                                                                  y(
                                                                      P(Ce),
                                                                      {
                                                                          key: 2,
                                                                      },
                                                                      {
                                                                          default:
                                                                              _(
                                                                                  () => [
                                                                                      w(
                                                                                          P(
                                                                                              Ee,
                                                                                          ),
                                                                                          null,
                                                                                          {
                                                                                              default:
                                                                                                  _(
                                                                                                      () => [
                                                                                                          w(
                                                                                                              P(
                                                                                                                  Fe,
                                                                                                              ),
                                                                                                              {
                                                                                                                  'as-child': ``,
                                                                                                              },
                                                                                                              {
                                                                                                                  default:
                                                                                                                      _(
                                                                                                                          () => [
                                                                                                                              w(
                                                                                                                                  P(
                                                                                                                                      z,
                                                                                                                                  ),
                                                                                                                                  {
                                                                                                                                      'data-test': `member-remove-button`,
                                                                                                                                      variant: `ghost`,
                                                                                                                                      size: `sm`,
                                                                                                                                      onClick:
                                                                                                                                          (
                                                                                                                                              e,
                                                                                                                                          ) =>
                                                                                                                                              v(
                                                                                                                                                  t,
                                                                                                                                              ),
                                                                                                                                  },
                                                                                                                                  {
                                                                                                                                      default:
                                                                                                                                          _(
                                                                                                                                              () => [
                                                                                                                                                  w(
                                                                                                                                                      P(
                                                                                                                                                          ke,
                                                                                                                                                      ),
                                                                                                                                                      {
                                                                                                                                                          class: `h-4 w-4`,
                                                                                                                                                      },
                                                                                                                                                  ),
                                                                                                                                              ],
                                                                                                                                          ),
                                                                                                                                      _: 1,
                                                                                                                                  },
                                                                                                                                  8,
                                                                                                                                  [
                                                                                                                                      `onClick`,
                                                                                                                                  ],
                                                                                                                              ),
                                                                                                                          ],
                                                                                                                      ),
                                                                                                                  _: 2,
                                                                                                              },
                                                                                                              1024,
                                                                                                          ),
                                                                                                          w(
                                                                                                              P(
                                                                                                                  Ze,
                                                                                                              ),
                                                                                                              null,
                                                                                                              {
                                                                                                                  default:
                                                                                                                      _(
                                                                                                                          () => [
                                                                                                                              ...(l[9] ||=
                                                                                                                                  [
                                                                                                                                      x(
                                                                                                                                          `p`,
                                                                                                                                          null,
                                                                                                                                          `Remove member`,
                                                                                                                                          -1,
                                                                                                                                      ),
                                                                                                                                  ]),
                                                                                                                          ],
                                                                                                                      ),
                                                                                                                  _: 1,
                                                                                                              },
                                                                                                          ),
                                                                                                      ],
                                                                                                  ),
                                                                                              _: 2,
                                                                                          },
                                                                                          1024,
                                                                                      ),
                                                                                  ],
                                                                              ),
                                                                          _: 2,
                                                                      },
                                                                      1024,
                                                                  ))
                                                                : S(``, !0),
                                                        ]),
                                                    ],
                                                )
                                            ),
                                        ),
                                        128,
                                    )),
                                ]),
                            ]),
                            e.invitations.length > 0
                                ? (u(),
                                  m(`div`, hn, [
                                      w(W, {
                                          variant: `small`,
                                          title: `Pending invitations`,
                                          description: `Invitations that haven't been accepted yet`,
                                      }),
                                      x(`div`, gn, [
                                          (u(!0),
                                          m(
                                              g,
                                              null,
                                              h(
                                                  e.invitations,
                                                  (t) => (
                                                      u(),
                                                      m(
                                                          `div`,
                                                          {
                                                              key: t.code,
                                                              'data-test': `invitation-row`,
                                                              class: `flex items-center justify-between rounded-lg border p-4`,
                                                          },
                                                          [
                                                              x(`div`, _n, [
                                                                  x(`div`, vn, [
                                                                      w(P(It), {
                                                                          class: `h-5 w-5 text-muted-foreground`,
                                                                      }),
                                                                  ]),
                                                                  x(
                                                                      `div`,
                                                                      null,
                                                                      [
                                                                          x(
                                                                              `div`,
                                                                              yn,
                                                                              O(
                                                                                  t.email,
                                                                              ),
                                                                              1,
                                                                          ),
                                                                          x(
                                                                              `div`,
                                                                              bn,
                                                                              O(
                                                                                  t.role_label,
                                                                              ),
                                                                              1,
                                                                          ),
                                                                      ],
                                                                  ),
                                                              ]),
                                                              e.permissions
                                                                  .canCancelInvitation
                                                                  ? (u(),
                                                                    y(
                                                                        P(Ce),
                                                                        {
                                                                            key: 0,
                                                                        },
                                                                        {
                                                                            default:
                                                                                _(
                                                                                    () => [
                                                                                        w(
                                                                                            P(
                                                                                                Ee,
                                                                                            ),
                                                                                            null,
                                                                                            {
                                                                                                default:
                                                                                                    _(
                                                                                                        () => [
                                                                                                            w(
                                                                                                                P(
                                                                                                                    Fe,
                                                                                                                ),
                                                                                                                {
                                                                                                                    'as-child': ``,
                                                                                                                },
                                                                                                                {
                                                                                                                    default:
                                                                                                                        _(
                                                                                                                            () => [
                                                                                                                                w(
                                                                                                                                    P(
                                                                                                                                        z,
                                                                                                                                    ),
                                                                                                                                    {
                                                                                                                                        'data-test': `invitation-cancel-button`,
                                                                                                                                        variant: `ghost`,
                                                                                                                                        size: `sm`,
                                                                                                                                        onClick:
                                                                                                                                            (
                                                                                                                                                e,
                                                                                                                                            ) =>
                                                                                                                                                C(
                                                                                                                                                    t,
                                                                                                                                                ),
                                                                                                                                    },
                                                                                                                                    {
                                                                                                                                        default:
                                                                                                                                            _(
                                                                                                                                                () => [
                                                                                                                                                    w(
                                                                                                                                                        P(
                                                                                                                                                            ke,
                                                                                                                                                        ),
                                                                                                                                                        {
                                                                                                                                                            class: `h-4 w-4`,
                                                                                                                                                        },
                                                                                                                                                    ),
                                                                                                                                                ],
                                                                                                                                            ),
                                                                                                                                        _: 1,
                                                                                                                                    },
                                                                                                                                    8,
                                                                                                                                    [
                                                                                                                                        `onClick`,
                                                                                                                                    ],
                                                                                                                                ),
                                                                                                                            ],
                                                                                                                        ),
                                                                                                                    _: 2,
                                                                                                                },
                                                                                                                1024,
                                                                                                            ),
                                                                                                            w(
                                                                                                                P(
                                                                                                                    Ze,
                                                                                                                ),
                                                                                                                null,
                                                                                                                {
                                                                                                                    default:
                                                                                                                        _(
                                                                                                                            () => [
                                                                                                                                ...(l[10] ||=
                                                                                                                                    [
                                                                                                                                        x(
                                                                                                                                            `p`,
                                                                                                                                            null,
                                                                                                                                            `Cancel invitation`,
                                                                                                                                            -1,
                                                                                                                                        ),
                                                                                                                                    ]),
                                                                                                                            ],
                                                                                                                        ),
                                                                                                                    _: 1,
                                                                                                                },
                                                                                                            ),
                                                                                                        ],
                                                                                                    ),
                                                                                                _: 2,
                                                                                            },
                                                                                            1024,
                                                                                        ),
                                                                                    ],
                                                                                ),
                                                                            _: 2,
                                                                        },
                                                                        1024,
                                                                    ))
                                                                  : S(``, !0),
                                                          ],
                                                      )
                                                  ),
                                              ),
                                              128,
                                          )),
                                      ]),
                                  ]))
                                : S(``, !0),
                            e.permissions.canDeleteTeam && !e.team.isPersonal
                                ? (u(),
                                  m(`div`, xn, [
                                      w(W, {
                                          variant: `small`,
                                          title: `Delete team`,
                                          description: `Permanently delete your team`,
                                      }),
                                      x(`div`, Sn, [
                                          (l[12] ||= x(
                                              `div`,
                                              {
                                                  class: `relative space-y-0.5 text-red-600 dark:text-red-100`,
                                              },
                                              [
                                                  x(
                                                      `p`,
                                                      { class: `font-medium` },
                                                      `Warning`,
                                                  ),
                                                  x(
                                                      `p`,
                                                      { class: `text-sm` },
                                                      ` Please proceed with caution, this cannot be undone. `,
                                                  ),
                                              ],
                                              -1,
                                          )),
                                          w(
                                              P(z),
                                              {
                                                  'data-test': `delete-team-button`,
                                                  variant: `destructive`,
                                                  onClick: (l[1] ||= (e) =>
                                                      (i.value = !0)),
                                              },
                                              {
                                                  default: _(() => [
                                                      ...(l[11] ||= [
                                                          k(`Delete team`, -1),
                                                      ]),
                                                  ]),
                                                  _: 1,
                                              },
                                          ),
                                      ]),
                                  ]))
                                : S(``, !0),
                        ]),
                        e.permissions.canCreateInvitation
                            ? (u(),
                              y(
                                  $t,
                                  {
                                      key: 0,
                                      team: e.team,
                                      'available-roles': e.availableRoles,
                                      open: r.value,
                                      'onUpdate:open': (l[2] ||= (e) =>
                                          (r.value = e)),
                                  },
                                  null,
                                  8,
                                  [`team`, `available-roles`, `open`],
                              ))
                            : S(``, !0),
                        w(
                            en,
                            {
                                team: e.team,
                                member: o.value,
                                open: a.value,
                                'onUpdate:open': (l[3] ||= (e) =>
                                    (a.value = e)),
                            },
                            null,
                            8,
                            [`team`, `member`, `open`],
                        ),
                        w(
                            Rt,
                            {
                                team: e.team,
                                invitation: c.value,
                                open: s.value,
                                'onUpdate:open': (l[4] ||= (e) =>
                                    (s.value = e)),
                            },
                            null,
                            8,
                            [`team`, `invitation`, `open`],
                        ),
                        e.permissions.canDeleteTeam && !e.team.isPersonal
                            ? (u(),
                              y(
                                  Vt,
                                  {
                                      key: 1,
                                      team: e.team,
                                      open: i.value,
                                      'onUpdate:open': (l[5] ||= (e) =>
                                          (i.value = e)),
                                  },
                                  null,
                                  8,
                                  [`team`, `open`],
                              ))
                            : S(``, !0),
                    ],
                    64,
                )
            );
        },
    });
export { Cn as default };
