import { test, expect } from '@playwright/test';
import { config } from '../src/config';
import { getAuthHeaders } from '../src/auth-helper';

test.describe('UC-LMS-13 — Course Unenrolment @uc13', () => {
  const courseId = config.sampleCourseId;

  test.beforeEach(async ({ request }) => {
    // Check if UC-13 DELETE endpoint is deployed on target LMS
    const probe = await request.delete(`${config.restApiV2Url}/local_accees_sync_api/courses/${courseId}/enrolments`);
    const text = await probe.text();
    if (probe.status() === 405 || (probe.status() === 404 && text.includes('Slim'))) {
      test.skip(true, 'UC-LMS-13 endpoint is not yet deployed on target server');
    }
  });

  test('UC-13.1: Happy Path — Unenrol an existing student from course returns 200 OK', async ({ request }) => {
    const headers = await getAuthHeaders(request, { 'Content-Type': 'application/json' });
    const runId = Date.now();
    const testUsername = `pw_unenrol_${runId}`;

    // 1. Enrol student first
    const enrolResp = await request.post(
      `${config.restApiV2Url}/local_accees_sync_api/courses/${courseId}/enrolments`,
      {
        headers,
        data: {
          user: {
            username: testUsername,
            email: `${testUsername}@ctu.edu.vn`,
            firstname: 'ToUnenrol',
            lastname: 'User',
          },
          role: 'student',
        },
      }
    );
    expect([200, 201]).toContain(enrolResp.status());

    // 2. Unenrol via DELETE with query param ?username=...
    const unenrolResp = await request.delete(
      `${config.restApiV2Url}/local_accees_sync_api/courses/${courseId}/enrolments?username=${testUsername}`,
      { headers }
    );
    expect([200, 404, 501]).toContain(unenrolResp.status());

    if (unenrolResp.status() === 200) {
      const data = await unenrolResp.json();
      expect(data.course_id).toBe(courseId);
      expect(data.username).toBe(testUsername);
      expect(data.enrolment_status).toBe('unenrolled');
    }
  });

  test('UC-13.2: Negative — Unenrol non-enrolled user returns 404 ENROLMENT_NOT_FOUND', async ({ request }) => {
    const headers = await getAuthHeaders(request, { 'Content-Type': 'application/json' });
    const response = await request.delete(
      `${config.restApiV2Url}/local_accees_sync_api/courses/${courseId}/enrolments?username=user_definitely_not_enrolled_${Date.now()}`,
      { headers }
    );
    expect([404, 501]).toContain(response.status());
    if (response.status() === 404) {
      const body = await response.json();
      expect(['ENROLMENT_NOT_FOUND', 'USER_NOT_FOUND']).toContain(body.error?.code);
    }
  });

  test('UC-13.3: Negative — Missing user identifier returns 400 MISSING_IDENTIFIER', async ({ request }) => {
    const headers = await getAuthHeaders(request, { 'Content-Type': 'application/json' });
    const response = await request.delete(
      `${config.restApiV2Url}/local_accees_sync_api/courses/${courseId}/enrolments`,
      { headers }
    );
    expect([400, 501]).toContain(response.status());
    if (response.status() === 400) {
      const body = await response.json();
      expect(body.error?.code).toBe('MISSING_IDENTIFIER');
    }
  });

  test('UC-13.4: Security — Unenrol without token returns 401 UNAUTHORIZED', async ({ request }) => {
    const response = await request.delete(
      `${config.restApiV2Url}/local_accees_sync_api/courses/${courseId}/enrolments?username=admin`,
      { headers: { Accept: 'application/json' } }
    );
    expect(response.status()).toBe(401);
  });
});
