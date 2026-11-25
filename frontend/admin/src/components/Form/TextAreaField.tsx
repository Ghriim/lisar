import { Form as AntForm, Input } from 'antd'
import { useMandatoryRules, type FieldProps } from './fieldProps'

interface TextAreaFieldProps extends FieldProps {
    rows?: number
}

export function TextAreaField({ name, label, isMandatory = false, placeholder, hint, rows = 3 }: TextAreaFieldProps) {
    const rules = useMandatoryRules(isMandatory)

    return (
        <AntForm.Item label={label} name={name} rules={rules} extra={hint}>
            <Input.TextArea rows={rows} placeholder={placeholder} />
        </AntForm.Item>
    )
}
