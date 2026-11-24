import { test, expect } from '@playwright/test';
import crypto from 'crypto';
import { config } from '../src/config';
import { getAuthHeaders } from '../src/auth-helper';

test.describe('UC-LMS-09 — Export Course as .imscc Package @uc09 @deployed', () => {
  const courseId = config.sampleCourseId;

  test('UC-09.1: Happy Path — Export course with delivery=download_url and verify artifact', async ({ request }) => {
    const headers = await getAuthHeaders(request, { 'Content-Type': 'application/json' });
    const exportResponse = await request.post(
      `${config.restApiV2Url}/local_accees_imscp/courses/${courseId}/export`,
      {
        headers,
        data: {
          include_userdata: false,
          delivery: 'download_url',
        },
      }
    );

    expect([200, 202]).toContain(exportResponse.status());
    const exportData = await exportResponse.json();

    expect(exportData.course_id).toBe(courseId);
    expect(exportData).toHaveProperty('filename');
    expect(exportData).toHaveProperty('download_url');
    expect(exportData).toHaveProperty('checksum_sha256');

    // Download the generated package
    const downloadUrl = exportData.download_url;
    const downloadResp = await request.get(downloadUrl, {
      headers: {
        Authorization: headers.Authorization,
      },
    });

    expect(downloadResp.status()).toBe(200);
    const bodyBuffer = await downloadResp.body();
    expect(bodyBuffer.length).toBeGreaterThan(0);

    // Verify SHA-256 checksum matches
    if (exportData.checksum_sha256) {
      const actualHash = crypto.createHash('sha256').update(bodyBuffer).digest('hex');
      expect(actualHash.toLowerCase()).toBe(exportData.checksum_sha256.toLowerCase());
    }
  });

  test('UC-09.2: Happy Path — Export course with delivery=binary returns raw zip package', async ({ request }) => {
    const headers = await getAuthHeaders(request, { 'Content-Type': 'application/json' });
    const response = await request.post(
      `${config.restApiV2Url}/local_accees_imscp/courses/${courseId}/export`,
      {
        headers,
        data: {
          include_userdata: false,
          delivery: 'binary',
        },
      }
    );

    expect(response.status()).toBe(200);
    const buffer = await response.body();
    expect(buffer.length).toBeGreaterThan(0);
    // Check ZIP magic header bytes (PK\x03\x04)
    expect(buffer[0]).toBe(0x50);
    expect(buffer[1]).toBe(0x4b);
  });

  test('UC-09.3: Negative — Export non-existent course ID returns 404 COURSE_NOT_FOUND', async ({ request }) => {
    const headers = await getAuthHeaders(request, { 'Content-Type': 'application/json' });
    const response = await request.post(
      `${config.restApiV2Url}/local_accees_imscp/courses/999999/export`,
      {
        headers,
        data: { delivery: 'download_url' },
      }
    );
    expect(response.status()).toBe(404);
    const body = await response.json();
    expect(body.error?.code).toBe('COURSE_NOT_FOUND');
  });

  test('UC-09.4: Security — Export endpoint without token returns 401 UNAUTHORIZED', async ({ request }) => {
    const response = await request.post(
      `${config.restApiV2Url}/local_accees_imscp/courses/${courseId}/export`,
      {
        headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
        data: { delivery: 'download_url' },
      }
    );
    expect(response.status()).toBe(401);
  });
});

