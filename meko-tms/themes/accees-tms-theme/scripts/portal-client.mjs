import assert from 'node:assert/strict';

function decode(value) {
    return value.replaceAll('&amp;', '&').replaceAll('&quot;', '"').replaceAll('&#39;', "'");
}

function attributes(tag) {
    return Object.fromEntries([...tag.matchAll(/([\w:-]+)="([^"]*)"/g)].map(match => [match[1], decode(match[2])]));
}

export class PortalClient {
    constructor(base) {
        this.base = new URL(base);
        this.cookies = new Map();
    }

    async request(path, form, depth = 0) {
        assert.ok(depth < 12, 'Portal redirect loop');
        const url = new URL(decode(String(path)), this.base);
        assert.equal(url.origin, this.base.origin, 'Test credentials must stay on the configured portal');
        const response = await fetch(url, {
            method: form ? 'POST' : 'GET',
            body: form,
            redirect: 'manual',
            headers: {Cookie: [...this.cookies].map(([key, value]) => `${key}=${value}`).join('; ')}
        });
        for (const cookie of response.headers.getSetCookie()) {
            const pair = cookie.split(';', 1)[0];
            const separator = pair.indexOf('=');
            this.cookies.set(pair.slice(0, separator), pair.slice(separator + 1));
        }
        if ([301, 302, 303, 307, 308].includes(response.status)) {
            return this.request(new URL(response.headers.get('location'), url), [307, 308].includes(response.status) ? form : undefined, depth + 1);
        }
        const body = await response.text();
        if (url.pathname === '/c/portal/logout' && response.status === 200) {
            const target = body.match(/location\.href\s*=\s*'([^']+)'/);
            assert.ok(target, 'Liferay logout must return its redirect target');
            return this.request(target[1].replace(/\\x([0-9a-f]{2})/gi, (_, hex) => String.fromCharCode(parseInt(hex, 16))), undefined, depth + 1);
        }
        for (const meta of body.matchAll(/<meta\b[^>]*>/gi)) {
            const values = attributes(meta[0]);
            const target = values['http-equiv']?.toLowerCase() === 'refresh' && values.content?.match(/;\s*url=(.*)$/i);
            if (response.status === 200 && target) return this.request(new URL(target[1], url), undefined, depth + 1);
        }
        return {body, status: response.status, url};
    }

    async signIn(email, password) {
        const home = await this.request('/tms');
        const signIn = [...home.body.matchAll(/<a\b[^>]*>/g)].map(match => attributes(match[0]))
            .find(link => link.class === 'tms-login');
        assert.ok(signIn, 'Guest must have a portal sign-in link');
        const login = await this.request(signIn.href);
        const form = [...login.body.matchAll(/<form\b([^>]*)>([\s\S]*?)<\/form>/gi)].find(match => /type="password"/.test(match[2]));
        assert.ok(form, 'Native Liferay sign-in form must render');
        const fields = new URLSearchParams();
        for (const input of form[2].matchAll(/<input\b[^>]*>/gi)) {
            const values = attributes(input[0]);
            if (!values.name) continue;
            if (values.type === 'hidden') fields.set(values.name, values.value || '');
            if (values.name.endsWith('_login')) fields.set(values.name, email);
            if (values.type === 'password') fields.set(values.name, password);
        }
        return this.request(new URL(attributes(form[1]).action, login.url), fields);
    }

    async signOut(page) {
        const signOut = [...page.body.matchAll(/<a\b[^>]*>/g)].map(match => attributes(match[0]))
            .find(link => link.class === 'tms-logout');
        assert.ok(signOut, 'Signed-in user must have a logout link');
        const result = await this.request(signOut.href);
        assert.ok(['/tms', '/web/tms', '/web/tms/home'].includes(result.url.pathname), 'Logout must return through /tms to the public TMS site');
        assert.match(result.body, /class="tms-login"/);
        return result;
    }
}
