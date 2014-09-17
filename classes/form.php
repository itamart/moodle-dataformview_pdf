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

class dataformview_pdf_form extends mod_dataform\pluginbase\dataformviewform {

    /**
     *
     */
    protected function definition_view_specific() {
        /* View template */
        $this->definition_view_template();

        /* Entry template */
        $this->definition_entry_template();

        /* Submission settings */
        $this->definition_view_submission();

        /* PDF settings */
        $this->definition_pdf_settings();
    }

    /**
     *
     */
    protected function definition_entry_template() {
        $view = $this->_view;
        $editoroptions = $view->editoroptions;

        $mform = &$this->_form;

        /* Header */
        $mform->addElement('header', 'entrytemplatehdr', get_string('entrytemplate', 'dataform'));
        $mform->addHelpButton('entrytemplatehdr', 'entrytemplate', 'dataform');

        /* Template editor (param2) */
        $mform->addElement('editor', 'param2_editor', get_string('entrytemplate', 'dataform'), null, $editoroptions);
        $this->add_patterns_selectors('param2_editor', array('view', 'field'));
    }

    /**
     *
     */
    protected function definition_pdf_settings() {
        $this->definition_pdf_general();
        $this->definition_pdf_toc();
        $this->definition_pdf_frame();
        $this->definition_pdf_watermark();
        $this->definition_pdf_header();
        $this->definition_pdf_footer();
        $this->definition_pdf_margin_paging();
        $this->definition_pdf_protection();
        $this->definition_pdf_digital_signature();
        $this->definition_pdf_exclude_patterns();
    }

    /**
     *
     */
    protected function definition_pdf_general() {
        $mform = &$this->_form;

        $mform->addElement('header', 'pdfsettingshdr', get_string('pdfsettings', 'dataformview_pdf'));
        /* Document name */
        $mform->addElement('text', 'docname', get_string('docname', 'dataformview_pdf'), array('size' => 64));
        $mform->setType('docname', PARAM_TEXT);
        $mform->addHelpButton('docname', 'docname', 'dataformview_pdf');
        /* Orientation: Portrait, Landscape */
        $options = array(
            '' => get_string('auto', 'dataformview_pdf'),
            'P' => get_string('portrait', 'dataformview_pdf'),
            'L' => get_string('landscape', 'dataformview_pdf'),
        );
        $mform->addElement('select', 'orientation', get_string('orientation', 'dataformview_pdf'), $options);
        /* Unit */
        $options = array(
            'mm' => get_string('unit_mm', 'dataformview_pdf'),
            'pt' => get_string('unit_pt', 'dataformview_pdf'),
            'cm' => get_string('unit_cm', 'dataformview_pdf'),
            'in' => get_string('unit_in', 'dataformview_pdf'),
        );
        $mform->addElement('select', 'unit', get_string('unit', 'dataformview_pdf'), $options);
        /* Format */
        $options = array(
            'A4' => get_string('A4', 'dataformview_pdf'),
            'LETTER' => get_string('LETTER', 'dataformview_pdf'),
        );
        $mform->addElement('select', 'format', get_string('format', 'dataformview_pdf'), $options);
        /* Destination */
        $options = array(
            'D' => get_string('dest_D', 'dataformview_pdf'),
            'I' => get_string('dest_I', 'dataformview_pdf'),
            /* 'F' => get_string('dest_F', 'dataformview_pdf'), */
            /* 'FI' => get_string('dest_FI', 'dataformview_pdf'), */
            /* 'FD' => get_string('dest_FD', 'dataformview_pdf'), */
        );
        $mform->addElement('select', 'destination', get_string('destination', 'dataformview_pdf'), $options);
    }

    /**
     *
     */
    protected function definition_pdf_toc() {
        $mform = &$this->_form;

        $mform->addElement('header', 'pdftochdr', get_string('pdftoc', 'dataformview_pdf'));
        /* Page */
        $mform->addElement('text', 'tocpage', get_string('tocpage', 'dataformview_pdf'));
        $mform->setType('tocpage', PARAM_INT);
        $mform->addHelpButton('tocpage', 'tocpage', 'dataformview_pdf');
        /* Name */
        $mform->addElement('text', 'tocname', get_string('tocname', 'dataformview_pdf'));
        $mform->setType('tocname', PARAM_TEXT);
        $mform->addHelpButton('tocname', 'tocname', 'dataformview_pdf');
        /* Title */
        $attrs = array('rows' => 3, 'style' => 'width:100%');
        $mform->addElement('textarea', 'toctitle', get_string('toctitle', 'dataformview_pdf'), $attrs);
        $mform->addHelpButton('toctitle', 'toctitle', 'dataformview_pdf');
        /* Template */
        $attrs = array('rows' => 10, 'style' => 'width:100%');
        $mform->addElement('textarea', 'toctmpl', get_string('toctmpl', 'dataformview_pdf'), $attrs);
        $mform->addHelpButton('toctmpl', 'toctmpl', 'dataformview_pdf');
    }

