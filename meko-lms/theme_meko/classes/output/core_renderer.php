<?php

namespace theme_meko\output;

defined('MOODLE_INTERNAL') || die();

class core_renderer extends \theme_boost\output\core_renderer {
    public function body_attributes($additionalclasses = []) {
        if ($this->page->context->contextlevel === CONTEXT_COURSE && (int) $this->page->course->id !== SITEID) {
            $additionalclasses = is_array($additionalclasses) ? $additionalclasses : explode(' ', $additionalclasses);
            $additionalclasses[] = 'meko-course-secondary-nav';
        }

        return parent::body_attributes($additionalclasses);
    }
}
