import type { ReactNode } from 'react'

/**
 * Every form in the app ends with one of these: centred, cancel first, the verb that commits
 * second. The order never changes, so nobody has to read the buttons to know which is which.
 */
export function FormActions({ children }: { children: ReactNode }) {
    return <div className="form-actions">{children}</div>
}
