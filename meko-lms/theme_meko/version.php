<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

defined('MOODLE_INTERNAL') || die();

$plugin->version   = 2026111301;              // YYYYMMDDXX
$plugin->requires  = 2022112800;              // Moodle 4.1 LTS+ / 4.3+ / 5.x
$plugin->component = 'theme_meko';            // Moodle Frankenstyle component name
$plugin->maturity  = MATURITY_STABLE;
$plugin->release   = 'v1.7.1 (MEKOSOFT LMS Theme - Merged Devteam Features & MEKO Brand Edition)';

$plugin->dependencies = [
    'theme_boost' => ANY_VERSION,
];

