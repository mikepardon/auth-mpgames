# MPGames Central Auth Service

Central OAuth2 authentication service for all MPGames titles. Handles login, registration, email verification, and SSO (Google + Apple). Games authenticate via OAuth2 Authorization Code + PKCE flow.

## Quick Start

```bash
# Install dependencies
composer install

# Copy environment file
cp .env.example .env

# Generate app key
php artisan key:generate

# Create SQLite database
touch database/database.sqlite

# Install Passport (encryption keys + OAuth tables)
php artisan passport:install

# Run migrations
php artisan migrate

# Register your first game client
php artisan game:register "Trusted Advisors" "http://trusted-advisors.test/auth/callback"

# Start dev server
php artisan serve
```

## Configuration

### Environment Variables

| Variable | Description |
|----------|-------------|
| `APP_URL` | Base URL of the auth service |
| `GOOGLE_CLIENT_ID` | Google OAuth client ID |
| `GOOGLE_CLIENT_SECRET` | Google OAuth client secret |
| `APPLE_CLIENT_ID` | Apple Sign In service ID |
| `APPLE_CLIENT_SECRET` | Apple Sign In client secret |
| `ONESIGNAL_APP_ID` | OneSignal app ID for email delivery |
| `ONESIGNAL_REST_API_KEY` | OneSignal REST API key |
| `CORS_ALLOWED_ORIGINS` | Comma-separated allowed origins for game clients |

### Google SSO Setup

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create OAuth 2.0 credentialsA
3. Set authorized redirect URI to `{APP_URL}/auth/google/callback`
4. Add `GOOGLE_CLIENT_ID` and `GOOGLE_CLIENT_SECRET` to `.env`

### Apple SSO Setup

1. Go to [Apple Developer Portal](https://developer.apple.com/)
2. Register a Services ID for Sign in with Apple
3. Set return URL to `{APP_URL}/auth/apple/callback`
4. Add `APPLE_CLIENT_ID` and `APPLE_CLIENT_SECRET` to `.env`

## Registering a New Game Client

```bash
php artisan game:register "Game Name" "https://game.example.com/auth/callback"
```

This creates a public OAuth2 client (no secret needed - games are SPAs). Note the `client_id` from the output.

## API Endpoints

### Auth UI Routes (Session-Based)

These are the web pages served by the auth service.

| Method | URL | Description |
|--------|-----|-------------|
| GET | `/login` | Login page |
| GET | `/register` | Registration page |
| GET | `/verify-email?user_id=` | Email verification page |
| GET | `/forgot-password` | Forgot password page |
| GET | `/reset-password?token=&email=` | Reset password page |
| GET | `/auth/google/redirect` | Start Google SSO |
| GET | `/auth/apple/redirect` | Start Apple SSO |

### Auth JSON Endpoints

| Method | URL | Body | Response |
|--------|-----|------|----------|
| POST | `/register` | `{username, email, password, password_confirmation}` | `{requires_verification, user_id}` |
| POST | `/login` | `{login, password}` | `{user: {id, username, email, ...}}` |
| POST | `/verify-email` | `{user_id, code}` | `{message, user}` |
| POST | `/resend-verification` | `{user_id}` | `{message}` |
| POST | `/forgot-password` | `{email}` | `{message}` |
| POST | `/reset-password` | `{email, token, password, password_confirmation}` | `{message}` |
| POST | `/logout` | _(auth required)_ | `{message}` |
| POST | `/change-password` | `{current_password, new_password, new_password_confirmation}` | `{message}` |

### OAuth2 Endpoints (Passport)

| Method | URL | Description |
|--------|-----|-------------|
| GET | `/oauth/authorize` | Authorization endpoint (redirect users here) |
| POST | `/oauth/token` | Token exchange endpoint |

### API Endpoints (Token-Protected)

| Method | URL | Auth | Response |
|--------|-----|------|----------|
| GET | `/api/user` | Bearer token | `{id, username, email, email_verified_at, avatar_url, created_at}` |

## OAuth2 PKCE Flow (Game Integration)

This is how game clients (like Trusted Advisors) authenticate users through the auth service.

### Step 1: Generate PKCE Challenge

```javascript
// Generate random code verifier
function generateCodeVerifier() {
    const array = new Uint8Array(32);
    crypto.getRandomValues(array);
    return btoa(String.fromCharCode(...array))
        .replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
}

// Create code challenge from verifier
async function generateCodeChallenge(verifier) {
    const encoder = new TextEncoder();
    const data = encoder.encode(verifier);
    const digest = await crypto.subtle.digest('SHA-256', data);
    return btoa(String.fromCharCode(...new Uint8Array(digest)))
        .replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
}

const codeVerifier = generateCodeVerifier();
const codeChallenge = await generateCodeChallenge(codeVerifier);
const state = generateCodeVerifier(); // random state for CSRF protection

// Store these in sessionStorage for later
sessionStorage.setItem('code_verifier', codeVerifier);
sessionStorage.setItem('oauth_state', state);
```

### Step 2: Redirect to Auth Service

```javascript
const params = new URLSearchParams({
    client_id: 'YOUR_CLIENT_ID',
    redirect_uri: 'http://your-game.com/auth/callback',
    response_type: 'code',
    scope: '',
    state: state,
    code_challenge: codeChallenge,
    code_challenge_method: 'S256',
});

window.location.href = `https://auth.mpgames.com/oauth/authorize?${params}`;
```

### Step 3: Handle Callback

The auth service redirects back to your `redirect_uri` with `?code=AUTH_CODE&state=STATE`.

```javascript
// On your callback page
const urlParams = new URLSearchParams(window.location.search);
const code = urlParams.get('code');
const returnedState = urlParams.get('state');

// Verify state matches
if (returnedState !== sessionStorage.getItem('oauth_state')) {
    throw new Error('State mismatch - possible CSRF attack');
}
```

### Step 4: Exchange Code for Tokens

```javascript
const response = await fetch('https://auth.mpgames.com/oauth/token', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        grant_type: 'authorization_code',
        client_id: 'YOUR_CLIENT_ID',
        redirect_uri: 'http://your-game.com/auth/callback',
        code_verifier: sessionStorage.getItem('code_verifier'),
        code: code,
    }),
});

