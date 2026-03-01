# reCAPTCHA v3 Setup Guide

## Overview
reCAPTCHA v3 has been integrated into ReklaMap to protect forms from spam and abuse. Unlike reCAPTCHA v2, v3 works silently in the background without user interaction.

## Getting reCAPTCHA Keys

### Step 1: Go to Google reCAPTCHA Admin Console
1. Visit: https://www.google.com/recaptcha/admin
2. Sign in with your Google account

### Step 2: Create a New Site
1. Click "Create" or "+" button
2. Fill in the form:
   - **Label**: ReklaMap (or your preferred name)
   - **reCAPTCHA type**: Select "reCAPTCHA v3"
   - **Domains**: Add your domain(s):
     - `reklamap.pl`
     - `www.reklamap.pl`
     - `localhost` (for development)
3. Accept the reCAPTCHA Terms of Service
4. Click "Create"

### Step 3: Copy Your Keys
After creation, you'll see:
- **Site Key** (public key)
- **Secret Key** (private key - keep this secret!)

## Configuration

### Frontend Setup (.env)

1. Create or update `/frontend/.env` file:
```env
VITE_RECAPTCHA_SITE_KEY=your-site-key-here
```

Or copy from `.env.example`:
```bash
cp frontend/.env.example frontend/.env
# Then edit and add your Site Key
```

### Backend Setup (.env)

1. Create or update `/backend/.env` file:
```env
RECAPTCHA_SECRET_KEY=your-secret-key-here
```

Or copy from `.env.example`:
```bash
cp backend/.env.example backend/.env
# Then edit and add your Secret Key
```

## Usage

### Frontend - Getting reCAPTCHA Token

In any Vue component that has a form:

```typescript
import { getRecaptchaToken, isRecaptchaAvailable } from '@/services/recaptchaService'

// Before submitting form
if (isRecaptchaAvailable()) {
  const token = await getRecaptchaToken('your_action_name')
  // Send token with form data
}
```

Example in a form submission:

```typescript
const handleSubmit = async () => {
  try {
    let recaptchaToken = ''
    
    if (isRecaptchaAvailable()) {
      recaptchaToken = await getRecaptchaToken('submit_form')
    }

    const response = await api.post('/your-endpoint', {
      // ... your form data
      recaptcha_token: recaptchaToken
    })
    
    // Handle response
  } catch (error) {
    // Handle error
  }
}
```

### Backend - Verifying reCAPTCHA Token

#### Option 1: Using Middleware (Recommended)

Register the middleware in `app/Http/Kernel.php`:

```php
protected $routeMiddleware = [
    // ... other middleware
    'verify.recaptcha' => \App\Http\Middleware\VerifyRecaptcha::class,
];
```

Use on routes:

```php
Route::post('/contact', 'ContactController@store')->middleware('verify.recaptcha');
Route::post('/ads', 'AdController@store')->middleware('verify.recaptcha');
```

#### Option 2: Manual Verification in Controller

```php
use Illuminate\Support\Facades\Http;

public function store(Request $request)
{
    $token = $request->input('recaptcha_token');
    
    // Verify with Google
    $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
        'secret' => config('recaptcha.secret'),
        'response' => $token,
    ]);

    $data = $response->json();

    if (!$data['success'] || ($data['score'] ?? 1) < 0.5) {
        return response()->json([
            'message' => 'reCAPTCHA verification failed'
        ], 422);
    }

    // Continue with your logic
}
```

## reCAPTCHA Score Interpretation

reCAPTCHA v3 returns a score from 0.0 to 1.0:

- **0.9+**: Very likely legitimate
- **0.5-0.9**: Probably legitimate
- **0.0-0.5**: Likely bot/spam

Current threshold: **0.5** (configurable)

## Protected Forms/Endpoints

Add reCAPTCHA protection to:

1. **Contact Form** (`/contact`)
2. **Add Advertisement** (`/ads`)
3. **Email Modal** (contact owner)
4. **Feedback Form** (`/feedback`)
5. **User Registration** (if applicable)

## Testing

### Development Mode

1. reCAPTCHA works on `localhost` without domain verification
2. Use the test keys for development:
   - Site Key: `6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI`
   - Secret Key: `6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe`

### Production Mode

1. Use your actual keys from Google reCAPTCHA Admin
2. Monitor scores in Google reCAPTCHA Admin Console
3. Adjust score threshold if needed

## Troubleshooting

### "reCAPTCHA site key not configured"
- Check that `VITE_RECAPTCHA_SITE_KEY` is set in frontend `.env`
- Rebuild frontend: `npm run build`

### "reCAPTCHA verification failed"
- Check that `RECAPTCHA_SECRET_KEY` is set in backend `.env`
- Verify the token is being sent in the request
- Check that domains are added in Google reCAPTCHA Admin

### Low Scores for Legitimate Users
- Adjust the score threshold in `VerifyRecaptcha` middleware
- Monitor patterns in Google reCAPTCHA Admin Console
- Consider adding additional verification steps

## Security Notes

⚠️ **Important:**
- Never expose `RECAPTCHA_SECRET_KEY` in frontend code
- Always verify tokens on the backend
- Keep your Secret Key private
- Regularly review reCAPTCHA Admin Console for suspicious activity

## References

- [Google reCAPTCHA Documentation](https://developers.google.com/recaptcha/docs/v3)
- [reCAPTCHA Admin Console](https://www.google.com/recaptcha/admin)
- [reCAPTCHA Score Guide](https://developers.google.com/recaptcha/docs/v3#score_interpretation)
