import {
    Kt as e,
    Q as t,
    Tn as n,
    X as r,
    Xn as i,
    Y as a,
    Z as o,
    at as s,
    dr as c,
    fr as l,
    lr as u,
    nn as d,
    nt as f,
    or as p,
    st as m,
} from './dist-C0kHilBU.js';
import { t as h } from './button-BRBVuDNP.js';
import { t as g } from './createLucideIcon-C6NZ5pDL.js';
import { t as _ } from './file-text-Bf77k8xe.js';
import { t as v } from './SectionCard-sO5uXxKO.js';
var y = g({
        name: `check-check`,
        size: 24,
        node: [
            [`path`, { d: `M18 6 7 17l-5-5`, key: `116fxf` }],
            [`path`, { d: `m22 10-7.5 7.5L13 16`, key: `ke71qq` }],
        ],
    }),
    b = g({
        name: `rotate-cw`,
        size: 24,
        node: [
            [
                `path`,
                {
                    d: `M21 12a9 9 0 1 1-9-9c2.52 0 4.93 1 6.74 2.74L21 8`,
                    key: `1p45f6`,
                },
            ],
            [`path`, { d: `M21 3v5h-5`, key: `1q7to0` }],
        ],
    }),
    x = g({
        name: `save`,
        size: 24,
        node: [
            [
                `path`,
                {
                    d: `M15.2 3a2 2 0 0 1 1.4.6l3.8 3.8a2 2 0 0 1 .6 1.4V19a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z`,
                    key: `1c8476`,
                },
            ],
            [
                `path`,
                {
                    d: `M17 21v-7a1 1 0 0 0-1-1H8a1 1 0 0 0-1 1v7`,
                    key: `1ydtos`,
                },
            ],
            [`path`, { d: `M7 3v4a1 1 0 0 0 1 1h7`, key: `t51u73` }],
        ],
    }),
    S = g({
        name: `zoom-out`,
        size: 24,
        node: [
            [`circle`, { cx: `11`, cy: `11`, r: `8`, key: `4ej97u` }],
            [
                `line`,
                { x1: `21`, x2: `16.65`, y1: `21`, y2: `16.65`, key: `13gj7c` },
            ],
            [`line`, { x1: `8`, x2: `14`, y1: `11`, y2: `11`, key: `durymu` }],
        ],
    }),
    C = g({
        name: `zoom-in`,
        size: 24,
        node: [
            [`circle`, { cx: `11`, cy: `11`, r: `8`, key: `4ej97u` }],
            [
                `line`,
                { x1: `21`, x2: `16.65`, y1: `21`, y2: `16.65`, key: `13gj7c` },
            ],
            [`line`, { x1: `11`, x2: `11`, y1: `8`, y2: `14`, key: `1vmskp` }],
            [`line`, { x1: `8`, x2: `14`, y1: `11`, y2: `11`, key: `durymu` }],
        ],
    }),
    w = { key: 0, class: `text-sm text-muted-foreground` },
    T = [`href`],
    E = { class: `font-medium text-foreground` },
    D = [`src`, `alt`],
    O = m({
        __name: `ScanImagePanel`,
        props: { image: {} },
        setup(m) {
            let g = m,
                y = i(0),
                x = i(!1);
            function O() {
                y.value = (y.value + 90) % 360;
            }
            return (i, m) => (
                e(),
                r(
                    v,
                    { title: `صورة الفاتورة`, class: `lg:sticky lg:top-20` },
                    f(
                        {
                            default: n(() => [
                                g.image
                                    ? g.image.isPdf
                                        ? (e(),
                                          t(
                                              `a`,
                                              {
                                                  key: 1,
                                                  href: g.image.previewUrl,
                                                  target: `_blank`,
                                                  rel: `noopener`,
                                                  class: `flex flex-col items-center gap-2 rounded-lg border border-dashed border-input px-4 py-10 text-sm text-muted-foreground transition-colors hover:border-primary/50 hover:bg-accent`,
                                              },
                                              [
                                                  s(p(_), { class: `size-7` }),
                                                  a(
                                                      `span`,
                                                      E,
                                                      l(g.image.name),
                                                      1,
                                                  ),
                                                  (m[1] ||= a(
                                                      `span`,
                                                      { class: `text-xs` },
                                                      `افتح الـ PDF في تاب جديد`,
                                                      -1,
                                                  )),
                                              ],
                                              8,
                                              T,
                                          ))
                                        : (e(),
                                          t(
                                              `div`,
                                              {
                                                  key: 2,
                                                  class: u([
                                                      `scrollbar-thin overflow-auto rounded-lg border border-border bg-muted/30`,
                                                      x.value
                                                          ? `max-h-[75vh]`
                                                          : `max-h-[55vh]`,
                                                  ]),
                                              },
                                              [
                                                  a(
                                                      `img`,
                                                      {
                                                          src: g.image
                                                              .previewUrl,
                                                          alt: g.image.name,
                                                          class: u([
                                                              `mx-auto origin-center transition-transform duration-200`,
                                                              x.value
                                                                  ? `max-w-none`
                                                                  : `w-full`,
                                                          ]),
                                                          style: c({
                                                              transform: `rotate(${y.value}deg)${x.value ? ` scale(1.6)` : ``}`,
                                                          }),
                                                          'data-test': `scan-image`,
                                                      },
                                                      null,
                                                      14,
                                                      D,
                                                  ),
                                              ],
                                              2,
                                          ))
                                    : (e(),
                                      t(
                                          `p`,
                                          w,
                                          ` مفيش صورة مرفقة على المسودة دي. `,
                                      )),
                            ]),
                            _: 2,
                        },
                        [
                            g.image
                                ? {
                                      name: `actions`,
                                      fn: n(() => [
                                          g.image.isPdf
                                              ? o(``, !0)
                                              : (e(),
                                                r(
                                                    p(h),
                                                    {
                                                        key: 0,
                                                        type: `button`,
                                                        variant: `ghost`,
                                                        size: `icon`,
                                                        class: `size-8`,
                                                        'aria-label': `تدوير الصورة`,
                                                        'data-test': `scan-image-rotate`,
                                                        onClick: O,
                                                    },
                                                    {
                                                        default: n(() => [
                                                            s(p(b), {
                                                                class: `size-4`,
                                                            }),
                                                        ]),
                                                        _: 1,
                                                    },
                                                )),
                                          g.image.isPdf
                                              ? o(``, !0)
                                              : (e(),
                                                r(
                                                    p(h),
                                                    {
                                                        key: 1,
                                                        type: `button`,
                                                        variant: `ghost`,
                                                        size: `icon`,
                                                        class: `size-8`,
                                                        'aria-label': x.value
                                                            ? `تصغير`
                                                            : `تكبير`,
                                                        'data-test': `scan-image-zoom`,
                                                        onClick: (m[0] ||= (
                                                            e,
                                                        ) =>
                                                            (x.value =
                                                                !x.value)),
                                                    },
                                                    {
                                                        default: n(() => [
                                                            (e(),
                                                            r(
                                                                d(
                                                                    x.value
                                                                        ? p(S)
                                                                        : p(C),
                                                                ),
                                                                {
                                                                    class: `size-4`,
                                                                },
                                                            )),
                                                        ]),
                                                        _: 1,
                                                    },
                                                    8,
                                                    [`aria-label`],
                                                )),
                                      ]),
                                      key: `0`,
                                  }
                                : void 0,
                        ],
                    ),
                    1024,
                )
            );
        },
    });
