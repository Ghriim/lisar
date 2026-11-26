import { Link } from 'react-router-dom'
import { useAuth } from '../../auth/useAuth'
import { SegmentedBar, SystemPanel, Tooltip } from '../../components'
import { playerTitle } from './playerTitle'
import { useStatus } from './queries'
import { STAT_ORDER, statLabel } from './statFormat'

/**
 * The status, at the top of the dashboard: the level in its medallion, the name and the title,
 * the bar towards the next level, and the six stats' levels. It is the Statut window's entry —
 * the medallion opens it. No rank, no run, no « X XP to go ».
 */
export function StatusCard() {
    const status = useStatus()
    const { user } = useAuth()

    if (status.data === undefined) {
        return null
    }

    const { level, experienceIntoLevel, experienceForNextLevel } = status.data.progress
    const levels = new Map(status.data.groups.flatMap((group) => group.stats).map((stat) => [stat.stat, stat.progress.level]))
    const title = playerTitle(status.data)

    return (
        <SystemPanel className="status-card">
            <div className="status-card-player">
                <Link to="/statut" className="status-medallion" aria-label={`Statut — niveau ${level}`}>
                    <span className="status-medallion-level">{level}</span>
                </Link>

                <div className="status-card-progress">
                    <div className="status-card-identity">
                        <span className="status-card-name">{capitalised(user?.username ?? '')}</span>
                        <span className="status-card-title">Titre : {title ?? '—'}</span>
                    </div>
                    <SegmentedBar
                        value={experienceIntoLevel}
                        max={experienceForNextLevel}
                        segments={25}
                        label={`Expérience : ${experienceIntoLevel} / ${experienceForNextLevel} XP`}
                    />
                    <span className="status-card-xp">
                        {experienceIntoLevel} / {experienceForNextLevel} XP
                    </span>
                </div>
            </div>

            <ul className="status-card-stats">
                {STAT_ORDER.map(({ stat, abbreviation }) => (
                    <li key={stat}>
                        <Tooltip label={statLabel(stat)}>
                            <span className="status-stat" tabIndex={0} aria-label={`${statLabel(stat)} : ${levels.get(stat) ?? 0}`}>
                                <span className="status-stat-abbr" aria-hidden>{abbreviation}</span>
                                <span className="status-stat-value" aria-hidden>{levels.get(stat) ?? 0}</span>
                            </span>
                        </Tooltip>
                    </li>
                ))}
            </ul>
        </SystemPanel>
    )
}

/** « ghriim » shown « Ghriim »: the pseudo as it was typed, its first letter raised. */
function capitalised(name: string): string {
    return name.charAt(0).toLocaleUpperCase('fr-FR') + name.slice(1)
}
