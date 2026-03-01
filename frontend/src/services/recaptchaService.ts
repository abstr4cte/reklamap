/**
 * reCAPTCHA v3 Service
 * Handles reCAPTCHA token generation and validation
 */

const RECAPTCHA_SITE_KEY = import.meta.env.VITE_RECAPTCHA_SITE_KEY

/**
 * Get reCAPTCHA token for a specific action
 * @param action - The action name (e.g., 'submit_form', 'contact_us', 'add_ad')
 * @returns Promise with the reCAPTCHA token
 */
export async function getRecaptchaToken(action: string): Promise<string> {
  if (!RECAPTCHA_SITE_KEY) {
    console.warn('reCAPTCHA site key not configured')
    return ''
  }

  try {
    // Wait for grecaptcha to be ready
    await new Promise<void>((resolve) => {
      (window as any).grecaptcha.ready(() => resolve());
    });

    const token = await (window as any).grecaptcha.execute(RECAPTCHA_SITE_KEY, {
      action: action
    })
    return token
  } catch (error) {
    console.error('Failed to get reCAPTCHA token:', error)
    return ''
  }
}

/**
 * Check if reCAPTCHA is available
 */
export function isRecaptchaAvailable(): boolean {
  return !!(window as any).grecaptcha && !!RECAPTCHA_SITE_KEY
}
