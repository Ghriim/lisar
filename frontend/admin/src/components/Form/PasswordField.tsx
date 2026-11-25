import { Form as AntForm, Input } from 'antd'
import { useMandatoryRules, type FieldProps } from './fieldProps'

export function PasswordField({ name, label, isMandatory = false }: FieldProps) {
    const rules = useMandatoryRules(isMandatory)

    return (
        <AntForm.Item label={label} name={name} rules={rules}>
            <Input.Password autoComplete="current-password" />
        </AntForm.Item>
    )
}
