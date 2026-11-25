/**
 * Everything a screen is allowed to draw with.
 *
 * The rule this file enforces: **no page imports `antd`**. Only the components here do, so the
 * library stays one decision rather than a hundred, and a screen reads in the vocabulary of the
 * product instead of the vocabulary of a widget set.
 *
 * Sorted by what they are for: `Button/`, `Form/`, `List/`, `Modal/`, `Navigation/`; what fits
 * none of them sits at the root.
 */
export { Button, type ButtonVariant } from './Button/Button'
export { ConfirmButton } from './Button/ConfirmButton'

export { EmailField } from './Form/EmailField'
export { Form, type ValuesChangeHandler } from './Form/Form'
export { FormActions } from './Form/FormActions'
export { NumberField } from './Form/NumberField'
export { PasswordField } from './Form/PasswordField'
export { SelectField, type SelectOption, type SelectOptionGroup } from './Form/SelectField'
export { SwitchField } from './Form/SwitchField'
export { TextArea } from './Form/TextArea'
export { TextAreaField } from './Form/TextAreaField'
export { TextField } from './Form/TextField'

export { ActiveFilter } from './List/ActiveFilter'
export { DataTable, type Column, type Pagination } from './List/DataTable'
export { DescriptionList, type Description } from './List/DescriptionList'
export { FilterSelect } from './List/FilterSelect'
export { ItemList } from './List/ItemList'
export { ListToolbar } from './List/ListToolbar'
export { useActiveFilter, type ActiveStatus } from './List/useActiveFilter'

export { DetailDrawer, Separator } from './Modal/DetailDrawer'
export { FormModal } from './Modal/FormModal'

export { LinkText } from './Navigation/LinkText'
export { SideMenu, type Screen, type Section } from './Navigation/SideMenu'

export { AppShell, Panel, PlainShell } from './AppShell'
export { ColourDot, RoleTag, StatusTag } from './Badges'
export { DateText } from './DateText'
export { Alert, EmptyState, FullPageLoader, Tag } from './Feedback'
export { HabitIcon } from './HabitIcon'
export { HydrationIcon } from './HydrationIcon'
export { IconGallery } from './IconGallery'
export { Centered, Row, Stack } from './Layout'
export { Page } from './Page'
export { Paragraph, Text, Title } from './Text'
export { useNotifier, type Notifier } from './useNotifier'
