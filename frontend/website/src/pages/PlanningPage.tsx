import { ArrowLeft, ArrowRight, Dumbbell, ListChecks, Plus } from 'lucide-react'
import { useState } from 'react'
import { Alert, Field, IconButton, Loader, Modal, Row, Select, Stack, SystemPanel, WeekCalendar, type CalendarDay } from '../components'
import { iconFor } from '../features/habits/habitIcons'
import { PlanningEntryDetail } from '../features/planning/PlanningEntryDetail'
import { PlanningEntryForm } from '../features/planning/PlanningEntryForm'
import { addDays, dayKey, formatDayLabel, formatTimeSpan, formatWeek, minutesOf, mondayOf } from '../features/planning/planningFormat'
import { KINDS, type KindedEntry, type PlanningKind, subjectOf } from '../features/planning/planningKinds'
import { PlanningSlotCard } from '../features/planning/PlanningSlotCard'
import { useHabitPlanning, useTaskPlanning, useTrainingPlanning } from '../features/planning/queries'
import { useStartRoutineAction } from '../features/routines/useStartRoutineAction'

type Panel = { kind: 'create' } | { kind: 'view'; kinded: KindedEntry } | { kind: 'edit'; kinded: KindedEntry } | null

type DayItem = CalendarDay['items'][number]

/**
 * The week, Monday to Sunday, with what is planned on each day: the current one on arrival, the
 * arrows for the others. Training, task and habit entries share the grid.
 */
export function PlanningPage() {
    const today = new Date()
    const todayKey = dayKey(today)
    const [monday, setMonday] = useState(() => mondayOf(today))
    const sunday = addDays(monday, 6)
    const training = useTrainingPlanning(dayKey(monday), dayKey(sunday))
    const tasks = useTaskPlanning(dayKey(monday), dayKey(sunday))
    const habits = useHabitPlanning(dayKey(monday), dayKey(sunday))
    const start = useStartRoutineAction()
    const [panel, setPanel] = useState<Panel>(null)
    const close = () => setPanel(null)

    const trainingEntries = new Map((training.data?.entries ?? []).map((entry) => [entry.id, entry]))
    const taskEntries = new Map((tasks.data?.entries ?? []).map((entry) => [entry.id, entry]))
    const habitEntries = new Map((habits.data?.entries ?? []).map((entry) => [entry.id, entry]))
    // A day kept unplanned names its habit, not an entry: any entry of that habit says its name.
    const habitsById = new Map((habits.data?.entries ?? []).map((entry) => [entry.habitId, entry]))

    const trainingItems = (training.data?.slots ?? []).flatMap((slot): (DayItem & { day: string })[] => {
        const entry = trainingEntries.get(slot.entryId)
        if (entry === undefined) {
            return []
        }
        const startable = slot.day === todayKey && slot.state === 'to_come' && start.isOffered

        return [
            {
                day: slot.day,
                key: `training-${slot.entryId}-${slot.day}`,
                startInMinutes: minutesOf(entry.schedule.startTime),
                durationInMinutes: entry.schedule.durationInMinutes,
                content: (
                    <PlanningSlotCard
                        icon={Dumbbell}
                        title={entry.routineName}
                        time={formatTimeSpan(entry.schedule)}
                        state={slot.state}
                        onStart={startable ? () => start.start(entry.routineId) : undefined}
                        startPending={start.isPending}
                        onOpen={() => setPanel({ kind: 'view', kinded: { kind: 'training', entry } })}
                    />
                ),
            },
        ]
    })

    const taskItems = (tasks.data?.slots ?? []).flatMap((slot): (DayItem & { day: string })[] => {
        const entry = taskEntries.get(slot.entryId)
        if (entry === undefined) {
            return []
        }
        // An open task of a day gone by is overdue, as everywhere a due date has passed.
        const state = slot.state === 'open' && slot.day < todayKey ? 'overdue' : slot.state

        return [
            {
                day: slot.day,
                key: `task-${slot.entryId}-${slot.day}-${slot.taskId ?? 'to-come'}`,
                startInMinutes: minutesOf(entry.schedule.startTime),
                durationInMinutes: entry.schedule.durationInMinutes,
                content: (
                    <PlanningSlotCard
                        icon={ListChecks}
                        title={entry.title}
                        time={formatTimeSpan(entry.schedule)}
                        state={state}
                        onOpen={() => setPanel({ kind: 'view', kinded: { kind: 'task', entry } })}
                    />
                ),
            },
        ]
    })

    const habitItems = (habits.data?.slots ?? []).flatMap((slot): (DayItem & { day: string })[] => {
        const entry = slot.entryId === null ? undefined : habitEntries.get(slot.entryId)
        const habit = entry ?? habitsById.get(slot.habitId)
        if (habit === undefined) {
            return []
        }

        return [
            {
                day: slot.day,
                key: `habit-${slot.entryId ?? 'unplanned'}-${slot.habitId}-${slot.day}`,
                startInMinutes: entry === undefined ? null : minutesOf(entry.schedule.startTime),
                durationInMinutes: entry === undefined ? null : entry.schedule.durationInMinutes,
                content: (
                    <PlanningSlotCard
                        icon={iconFor(habit.habitIcon)}
                        title={habit.habitName}
                        time={entry === undefined ? null : formatTimeSpan(entry.schedule)}
                        state={slot.state}
                        onOpen={entry === undefined ? undefined : () => setPanel({ kind: 'view', kinded: { kind: 'habit', entry } })}
                    />
                ),
            },
        ]
    })

    const days: CalendarDay[] = Array.from({ length: 7 }, (_, index) => {
        const date = addDays(monday, index)
        const key = dayKey(date)

        return {
            key,
            label: formatDayLabel(date),
            isToday: key === todayKey,
            items: [...trainingItems, ...taskItems, ...habitItems].filter((item) => item.day === key).map(({ day: _day, ...item }) => item),
        }
    })

    const isPending = training.isPending || tasks.isPending || habits.isPending
    const isError = training.isError || tasks.isError || habits.isError

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

                {isPending ? (
                    <Loader />
                ) : isError ? (
                    <Alert>Le System ne répond pas. Réessaie dans un instant.</Alert>
                ) : (
                    <WeekCalendar days={days} />
                )}
            </SystemPanel>

            {panel?.kind === 'create' && (
                <Modal title="Planifier" onClose={close}>
                    <CreateEntry today={todayKey} onDone={close} />
                </Modal>
            )}

            {panel?.kind === 'view' && (
                <Modal title={subjectOf(panel.kinded)} onClose={close}>
                    <PlanningEntryDetail kinded={panel.kinded} onEdit={() => setPanel({ kind: 'edit', kinded: panel.kinded })} onDeleted={close} />
                </Modal>
            )}

            {panel?.kind === 'edit' && (
                <Modal title={`Modifier · ${subjectOf(panel.kinded)}`} onClose={close}>
                    <PlanningEntryForm kind={panel.kinded.kind} editing={panel.kinded} today={todayKey} onDone={close} />
                </Modal>
            )}
        </Stack>
    )
}

/** The plus asks the kind first — nothing chosen for the person — then what it ties, and when. */
function CreateEntry({ today, onDone }: { today: string; onDone: () => void }) {
    const [kind, setKind] = useState<PlanningKind | ''>('')

    return (
        <Stack>
            <Field label="Quoi">
                <Select value={kind} onChange={(value) => setKind(value as PlanningKind)} placeholder="Choisir un type" options={KINDS} />
            </Field>
            {kind !== '' && <PlanningEntryForm key={kind} kind={kind} editing={null} today={today} onDone={onDone} />}
        </Stack>
    )
}
