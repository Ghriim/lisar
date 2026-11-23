import { ArrowLeft, ArrowRight, Plus } from 'lucide-react'
import { useState } from 'react'
import type { TrainingPlanningEntry } from '../api/types'
import { Alert, IconButton, Loader, Modal, Row, Stack, SystemPanel, WeekCalendar, type CalendarDay } from '../components'
import { addDays, dayKey, formatDayLabel, formatWeek, minutesOf, mondayOf } from '../features/planning/planningFormat'
import { useTrainingPlanning } from '../features/planning/queries'
import { TrainingEntryDetail } from '../features/planning/TrainingEntryDetail'
import { TrainingEntryForm } from '../features/planning/TrainingEntryForm'
import { TrainingSlotCard } from '../features/planning/TrainingSlotCard'
import { useStartRoutineAction } from '../features/routines/useStartRoutineAction'

type Panel = { kind: 'create' } | { kind: 'view'; entry: TrainingPlanningEntry } | { kind: 'edit'; entry: TrainingPlanningEntry } | null

/**
 * The week, Monday to Sunday, with what is planned on each day: the current one on arrival, the
 * arrows for the others. Only training entries for now; tasks and habits will join the same grid.
 */
export function PlanningPage() {
    const today = new Date()
    const todayKey = dayKey(today)
    const [monday, setMonday] = useState(() => mondayOf(today))
    const sunday = addDays(monday, 6)
    const planning = useTrainingPlanning(dayKey(monday), dayKey(sunday))
    const start = useStartRoutineAction()
    const [panel, setPanel] = useState<Panel>(null)
    const close = () => setPanel(null)

    const entries = new Map((planning.data?.entries ?? []).map((entry) => [entry.id, entry]))
    const days: CalendarDay[] = Array.from({ length: 7 }, (_, index) => {
        const date = addDays(monday, index)
        const key = dayKey(date)

        return {
            key,
            label: formatDayLabel(date),
            isToday: key === todayKey,
            items: (planning.data?.slots ?? []).flatMap((slot) => {
                const entry = entries.get(slot.entryId)
                if (slot.day !== key || entry === undefined) {
                    return []
                }
                const startable = slot.day === todayKey && slot.state === 'to_come' && start.isOffered

                return [
                    {
                        key: `${slot.entryId}-${slot.day}`,
                        startInMinutes: minutesOf(entry.schedule.startTime),
                        durationInMinutes: entry.schedule.durationInMinutes,
                        content: (
                            <TrainingSlotCard
                                slot={slot}
                                entry={entry}
                                onStart={startable ? () => start.start(entry.routineId) : undefined}
                                startPending={start.isPending}
                                onOpen={() => setPanel({ kind: 'view', entry })}
                            />
                        ),
                    },
                ]
            }),
        }
    })

    return (
        <Stack>
            <SystemPanel
                title="Planning"
                actions={<IconButton icon={Plus} label="Planifier" onClick={() => setPanel({ kind: 'create' })} />}
            >
                <Row style={{ justifyContent: 'center', marginBottom: 'var(--step)' }}>
                    <IconButton icon={ArrowLeft} label="Semaine précédente" onClick={() => setMonday(addDays(monday, -7))} />
                    <span className="tracker-note">{formatWeek(monday)}</span>
                    <IconButton icon={ArrowRight} label="Semaine suivante" onClick={() => setMonday(addDays(monday, 7))} />
                </Row>

                {start.error !== null && <Alert>{start.error}</Alert>}

                {planning.isPending ? (
                    <Loader />
                ) : planning.isError ? (
                    <Alert>Le System ne répond pas. Réessaie dans un instant.</Alert>
                ) : (
                    <WeekCalendar days={days} />
                )}
            </SystemPanel>

            {panel?.kind === 'create' && (
                <Modal title="Planifier une routine" onClose={close}>
                    <TrainingEntryForm editing={null} today={todayKey} onDone={close} />
                </Modal>
            )}

            {panel?.kind === 'view' && (
                <Modal title={panel.entry.routineName} onClose={close}>
                    <TrainingEntryDetail entry={panel.entry} onEdit={() => setPanel({ kind: 'edit', entry: panel.entry })} onDeleted={close} />
                </Modal>
            )}

            {panel?.kind === 'edit' && (
                <Modal title={`Modifier · ${panel.entry.routineName}`} onClose={close}>
                    <TrainingEntryForm editing={panel.entry} today={todayKey} onDone={close} />
                </Modal>
            )}
        </Stack>
    )
}
