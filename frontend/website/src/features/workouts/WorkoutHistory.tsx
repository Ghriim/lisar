import { ArrowLeft, ArrowRight, Eye } from 'lucide-react'
import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import type { WorkoutSummary } from '../../api/types'
import { Chip, DataList, IconButton, ListItem, Row, SystemPanel } from '../../components'
import { useWorkoutHistory } from './queries'
import { formatElapsed, formatShortDay, workoutName } from './workoutFormat'

/** Past workouts, the latest first, a page at a time. The one in progress joins when it ends. */
export function WorkoutHistory() {
    const [page, setPage] = useState(1)
    const history = useWorkoutHistory(page)
    const navigate = useNavigate()

    const pageCount = history.data === undefined ? 1 : Math.max(1, Math.ceil(history.data.total / history.data.perPage))

    return (
        <SystemPanel title="Historique">
            <DataList<WorkoutSummary>
                groups={[{ items: history.data?.items ?? [] }]}
                keyOf={(workout) => workout.id}
                loading={history.isPending}
                error={history.isError ? 'Le System ne répond pas. Réessaie dans un instant.' : null}
                emptyText="Aucune séance terminée."
                renderItem={(workout) => (
                    <HistoryRow workout={workout} onView={() => void navigate(`/workouts/${workout.id}`)} />
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

function HistoryRow({ workout, onView }: { workout: WorkoutSummary; onView: () => void }) {
    const name = workoutName(workout)
    const when =
        workout.finishedAt === null
            ? formatShortDay(workout.startedAt)
            : `${formatShortDay(workout.startedAt)} · ${formatElapsed(workout.startedAt, workout.finishedAt)}`

    return (
        <ListItem
            title={name}
            note={when}
            meta={
                <>
                    {workout.movementNames.map((movementName) => (
                        <Chip key={movementName}>{movementName}</Chip>
                    ))}
                    <Chip>
                        {workout.setCount} série{workout.setCount > 1 ? 's' : ''}
                    </Chip>
                </>
            }
            actions={<IconButton icon={Eye} label="Consulter" subject={name} onClick={onView} />}
        />
    )
}
