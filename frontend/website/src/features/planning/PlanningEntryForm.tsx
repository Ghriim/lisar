import { type ReactNode, useState } from 'react'
import type { PlanningSchedule } from '../../api/types'
import {
    Alert,
    Button,
    ChoiceField,
    DateField,
    Form,
    FormActions,
    FormGrid,
    NumberField,
    SelectField,
    TextAreaField,
    TextField,
    TimeField,
    useViolations,
} from '../../components'
import { useHabits } from '../habits/queries'
import { useRoutines } from '../routines/queries'
import { useCategories } from '../categories/queries'
import { failureOf } from '../workouts/workoutFormat'
import { DAILY, MONTH_WEEKS, RULES, ruleChoiceOf, scheduleRuleOf, WEEKDAYS } from './planningFormat'
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
    /**
     * The field choosing the kind, while it is still being chosen: it sits in this form, so its
     * asterisk counts towards the legend like any other.
     */
    kindPicker?: ReactNode
    /**
     * A habit entry written from that habit's own line: the habit is already chosen, and the form
     * does not ask for it.
     */
    habitId?: number
    onDone: () => void
}

const MONTH_DAYS = Array.from({ length: 31 }, (_, index) => index + 1)

/**
 * What is planned — a routine, a task model, or a habit one keeps — and when: the rule, then only
 * the fields that rule reads. Nothing is chosen for the person — no routine, category or habit, no
 * day — but the start date, which opens on today, and the rule, which opens on « tous les jours ».
 */
export function PlanningEntryForm({ kind, editing, today, kindPicker, habitId, onDone }: PlanningEntryFormProps) {
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
        editing?.kind === 'training'
            ? `${editing.entry.routineId}`
            : editing?.kind === 'habit'
              ? `${editing.entry.habitId}`
              : habitId !== undefined
                ? `${habitId}`
                : '',
    )
    // A task entry's model.
    const [title, setTitle] = useState(editing?.kind === 'task' ? editing.entry.title : '')
    const [description, setDescription] = useState(editing?.kind === 'task' ? (editing.entry.description ?? '') : '')
    const [categoryId, setCategoryId] = useState(editing?.kind === 'task' && editing.entry.category !== null ? `${editing.entry.category.id}` : '')
    // « Tous les jours » is offered first and chosen from the start: it is what most plans are.
    const [rule, setRule] = useState(initial === undefined ? DAILY : ruleChoiceOf(initial))
    const [startDate, setStartDate] = useState(initial?.startDate ?? today)
    const [endDate, setEndDate] = useState(initial?.endDate ?? '')
    const [weekdays, setWeekdays] = useState<number[]>(initial?.weekdays ?? [])
    const [weekInterval, setWeekInterval] = useState(`${initial?.weekInterval ?? 1}`)
    const [monthWeeks, setMonthWeeks] = useState<number[]>(initial?.monthWeeks ?? [])
    const [monthDays, setMonthDays] = useState<number[]>(initial?.monthDays ?? [])
    const [startTime, setStartTime] = useState(initial?.startTime ?? '')
    const [duration, setDuration] = useState(initial?.durationInMinutes?.toString() ?? '')

    const submit = async () => {
        const schedule: PlanningSchedule = {
            ...scheduleRuleOf(rule, weekdays, Number(weekInterval) || 1),
            startDate,
            endDate: rule === 'once' || endDate === '' ? null : endDate,
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
        <Form onSubmit={submit}>
            {kindPicker}

            {kind === 'training' && (
                <SelectField
                    label="Routine"
                    isMandatory
                    errors={violations.for('routineId')}
                    value={subjectId}
                    onChange={setSubjectId}
                    placeholder={routines.isPending ? 'Chargement…' : 'Choisir une routine'}
                    options={(routines.data ?? []).map((routine) => ({ value: `${routine.id}`, label: routine.name }))}
                />
            )}

            {kind === 'task' && (
                <>
                    <TextField
                        label="Intitulé"
                        isMandatory
                        errors={violations.for('title')}
                        value={title}
                        onChange={setTitle}
                        placeholder="Ce qu'il y a à faire"
                    />
                    <TextAreaField label="Détail" errors={violations.for('description')} value={description} onChange={setDescription} />
                    <SelectField
                        label="Catégorie"
                        errors={violations.for('categoryId')}
                        value={categoryId}
                        onChange={setCategoryId}
                        placeholder="Aucune"
                        options={(categories.data ?? []).map((category) => ({
                            value: String(category.id),
                            label: category.isPersonal ? `${category.label} ·` : category.label,
                        }))}
                    />
                </>
            )}

            {kind === 'habit' && habitId === undefined && (
                <SelectField
                    label="Habitude"
                    isMandatory
                    errors={violations.for('habitId')}
                    value={subjectId}
                    onChange={setSubjectId}
                    placeholder={habits.isPending ? 'Chargement…' : 'Choisir une habitude'}
                    options={(habits.data ?? []).map((habit) => ({ value: `${habit.habitId}`, label: habit.name }))}
                />
            )}

            <SelectField label="Quand" isMandatory errors={field('rule')} value={rule} onChange={setRule} placeholder="Choisir une règle" options={RULES} />

            <FormGrid columns={2}>
                <DateField label={rule === 'once' ? 'Le' : 'À partir du'} isMandatory errors={field('startDate')} value={startDate} onChange={setStartDate} />
                {rule !== 'once' && <DateField label="Jusqu’au" errors={field('endDate')} value={endDate} onChange={setEndDate} />}
            </FormGrid>

            {readsWeekdays && (
                <ChoiceField
                    label="Jours"
                    isMandatory
                    errors={field('weekdays')}
                    choices={WEEKDAYS.map((day) => ({ value: day.value, label: day.long }))}
                    chosen={weekdays}
                    onToggle={(day) => setWeekdays(toggled(weekdays, day))}
                />
            )}

            {rule === 'weekly' && (
                <NumberField label="Une semaine sur" isMandatory errors={field('weekInterval')} min={1} max={52} value={weekInterval} onChange={setWeekInterval} />
            )}

            {rule === 'monthly_weekday' && (
                <ChoiceField
                    label="Rang dans le mois"
                    isMandatory
                    errors={field('monthWeeks')}
                    choices={MONTH_WEEKS}
                    chosen={monthWeeks}
                    onToggle={(week) => setMonthWeeks(toggled(monthWeeks, week))}
                />
            )}

            {rule === 'monthly_day' && (
                <ChoiceField
                    label="Jours du mois"
                    isMandatory
                    errors={field('monthDays')}
                    choices={MONTH_DAYS.map((day) => ({ value: day, label: day }))}
                    chosen={monthDays}
                    onToggle={(day) => setMonthDays(toggled(monthDays, day))}
                />
            )}

            <FormGrid columns={2}>
                <TimeField label="Heure" errors={field('startTime')} value={startTime} onChange={setStartTime} />
                {startTime !== '' && (
                    <NumberField label="Durée en minutes" errors={field('durationInMinutes')} min={1} max={1440} placeholder="75" value={duration} onChange={setDuration} />
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
        </Form>
    )
}

function toggled(values: number[], value: number): number[] {
    return values.includes(value) ? values.filter((one) => one !== value) : [...values, value]
}
