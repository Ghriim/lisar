import type { ReactNode } from 'react'

export type DefinitionWidth = 'full' | 'three-quarters' | 'half' | 'quarter'

export interface Definition {
    label: string
    /** Null, undefined or an empty string all mean "not filled in". */
    value: ReactNode
    /** What to show instead. Worded per field: "Aucune échéance" reads better than a dash. */
    placeholder: string
    /** How much of a line the field takes. Full by default. */
    width?: DefinitionWidth
    /**
     * Hides the heading for a value that already says what it is — a state chip reads "En cours"
     * under a heading saying "État", which is a word too many. The label stays for a screen
     * reader, which cannot see the chip.
     */
    hideLabel?: boolean
}

const WIDTHS: Record<DefinitionWidth, string> = {
    full: 'definition-full',
    'three-quarters': 'definition-three-quarters',
    half: 'definition-half',
    quarter: 'definition-quarter',
}

/**
 * Label-and-value pairs, laid out on the same grid a form uses, so reading a thing and editing
 * it put the same field in the same place.
 *
 * Every field is listed, filled in or not: a missing value is information too, and a reader who
 * does not see "Échéance" cannot tell whether there is none or whether the app forgot.
 */
export function DefinitionList({ items }: { items: Definition[] }) {
    return (
        <dl className="definitions">
            {items.map((item) => {
                const isEmpty = item.value === null || item.value === undefined || item.value === ''

                return (
                    <div key={item.label} className={WIDTHS[item.width ?? 'full']}>
                        <dt
                            className={
                                item.hideLabel === true
                                    ? 'definition-label definition-label-silent'
                                    : 'definition-label'
                            }
                        >
                            {item.label}
                        </dt>
                        <dd className={isEmpty ? 'definition-value definition-empty' : 'definition-value'}>
                            {isEmpty ? item.placeholder : item.value}
                        </dd>
                    </div>
                )
            })}
        </dl>
    )
}
