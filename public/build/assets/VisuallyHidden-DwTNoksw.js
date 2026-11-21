import { i as e } from './Presence-BI6XYFRa.js';
import {
    $t as t,
    J as n,
    Kt as r,
    Tn as i,
    X as a,
    Xn as o,
    or as s,
    st as c,
} from './dist-C0kHilBU.js';
import { n as l } from './button-BRBVuDNP.js';
function u(t) {
    let r = e({ dir: o(`ltr`) });
    return n(() => t?.value || r.dir?.value || `ltr`);
}
var d = c({
    __name: `VisuallyHidden`,
    props: {
        feature: { type: String, required: !1, default: `focusable` },
        asChild: { type: Boolean, required: !1 },
        as: { type: null, required: !1, default: `span` },
    },
    setup(e) {
        return (n, o) => (
            r(),
            a(
                s(l),
                {
                    as: e.as,
                    'as-child': e.asChild,
                    'aria-hidden':
                        e.feature === `fully-hidden` ? `true` : void 0,
                    'data-hidden': e.feature === `fully-hidden` ? `` : void 0,
                    tabindex: e.feature === `fully-hidden` ? `-1` : void 0,
                    style: {
                        position: `absolute`,
                        border: 0,
                        width: `1px`,
                        height: `1px`,
                        padding: 0,
                        margin: `-1px`,
                        overflow: `hidden`,
                        clip: `rect(0, 0, 0, 0)`,
                        clipPath: `inset(50%)`,
                        whiteSpace: `nowrap`,
                        wordWrap: `normal`,
                        top: `-1px`,
                        left: `-1px`,
                    },
                },
                { default: i(() => [t(n.$slots, `default`)]), _: 3 },
                8,
                [`as`, `as-child`, `aria-hidden`, `data-hidden`, `tabindex`],
            )
        );
    },
});
export { u as n, d as t };