function k(e, t) {
    return `items.${e}.${t}`;
}
function A() {
    let e = i(null),
        t = i(new Set());
    function n(n, r) {
        if (((e.value = n), !n)) {
            t.value = new Set();
            return;
        }
        let i = new Set();
        (n.party.confidence !== `exact` &&
            i.add(r === `purchase` ? `supplier_id` : `customer_id`),
            n.fields.forEach((e) => i.add(e)),
            n.items.forEach((e, t) => {
                (e.confidence !== `exact` && i.add(k(t, `product_id`)),
                    (e.priceFromSystem || e.missingPrice) &&
                        i.add(k(t, `unit_price`)));
            }),
            (t.value = i));
    }
    function r(e) {
        if (!t.value.has(e)) return;
        let n = new Set(t.value);
        (n.delete(e), (t.value = n));
    }
    function a(e) {
        return t.value.has(e);
    }
    function o(e) {
        return a(e)
            ? `ring-2 ring-yellow-400 bg-yellow-50 dark:bg-yellow-500/10 dark:ring-yellow-500/60`
            : ``;
    }
    function s(t) {
        return e.value?.items[t] ?? null;
    }
    return {
        review: e,
        pending: t,
        seed: n,
        confirm: r,
        needsReview: a,
        reviewClass: o,
        rawItem: s,
    };
}
export { y as a, x as i, A as n, O as r, k as t };
