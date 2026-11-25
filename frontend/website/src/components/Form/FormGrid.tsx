import type { ReactNode } from 'react'

/** The grid a form's fields sit in. `columns` splits a line in two. */
export function FormGrid({ children, columns = 1 }: { children: ReactNode; columns?: 1 | 2 }) {
    return <div className={columns === 2 ? 'form-grid form-grid-two' : 'form-grid'}>{children}</div>
}
