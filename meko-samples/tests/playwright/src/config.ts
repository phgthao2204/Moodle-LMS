import path from 'path';

export const config = {
  get lmsBaseUrl(): string {
    return (process.env.LMS_BASE_URL || 'http://meko.localhost/lms').replace(/\/+$/, '');
  },

  get restApiV2Url(): string {
    return `${this.lmsBaseUrl}/api/rest/v2`;
  },

  get oauthTokenUrl(): string {
    return `${this.lmsBaseUrl}/oauth2/token`;
  },

  get apiVersion(): string {
    return process.env.ACCEES_API_VERSION || '1.0';
  },

  get clientId(): string {
    return process.env.OAUTH_CLIENT_ID || 'accees-dev-client';
  },

  get clientSecret(): string {
    return process.env.OAUTH_CLIENT_SECRET || '';
  },

  get oauthScopes(): string {
    return (
      process.env.OAUTH_SCOPES ||
      'local_accees_imscp:imscp:import local_accees_imscp:imscp:export local_accees_sync_api:course:read local_accees_sync_api:course:write local_accees_sync_api:enrol:manage local_accees_sync_api:result:read'
    );
  },

  get debugPat(): string {
    return process.env.DEBUG_PAT || '';
  },

  get sampleCourseId(): number {
    return parseInt(process.env.SAMPLE_COURSE_ID || '2', 10);
  },

  get testCategoryId(): number {
    return parseInt(process.env.TEST_CATEGORY_ID || '2', 10);
  },

  get webhookReceiverUrl(): string {
    return process.env.WEBHOOK_RECEIVER_URL || 'http://localhost:8080/webhook';
  },

  get webhookSecret(): string {
    return process.env.WEBHOOK_SECRET || 'default_webhook_secret_key';
  },

  resolveFixture(...segments: string[]): string {
    // meko-samples/fixtures is 3 levels up from meko-samples/tests/playwright/src
    return path.resolve(__dirname, '../../../fixtures', ...segments);
  },
};

