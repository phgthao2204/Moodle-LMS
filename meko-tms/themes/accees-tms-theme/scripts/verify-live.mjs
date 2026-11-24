import assert from 'node:assert/strict';
import {PortalClient} from './portal-client.mjs';

const base = process.env.LIFERAY_TEST_URL;
if (!base) throw new Error('Set LIFERAY_TEST_URL to the local portal URL');
const demo = process.env.TMS_DEMO_MODE === 'true';
const home = await (await fetch(new URL('/web/tms/home', base))).text();
assert.ok(home.includes('logo_ctu.png') && home.includes('101179860'), 'ACCEES/CTU branding must render');
assert.equal(home.includes('data-demo-page="home"'), demo, 'Demo must be explicitly enabled for fixture checks');
const lmsLinks = [...home.matchAll(/<a\b[^>]*href="[^"]*\/lms\/[^"]*"[^>]*>/g)];
assert.ok(lmsLinks.length >= 2, 'Header and footer expose LMS links');
for (const match of lmsLinks) assert.match(match[0], /\bdata-senna-off="true"/);
const signIn = [...home.matchAll(/<a\b[^>]*>/g)].find(match => match[0].includes('class="tms-login"'))?.[0].match(/href="([^"]+)"/)?.[1].replaceAll('&amp;', '&');
assert.ok(signIn?.includes('redirect='), 'TMS login must have its own scoped destination');
const login = await (await fetch(new URL(signIn, base))).text();
assert.ok(login.includes('type="password"'), 'Native sign-in form must render');
const adminPages = new Map([
    ['/admin', 'admin'], ['/admin/enrollments', 'enrollments'], ['/admin/course-plan', 'plan'],
    ['/admin/import-logs', 'logs'], ['/admin/email-templates', 'email'], ['/admin/email-rejected', 'email-rejected']
]);
for (const route of [...adminPages.keys(), '/my-profile']) {
    const response = await fetch(new URL('/web/tms' + route, base), {redirect: 'manual'});
    assert.ok([403, 404].includes(response.status), `${route}: guest must be denied, got ${response.status}`);
}
if (demo) {
    const verify = await (await fetch(new URL('/web/tms/verify', base))).text();
    assert.ok(verify.includes('MEKO-2026-00018'), 'Demo certificate must render');
}
console.log(`PASS: guest identity, scoped login, restricted routes and demo-mode=${demo}`);

async function checkRole(prefix, landingPath, allowed) {
    const email = process.env[`TMS_${prefix}_EMAIL`];
    const password = process.env[`TMS_${prefix}_PASSWORD`];
    if (!email || !password) return;
    const client = new PortalClient(base);
    const landing = await client.signIn(email, password);
    assert.equal(landing.url.pathname, '/web/tms' + landingPath, `${prefix} landing`);
    assert.ok(landing.body.includes('href="/web/tms/my-profile"'), `${prefix}: canonical profile menu must be visible`);
    const profile = await client.request('/web/tms/my-profile');
    assert.equal(profile.status, 200, `${prefix} learner access`);
    assert.ok(!profile.body.includes('class="tms-sidebar"'), 'Learner space must not show admin sidebar');
    for (const [route, fixture] of adminPages) {
        const page = await client.request('/web/tms' + route);
        if (!allowed.includes(route)) {
            assert.ok([403, 404].includes(page.status), `${prefix} must not open ${route}`);
            assert.ok(!landing.body.includes(`href="/web/tms${route}"`), `${prefix} must not see forbidden menu ${route}`);
            continue;
        }
        assert.equal(page.status, 200, `${prefix}: ${route}`);
        assert.ok(page.body.includes('accees-tms tms-admin') && page.body.includes('class="tms-sidebar"'), route);
        assert.equal(page.body.includes(`data-demo-page="${fixture}"`), demo, `${route}: demo setting`);
    }
    await client.signOut(landing);
    console.log(`PASS: ${prefix} landing, page permissions, sidebar and TMS logout`);
}
await checkRole('ADMIN', '/home', [...adminPages.keys()]);
await checkRole('STUDENT', '/home', []);
await checkRole('LECTURER', '/home', []);
await checkRole('COURSE_MANAGER', '/home', ['/admin', '/admin/course-plan', '/admin/import-logs']);
await checkRole('ENROLLMENT_OFFICER', '/home', ['/admin', '/admin/enrollments', '/admin/import-logs', '/admin/email-templates', '/admin/email-rejected']);
