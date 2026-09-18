import type { CSSProperties, ReactNode } from 'react'

interface LayoutProps {
    children: ReactNode
    /** Pushes the first and last child apart. */
    spread?: boolean
    wrap?: boolean
    style?: CSSProperties
}

export function Row({ children, spread = false, wrap = false, style }: LayoutProps) {
    return (
        <div className={['row', spread ? 'spread' : '', wrap ? 'wrap' : ''].filter(Boolean).join(' ')} style={style}>
            {children}
        </div>
    )
}

export function Stack({ children, style }: { children: ReactNode; style?: CSSProperties }) {
    return (
        <div className="stack" style={style}>
            {children}
        </div>
    )
}

/** The grid a form's fields sit in. `columns` splits a line in two. */
export function FormGrid({ children, columns = 1 }: { children: ReactNode; columns?: 1 | 2 }) {
    return <div className={columns === 2 ? 'form-grid form-grid-two' : 'form-grid'}>{children}</div>
}
