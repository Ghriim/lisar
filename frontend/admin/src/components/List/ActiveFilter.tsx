import { Segmented } from 'antd'
import type { ActiveStatus } from './useActiveFilter'

/** The labels agree with the noun being filtered: "Actifs" for accounts, "Actives" for habits. */
type Gender = 'masculine' | 'feminine'

const LABELS: Record<Gender, Record<ActiveStatus, string>> = {
    masculine: { active: 'Actifs', inactive: 'Inactifs', all: 'Tous' },
    feminine: { active: 'Actives', inactive: 'Inactives', all: 'Toutes' },
}

/** Always this order: active, inactive, all (docs/dev/frontend-conventions.md §4.1). */
const ORDER: ActiveStatus[] = ['active', 'inactive', 'all']

interface ActiveFilterProps {
    value: ActiveStatus
    onChange: (value: ActiveStatus) => void
    gender?: Gender
}

/** Every list filtered on whether its rows are active uses this, and nothing else. */
export function ActiveFilter({ value, onChange, gender = 'masculine' }: ActiveFilterProps) {
    return (
        <Segmented<ActiveStatus>
            value={value}
            options={ORDER.map((status) => ({ label: LABELS[gender][status], value: status }))}
            onChange={onChange}
        />
    )
}
