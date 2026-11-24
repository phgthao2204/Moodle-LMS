package vn.mekosoft.tms.setup;

import javax.portlet.PortletPreferences;
import org.junit.Test;
import static org.mockito.Mockito.*;

public class LegacyNavigationMigrationTest {
    @Test
    public void removesOnlyThePreviousInitializerSignature() throws Exception {
        PortletPreferences preferences = mock(PortletPreferences.class);
        when(preferences.getValue("default.landing.page.path", "")).thenReturn("/web/tms/entry");
        when(preferences.getValue("default.logout.page.path", "")).thenReturn("/tms");
        LegacyNavigationMigration.restore(preferences, "/tms");
        verify(preferences).reset("default.landing.page.path");
        verify(preferences).reset("default.logout.page.path");
        verify(preferences).store();
    }

    @Test
    public void preservesExistingCmsConfiguration() throws Exception {
        PortletPreferences preferences = mock(PortletPreferences.class);
        when(preferences.getValue("default.landing.page.path", "")).thenReturn("/web/guest/welcome");
        LegacyNavigationMigration.restore(preferences, "/tms");
        verify(preferences, never()).reset(anyString());
        verify(preferences, never()).store();
    }

    @Test
    public void preservesACustomLogoutEvenWithTheOldLanding() throws Exception {
        PortletPreferences preferences = mock(PortletPreferences.class);
        when(preferences.getValue("default.landing.page.path", "")).thenReturn("/web/tms/entry");
        when(preferences.getValue("default.logout.page.path", "")).thenReturn("/web/guest/goodbye");
        LegacyNavigationMigration.restore(preferences, "/tms");
        verify(preferences, never()).reset(anyString());
        verify(preferences, never()).store();
    }
}
