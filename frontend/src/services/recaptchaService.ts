const RECAPTCHA_SITE_KEY = import.meta.env.VITE_RECAPTCHA_SITE_KEY
const RECAPTCHA_TIMEOUT_MS = 5000

function withTimeout<T>(promise: Promise<T>): Promise<T> {
  return Promise.race([
    promise,
    new Promise<T>((_, reject) =>
      setTimeout(() => reject(new Error('reCAPTCHA timeout')), RECAPTCHA_TIMEOUT_MS)
    ),
  ])
}

export async function getRecaptchaToken(action: string): Promise<string> {
  if (!RECAPTCHA_SITE_KEY) return ''

  try {
    const gr = (window as any).grecaptcha
    if (!gr) return ''

    await withTimeout(new Promise<void>((resolve) => gr.ready(resolve)))

    const token = await withTimeout<string>(
      gr.execute(RECAPTCHA_SITE_KEY, { action })
    )
    return token
  } catch {
    return ''
  }
}

/**
 * Check if reCAPTCHA is available
 */
export function isRecaptchaAvailable(): boolean {
  return !!(window as any).grecaptcha && !!RECAPTCHA_SITE_KEY
}
