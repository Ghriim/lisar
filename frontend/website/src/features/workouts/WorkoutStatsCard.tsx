import { Trophy } from 'lucide-react'
import type { WorkoutStats } from '../../api/types'
import { Alert, DefinitionList, ListItem, Loader, ProgressBar, Stack, SystemPanel, type Definition } from '../../components'
import { formatPersonalBestValue, personalBestLabel } from '../records/personalBestFormat'
import { useWorkoutStats } from './queries'
import { formatDistance, formatDuration, formatLoad } from './workoutFormat'

/**
 * What a workout amounts to: its sets, then the load, time and distance — each only when a set
 * measured it — how its sets fell on the muscles, and the records it beat.
 */
export function WorkoutStatsCard({ workoutId }: { workoutId: number }) {
    const stats = useWorkoutStats(workoutId)

    return (
        <SystemPanel title="Bilan">
            {stats.isPending ? (
                <Loader />
            ) : stats.isError || stats.data === undefined ? (
                <Alert>Le System ne répond pas. Réessaie dans un instant.</Alert>
            ) : (
                <StatsBody stats={stats.data} />
            )}
        </SystemPanel>
    )
}

function StatsBody({ stats }: { stats: WorkoutStats }) {
    // A push-up workout moved no load: its volume is not zero, it does not exist, and is not shown.
    const totals: Definition[] = [{ label: 'Séries', placeholder: '0', width: 'quarter', value: `${stats.setCount}` }]
    if (stats.volumeInKilograms !== null) {
        totals.push({ label: 'Volume', placeholder: '—', width: 'quarter', value: formatLoad(stats.volumeInKilograms) })
    }
    if (stats.durationInSeconds !== null) {
        totals.push({ label: 'Durée d’effort', placeholder: '—', width: 'quarter', value: formatDuration(stats.durationInSeconds) })
    }
    if (stats.distanceInMetres !== null) {
        totals.push({ label: 'Distance', placeholder: '—', width: 'quarter', value: formatDistance(stats.distanceInMetres) })
    }

    return (
        <Stack>
            <DefinitionList items={totals} />

            {stats.muscles.length > 0 && (
                <div className="list-group" style={{ marginBottom: 0 }}>
                    <h2 className="list-group-heading">Répartition par muscle</h2>
                    <p className="tracker-note" style={{ marginTop: 0 }}>
                        Une série compte pour 1 au muscle principal, pour 0,5 à chaque muscle secondaire.
                    </p>

                    <div className="muscle-shares">
                        {stats.muscles.map((muscle) => (
                            <div key={muscle.muscleId} className="muscle-share">
                                <span>{muscle.muscleName}</span>
                                <ProgressBar
                                    value={muscle.percentage}
                                    max={100}
                                    label={`${muscle.muscleName} : ${formatPercentage(muscle.percentage)}`}
                                />
                                <span className="tracker-note">{formatPercentage(muscle.percentage)}</span>
                            </div>
                        ))}
                    </div>
                </div>
            )}

            {stats.personalBests.length > 0 && (
                <div className="list-group" style={{ marginBottom: 0 }}>
                    <h2 className="list-group-heading">Records battus</h2>

                    {stats.personalBests.map((record) => (
                        <ListItem
                            key={record.id}
                            icon={<Trophy size={15} strokeWidth={2} aria-hidden />}
                            title={record.movementName === null ? personalBestLabel(record) : `${record.movementName} · ${personalBestLabel(record)}`}
                            note={formatPersonalBestValue(record)}
                        />
                    ))}
                </div>
            )}
        </Stack>
    )
}

/** « 13,3 % ». */
function formatPercentage(percentage: number): string {
    return `${`${percentage}`.replace('.', ',')} %`
}
