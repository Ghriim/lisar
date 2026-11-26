import { ArrowLeft, ArrowRight, CalendarCheck, Dumbbell, ListChecks, Plus } from 'lucide-react'
import { Fragment, useState } from 'react'
import type { HabitPlanningSlot } from '../api/types'
import {
    Alert,
    type CalendarDay,
    DataList,
    Form,
    IconButton,
    Loader,
    Modal,
    Row,
    SelectField,
    Stack,
    SystemPanel,
    WeekCalendar,
} from '../components'
import { categoryIconOf, iconForCode } from '../features/categories/categoryIcons'
import { groupByCategoryIfAny } from '../features/categories/groupByCategory'
import { useCompleteHabit, useHabits, useUncompleteHabit } from '../features/habits/queries'
import { PlanningEntryDetail } from '../features/planning/PlanningEntryDetail'
import { PlanningEntryForm } from '../features/planning/PlanningEntryForm'
import {
    addDays,
    dayKey,
    formatDayLabel,
    formatHabitRhythm,
    formatTimeSpan,
    formatWeek,
    minutesOf,
    mondayOf,
    parseDay,
} from '../features/planning/planningFormat'
import { KINDS, type KindedEntry, type PlanningKind, subjectOf } from '../features/planning/planningKinds'
import { PlanningHabitsCard } from '../features/planning/PlanningHabitsCard'
import { type Completion, PlanningSlotCard } from '../features/planning/PlanningSlotCard'
import { useHabitPlanning, useTaskPlanning, useTrainingPlanning } from '../features/planning/queries'
import { useStartRoutineAction } from '../features/routines/useStartRoutineAction'
import { useCompleteTask, useReopenTask } from '../features/tasks/queries'
import { failureOf } from '../features/workouts/workoutFormat'

