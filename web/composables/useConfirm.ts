export interface ConfirmOptions {
  title: string
  message: string
  confirmText?: string
  cancelText?: string
  variant?: 'danger' | 'default'
}

interface ConfirmState extends ConfirmOptions {
  isOpen: boolean
}

const state = reactive<ConfirmState>({
  isOpen: false,
  title: '',
  message: '',
  confirmText: 'Confirm',
  cancelText: 'Cancel',
  variant: 'danger',
})

let resolvePromise: ((value: boolean) => void) | null = null

export const useConfirm = () => {
  function confirm(options: ConfirmOptions): Promise<boolean> {
    state.title = options.title
    state.message = options.message
    state.confirmText = options.confirmText || 'Delete'
    state.cancelText = options.cancelText || 'Cancel'
    state.variant = options.variant || 'danger'
    state.isOpen = true

    return new Promise<boolean>((resolve) => {
      resolvePromise = resolve
    })
  }

  function handleConfirm() {
    state.isOpen = false
    if (resolvePromise) {
      resolvePromise(true)
      resolvePromise = null
    }
  }

  function handleCancel() {
    state.isOpen = false
    if (resolvePromise) {
      resolvePromise(false)
      resolvePromise = null
    }
  }

  return {
    state,
    confirm,
    handleConfirm,
    handleCancel,
  }
}