    /**
     *
     */
    protected function definition_pdf_frame() {
        $mform = &$this->_form;

        $mform->addElement('header', 'pdfframehdr', get_string('pdfframe', 'dataformview_pdf'));

        $fileoptions = array('subdirs' => 0, 'maxbytes' => -1, 'maxfiles' => 1, 'accepted_types' => array('image'));
        $mform->addElement('filemanager', 'pdfframe', get_string('image', 'dataformview_pdf'), null, $fileoptions);
        $mform->addHelpButton('pdfframe', 'pdfframe', 'dataformview_pdf');
    }

    /**
     *
     */
    protected function definition_pdf_watermark() {
        $mform = &$this->_form;

        $mform->addElement('header', 'pdfwatermarkhdr', get_string('pdfwmark', 'dataformview_pdf'));

        $fileoptions = array('subdirs' => 0, 'maxbytes' => -1, 'maxfiles' => 1, 'accepted_types' => array('image'));
        /* Watermark image */
        $mform->addElement('filemanager', 'pdfwmark', get_string('image', 'dataformview_pdf'), null, $fileoptions);
        $mform->addHelpButton('pdfwmark', 'pdfwmark', 'dataformview_pdf');
        /* Watermark Transparency */
        $transunits = range(0, 1, 0.1);
        $options = array_combine($transunits, $transunits);
        $mform->addElement('select', 'transparency', get_string('transparency', 'dataformview_pdf'), $options);
        $mform->addHelpButton('transparency', 'transparency', 'dataformview_pdf');
    }

    /**
     *
     */
    protected function definition_pdf_header() {
        $mform = &$this->_form;
        $editoroptions = $this->_view->editoroptions;
        $editorattr = array('cols' => 40, 'rows' => 12);

        $mform->addElement('header', 'pdfheaderhdr', get_string('pdfheader', 'dataformview_pdf'));

        /* Header enbabled */
        $mform->addElement('selectyesno', 'headerenabled', get_string('enabled', 'dataformview_pdf'));
        /* Header content (param3) */
        $mform->addElement('editor', 'param3_editor', get_string('content'), $editorattr, $editoroptions);
        /* Header margin top */
        $label = get_string('margin', 'dataformview_pdf'). ' '. get_string('margintop', 'dataformview_pdf');
        $mform->addElement('text', 'headermargintop', $label);
        $mform->setType('headermargintop', PARAM_INT);
        $mform->addRule('headermargintop', null, 'numeric', null, 'client');
        $mform->disabledIf('headermargintop', 'headerenabled', 'eq', 0);
        /* Header margin left */
        $label = get_string('margin', 'dataformview_pdf'). ' '. get_string('marginleft', 'dataformview_pdf');
        $mform->addElement('text', 'headermarginleft', $label);
        $mform->setType('headermarginleft', PARAM_INT);
        $mform->addRule('headermarginleft', null, 'numeric', null, 'client');
        $mform->disabledIf('headermarginleft', 'headerenabled', 'eq', 0);
    }

    /**
     *
     */
    protected function definition_pdf_footer() {
        $mform = &$this->_form;
        $editoroptions = $this->_view->editoroptions;
        $editorattr = array('cols' => 40, 'rows' => 12);

        $mform->addElement('header', 'pdffooterhdr', get_string('pdffooter', 'dataformview_pdf'));

        /* Footer enbabled */
        $mform->addElement('selectyesno', 'footerenabled', get_string('enabled', 'dataformview_pdf'));
        /* Footer content (param4) */
        $mform->addElement('editor', 'param4_editor', get_string('content'), $editorattr, $editoroptions);
        /* Footer margin */
        $options = array_combine(range(1, 30), range(1, 30));
        $mform->addElement('select', 'footermargin', get_string('margin', 'dataformview_pdf'), $options);
        $mform->disabledIf('footermargin', 'footerenabled', 'eq', 0);
    }

    /**
     *
     */
    protected function definition_pdf_margin_paging() {
        $mform = &$this->_form;
        $mform->addElement('header', 'pdfmarginshdr', get_string('pdfmargins', 'dataformview_pdf'));

        $mform->addElement('text', 'marginleft', get_string('marginleft', 'dataformview_pdf'));
        $mform->setType('marginleft', PARAM_INT);
        $mform->addElement('text', 'margintop', get_string('margintop', 'dataformview_pdf'));
        $mform->setType('margintop', PARAM_INT);
        $mform->addElement('text', 'marginright', get_string('marginright', 'dataformview_pdf'));
        $mform->setType('marginright', PARAM_INT);
        $mform->addElement('selectyesno', 'marginkeep', get_string('marginkeep', 'dataformview_pdf'));

        /* Page break */
        $options = array(
            '' => get_string('none'),
            'auto' => get_string('auto', 'dataformview_pdf'),
            'entry' => get_string('entry', 'dataform')
        );
        $mform->addElement('select', 'pagebreak', get_string('pagebreak', 'dataformview_pdf'), $options);
    }

