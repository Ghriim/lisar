import { useEffect, useLayoutEffect, useRef, useState, type ReactNode } from 'react'
import { createPortal } from 'react-dom'

/** Room the tooltip needs above its host; with less, it opens underneath instead. */
const ROOM_ABOVE = 40
const GAP = 6
/** How close to the window's edge it may come. */
const MARGIN = 8

interface Anchor {
    /** Horizontal centre of the host. */
    x: number
    /** Its top edge when the tooltip opens above, its bottom edge when it opens below. */
    y: number
    below: boolean
}

interface TooltipProps {
    /** The text shown on hover and on keyboard focus — for a control, the verb alone. */
    label: string
    children: ReactNode
}

/**
 * Names what it wraps, on hover and on keyboard focus, in the System's own words rather than
 * the browser's `title`.
 *
 * Drawn on `document.body`, positioned against the window: a card cuts its corner with a
 * `clip-path` and a window scrolls, and either would clip a tooltip drawn inside them. It opens
 * above, underneath when the window has no room above, and never runs off either side.
 */
export function Tooltip({ label, children }: TooltipProps) {
    const host = useRef<HTMLSpanElement>(null)
    const bubble = useRef<HTMLSpanElement>(null)
    const [anchor, setAnchor] = useState<Anchor | null>(null)

    const show = () => {
        if (host.current === null) {
            return
        }
        const box = host.current.getBoundingClientRect()
        const below = box.top < ROOM_ABOVE

        setAnchor({ x: box.left + box.width / 2, y: below ? box.bottom + GAP : box.top - GAP, below })
    }
    const hide = () => setAnchor(null)

    // Kept inside the window: centred on its host, unless that pushes it past an edge.
    useLayoutEffect(() => {
        if (anchor === null || bubble.current === null) {
            return
        }
        const half = bubble.current.offsetWidth / 2
        const x = Math.min(Math.max(anchor.x, MARGIN + half), window.innerWidth - MARGIN - half)
        bubble.current.style.left = `${x}px`
    }, [anchor])

    // A fixed tooltip would stay where the host was: anything that scrolls takes it away.
    useEffect(() => {
        if (anchor === null) {
            return undefined
        }
        window.addEventListener('scroll', hide, true)

        return () => window.removeEventListener('scroll', hide, true)
    }, [anchor])

    return (
        <span
            ref={host}
            className="tooltip-host"
            onMouseEnter={show}
            onMouseLeave={hide}
            onFocus={show}
            onBlur={hide}
            // What a click opens arrives over the page: the name of the control has done its job.
            onClick={hide}
        >
            {children}
            {anchor !== null &&
                createPortal(
                    <span
                        ref={bubble}
                        role="tooltip"
                        className={anchor.below ? 'tooltip tooltip-below' : 'tooltip'}
                        style={{ left: anchor.x, top: anchor.y }}
                    >
                        {label}
                    </span>,
                    document.body,
                )}
        </span>
    )
}
