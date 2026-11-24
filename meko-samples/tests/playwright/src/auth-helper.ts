import { APIRequestContext } from '@playwright/test';
import { config } from './config';

let cachedToken: string | null = null;
let tokenExpiresAt: number = 0;

/**
 * Retrieve a valid OAuth 2.0 Bearer JWT or debug Personal Access Token.
 */
export async function getBearerToken(request: APIRequestContext): Promise<string> {
  // 1. If explicit debug PAT is configured, use it directly.
  if (config.debugPat) {
    return config.debugPat;
  }

  // 2. Reuse cached token if not expired (with 30s margin).
  const now = Date.now();
  if (cachedToken && tokenExpiresAt > now + 30_000) {
    return cachedToken;
  }

  // 3. Otherwise request token from Moodle OAuth 2.0 endpoint.
  const response = await request.post(config.oauthTokenUrl, {
    form: {
      grant_type: 'client_credentials',
      client_id: config.clientId,
      client_secret: config.clientSecret,
      scope: config.oauthScopes,
    },
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
    },
  });

  if (!response.ok()) {
    const errorText = await response.text();
    throw new Error(
      `Failed to acquire OAuth 2.0 token from ${config.oauthTokenUrl} (HTTP ${response.status()}): ${errorText}`
    );
  }

  const json = await response.json();
  if (!json.access_token) {
    throw new Error(`OAuth token response missing access_token field: ${JSON.stringify(json)}`);
  }

  cachedToken = json.access_token;
  const expiresInSeconds = json.expires_in || 3600;
  tokenExpiresAt = now + expiresInSeconds * 1000;

  return cachedToken as string;
}

/**
 * Get standard authenticated headers for ACCEES REST API v2 requests.
 */
export async function getAuthHeaders(
  request: APIRequestContext,
  customHeaders: Record<string, string> = {}
): Promise<Record<string, string>> {
  const token = await getBearerToken(request);
  return {
    Authorization: `Bearer ${token}`,
    Accept: 'application/json',
    'X-ACCEES-API-Version': config.apiVersion,
    ...customHeaders,
  };
}