    /**
     *
     */
    protected function definition_pdf_protection() {
        $mform = &$this->_form;
        $view = &$this->_view;

        $mform->addElement('header', 'pdfprotectionhdr', get_string('pdfprotection', 'dataformview_pdf'));

        /* Permissions */
        $perms = $view::get_permission_options();
        foreach ($perms as $perm => $label) {
            $elemgrp[] = &$mform->createElement('advcheckbox', "perm_$perm", null, $label, null, array('', $perm));
        }
        $mform->addGroup($elemgrp, "perms_grp", get_string('protperms', 'dataformview_pdf'), '<br />', false);

        /* User Password */
        $mform->addElement('text', 'protuserpass', get_string('protuserpass', 'dataformview_pdf'));
        $mform->setType('protuserpass', PARAM_TEXT);

        /* Owner Password */
        $mform->addElement('text', 'protownerpass', get_string('protownerpass', 'dataformview_pdf'));
        $mform->setType('protownerpass', PARAM_TEXT);

        /* Mode */
        $options = array(
            '' => get_string('choosedots'),
            0 => get_string('protmode0', 'dataformview_pdf'),
            1 => get_string('protmode1', 'dataformview_pdf'),
            2 => get_string('protmode2', 'dataformview_pdf'),
            3 => get_string('protmode3', 'dataformview_pdf'),
        );
        $mform->addElement('select', 'protmode', get_string('protmode', 'dataformview_pdf'), $options);

        /* Pub keys */
        /* ... */
    }

    /**
     *
     */
    protected function definition_pdf_digital_signature() {
        $mform = &$this->_form;

        $mform->addElement('header', 'pdfsignaturehdr', get_string('pdfsignature', 'dataformview_pdf'));

        /* Certification */
        $fileoptions = array('subdirs' => 0, 'maxbytes' => -1, 'maxfiles' => 1, 'accepted_types' => array('.crt'));
        $mform->addElement('filemanager', 'pdfcert', get_string('certification', 'dataformview_pdf'), null, $fileoptions);

        /* Password */
        $mform->addElement('text', 'certpassword', get_string('certpassword', 'dataformview_pdf'));
        $mform->setType('certpassword', PARAM_TEXT);

        /* Type */
        $options = array(
            1 => get_string('none'),
            2 => get_string('certperm2', 'dataformview_pdf'),
            3 => get_string('certperm3', 'dataformview_pdf'),
        );
        $mform->addElement('select', 'certtype', get_string('certtype', 'dataformview_pdf'), $options);

        /* Info */
        $mform->addElement('text', 'certinfoname', get_string('certinfoname', 'dataformview_pdf'));
        $mform->setType('certinfoname', PARAM_TEXT);

        $mform->addElement('text', 'certinfoloc', get_string('certinfoloc', 'dataformview_pdf'));
        $mform->setType('certinfoloc', PARAM_TEXT);

        $mform->addElement('text', 'certinforeason', get_string('certinforeason', 'dataformview_pdf'));
        $mform->setType('certinforeason', PARAM_TEXT);

        $mform->addElement('text', 'certinfocontact', get_string('certinfocontact', 'dataformview_pdf'));
        $mform->setType('certinfocontact', PARAM_TEXT);
    }

    /**
     *
     */
    protected function definition_pdf_exclude_patterns() {
        $mform = &$this->_form;

        $mform->addElement('header', 'pdfnoexportpatternshdr', get_string('noexportpatterns', 'dataformview_pdf'));

        /* Patterns */
        $attrs = array('rows' => 3, 'style' => 'width:100%');
        $mform->addElement('textarea', 'noexportpatterns', get_string('patterns', 'dataform'), $attrs);
        $mform->addHelpButton('noexportpatterns', 'noexportpatterns', 'dataformview_pdf');
    }

