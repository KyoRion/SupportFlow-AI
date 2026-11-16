import { describe, expect, it } from 'vitest'
import { allowedTransitions } from '../ticket-status'

describe('Ticket status transitions', () => {
  it('allows open to in_progress', () => {
    expect(allowedTransitions.open).toContain('in_progress')
  })

  it('does not allow open to closed', () => {
    expect(allowedTransitions.open).not.toContain('closed')
  })

  it('does not allow changes after closed', () => {
    expect(allowedTransitions.closed).toEqual([])
  })
})
