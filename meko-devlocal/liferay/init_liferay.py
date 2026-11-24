#!/usr/bin/env python3
"""
Initialize Liferay 7.4 GA132 Sites & Pages via JSONWS API:
  - Ensures Site 'TMS' (friendlyURL: /tms) and its /home page exist
  - Prints verified URLs and credentials
"""

import base64
import json
import os
import sys
import time
import urllib.error
import urllib.parse
import urllib.request

BASE_URL = os.environ.get("LIFERAY_INIT_URL", "http://127.0.0.1").rstrip("/")
HOST_HEADER = os.environ.get("DOMAIN", "meko.localhost")
LR_USER = os.environ.get("LIFERAY_ADMIN_USER", "test@liferay.com")
LR_PASS = os.environ.get("LIFERAY_ADMIN_PASS", "test")
MAX_WAIT_SEC = int(os.environ.get("LIFERAY_WAIT_SEC", "480"))

# Bypass any Windows/OS HTTP proxy for local 127.0.0.1 / *.localhost calls
_NO_PROXY_OPENER = urllib.request.build_opener(urllib.request.ProxyHandler({}))


def auth_headers() -> dict:
    token = base64.b64encode(f"{LR_USER}:{LR_PASS}".encode("utf-8")).decode("ascii")
    return {
        "Authorization": f"Basic {token}",
        "Host": HOST_HEADER,
    }


def api_call(path: str, data: dict | None = None, method: str = "GET") -> tuple[int, dict | list | str]:
    url = f"{BASE_URL}{path}"
    headers = auth_headers()
    body = None
    if data is not None:
        body = urllib.parse.urlencode(data).encode("utf-8")
        headers["Content-Type"] = "application/x-www-form-urlencoded"
        method = "POST"

    req = urllib.request.Request(url, data=body, headers=headers, method=method)
    try:
        with _NO_PROXY_OPENER.open(req, timeout=15) as resp:
            raw = resp.read().decode("utf-8", errors="replace")
            try:
                return resp.status, json.loads(raw)
            except json.JSONDecodeError:
                return resp.status, raw
    except urllib.error.HTTPError as e:
        raw = e.read().decode("utf-8", errors="replace")
        return e.code, raw
    except Exception as e:
        return 0, str(e)


def wait_for_liferay() -> bool:
    print(f"[Liferay] Waiting up to {MAX_WAIT_SEC}s for Liferay 7.4 GA132 to boot...")
    deadline = time.time() + MAX_WAIT_SEC
    while time.time() < deadline:
        code, payload = api_call("/api/jsonws/user/get-current-user")
        if code == 200 and isinstance(payload, dict) and "companyId" in payload:
            print(f"[Liferay] Ready! companyId={payload['companyId']}")
            return True
        time.sleep(5)
    print("[Liferay] Timed out waiting for Liferay API.", file=sys.stderr)
    return False


def ensure_tms_site() -> None:
    code, user_info = api_call("/api/jsonws/user/get-current-user")
    if code != 200 or not isinstance(user_info, dict) or "companyId" not in user_info:
        print(f"[WARN] Could not query current user (HTTP {code}): {user_info}")
        return
    company_id = user_info["companyId"]

    code, grp = api_call(
        "/api/jsonws/group/get-friendly-url-group",
        {"companyId": company_id, "friendlyURL": "/tms"},
    )
    if code == 200 and isinstance(grp, dict) and grp.get("groupId"):
        group_id = grp["groupId"]
        print(f"[Liferay] Site '/tms' already exists (groupId={group_id}).")
    else:
        print("[Liferay] Creating Site 'TMS' (/tms)...")
        code, created = api_call(
            "/api/jsonws/group/add-group",
            {
                "parentGroupId": 0,
                "liveGroupId": 0,
                "nameMap": json.dumps({"en_US": "MEKOSOFT TMS Portal", "vi_VN": "Phân hệ TMS"}),
                "descriptionMap": json.dumps({"en_US": "Training Management System (TMS)"}),
                "type": 1,
                "typeSettings": "",
                "manualMembership": "true",
                "membershipRestriction": 0,
                "friendlyURL": "/tms",
                "site": "true",
                "inheritContent": "false",
                "active": "true",
            },
        )
        if code != 200 or not isinstance(created, dict) or not created.get("groupId"):
            print(f"[WARN] group/add-group response (HTTP {code}): {created}")
            return
        group_id = created["groupId"]
        print(f"[Liferay] Created Site 'TMS' (groupId={group_id}).")

    code, layouts = api_call(
        "/api/jsonws/layout/get-layouts",
        {"groupId": group_id, "privateLayout": "false"},
    )
    if code == 200 and isinstance(layouts, list) and len(layouts) > 0:
        print(f"[Liferay] Site '/tms' already has {len(layouts)} public page(s).")
        return

    code, layout = api_call(
        "/api/jsonws/layout/add-layout",
        {
            "groupId": group_id,
            "privateLayout": "false",
            "parentLayoutId": 0,
            "name": "TMS Dashboard",
            "title": "MEKOSOFT TMS - Training Management System",
            "description": "Training Management System Portal",
            "type": "portlet",
            "hidden": "false",
            "friendlyURL": "/home",
        },
    )
    if code == 200 and isinstance(layout, dict) and layout.get("plid"):
        print(f"[Liferay] Created TMS Home Page (/web/tms/home, plid={layout['plid']}).")
    else:
        print(f"[WARN] layout/add-layout response (HTTP {code}): {layout}")


if __name__ == "__main__":
    if wait_for_liferay():
        ensure_tms_site()