const { access_token, refresh_token, expires_in } = await response.json();

// Store tokens securely
localStorage.setItem('access_token', access_token);
localStorage.setItem('refresh_token', refresh_token);
```

### Step 5: Get User Info

```javascript
const userResponse = await fetch('https://auth.mpgames.com/api/user', {
    headers: { 'Authorization': `Bearer ${access_token}` },
});

const user = await userResponse.json();
// { id, username, email, email_verified_at, avatar_url, created_at }

// Use user.id to create/link a local user in your game's database
```

### Step 6: Refresh Tokens

```javascript
const refreshResponse = await fetch('https://auth.mpgames.com/oauth/token', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        grant_type: 'refresh_token',
        client_id: 'YOUR_CLIENT_ID',
        refresh_token: localStorage.getItem('refresh_token'),
    }),
});

const tokens = await refreshResponse.json();
localStorage.setItem('access_token', tokens.access_token);
localStorage.setItem('refresh_token', tokens.refresh_token);
```

## Token Lifetimes

| Token Type | Lifetime |
|-----------|----------|
| Access Token | 15 days |
| Refresh Token | 30 days |

## Architecture

```
Game Client (TA)              Auth Service (auth.mpgames.com)
  |                                  |
  | 1. Redirect to /oauth/authorize  |
  | -------------------------------->|
  |                                  | 2. Show login/register UI
  |                                  | 3. User authenticates
  |                                  | 4. Show consent screen
  | <--------------------------------|
  | 5. Callback with auth code       |
  |                                  |
  | 6. POST /oauth/token             |
  | -------------------------------->|
  | <--------------------------------|
  | 7. {access_token, refresh_token} |
  |                                  |
  | 8. GET /api/user (Bearer token)  |
  | -------------------------------->|
  | <--------------------------------|
  | 9. {id, username, email, ...}    |
  |                                  |
  | 10. Create/link local user       |
```

## Design Decisions

- **Passport over Sanctum**: Sanctum is for same-domain SPAs. Passport provides full OAuth2 with PKCE for cross-domain game clients.
- **Blade + Alpine.js**: Auth UI is ~6 pages. No build step needed.
- **Nullable password**: SSO-only users never set a password.
- **Identity only**: Auth service stores identity (username, email, SSO IDs). Game data (XP, stats) stays in each game's DB linked by `auth_id`.
- **OneSignal for email**: Matches existing Trusted Advisors infrastructure.