type Panel =
    | { kind: 'create' }
    | { kind: 'view'; kinded: KindedEntry }
    | { kind: 'edit'; kinded: KindedEntry }
    /** The habits of one day held within it, listed: the card on the grid only counts them. */
    | { kind: 'habits'; day: string }
    | null

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
    // Ticking off from the week: a task made for its date, and today's date of a habit kept by hand.
    const kept = useHabits()
    const completeTask = useCompleteTask()
    const reopenTask = useReopenTask()
    const completeHabit = useCompleteHabit()
    const uncompleteHabit = useUncompleteHabit()
    const completing = completeTask.isPending || reopenTask.isPending || completeHabit.isPending || uncompleteHabit.isPending
    const completionError = failureOf(completeTask.error ?? reopenTask.error ?? completeHabit.error ?? uncompleteHabit.error)
    const categoryOfHabit = new Map((kept.data ?? []).map((habit) => [habit.habitId, habit.category]))
    const manualHabits = new Set((kept.data ?? []).filter((habit) => habit.sourceKind === 'manual').map((habit) => habit.habitId))
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
                        icon={categoryIconOf(entry.category) ?? ListChecks}
                        title={entry.title}
                        time={formatTimeSpan(entry.schedule)}
                        state={state}
                        onOpen={() => setPanel({ kind: 'view', kinded: { kind: 'task', entry } })}
                        completion={
                            slot.taskId === null
                                ? undefined
                                : {
                                      isDone: slot.state === 'done',
                                      doLabel: 'Terminer',
                                      undoLabel: 'Rouvrir',
                                      pending: completing,
                                      onToggle: () => (slot.state === 'done' ? reopenTask : completeTask).mutate(slot.taskId ?? 0),
                                  }
                        }
                    />
                ),
            },
        ]
    })

    // Every habit is planned, most of them every day and at no hour: those of a day share one
    // card, which counts them, and a window lists them. A habit at an hour keeps its own place.
    // A habit is kept for the day in progress only, and by hand only when no tracker keeps it.
    const habitCompletion = (slot: HabitPlanningSlot): Completion | undefined => {
        if (slot.day !== todayKey || !manualHabits.has(slot.habitId)) {
            return undefined
        }
        const isDone = slot.state === 'kept' || slot.state === 'kept_unplanned'

        return {
            isDone,
            doLabel: 'Valider',
            undoLabel: 'Décocher',
            pending: completing,
            onToggle: () => (isDone ? uncompleteHabit : completeHabit).mutate(slot.habitId),
        }
    }
    const habitSlots = (habits.data?.slots ?? []).flatMap((slot) => {
        const entry = slot.entryId === null ? undefined : habitEntries.get(slot.entryId)
        const habit = entry ?? habitsById.get(slot.habitId)

        return habit === undefined ? [] : [{ slot, entry, habit }]
    })
    const isTimed = ({ entry }: (typeof habitSlots)[number]) => entry !== undefined && entry.schedule.startTime !== null
    // Listed in the day's window, a habit also says the days it counts: the card on the grid has no room for it.
    const habitCard = ({ slot, entry, habit }: (typeof habitSlots)[number], withRhythm = false) => (
        <PlanningSlotCard
            // Its category's icon; a habit in none keeps the planning's mark for a habit.
            icon={iconForCode(habit.habitIcon) ?? CalendarCheck}
            title={habit.habitName}
            time={entry === undefined ? null : formatTimeSpan(entry.schedule)}
            rhythm={withRhythm ? formatHabitRhythm(slot.habitId, habits.data?.entries ?? []) : null}
            state={slot.state}
            onOpen={entry === undefined ? undefined : () => setPanel({ kind: 'view', kinded: { kind: 'habit', entry } })}
            completion={habitCompletion(slot)}
        />
    )
    const untimedHabitsOf = (day: string) => habitSlots.filter((one) => one.slot.day === day && !isTimed(one))

    const timedHabitItems = habitSlots.filter(isTimed).map(
        (one): DayItem & { day: string } => ({
            day: one.slot.day,
            key: `habit-${one.slot.entryId ?? 'unplanned'}-${one.slot.habitId}-${one.slot.day}`,
            startInMinutes: minutesOf(one.entry?.schedule.startTime ?? null),
            durationInMinutes: one.entry?.schedule.durationInMinutes ?? null,
            content: habitCard(one),
        }),
    )
    const habitGroupItems = [...new Set(habitSlots.filter((one) => !isTimed(one)).map((one) => one.slot.day))].map(
        (day): DayItem & { day: string } => {
            const group = untimedHabitsOf(day)
            const kept = group.filter(({ slot }) => slot.state === 'kept' || slot.state === 'kept_unplanned').length

            return {
                day,
                key: `habits-${day}`,
                startInMinutes: null,
                durationInMinutes: null,
                content: (
                    <PlanningHabitsCard
                        kept={kept}
                        total={group.length}
                        hasMissed={group.some(({ slot }) => slot.state === 'missed')}
                        onOpen={() => setPanel({ kind: 'habits', day })}
                    />
                ),
            }
        },
    )
    const habitItems = [...timedHabitItems, ...habitGroupItems]
    const entriesOfHabit = (habitId: number) => (habits.data?.entries ?? []).filter((entry) => entry.habitId === habitId).length

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
                {completionError !== null && <Alert>{completionError}</Alert>}

                {isPending ? (
                    <Loader />
                ) : isError ? (
                    <Alert>Le System ne répond pas. Réessaie dans un instant.</Alert>
                ) : (
                    <WeekCalendar days={days} />
                )}
            </SystemPanel>

            {/* One window for every panel: going from one to the next changes what it holds, and
                the page behind stays blurred instead of flashing as a window closes and another opens. */}
            {panel !== null && (
                <Modal title={titleOf(panel)} onClose={close}>
                    <Fragment key={keyOf(panel)}>
                        {panel.kind === 'create' && <CreateEntry today={todayKey} onDone={close} />}

                        {panel.kind === 'habits' && (
                            // Under their category, as on the dashboard: the category comes with the kept habit.
                            <DataList<(typeof habitSlots)[number]>
                                groups={groupByCategoryIfAny(untimedHabitsOf(panel.day), (one) => categoryOfHabit.get(one.slot.habitId) ?? null)}
                                keyOf={(one) => `${one.slot.entryId ?? 'unplanned'}-${one.slot.habitId}`}
                                emptyText="Aucune habitude ce jour-là."
                                renderItem={(one) => habitCard(one, true)}
                            />
                        )}

                        {panel.kind === 'view' && (
                            <PlanningEntryDetail
                                kinded={panel.kinded}
                                isLastOfHabit={panel.kinded.kind === 'habit' && entriesOfHabit(panel.kinded.entry.habitId) <= 1}
                                onEdit={() => setPanel({ kind: 'edit', kinded: panel.kinded })}
                                onDeleted={close}
                            />
                        )}

                        {panel.kind === 'edit' && <PlanningEntryForm kind={panel.kinded.kind} editing={panel.kinded} today={todayKey} onDone={close} />}
                    </Fragment>
                </Modal>
            )}
        </Stack>
    )
}

/** The plus asks the kind first — nothing chosen for the person — then what it ties, and when. */
function CreateEntry({ today, onDone }: { today: string; onDone: () => void }) {
    const [kind, setKind] = useState<PlanningKind | ''>('')

    const picker = (
        <SelectField
            label="Quoi"
            isMandatory
            value={kind}
            onChange={(value) => setKind(value as PlanningKind)}
            placeholder="Choisir un type"
            options={KINDS}
        />
    )

    // Until a kind is chosen there is nothing to submit: the picker sits alone in a form of its
    // own, which gives it the same legend as the form it then opens.
    return kind === '' ? (
        <Form onSubmit={() => undefined}>{picker}</Form>
    ) : (
        <PlanningEntryForm key={kind} kind={kind} editing={null} today={today} kindPicker={picker} onDone={onDone} />
    )
}

function titleOf(panel: NonNullable<Panel>): string {
    switch (panel.kind) {
        case 'create':
            return 'Planifier'
        case 'habits':
            return `Habitudes · ${formatDayLabel(parseDay(panel.day))}`
        case 'view':
            return subjectOf(panel.kinded)
        case 'edit':
            return `Modifier · ${subjectOf(panel.kinded)}`
    }
}

/** What resets the window's content between two panels: a form starts over, a list does not carry its state into a detail. */
function keyOf(panel: NonNullable<Panel>): string {
    switch (panel.kind) {
        case 'create':
            return 'create'
        case 'habits':
            return `habits-${panel.day}`
        case 'view':
        case 'edit':
            return `${panel.kind}-${panel.kinded.kind}-${panel.kinded.entry.id}`
    }
}
