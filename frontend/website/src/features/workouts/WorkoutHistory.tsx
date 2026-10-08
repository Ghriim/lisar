import { ArrowLeft, ArrowRight, Eye, Repeat, Trophy } from 'lucide-react'
import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import type { WorkoutSummary } from '../../api/types'
import { Alert, Chip, DataList, IconButton, ListItem, Row, SystemPanel } from '../../components'
import { useWorkoutHistory } from './queries'
import { useCopyWorkoutAction } from './useCopyWorkoutAction'
import { formatDistance, formatDuration, formatElapsed, formatLoad, formatShortDay, workoutName } from './workoutFormat'

/** A row names this many movements; the rest are counted. */
const SHOWN_MOVEMENTS = 5

/** Past workouts, the latest first, a page at a time. The one in progress joins when it ends. */
export function WorkoutHistory() {
    const [page, setPage] = useState(1)
    const history = useWorkoutHistory(page)
    const navigate = useNavigate()
    const copy = useCopyWorkoutAction()

    const pageCount = history.data === undefined ? 1 : Math.max(1, Math.ceil(history.data.total / history.data.perPage))

    return (
        <SystemPanel title="Historique">
            {copy.error !== null && <Alert>{copy.error}</Alert>}

            <DataList<WorkoutSummary>
                groups={[{ items: history.data?.items ?? [] }]}
                keyOf={(workout) => workout.id}
                loading={history.isPending}
                error={history.isError ? 'Le System ne répond pas. Réessaie dans un instant.' : null}
                emptyText="Aucune séance terminée."
                renderItem={(workout) => (
                    <HistoryRow
                        workout={workout}
                        onView={() => void navigate(`/workouts/${workout.id}`)}
                        onCopy={copy.isOffered ? () => copy.copy(workout.id) : undefined}
                        copyPending={copy.isPending}
                    />
                )}
            />

            {pageCount > 1 && (
                <Row style={{ justifyContent: 'center', marginTop: 'var(--step)' }}>
                    <IconButton
                        icon={ArrowLeft}
                        label="Page précédente"
                        disabled={page === 1}
                        onClick={() => setPage(page - 1)}
                    />
                    <span className="tracker-note">
                        Page {page} / {pageCount}
                    </span>
                    <IconButton
                        icon={ArrowRight}
                        label="Page suivante"
                        disabled={page >= pageCount}
                        onClick={() => setPage(page + 1)}
                    />
                </Row>
            )}
        </SystemPanel>
    )
}

interface HistoryRowProps {
    workout: WorkoutSummary
    onView: () => void
    /** Absent while a workout is in progress: doing one again is not offered then. */
    onCopy?: () => void
    copyPending: boolean
}

function HistoryRow({ workout, onView, onCopy, copyPending }: HistoryRowProps) {
    const name = workoutName(workout)
    const hidden = workout.movementNames.length - SHOWN_MOVEMENTS

    return (
        <ListItem
            title={name}
            note={
                <>
                    {historyNote(workout)}
                    {workout.personalBestCount > 0 && (
                        <>
                            {' · '}
                            <span className="list-item-note-record">
                                <Trophy size={12} strokeWidth={2} role="img" aria-label="Records battus" />
                                {workout.personalBestCount}
                            </span>
                        </>
                    )}
                </>
            }
            meta={
                <>
                    {workout.movementNames.slice(0, SHOWN_MOVEMENTS).map((movementName) => (
                        <Chip key={movementName}>{movementName}</Chip>
                    ))}
                    {hidden > 0 && (
                        <Chip>
                            {hidden} autre{hidden > 1 ? 's' : ''} exercice{hidden > 1 ? 's' : ''}
                        </Chip>
                    )}
                </>
            }
            actions={
                <Row style={{ gap: 6 }}>
                    <IconButton icon={Eye} label="Consulter" subject={name} onClick={onView} />
                    {onCopy !== undefined && (
                        <IconButton icon={Repeat} label="Refaire" subject={name} disabled={copyPending} onClick={onCopy} />
                    )}
                </Row>
            }
        />
    )
}

/**
 * « 2 oct. · 1 h 08 · 8 séries · 1780 kg · 80 m »: the day, how long, then the bilan's totals —
 * each only when a set measured it.
 */
function historyNote(workout: WorkoutSummary): string {
    const parts = [formatShortDay(workout.startedAt)]

    if (workout.finishedAt !== null) {
        parts.push(formatElapsed(workout.startedAt, workout.finishedAt))
    }
    parts.push(`${workout.setCount} série${workout.setCount > 1 ? 's' : ''}`)
    if (workout.volumeInKilograms !== null) {
        parts.push(formatLoad(workout.volumeInKilograms))
    }
    if (workout.durationInSeconds !== null) {
        parts.push(`${formatDuration(workout.durationInSeconds)} d’effort`)
    }
    if (workout.distanceInMetres !== null) {
        parts.push(formatDistance(workout.distanceInMetres))
    }

    return parts.join(' · ')
}
