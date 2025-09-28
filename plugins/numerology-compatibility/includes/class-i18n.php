<?php
// includes/class-i18n.php

namespace NC;

class I18n {

    /**
     * Load plugin text domain
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            'numerology-compatibility',
            false,
            dirname(dirname(plugin_basename(__FILE__))) . '/languages/'
        );
    }
}