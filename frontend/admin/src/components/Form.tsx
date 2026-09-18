import { Form as AntForm, Input, InputNumber, Select, Switch } from 'antd'
import type { ReactNode } from 'react'
import { Row } from './Layout'

interface FormProps<TValues> {
    initialValues?: Partial<TValues>
    onSubmit: (values: TValues) => void
    children: ReactNode
}

/**
 * Every form in the back-office is one of these: vertical labels, no asterisks, and the fields
 * below bound by name.
 */
export function Form<TValues extends object>({ initialValues, onSubmit, children }: FormProps<TValues>) {
    return (
        <AntForm<TValues>
            layout="vertical"
            requiredMark={false}
            initialValues={initialValues}
            onFinish={onSubmit}
        >
            {children}
        </AntForm>
    )
}

/**
 * The house rule, in one place: centred, cancel first, the verb that commits second.
 * See docs/dev/frontend-conventions.md.
 */
export function FormActions({ children }: { children: ReactNode }) {
    return (
        <Row justify="center" gap={8}>
            {children}
        </Row>
    )
}

interface FieldProps {
    name: string
    label: string
    required?: boolean
    placeholder?: string
    /** A line under the field explaining what it does to the rest of the app. */
    hint?: string
    autoFocus?: boolean
    disabled?: boolean
}

function rulesFor(required: boolean) {
    return required ? [{ required: true, message: 'Requis' }] : undefined
}

export function TextField({ name, label, required = false, placeholder, hint, autoFocus, disabled }: FieldProps) {
    return (
        <AntForm.Item label={label} name={name} rules={rulesFor(required)} extra={hint}>
            <Input placeholder={placeholder} autoFocus={autoFocus} disabled={disabled} />
        </AntForm.Item>
    )
}

export function EmailField(props: FieldProps) {
    return (
        <AntForm.Item label={props.label} name={props.name} rules={rulesFor(props.required ?? false)}>
            <Input type="email" autoComplete="email" autoFocus={props.autoFocus} />
        </AntForm.Item>
    )
}

export function PasswordField(props: FieldProps) {
    return (
        <AntForm.Item label={props.label} name={props.name} rules={rulesFor(props.required ?? false)}>
            <Input.Password autoComplete="current-password" />
        </AntForm.Item>
    )
}

interface NumberFieldProps extends FieldProps {
    min?: number
    max?: number
}

export function NumberField({ name, label, required = false, hint, min, max }: NumberFieldProps) {
    return (
        <AntForm.Item label={label} name={name} rules={rulesFor(required)} extra={hint}>
            <InputNumber min={min} max={max} style={{ width: '100%' }} />
        </AntForm.Item>
    )
}

export interface SelectOption {
    value: string
    label: ReactNode
}

interface SelectFieldProps extends FieldProps {
    options: SelectOption[]
}

export function SelectField({ name, label, required = false, hint, options }: SelectFieldProps) {
    return (
        <AntForm.Item label={label} name={name} rules={rulesFor(required)} extra={hint}>
            <Select options={options} />
        </AntForm.Item>
    )
}

export function SwitchField({ name, label, hint, disabled }: FieldProps) {
    return (
        <AntForm.Item label={label} name={name} valuePropName="checked" extra={hint}>
            <Switch disabled={disabled} />
        </AntForm.Item>
    )
}

interface TextAreaProps {
    value: string
    onChange: (value: string) => void
    placeholder?: string
    rows?: number
}

/**
 * Standalone, outside a Form: the note box on an account is a single control, not a form, so it
 * takes a value and a handler rather than a field name.
 */
export function TextArea({ placeholder, rows = 3, value, onChange }: TextAreaProps) {
    return (
        <Input.TextArea
            rows={rows}
            value={value}
            placeholder={placeholder}
            onChange={(event) => onChange(event.target.value)}
        />
    )
}
