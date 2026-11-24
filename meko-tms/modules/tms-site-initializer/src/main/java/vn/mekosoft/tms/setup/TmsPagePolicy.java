package vn.mekosoft.tms.setup;

import java.util.List;

public final class TmsPagePolicy {
    public static final String STUDENT = "TMS Student";
    public static final String COURSE_MANAGER = "Course Manager";
    public static final String ENROLLMENT_OFFICER = "Enrollment Officer";
    public static final String LECTURER = "Lecturer";
    public static final List<String> SITE_ROLES = List.of(STUDENT, COURSE_MANAGER, ENROLLMENT_OFFICER, LECTURER);

    private TmsPagePolicy() {}

    public static List<String> rolesFor(String path) {
        if (path.equals("/admin/course-plan")) return List.of(COURSE_MANAGER);
        if (path.equals("/admin/enrollments") || path.startsWith("/admin/email-")) return List.of(ENROLLMENT_OFFICER);
        if (path.equals("/admin") || path.equals("/admin/import-logs")) return List.of(COURSE_MANAGER, ENROLLMENT_OFFICER);
        if (path.startsWith("/admin/")) return List.of();
        return SITE_ROLES;
    }
}
