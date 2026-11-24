import { test, expect } from '@playwright/test';
import { config } from '../src/config';
import { getAuthHeaders } from '../src/auth-helper';

test.describe('UC-LMS-12 — Course Enrolments (Create) @uc12', () => {
  const courseId = config.sampleCourseId;

  test('UC-12.1: Happy Path — Auto-create new user and enrol with role student', async ({ request }) => {
    const headers = await getAuthHeaders(request, { 'Content-Type': 'application/json' });
    const runId = Date.now();
    const newUser = {
      username: `pw_user_${runId}`,
      email: `pw_user_${runId}@ctu.edu.vn`,
      firstname: 'Playwright',
      lastname: 'Tester',
      idnumber: `PW${runId.toString().slice(-6)}`,
    };

    const response = await request.post(
      `${config.restApiV2Url}/local_accees_sync_api/courses/${courseId}/enrolments`,
      {
        headers,
        data: {
          user: newUser,
          role: 'student',
          timestart: 0,
          timeend: 0,
          strict_sso: false,
        },
      }
    );

    expect([200, 201]).toContain(response.status());
    const data = await response.json();
    expect(data.course_id).toBe(courseId);
    expect(data.username).toBe(newUser.username);
    expect(data.role).toBe('student');
    expect(data.user_created).toBe(true);
    expect(data.enrolment_status).toBe('enrolled');
  });

  test('UC-12.2: Happy Path — Idempotent re-enrolment returns already_enrolled', async ({ request }) => {
    const headers = await getAuthHeaders(request, { 'Content-Type': 'application/json' });
    const runId = Date.now();
    const payload = {
      user: {
        username: `pw_idem_${runId}`,
        email: `pw_idem_${runId}@ctu.edu.vn`,
        firstname: 'Idem',
        lastname: 'Check',
      },
      role: 'student',
      strict_sso: false,
    };

    // First call -> creates and enrols
    const firstResp = await request.post(
      `${config.restApiV2Url}/local_accees_sync_api/courses/${courseId}/enrolments`,
      { headers, data: payload }
    );
    expect([200, 201]).toContain(firstResp.status());
    const firstData = await firstResp.json();
    expect(firstData.enrolment_status).toBe('enrolled');

    // Second call -> returns already_enrolled
    const secondResp = await request.post(
      `${config.restApiV2Url}/local_accees_sync_api/courses/${courseId}/enrolments`,
      { headers, data: payload }
    );
    expect(secondResp.status()).toBe(200);
    const secondData = await secondResp.json();
    expect(secondData.user_created).toBe(false);
    expect(secondData.enrolment_status).toBe('already_enrolled');
  });

  test('UC-12.3: Happy Path — Enrol user with editingteacher role', async ({ request }) => {
    const headers = await getAuthHeaders(request, { 'Content-Type': 'application/json' });
    const runId = Date.now();
    const payload = {
      user: {
        username: `pw_teacher_${runId}`,
        email: `pw_teacher_${runId}@ctu.edu.vn`,
        firstname: 'Teacher',
        lastname: 'Accees',
      },
      role: 'editingteacher',
      strict_sso: false,
    };

    const response = await request.post(
      `${config.restApiV2Url}/local_accees_sync_api/courses/${courseId}/enrolments`,
      { headers, data: payload }
    );
    expect([200, 201]).toContain(response.status());
    const data = await response.json();
    expect(data.role).toBe('editingteacher');
  });

  test('UC-12.4: Negative — strict_sso=true with non-existent user returns 404 USER_NOT_FOUND', async ({ request }) => {
    const headers = await getAuthHeaders(request, { 'Content-Type': 'application/json' });
    const response = await request.post(
      `${config.restApiV2Url}/local_accees_sync_api/courses/${courseId}/enrolments`,
      {
        headers,
        data: {
          user: {
            username: `non_existent_${Date.now()}`,
            email: `non_existent_${Date.now()}@example.com`,
          },
          role: 'student',
          strict_sso: true,
        },
      }
    );

    expect(response.status()).toBe(404);
    const body = await response.json();
    expect(body.error?.code).toBe('USER_NOT_FOUND');
  });

  test('UC-12.5: Negative — Missing required user fields for auto-create returns 400 MISSING_USER_FIELDS', async ({ request }) => {
    const headers = await getAuthHeaders(request, { 'Content-Type': 'application/json' });
    const response = await request.post(
      `${config.restApiV2Url}/local_accees_sync_api/courses/${courseId}/enrolments`,
      {
        headers,
        data: {
          user: {
            email: `incomplete_${Date.now()}@example.com`,
            // missing firstname and lastname
          },
          role: 'student',
          strict_sso: false,
        },
      }
    );

    expect(response.status()).toBe(400);
    const body = await response.json();
    expect(body.error?.code).toBe('MISSING_USER_FIELDS');
  });

  test('UC-12.6: Negative — Invalid or non-existent role returns 404 ROLE_NOT_FOUND', async ({ request }) => {
    const headers = await getAuthHeaders(request, { 'Content-Type': 'application/json' });
    const response = await request.post(
      `${config.restApiV2Url}/local_accees_sync_api/courses/${courseId}/enrolments`,
      {
        headers,
        data: {
          user: {
            username: `sv_role_err_${Date.now()}`,
            email: `sv_role_err_${Date.now()}@ctu.edu.vn`,
            firstname: 'A',
            lastname: 'B',
          },
          role: 'non_existent_invalid_role_xyz',
          strict_sso: false,
        },
      }
    );

    expect(response.status()).toBe(404);
    const body = await response.json();
    expect(body.error?.code).toBe('ROLE_NOT_FOUND');
  });

  test('UC-12.7: Security — Call enrolments without token returns 401 UNAUTHORIZED', async ({ request }) => {
    const response = await request.post(
      `${config.restApiV2Url}/local_accees_sync_api/courses/${courseId}/enrolments`,
      {
        headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
        data: { user: { email: 'test@example.com' } },
      }
    );
    expect(response.status()).toBe(401);
  });
});

