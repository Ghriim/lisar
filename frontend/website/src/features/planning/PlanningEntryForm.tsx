import { type FormEvent, useState } from 'react'
import type { PlanningSchedule } from '../../api/types'
import {
    Alert,
    Button,
    Field,
    FormActions,
    FormGrid,
    Select,
    TextArea,
    TextInput,
    ToggleChip,
    useViolations,
} from '../../components'
import { useHabits } from '../habits/queries'
import { useRoutines } from '../routines/queries'
import { useCategories } from '../tasks/queries'
import { failureOf } from '../workouts/workoutFormat'
import { MONTH_WEEKS, RULES, WEEKDAYS } from './planningFormat'
import type { KindedEntry, PlanningKind } from './planningKinds'
import {
    useCreateHabitPlanningEntry,
    useCreateTaskPlanningEntry,
    useCreateTrainingPlanningEntry,
    useUpdateHabitPlanningEntry,
    useUpdateTaskPlanningEntry,
    useUpdateTrainingPlanningEntry,
} from './queries'

interface PlanningEntryFormProps {
    kind: PlanningKind
    /** The entry being changed, of that kind, or null for a new one. */
    editing: KindedEntry | null
    /** "YYYY-MM-DD": what a new entry starts on, shown filled in. */
    today: string
    onDone: () => void
}

const MONTH_DAYS = Array.from({ length: 31 }, (_, index) => index + 1)

/**
 * What is planned — a routine, a task model, or a habit one keeps — and when: the rule, then only
 * the fields that rule reads. Nothing is chosen for the person — no routine, category or habit, no
 * rule, no day — but the start date, which opens on today.
 */
