import axios from 'axios'

interface ApiError {
  message?: string
  errors?: Record<string, string[]>
}

export const api = axios.create({
  baseURL: '/',
  withCredentials: true,
  withXSRFToken: true,
  headers: {
    Accept: 'application/json',
  },
})

export function getErrorMessage(error: unknown): string {
  if (axios.isAxiosError<ApiError>(error)) {
    const payload = error.response?.data

    const validationMessage = payload?.errors
      ? Object.values(payload.errors).flat()[0]
      : undefined

    return (
      validationMessage ??
      payload?.message ??
      error.message
    )
  }

  return error instanceof Error
    ? error.message
    : 'An unexpected error occurred.'
}
