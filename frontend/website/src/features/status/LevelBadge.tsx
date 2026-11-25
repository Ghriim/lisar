import { Link } from 'react-router-dom'
import { ProgressBar } from '../../components'
import { useStatus } from './queries'

/**
 * The global level, under the app's name: « Niv. 3 » and a thin bar towards the next; folded,
 * the number alone. It is the Statut window's only entry.
 */
export function LevelBadge({ collapsed }: { collapsed: boolean }) {
    const status = useStatus()
    if (status.data === undefined) {
        return null
    }
    const { level, experienceIntoLevel, experienceForNextLevel } = status.data.progress

    return (
        <Link to="/statut" className="level-badge side-nav-tip" data-tooltip={`Niveau ${level}`} aria-label={`Statut — niveau ${level}`}>
            <span className="level-badge-level">{collapsed ? level : `Niv. ${level}`}</span>
            {!collapsed && (
                <ProgressBar value={experienceIntoLevel} max={experienceForNextLevel} label={`${experienceIntoLevel} sur ${experienceForNextLevel} XP vers le niveau ${level + 1}`} />
            )}
        </Link>
    )
}
