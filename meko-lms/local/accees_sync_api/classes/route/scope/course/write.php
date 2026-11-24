<?php
namespace local_accees_sync_api\route\scope\course;

/** OAuth scope for updating ACCEES courses. */
#[\core\router\scope\identifier_attribute('write')]
#[\core\router\scope\summary_attribute('scope_course_write_summary', 'local_accees_sync_api')]
#[\core\router\scope\description_attribute('scope_course_write_description', 'local_accees_sync_api')]
class write extends abstract_scope {
}
