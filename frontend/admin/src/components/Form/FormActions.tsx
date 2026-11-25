import type { ReactNode } from 'react'
import { Row } from '../Layout'

/**
 * The house rule, in one place: centred, cancel first, the verb that commits second.
 * See docs/dev/frontend-conventions.md.
 */
export function FormActions({ children }: { children: ReactNode }) {
    return (
        <Row justify="center" gap={8}>
            {children}
        </Row>
    )
}
