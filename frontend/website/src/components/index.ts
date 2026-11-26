/**
 * Everything a screen is allowed to draw with.
 *
 * The rule: a page never writes a `<button>`, an `<input>`, a `<form>` or a layout class by hand.
 * Only the components here do, so the look of the System is one decision rather than a hundred.
 *
 * Sorted by what they are for: `Button/`, `Form/`, `List/`, `Modal/`, `Navigation/`; what fits
 * none of them sits at the root.
 */
export { Button, type ButtonVariant } from './Button/Button'
export { CheckToggle } from './Button/CheckToggle'
export { IconButton } from './Button/IconButton'
export { TextButton } from './Button/TextButton'

export { ChoiceField, type Choice, type ChoiceGroup } from './Form/ChoiceField'
export { DateField } from './Form/DateField'
export { DecimalField } from './Form/DecimalField'
export { DurationField } from './Form/DurationField'
export { EmailField } from './Form/EmailField'
export { Form } from './Form/Form'
export { FormActions } from './Form/FormActions'
export { FormGrid } from './Form/FormGrid'
export { NumberField } from './Form/NumberField'
export { PasswordField } from './Form/PasswordField'
export { RatingField, type RatingLevel } from './Form/RatingField'
export { SelectField, type Option } from './Form/SelectField'
export { TextAreaField } from './Form/TextAreaField'
export { TextField } from './Form/TextField'
export { TimeField } from './Form/TimeField'
export { useViolations, type Violations } from './Form/useViolations'

export { DataList, type ListGroup } from './List/DataList'
export { DefinitionList, type Definition } from './List/DefinitionList'
export { FoldGroup } from './List/FoldGroup'
export { ListItem } from './List/ListItem'

export { ConfirmDialog } from './Modal/ConfirmDialog'
export { Modal } from './Modal/Modal'

export { IconLink } from './Navigation/IconLink'
export { LinkChip } from './Navigation/LinkChip'
export { SideNav, type NavItem } from './Navigation/SideNav'
export { Tabs, type Tab } from './Navigation/Tabs'

export { Chip, DotChip, StateChip } from './Chip'
export { Alert, EmptyState, Loader } from './Feedback'
export { IndexBadge } from './IndexBadge'
export { Row, Stack } from './Layout'
export { AuthShell, PageShell } from './PageShell'
export { ProgressBar } from './ProgressBar'
export { SectionHeading } from './SectionHeading'
export { SegmentedBar } from './SegmentedBar'
export { SystemPanel } from './SystemPanel'
export { Tooltip } from './Tooltip'
export { WeekCalendar, type CalendarDay, type CalendarItem } from './WeekCalendar'
export { useReloadOnDayChange } from './useReloadOnDayChange'
