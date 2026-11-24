package vn.mekosoft.tms.setup;

import org.junit.Test;
import static org.junit.Assert.*;

public class TmsPagePolicyTest {
    @Test
    public void learnerAndLecturerCannotViewAdminPages() {
        for (String path : new String[] {"/admin", "/admin/course-plan", "/admin/enrollments", "/admin/import-logs", "/admin/email-templates", "/admin/email-rejected"}) {
            assertFalse(path, TmsPagePolicy.rolesFor(path).contains(TmsPagePolicy.STUDENT));
            assertFalse(path, TmsPagePolicy.rolesFor(path).contains(TmsPagePolicy.LECTURER));
        }
    }

    @Test
    public void officersHaveSeparateResponsibilities() {
        assertEquals(java.util.List.of(TmsPagePolicy.COURSE_MANAGER), TmsPagePolicy.rolesFor("/admin/course-plan"));
        assertEquals(java.util.List.of(TmsPagePolicy.ENROLLMENT_OFFICER), TmsPagePolicy.rolesFor("/admin/enrollments"));
        assertEquals(java.util.List.of(TmsPagePolicy.ENROLLMENT_OFFICER), TmsPagePolicy.rolesFor("/admin/email-templates"));
        assertTrue(TmsPagePolicy.rolesFor("/admin/import-logs").containsAll(java.util.List.of(TmsPagePolicy.COURSE_MANAGER, TmsPagePolicy.ENROLLMENT_OFFICER)));
        assertTrue(TmsPagePolicy.rolesFor("/admin/unconfigured").isEmpty());
    }

    @Test
    public void allSiteRolesCanReachPublicAndLearnerPages() {
        assertEquals(TmsPagePolicy.SITE_ROLES, TmsPagePolicy.rolesFor("/courses"));
        assertEquals(TmsPagePolicy.SITE_ROLES, TmsPagePolicy.rolesFor("/student/my-profile"));
    }
}