    /**
     *
     */
    public function data_preprocessing(&$data) {
        parent::data_preprocessing($data);

        $view = $this->_view;

        /* Pdf settings */
        if ($settings = $view->pdf_settings) {
            foreach ($settings as $name => $value) {
                if ($name == 'header') {
                    $data->headerenabled = $settings->header->enabled;
                    $data->headermargintop = $settings->header->margintop;
                    $data->headermarginleft = $settings->header->marginleft;
                } else if ($name == 'footer') {
                    $data->footerenabled = $settings->footer->enabled;
                    $data->footermargin = $settings->footer->margin;
                } else if ($name == 'margins') {
                    $data->marginleft = $settings->margins->left;
                    $data->margintop = $settings->margins->top;
                    $data->marginright = $settings->margins->right;
                    $data->marginkeep = $settings->margins->keep;
                } else if ($name == 'toc') {
                    $data->tocpage = $settings->toc->page;
                    $data->tocname = $settings->toc->name;
                    $data->toctitle = $settings->toc->title;
                    $data->toctmpl = $settings->toc->template;
                } else if ($name == 'protection') {
                    $this->data_preprocess_protection($data, $value);
                } else if ($name == 'signature') {
                    $this->data_preprocess_signature($data, $value);
                } else if ($name == 'noexportpatterns') {
                    $data->noexportpatterns = implode("\n", $settings->noexportpatterns);
                } else {
                    $data->$name = $value;
                }
            }
        }
    }

    /**
     *
     */
    protected function data_preprocess_protection(&$data, $protection) {
        $view = $this->_view;
        $perms = $view::get_permission_options();
        foreach ($perms as $perm => $unused) {
            if (in_array($perm, $protection->permissions)) {
                $var = "perm_$perm";
                $data->$var = $perm;
            }
        }
        $data->protuserpass = $protection->user_pass;
        $data->protownerpass = $protection->owner_pass;
        $data->protmode = $protection->mode;
    }

    /**
     *
     */
    protected function data_preprocess_signature(&$data, $signsettings) {
        $data->certpassword = $signsettings->password;
        $data->certtype = $signsettings->type;
        $data->certinfoname = $signsettings->info['Name'];
        $data->certinfoloc = $signsettings->info['Location'];
        $data->certinforeason = $signsettings->info['Reason'];
        $data->certinfocontact = $signsettings->info['ContactInfo'];
    }

    /**
     *
     */
    public function get_data() {
        if (!$data = parent::get_data()) {
            return null;
        }

        /* Pdf settings */
        $view = $this->_view;
        if ($settings = $view->pdf_settings) {
            foreach ($settings as $name => $value) {
                if ($name == 'header') {
                    $settings->header->enabled = $data->headerenabled;
                    if ($data->headerenabled) {
                        $settings->header->margintop = $data->headermargintop;
                        $settings->header->marginleft = $data->headermarginleft;
                    }
                } else if ($name == 'footer') {
                    $settings->footer->enabled = $data->footerenabled;
                    if ($data->footerenabled) {
                        $settings->footer->margin = $data->footermargin;
                    }
                } else if ($name == 'margins') {
                    $settings->margins->left = $data->marginleft;
                    $settings->margins->top = $data->margintop;
                    $settings->margins->right = $data->marginright;
                    $settings->margins->keep = $data->marginkeep;
                } else if ($name == 'toc') {
                    $settings->toc->page = $data->tocpage;
                    $settings->toc->name = $data->tocname;
                    $settings->toc->title = $data->toctitle;
                    $settings->toc->template = $data->toctmpl;
                } else if ($name == 'protection') {
                    $this->data_postprocess_protection($settings, $data);
                } else if ($name == 'signature') {
                    $this->data_postprocess_signature($settings, $data);
                } else if ($name == 'noexportpatterns') {
                    $settings->noexportpatterns = array_map('trim', explode("\n", $data->noexportpatterns));
                } else if (isset($data->$name)) {
                    $settings->$name = $data->$name;
                }
            }

            $data->param1 = serialize($settings);
        }

        return $data;
    }

    /**
     *
     */
    protected function data_postprocess_protection(&$settings, $data) {
        $view = $this->_view;

        $protection = $settings->protection;
        $protection->permissions = array();
        $perms = $view::get_permission_options();
        foreach ($perms as $perm => $unused) {
            $var = "perm_$perm";
            if (!empty($data->$var)) {
                $protection->permissions[] = $perm;
            }
        }
        $protection->user_pass = $data->protuserpass;
        $protection->owner_pass = $data->protownerpass;
        $protection->mode = $data->protmode;
        /* data->pubkeys = protection->pubkeys; */

        $settings->protection = $protection;
    }

    /**
     *
     */
    protected function data_postprocess_signature(&$settings, $data) {
        $signsettings = $settings->signature;
        $signsettings->password = $data->certpassword;
        $signsettings->type = $data->certtype;
        $signsettings->info['Name'] = $data->certinfoname;
        $signsettings->info['Location'] = $data->certinfoloc;
        $signsettings->info['Reason'] = $data->certinforeason;
        $signsettings->info['ContactInfo'] = $data->certinfocontact;

        $settings->signature = $signsettings;
    }

}
