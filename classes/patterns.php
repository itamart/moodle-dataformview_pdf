<?php
// This file is part of Moodle - http://moodle.org/.
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package dataformview_pdf
 * @copyright 2014 Itamar Tzadok
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') or die;

/**
 * Specialized class for pdf view patterns.
 */
class dataformview_pdf_patterns extends mod_dataform\pluginbase\dataformviewpatterns {

    /**
     *
     */
    public function get_replacements(array $patterns, $entry = null, array $options = array()) {
        global $CFG, $OUTPUT;

        $replacements = parent::get_replacements($patterns, $entry, $options);

        $view = $this->_view;
        $df = $view->get_df();
        $filter = $view->get_filter();
        $baseurl = new moodle_url($view->get_baseurl());
        $baseurl->param('sesskey', sesskey());

        foreach ($patterns as $pattern) {
            switch ($pattern) {
                case '##exportall##':
                    $actionurl = new moodle_url($baseurl, array('pdfexportall' => true));
                    $label = html_writer::tag('span', get_string('exportall', 'dataformview_pdf'));
                    $replacements[$pattern] = html_writer::link($actionurl, $label, array('class' => 'actionlink exportall'));

                    break;
                case '##exportpage##':
                    $actionurl = new moodle_url($baseurl, array('pdfexportpage' => true));
                    $label = html_writer::tag('span', get_string('exportpage', 'dataformview_pdf'));
                    $replacements[$pattern] = html_writer::link($actionurl, $label, array('class' => 'actionlink exportpage'));

                    break;
                case '##pagebreak##':
                    $replacements[$pattern] = $view::PAGE_BREAK;

                    break;
            }
        }

        return $replacements;
    }

    /**
     *
     */
    protected function patterns() {
        $patterns = parent::patterns();
        $cat = get_string('pluginname', 'dataformview_pdf');
        $patterns['##exportall##'] = array(true, $cat);
        $patterns['##exportpage##'] = array(true, $cat);
        $patterns['##pagebreak##'] = array(true, $cat);

        return $patterns;
    }
}
