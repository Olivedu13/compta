/**
 * FormInput - Input field réutilisable avec validation
 */

import React from 'react';
import { TextField, FormHelperText, Box } from '@mui/material';

export const FormInput = ({
  label,
  type = 'text',
  value,
  onChange,
  onBlur,
  error = false,
  helperText = '',
  required = false,
  disabled = false,
  placeholder = '',
  variant = 'outlined',
  size = 'medium',
  fullWidth = true,
  multiline = false,
  rows = 1,
  autoComplete = 'off',
  ...props
}) => {
  return (
    <Box sx={{ width: fullWidth ? '100%' : 'auto' }}>
      <TextField
        label={label}
        type={type}
        value={value}
        onChange={onChange}
        onBlur={onBlur}
        error={error}
        helperText={helperText}
        required={required}
        disabled={disabled}
        placeholder={placeholder}
        variant={variant}
        size={size}
        fullWidth={fullWidth}
        multiline={multiline}
        rows={rows}
        autoComplete={autoComplete}
        {...props}
      />
    </Box>
  );
};

export default FormInput;
