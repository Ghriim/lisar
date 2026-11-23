import { Copy, Pencil, Play, Trash2 } from 'lucide-react'
import { useState } from 'react'
import type { Routine } from '../../api/types'
import { Alert, ConfirmDialog, IconButton, Modal, Row, Stack, SystemPanel } from '../../components'
import { failureOf } from '../workouts/workoutFormat'
import { useDeleteRoutine, useRenameRoutine } from './queries'
import { RoutineExercisesCard } from './RoutineExercisesCard'
import { RoutineNameForm } from './RoutineNameForm'
import { useCopyRoutineAction } from './useCopyRoutineAction'
import { useStartRoutineAction } from './useStartRoutineAction'

interface RoutineSheetProps {
    routine: Routine
    /** Called once the routine is gone, so whoever shows it can move on. */
    onDeleted: () => void
}

/**
 * One routine on its own page, laid out like a workout: its panel with what can be done to it,
 * then its exercises.
 */
export function RoutineSheet({ routine, onDeleted }: RoutineSheetProps) {
    const [panel, setPanel] = useState<'rename' | 'delete' | null>(null)
    const close = () => setPanel(null)

    const rename = useRenameRoutine()
    const remove = useDeleteRoutine()
    const start = useStartRoutineAction()
    const copy = useCopyRoutineAction()

    const busy = remove.isPending || start.isPending || copy.isPending
    const error = failureOf(remove.error) ?? start.error ?? copy.error

    return (
        <Stack>
            <SystemPanel
                title={routine.name}
                actions={
                    <Row style={{ gap: 6 }}>
                        <IconButton icon={Pencil} label="Renommer" subject={routine.name} onClick={() => setPanel('rename')} />
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
                            onClick={() => setPanel('delete')}
                        />
                    </Row>
                }
            >
                <p className="tracker-note" style={{ margin: 0 }}>
                    Chaque série est prescrite en part du 1RM estimé : la charge se calcule au démarrage, sur tes records
                    du jour.
                </p>
                {error !== null && <Alert>{error}</Alert>}
            </SystemPanel>

            <RoutineExercisesCard routine={routine} />

            {panel === 'rename' && (
                <Modal title="Renommer la routine" onClose={close}>
                    <RoutineNameForm
                        current={routine.name}
                        save={(name) => rename.mutateAsync({ id: routine.id, name })}
                        pending={rename.isPending}
                        failure={rename.error}
                        onDone={close}
                    />
                </Modal>
            )}

            {panel === 'delete' && (
                <ConfirmDialog
                    title="Supprimer la routine"
                    question={`« ${routine.name} » sera supprimée. Les séances démarrées depuis elle restent. C’est définitif.`}
                    confirmLabel="Supprimer"
                    onCancel={close}
                    onConfirm={() => {
                        close()
                        remove.mutate(routine.id, { onSuccess: onDeleted })
                    }}
                />
            )}
        </Stack>
    )
}