export function PlanningEntryForm({ kind, editing, today, onDone }: PlanningEntryFormProps) {
    const routines = useRoutines()
    const habits = useHabits()
    const categories = useCategories()
    const createTraining = useCreateTrainingPlanningEntry()
    const updateTraining = useUpdateTrainingPlanningEntry()
    const createTask = useCreateTaskPlanningEntry()
    const updateTask = useUpdateTaskPlanningEntry()
    const createHabit = useCreateHabitPlanningEntry()
    const updateHabit = useUpdateHabitPlanningEntry()
    const isNew = editing === null
    const mutation = {
        training: isNew ? createTraining : updateTraining,
        task: isNew ? createTask : updateTask,
        habit: isNew ? createHabit : updateHabit,
    }[kind]
    const violations = useViolations(mutation.error)
    const field = (name: string) => violations.for(`schedule.${name}`)

    const initial = editing?.entry.schedule
    const [subjectId, setSubjectId] = useState(
        editing?.kind === 'training' ? `${editing.entry.routineId}` : editing?.kind === 'habit' ? `${editing.entry.habitId}` : '',
    )
    // A task entry's model.
    const [title, setTitle] = useState(editing?.kind === 'task' ? editing.entry.title : '')
    const [description, setDescription] = useState(editing?.kind === 'task' ? (editing.entry.description ?? '') : '')
    const [categoryId, setCategoryId] = useState(editing?.kind === 'task' && editing.entry.category !== null ? `${editing.entry.category.id}` : '')
    const [rule, setRule] = useState(initial?.rule ?? '')
    const [startDate, setStartDate] = useState(initial?.startDate ?? today)
    const [endDate, setEndDate] = useState(initial?.endDate ?? '')
    const [weekdays, setWeekdays] = useState<number[]>(initial?.weekdays ?? [])
    const [weekInterval, setWeekInterval] = useState(`${initial?.weekInterval ?? 1}`)
    const [monthWeeks, setMonthWeeks] = useState<number[]>(initial?.monthWeeks ?? [])
    const [monthDays, setMonthDays] = useState<number[]>(initial?.monthDays ?? [])
    const [startTime, setStartTime] = useState(initial?.startTime ?? '')
    const [duration, setDuration] = useState(initial?.durationInMinutes?.toString() ?? '')

    const submit = async (event: FormEvent) => {
        event.preventDefault()

        const schedule: PlanningSchedule = {
            rule,
            startDate,
            endDate: rule === 'once' || endDate === '' ? null : endDate,
            weekdays,
            weekInterval: Number(weekInterval) || 1,
            monthWeeks,
            monthDays,
            startTime: startTime === '' ? null : startTime,
            durationInMinutes: startTime === '' || duration === '' ? null : Number(duration),
        }
        try {
            if (kind === 'training') {
                const payload = { routineId: Number(subjectId), schedule }
                await (editing === null ? createTraining.mutateAsync(payload) : updateTraining.mutateAsync({ id: editing.entry.id, payload }))
            } else if (kind === 'task') {
                const payload = {
                    title,
                    description: description.trim() === '' ? null : description,
                    categoryId: categoryId === '' ? null : Number(categoryId),
                    schedule,
                }
                await (editing === null ? createTask.mutateAsync(payload) : updateTask.mutateAsync({ id: editing.entry.id, payload }))
            } else {
                const payload = { habitId: Number(subjectId), schedule }
                await (editing === null ? createHabit.mutateAsync(payload) : updateHabit.mutateAsync({ id: editing.entry.id, payload }))
            }
            onDone()
        } catch {
            // The violations are on the mutation, and the fields below read them.
        }
    }

    const readsWeekdays = rule === 'weekly' || rule === 'monthly_weekday'

    return (
        <form className="form-grid" onSubmit={(event) => void submit(event)}>
            {kind === 'training' && (
                <Field label="Routine" errors={violations.for('routineId')}>
                    <Select
                        value={subjectId}
                        onChange={setSubjectId}
                        placeholder={routines.isPending ? 'Chargement…' : 'Choisir une routine'}
                        options={(routines.data ?? []).map((routine) => ({ value: `${routine.id}`, label: routine.name }))}
                    />
                </Field>
            )}

            {kind === 'task' && (
                <>
                    <Field label="Intitulé" errors={violations.for('title')}>
                        <TextInput value={title} onChange={(event) => setTitle(event.target.value)} placeholder="Ce qu'il y a à faire" required />
                    </Field>
                    <Field label="Détail" errors={violations.for('description')}>
                        <TextArea value={description} onChange={(event) => setDescription(event.target.value)} placeholder="Facultatif" />
                    </Field>
                    <Field label="Catégorie" errors={violations.for('categoryId')}>
                        <Select
                            value={categoryId}
                            onChange={setCategoryId}
                            placeholder="Aucune"
                            options={(categories.data ?? []).map((category) => ({
                                value: String(category.id),
                                label: category.isPersonal ? `${category.label} ·` : category.label,
                            }))}
                        />
                    </Field>
                </>
            )}

            {kind === 'habit' && (
                <Field label="Habitude" errors={violations.for('habitId')}>
                    <Select
                        value={subjectId}
                        onChange={setSubjectId}
                        placeholder={habits.isPending ? 'Chargement…' : 'Choisir une habitude'}
                        options={(habits.data ?? []).map((habit) => ({ value: `${habit.habitId}`, label: habit.name }))}
                    />
                </Field>
            )}

            <Field label="Quand" errors={field('rule')}>
                <Select value={rule} onChange={setRule} placeholder="Choisir une règle" options={RULES} />
            </Field>

            <FormGrid columns={2}>
                <Field label={rule === 'once' ? 'Le' : 'À partir du'} errors={field('startDate')}>
                    <TextInput type="date" value={startDate} onChange={(event) => setStartDate(event.target.value)} />
                </Field>
                {rule !== 'once' && (
                    <Field label="Jusqu’au (facultatif)" errors={field('endDate')}>
                        <TextInput type="date" value={endDate} onChange={(event) => setEndDate(event.target.value)} />
                    </Field>
                )}
            </FormGrid>

            {readsWeekdays && (
                <Field label="Jours" errors={field('weekdays')}>
                    <div className="chip-choices">
                        {WEEKDAYS.map((day) => (
                            <ToggleChip key={day.value} pressed={weekdays.includes(day.value)} onToggle={() => setWeekdays(toggled(weekdays, day.value))}>
                                {day.long}
                            </ToggleChip>
                        ))}
                    </div>
                </Field>
            )}

            {rule === 'weekly' && (
                <Field label="Une semaine sur" errors={field('weekInterval')}>
                    <TextInput type="number" inputMode="numeric" min={1} max={52} value={weekInterval} onChange={(event) => setWeekInterval(event.target.value)} />
                </Field>
            )}

            {rule === 'monthly_weekday' && (
                <Field label="Rang dans le mois" errors={field('monthWeeks')}>
                    <div className="chip-choices">
                        {MONTH_WEEKS.map((week) => (
                            <ToggleChip key={week.value} pressed={monthWeeks.includes(week.value)} onToggle={() => setMonthWeeks(toggled(monthWeeks, week.value))}>
                                {week.label}
                            </ToggleChip>
                        ))}
                    </div>
                </Field>
            )}

            {rule === 'monthly_day' && (
                <Field label="Jours du mois" errors={field('monthDays')}>
                    <div className="chip-choices">
                        {MONTH_DAYS.map((day) => (
                            <ToggleChip key={day} pressed={monthDays.includes(day)} onToggle={() => setMonthDays(toggled(monthDays, day))}>
                                {day}
                            </ToggleChip>
                        ))}
                    </div>
                </Field>
            )}

            <FormGrid columns={2}>
                <Field label="Heure (facultatif)" errors={field('startTime')}>
                    <TextInput type="time" value={startTime} onChange={(event) => setStartTime(event.target.value)} />
                </Field>
                {startTime !== '' && (
                    <Field label="Durée en minutes (facultatif)" errors={field('durationInMinutes')}>
                        <TextInput type="number" inputMode="numeric" min={1} max={1440} placeholder="75" value={duration} onChange={(event) => setDuration(event.target.value)} />
                    </Field>
                )}
            </FormGrid>

            {violations.isGeneral && <Alert>{failureOf(mutation.error) ?? ''}</Alert>}

            <FormActions>
                <Button variant="quiet" onClick={onDone}>
                    Annuler
                </Button>
                <Button submit disabled={mutation.isPending}>
                    {editing === null
                        ? mutation.isPending ? 'Planifier…' : 'Planifier'
                        : mutation.isPending ? 'Enregistrer…' : 'Enregistrer'}
                </Button>
            </FormActions>
        </form>
    )
}

function toggled(values: number[], value: number): number[] {
    return values.includes(value) ? values.filter((one) => one !== value) : [...values, value]
}
