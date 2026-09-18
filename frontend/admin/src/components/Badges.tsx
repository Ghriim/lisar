import { ROLE_ADMIN } from '../api/types'
import { Tag } from './Feedback'

export function StatusTag({ isActive }: { isActive: boolean }) {
    return <Tag colour={isActive ? 'green' : 'red'}>{isActive ? 'Actif' : 'Désactivé'}</Tag>
}

/** Shows nothing at all for an ordinary account: a role is only worth saying when it is not one. */
export function RoleTag({ role }: { role: string }) {
    if (role !== ROLE_ADMIN) {
        return null
    }

    return <Tag colour="gold">admin</Tag>
}

/** The colour a priority is rendered in, as the front ends show it. */
export function ColourDot({ colour }: { colour: string }) {
    return (
        <span
            aria-hidden
            style={{
                display: 'inline-block',
                width: 10,
                height: 10,
                borderRadius: '50%',
                background: colour,
            }}
        />
    )
}
