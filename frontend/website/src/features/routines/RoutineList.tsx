import { Copy, Eye, Play, Trash2 } from 'lucide-react'
import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import type { RoutineSummary } from '../../api/types'
import { Alert, ConfirmDialog, DataList, IconButton, ListItem, Row } from '../../components'
import { failureOf } from '../workouts/workoutFormat'
import { useDeleteRoutine, useRoutines } from './queries'
import { formatMovementCount } from './routineFormat'
import { useCopyRoutineAction } from './useCopyRoutineAction'
import { useStartRoutineAction } from './useStartRoutineAction'

/**
 * The person's routines, by name, not paginated: a person keeps a handful. Each one is started,
 * opened, duplicated or deleted from its row.
 */
export function RoutineList() {
    const routines = useRoutines()
    const navigate = useNavigate()
    const start = useStartRoutineAction()
    const copy = useCopyRoutineAction()
    const remove = useDeleteRoutine()
    const [deleting, setDeleting] = useState<RoutineSummary | null>(null)

    const busy = start.isPending || copy.isPending || remove.isPending
    const error = start.error ?? copy.error ?? failureOf(remove.error)

    return (
        <>
            {error !== null && <Alert>{error}</Alert>}

            <DataList<RoutineSummary>
                groups={[{ items: routines.data ?? [] }]}
                keyOf={(routine) => routine.id}
                loading={routines.isPending}
                error={routines.isError ? 'Le System ne répond pas. Réessaie dans un instant.' : null}
                emptyText="Aucune routine pour l’instant."
                renderItem={(routine) => (
                    <ListItem
                        title={routine.name}
                        note={formatMovementCount(routine.movementCount)}
                        actions={
                            <Row style={{ gap: 6 }}>
                                {start.isOffered && (
                                    <IconButton
                                        icon={Play}
                                        label="Démarrer"
                                        subject={routine.name}
                                        disabled={busy}
                                        onClick={() => start.start(routine.id)}
                                    />
                                )}
                                <IconButton
                                    icon={Eye}
                                    label="Consulter"
                                    subject={routine.name}
                                    onClick={() => void navigate(`/routines/${routine.id}`)}
                                />
                                <IconButton
                                    icon={Copy}
                                    label="Dupliquer"
                                    subject={routine.name}
                                    disabled={busy}
                                    onClick={() => copy.copy(routine.id)}
                                />
                                <IconButton
                                    icon={Trash2}
                                    variant="danger"
                                    label="Supprimer"
                                    subject={routine.name}
                                    disabled={busy}
                                    onClick={() => setDeleting(routine)}
                                />
                            </Row>
                        }
                    />
                )}
            />

            {deleting !== null && (
                <ConfirmDialog
                    title="Supprimer la routine"
                    question={`« ${deleting.name} » sera supprimée. Les séances démarrées depuis elle restent. C’est définitif.`}
                    confirmLabel="Supprimer"
                    onCancel={() => setDeleting(null)}
                    onConfirm={() => {
                        remove.mutate(deleting.id)
                        setDeleting(null)
                    }}
                />
            )}
        </>
    )
}
