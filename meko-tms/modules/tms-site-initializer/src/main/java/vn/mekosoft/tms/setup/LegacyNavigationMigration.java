package vn.mekosoft.tms.setup;

import javax.portlet.PortletPreferences;

public final class LegacyNavigationMigration {
    private LegacyNavigationMigration() {}

    public static void restore(PortletPreferences preferences, String sitePath) throws Exception {
        String landingKey = "default.landing.page.path";
        String logoutKey = "default.logout.page.path";
        if (!("/web" + sitePath + "/entry").equals(preferences.getValue(landingKey, "")) ||
            !sitePath.equals(preferences.getValue(logoutKey, ""))) return;
        preferences.reset(landingKey);
        preferences.reset(logoutKey);
        preferences.store();
    }
}
