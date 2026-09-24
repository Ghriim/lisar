import type { KeyboardEvent, ReactNode } from 'react'

interface SystemPanelProps {
    /** Rendered as the System's own label for the window: short, and shouted. */
    title?: string
    actions?: ReactNode
    children: ReactNode
    className?: string
    /**
     * Makes the whole panel a button: clicking anywhere on it, or pressing Enter or Space when it
     * holds focus, runs this. A panel that carries this must not render an interactive child of
     * its own — the click would land on both.
     */
    onActivate?: () => void
    /** Spoken name for the panel when it is activatable and shows no visible title. */
    ariaLabel?: string
}

/**
 * The one surface of the app. Everything the System says arrives inside one of these.
 *
 * Given `onActivate`, the whole surface becomes one button — used by the tracker widgets, which
 * open their window from a click anywhere rather than from an icon in the corner.
 */
export function SystemPanel({ title, actions, children, className, onActivate, ariaLabel }: SystemPanelProps) {
    const interactive = onActivate !== undefined

    const classes = ['panel', interactive ? 'panel-interactive' : '', className ?? '']
        .filter((name) => name !== '')
        .join(' ')

    const onKeyDown = (event: KeyboardEvent<HTMLElement>) => {
        if (onActivate !== undefined && (event.key === 'Enter' || event.key === ' ')) {
            event.preventDefault()
            onActivate()
        }
    }

    return (
        <section
            className={classes}
            role={interactive ? 'button' : undefined}
            tabIndex={interactive ? 0 : undefined}
            aria-label={interactive ? ariaLabel : undefined}
            onClick={onActivate}
            onKeyDown={interactive ? onKeyDown : undefined}
        >
            {(title !== undefined || actions !== undefined) && (
                <header className="row spread panel-title">
                    <span>{title !== undefined ? `[ ${title} ]` : null}</span>
                    {actions}
                </header>
            )}
            {children}
        </section>
    )
}
