import type { ReactNode } from 'react'

interface TextButtonProps {
    /** What it shows. Unlike `Button`, not a verb: a thing's name that opens the thing. */
    children: ReactNode
    onClick: () => void
    /** Read out instead of the text, when the text alone does not say what clicking does. */
    ariaLabel?: string
    /** Dimmed, for something done. */
    muted?: boolean
}

/** A name that opens what it names — a task's title opens its window. It reads as text, not as a button. */
export function TextButton({ children, onClick, ariaLabel, muted = false }: TextButtonProps) {
    return (
        <button type="button" className={muted ? 'text-button text-button-muted' : 'text-button'} aria-label={ariaLabel} onClick={onClick}>
            {children}
        </button>
    )
}
