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

require_once("$CFG->libdir/pdflib.php");

class dataformview_pdf_pdf extends mod_dataform\pluginbase\dataformview {

    const EXPORT_ALL = 'all';
    const EXPORT_PAGE = 'page';
    const EXPORT_ENTRY = 'entry';
    const PAGE_BREAK = '<div class="pdfpagebreak"></div>';

    protected $_editors = array('section', 'param2', 'param3', 'param4');
    protected $_pdf_settings = null;
    protected $_tmpfiles = null;

    /**
     *
     * @return array
     */
    public static function get_file_areas() {
        return array('section', 'param2', 'param3', 'param4', 'pdffram', 'pdfwmark', 'pdfcert');
    }

    /**
     *
     * @return array
     */
    public static function get_permission_options() {
        return array(
            'print' => get_string('perm_print', 'dataformview_pdf'),
            'modify' => get_string('perm_modify', 'dataformview_pdf'),
            'copy' => get_string('perm_copy', 'dataformview_pdf'),
            'fill-forms' => get_string('perm_fill-forms', 'dataformview_pdf'),
            'extract' => get_string('perm_extract', 'dataformview_pdf'),
            'assemble' => get_string('perm_assemble', 'dataformview_pdf'),
            'print-high' => get_string('perm_print-high', 'dataformview_pdf'),
            /* 'owner' => get_string('perm_owner', 'dataformview_pdf'), */
        );
    }

    /**
     *
     * @return void
     */
    public function __construct($view) {
        parent::__construct($view);

        if ($this->param1) {
            $settings = unserialize($this->param1);
        }

        $noexportpatterns = $this->get_default_no_export_patterns();

        $this->pdf_settings = (object) array(
            'docname' => !empty($settings->docname) ? $settings->docname : '',
            'orientation' => !empty($settings->orientation) ? $settings->orientation : '',
            'unit' => !empty($settings->unit) ? $settings->unit : 'mm',
            'format' => !empty($settings->format) ? $settings->format : 'LETTER',
            'destination' => !empty($settings->destination) ? $settings->destination : 'I',
            'transparency' => !empty($settings->transparency) ? $settings->transparency : 0.5,
            'pagebreak' => !empty($settings->pagebreak) ? $settings->pagebreak : 'auto',
            'noexportpatterns' => isset($settings->noexportpatterns) ? $settings->noexportpatterns : $noexportpatterns,
            'toc' => (object) array(
                'page' => !empty($settings->toc->page) ? $settings->toc->page : '',
                'name' => !empty($settings->toc->name) ? $settings->toc->name : '',
                'title' => !empty($settings->toc->title) ? $settings->toc->title : '',
                'template' => !empty($settings->toc->template) ? $settings->toc->template : '',
            ),
            'header' => (object) array(
                'enabled' => !empty($settings->header->enabled) ? $settings->header->enabled : false,
                'margintop' => !empty($settings->header->margintop) ? $settings->header->margintop : 0,
                'marginleft' => !empty($settings->header->marginleft) ? $settings->header->marginleft : 10,
            ),
            'footer' => (object) array(
                'text' => $this->param4 ? $this->param4 : '',
                'enabled' => !empty($settings->footer->enabled) ? $settings->footer->enabled : false,
                'margin' => !empty($settings->footer->margin) ? $settings->footer->margin : 10,
            ),
            'margins' => (object) array(
                'left' => !empty($settings->margins->left) ? $settings->margins->left : 15,
                'top' => !empty($settings->margins->top) ? $settings->margins->top : 27,
                'right' => !empty($settings->margins->right) ? $settings->margins->right : -1,
                'keep' => !empty($settings->margins->keep) ? $settings->margins->keep : false,
            ),
            'protection' => (object) array(
                'permissions' => !empty($settings->protection->permissions) ? $settings->protection->permissions : array(),
                'user_pass' => !empty($settings->protection->user_pass) ? $settings->protection->user_pass : '',
                'owner_pass' => !empty($settings->protection->owner_pass) ? $settings->protection->owner_pass : null,
                'mode' => !empty($settings->protection->mode) ? $settings->protection->mode : 0,
                /* 'pubkeys' => null */
            ),
            'signature' => (object) array(
                'password' => !empty($settings->signature->password) ? $settings->signature->password : '',
                'type' => !empty($settings->signature->type) ? $settings->signature->type : 1,
                'info' => array(
                    'Name' => !empty($settings->signature->info->Name) ? $settings->signature->info->Name : '',
                    'Location' => !empty($settings->signature->info->Location) ? $settings->signature->info->Location : '',
                    'Reason' => !empty($settings->signature->info->Reason) ? $settings->signature->info->Reason : '',
                    'ContactInfo' => !empty($settings->signature->info->ContactInfo) ? $settings->signature->info->ContactInfo : '',
                )
            )
        );
    }

    /**
     * Process any view specific actions.
     */
    public function process_data() {
        global $CFG;

        // Process pdf export request.
        if (optional_param('pdfexportall', 0, PARAM_INT)) {
            $this->process_export(self::EXPORT_ALL);
        } else if (optional_param('pdfexportpage', 0, PARAM_INT)) {
            $this->process_export(self::EXPORT_PAGE);
        } else if ($exportentry = optional_param('pdfexportentry', 0, PARAM_INT)) {
            $this->process_export($exportentry);
        }

        // Do standard view processing.
        return parent::process_data();
    }

    /**
     *
     */
    public function process_export($export = self::EXPORT_PAGE) {
        global $CFG;

        $settings = $this->pdf_settings;
        $this->_tmpfiles = array();

        // Generate the pdf.
        $pdf = new dfpdf($settings);

        // Set margins.
        $pdf->SetMargins($settings->margins->left, $settings->margins->top, $settings->margins->right);

        // Set header.
        if (!empty($settings->header->enabled)) {
            $pdf->setHeaderMargin($settings->header->margintop);
            $this->set_header($pdf);
        } else {
            $pdf->setPrintHeader(false);
        }
        // Set footer.
        if (!empty($settings->footer->enabled)) {
            $pdf->setFooterMargin($settings->footer->margin);
            $this->set_footer($pdf);
        } else {
            $pdf->setPrintFooter(false);
        }

        // Protection.
        $protection = $settings->protection;
        $pdf->SetProtection(
            $protection->permissions,
            $protection->user_pass,
            $protection->owner_pass,
            $protection->mode
            /* $protection->pubkeys */
        );

        // Set document signature.
        $this->set_signature($pdf);

        // Paging.
        if (empty($settings->pagebreak)) {
            $pdf->SetAutoPageBreak(false, 0);
        }

        // Adjust filter for content.
        if ($export == self::EXPORT_ALL) {
            // Unset per page to retrieve all entries.
            $this->filter->perpage = 0;
        } else if ($export and $export != self::EXPORT_PAGE) {
            // Specific entry requested.
            $this->filter->eids = $export;
        }

        $entryman = $this->entry_manager;
        $entryman->set_content(array('filter' => $this->filter));

        $content = array();
        if ($settings->pagebreak == 'entry') {
            $entries = $entryman->entries ? $entryman->entries : array();
            foreach ($entries as $eid => $entry) {
                $entriesset = new object;
                $entriesset->max = 1;
                $entriesset->found = 1;
                $entriesset->entries = array($eid => $entry);
                $entryman->set_content(array('entriesset' => $entriesset));
                $pages = explode(self::PAGE_BREAK, $this->display(array('export' => true)));
                $content = array_merge($content, $pages);
            }
        } else {
            $content = explode(self::PAGE_BREAK, $this->display(array('export' => true)));
        }

        foreach ($content as $pagecontent) {
            $pdf->AddPage();
            // Set page bookmarks.
            $pagecontent = $this->set_page_bookmarks($pdf, $pagecontent);
            // Set frame.
            $this->set_frame($pdf);
            // Set watermark.
            $this->set_watermark($pdf);
            $pagecontent = $this->process_content_images($pagecontent);
            $this->write_html($pdf, $pagecontent);
        }

        // Set TOC.
        if (!empty($settings->toc->page)) {
            $pdf->addTOCPage();
            if (!empty($settings->toc->title)) {
                $pdf->writeHTML($settings->toc->title);
            }

            if (empty($settings->toc->template)) {
                $pdf->addTOC($settings->toc->page, '', '.', $settings->toc->name);
            } else {
                $templates = explode("\n", $settings->toc->template);
                $pdf->addHTMLTOC($settings->toc->page, $settings->toc->name, $templates);
            }
            $pdf->endTOCPage();
        }

        // Send the pdf.
        $documentname = optional_param('docname', $this->get_documentname($settings->docname), PARAM_TEXT);
        $destination = optional_param('dest', $settings->destination, PARAM_ALPHA);
        $pdf->Output("$documentname.pdf", $destination);

        // Clean up temp files.
        if ($this->_tmpfiles) {
            foreach ($this->_tmpfiles as $filepath) {
                unlink($filepath);
            }
        }

        exit;
    }

    /**
     * Sets the view pdf settings with settings object.
     * @return void
     */
    public function set_pdf_settings($value) {
        $this->_pdf_settings = $value;
    }

    /**
     * Returns the view pdf settings as an object.
     * @return stdClass pdf_settings
     */
    public function get_pdf_settings() {
        return $this->_pdf_settings;
    }

    /**
     * Overridden to process pdf specific area files.
     */
    public function from_form($data) {
        $data = parent::from_form($data);

        // Save pdf specific template files.
        $contextid = $this->df->context->id;
        $imageoptions = array('subdirs' => 0, 'maxbytes' => -1, 'maxfiles' => 1, 'accepted_types' => array('image'));
        $certoptions = array('subdirs' => 0, 'maxbytes' => -1, 'maxfiles' => 1, 'accepted_types' => array('.crt'));

        // Pdf frame.
        file_save_draft_area_files($data->pdfframe, $contextid, 'dataformview_pdf', 'pdfframe', $this->id, $imageoptions);
        // Pdf watermark.
        file_save_draft_area_files($data->pdfwmark, $contextid, 'dataformview_pdf', 'pdfwmark', $this->id, $imageoptions);
        // Pdf cert.
        file_save_draft_area_files($data->pdfcert, $contextid, 'dataformview_pdf', 'pdfcert', $this->id, $certoptions);

        return $data;
    }

    /**
     * Overridden to process pdf specific area files.
     */
    public function to_form($data = null) {
        $data = parent::to_form($data);

        // Save pdf specific template files.
        $contextid = $this->df->context->id;
        $imageoptions = array('subdirs' => 0, 'maxbytes' => -1, 'maxfiles' => 1, 'accepted_types' => array('image'));
        $certoptions = array('subdirs' => 0, 'maxbytes' => -1, 'maxfiles' => 1, 'accepted_types' => array('.crt'));

        // Pdf frame.
        $draftitemid = file_get_submitted_draft_itemid('pdfframe');
        file_prepare_draft_area($draftitemid, $contextid, 'dataformview_pdf', 'pdfframe', $this->id, $imageoptions);
        $data->pdfframe = $draftitemid;

        // Pdf watermark.
        $draftitemid = file_get_submitted_draft_itemid('pdfwmark');
        file_prepare_draft_area($draftitemid, $contextid, 'dataformview_pdf', 'pdfwmark', $this->id, $imageoptions);
        $data->pdfwmark = $draftitemid;

        // Pdf certification.
        $draftitemid = file_get_submitted_draft_itemid('pdfcert');
        file_prepare_draft_area($draftitemid, $contextid, 'dataformview_pdf', 'pdfcert', $this->id, $certoptions);
        $data->pdfcert = $draftitemid;

        return $data;
    }

    /**
     * Override parent to remove pdf bookmark tags.
     */
    public function display(array $options = array()) {
        /* Export */
        if (!empty($options['export'])) {
            // Remove export excluded patterns from templates.
            if ($noexportpatterns = $this->pdf_settings->noexportpatterns) {
                foreach ($this->editors as $editorname) {
                    $this->$editorname = str_replace($noexportpatterns, '', $this->$editorname);
                }
            }
            return parent::display($options);
        }

        // For display we need to clean up the bookmark patterns.
        foreach ($this->editors as $editorname) {
            $this->$editorname = preg_replace("%#@PDF-[G]*BM:\d+:[^@]*@#%", '', $this->$editorname);
        }
        return parent::display($options);
    }

    /**
     * Generates the default view template for a new view instance or when reseting an existing instance.
     * If content is specified, sets the template to the content.
     *
     * @param string $content HTML fragment.
     * @return void
     */
    public function set_default_view_template($content = null) {
        if ($content === null) {
            // Notifications.
            $notifications = \html_writer::tag('div', '##notifications##', array('class' => ''));

            // Export/import.
            $expimp = \html_writer::tag('div', '##exportall## | ##exportpage##', array('class' => ''));

            // Add new entry.
            $addnewentry = \html_writer::tag('div', '##addnewentry##', array('class' => 'addnewentry-wrapper'));

            // Filtering.
            $filtertemplate = $this->get_default_filtering_template();
            $quickfilters = \html_writer::tag('div', $filtertemplate, array('class' => 'quickfilters-wrapper'));

            // Paging bar.
            $pagingbar = \html_writer::tag('div', '##paging:bar##', array('class' => ''));
            // Entries.
            $entries = \html_writer::tag('div', '##entries##', array('class' => ''));

            // Set the view template.
            $exporthidecontent = $expimp. $addnewentry. $quickfilters. $pagingbar;
            $exporthide = \html_writer::tag('div', $exporthidecontent, array('class' => 'exporthide'));
            $content = \html_writer::tag('div', $exporthide. $entries);
        }
        $this->section = $content;
    }

    /**
     * Generates the default entry template for a new view instance or when reseting an existing instance.
     *
     * @return void
     */
    public function set_default_entry_template($content = null) {
        // Get all the fields.
        if (!$fields = $this->df->field_manager->get_fields()) {
            return;
        }

        // Set template content.
        if ($content === null) {
            $table = new html_table();
            $table->attributes['align'] = 'center';
            $table->attributes['cellpadding'] = '2';
            // Add field patterns.
            foreach ($fields as $field) {
                if ($field->id > 0) {
                    $name = new html_table_cell($field->name. ':');
                    $name->style = 'text-align:right;';
                    $content = new html_table_cell("[[{$field->name}]]");
                    $row = new html_table_row();
                    $row->cells = array($name, $content);
                    $table->data[] = $row;
                }
            }
            // Add action patterns.
            $row = new html_table_row();
            $entryactions = get_string('fieldname', 'dataformfield_entryactions');
            $actions = new html_table_cell("[[$entryactions:edit]]  [[$entryactions:delete]]");
            $actions->colspan = 2;
            $row->cells = array($actions);
            $table->data[] = $row;
            // Construct the table.
            $entrydefault = html_writer::table($table);
            $content = html_writer::tag('div', $entrydefault, array('class' => 'entry'));
        }
        $this->param2 = $content;
    }

    /**
     *
     */
    protected function group_entries_definition($entriesset, $name = '') {
        global $OUTPUT;

        $elements = array();

        // Flatten the set to a list of elements.
        foreach ($entriesset as $entrydefinitions) {
            $elements = array_merge($elements, $entrydefinitions);
        }

        // Add group heading.
        $name = ($name == 'newentry') ? get_string('entrynew', 'dataform') : $name;
        if ($name) {
            array_unshift($elements, $OUTPUT->heading($name, 3, 'main'));
        }
        // Wrap with entriesview.
        array_unshift($elements, html_writer::start_tag('div', array('class' => 'entriesview')));
        array_push($elements, html_writer::end_tag('div'));

        return $elements;
    }

    /**
     *
     */
    protected function entry_definition($fielddefinitions, array $options = null) {
        $elements = array();

        // If not editing, do simple replacement and return the html.
        if (empty($options['edit'])) {
            $elements[] = str_replace(array_keys($fielddefinitions), $fielddefinitions, $this->param2);
            return $elements;
        }

        // Editing so split the entry template to tags and html.
        $tags = array_keys($fielddefinitions);
        $parts = $this->split_tags($tags, $this->param2);

        foreach ($parts as $part) {
            if (in_array($part, $tags)) {
                if ($def = $fielddefinitions[$part]) {
                    $elements[] = $def;
                }
            } else {
                $elements[] = $part;
            }
        }

        return $elements;
    }

    /**
     *
     */
    protected function new_entry_definition($entryid = -1) {
        $elements = array();

        // Get patterns definitions.
        $fields = $this->get_fields();
        $tags = array();
        $patterndefinitions = array();
        $entry = new object;

        if ($fieldpatterns = $this->get_pattern_set('field')) {
            foreach ($fieldpatterns as $fieldid => $patterns) {
                $field = $fields[$fieldid];
                $entry->id = $entryid;
                $options = array('edit' => true, 'manage' => true);
                if ($fielddefinitions = $field->get_definitions($patterns, $entry, $options)) {
                    $patterndefinitions = array_merge($patterndefinitions, $fielddefinitions);
                }
                $tags = array_merge($tags, $patterns);
            }
        }

        // Split the entry template to tags and html.
        $parts = $this->split_tags($tags, $this->param2);

        foreach ($parts as $part) {
            if (in_array($part, $tags)) {
                if ($def = $patterndefinitions[$part]) {
                    $elements[] = $def;
                }
            } else {
                $elements[] = $part;
            }
        }

        return $elements;
    }

    /**
     *
     */
    protected function set_page_bookmarks($pdf, $pagecontent) {
        $settings = $this->pdf_settings;
        static $bookmarkgroup = '';

        // Find all patterns ##PDFBM:d:any text##.
        if (preg_match_all("%#@PDF-[G]*BM:\d+:[^@]*@#%", $pagecontent, $matches)) {
            if (!empty($settings->toc->page)) {
                // Get the array of templates.
                $templates = explode("\n", $settings->toc->template);

                // Add a bookmark for each pattern.
                foreach ($matches[0] as $bookmark) {
                    $bookmark = trim($bookmark, '#@');
                    list($bmtype, $bmlevel, $bmtext) = explode(':', $bookmark, 3);

                    // Must have a template for the TOC level.
                    if (empty($templates[$bmlevel])) {
                        continue;;
                    }

                    // Add a group bookmark only if new.
                    if ($bmtype == 'PDF-GBM') {
                        if ($bmtext != $bookmarkgroup) {
                            $pdf->Bookmark($bmtext, $bmlevel);
                            $bookmarkgroup = $bmtext;
                        }
                    } else {
                        $pdf->Bookmark($bmtext, $bmlevel);
                    }
                }
            }
            // Remove patterns from page content.
            $pagecontent = str_replace($matches[0], '', $pagecontent);
        }
        return $pagecontent;
    }

    /**
     *
     */
    protected function set_frame($pdf) {
        // Add to pdf frame image if any.
        $fs = get_file_storage();
        if ($frame = $fs->get_area_files($this->df->context->id, 'dataformview_pdf', 'pdfframe', $this->id, '', false)) {
            $frame = reset($frame);

            $tmpdir = make_temp_directory('');
            $filename = $frame->get_filename();
            $filepath = $tmpdir. "files/$filename";
            if ($frame->copy_content_to($filepath)) {
                $pdf->Image($filepath,
                    '', /* $x = '' */
                    '', /* ... $y = '' */
                    0, /* ... $w = 0 */
                    0, /* $h = 0 */
                    '', /* $type = '' */
                    '', /* $link = '' */
                    '', /* $align = '' */
                    false, /* $resize = false */
                    300, /* $dpi = 300 */
                    '', /* $palign = '' */
                    false, /* $ismask = false */
                    false, /* $imgmask = false */
                    0, /* $border = 0 */
                    false, /* $fitbox = false */
                    false, /* $hidden = false */
                    true /* $fitonpage = false */
                );
            }
            unlink($filepath);
        }
    }

    /**
     *
     */
    protected function set_watermark($pdf) {
        // Add to pdf watermark image if any.
        $fs = get_file_storage();
        if ($wmark = $fs->get_area_files($this->df->context->id, 'dataformview_pdf', 'pdfwmark', $this->id, '', false)) {
            $wmark = reset($wmark);

            $tmpdir = make_temp_directory('');
            $filename = $wmark->get_filename();
            $filepath = $tmpdir. "files/$filename";
            if ($wmark->copy_content_to($filepath)) {
                list($wmarkwidth, $wmarkheight, ) = array_values($wmark->get_imageinfo());
                // TODO 25.4 in Inch (assuming unit in mm) and 72 dpi by default when image dims not specified.
                $wmarkwidthmm = $wmarkwidth * 25.4 / 72;
                $wmarkheightmm = $wmarkheight * 25.4 / 72;
                $pagedim = $pdf->getPageDimensions();
                $centerx = ($pagedim['wk'] - $wmarkwidthmm) / 2;
                $centery = ($pagedim['hk'] - $wmarkheightmm) / 2;

                $pdf->SetAlpha($this->pdf_settings->transparency);
                $pdf->Image($filepath,
                    $centerx, /* $x = '' */
                    $centery /* $y = '' */
                );
                $pdf->SetAlpha(1);
            }
            unlink($filepath);
        }
    }

    /**
     *
     */
    protected function set_signature($pdf) {
        $fs = get_file_storage();
        if ($cert = $fs->get_area_files($this->df->context->id, 'dataformview_pdf', 'pdfcert', $this->id, '', false)) {
            $cert = reset($cert);

            $tmpdir = make_temp_directory('');
            $filename = $cert->get_filename();
            $filepath = $tmpdir. "files/$filename";
            if ($cert->copy_content_to($filepath)) {
                $signsettings = $this->pdf_settings->signature;
                $pdf->setSignature(
                    "file:// $filepath", "file:// $filepath",
                    $signsettings->password,
                    '',
                    $signsettings->type,
                    $signsettings->info
                );
            }
            unlink($filepath);
        }
    }

    /**
     *
     */
    protected function set_header($pdf) {
        if ($this->param3) {
            return;
        }

        // Rewrite plugin file urls.
        $content = file_rewrite_pluginfile_urls(
            $this->param3,
            'pluginfile.php',
            $this->df->context->id,
            'dataformview_pdf',
            'param3',
            $this->id
        );

        $content = $this->process_content_images($content);
        // Add the Dataform css to content.
        if ($this->df->css) {
            $style = html_writer::tag('style', $this->df->css, array('type' => 'text/css'));
            $content = $style. $content;
        }

        $pdf->SetHeaderData('', 0, '', $content);
    }

    /**
     *
     */
    protected function set_footer($pdf) {
        if (!$this->param4) {
            return;
        }

        // Rewrite plugin file urls.
        $content = file_rewrite_pluginfile_urls(
            $this->param4,
            'pluginfile.php',
            $this->df->context->id,
            'dataformview_pdf',
            'param4',
            $this->id
        );

        $content = $this->process_content_images($content);
        $pdf->SetFooterData('', 0, '', $content);
    }

    /**
     *
     */
    protected function process_content_images($content) {
        global $CFG;

        $replacements = array();
        $tmpdir = make_temp_directory('files');

        // Process theme images.
        $replacements = $this->get_theme_images_replacements($content, $replacements);

        // Process activity images.
        $replacements = $this->get_activity_plugin_files_replacements($content, $replacements, $tmpdir);

        // Process content images.
        $replacements = $this->get_content_plugin_files_replacements($content, $replacements, $tmpdir);

        // Replace content.
        if ($replacements) {
            $content = str_replace(array_keys($replacements), $replacements, $content);
        }
        return $content;
    }

    /**
     *
     */
    protected function get_theme_images_replacements($content, $replacements) {
        global $CFG;

        if (preg_match_all("%$CFG->wwwroot/theme/image.php/([^\"]+)%", $content, $matches)) {
            foreach ($matches[1] as $imagepath) {
                $imageurl = "$CFG->wwwroot/theme/image.php/$imagepath";
                // Process only once.
                if (array_key_exists($imageurl, $replacements)) {
                    continue;
                }
                $usesvg = true;
                if (strpos($imagepath, '_s/') === 0) {
                    // Can't use SVG
                    $imagepath = substr($imagepath, 3);
                    $usesvg = false;
                }
                // Image must be last because it may contain "/"
                list($themename, $component, $rev, $image) = explode('/', $imagepath, 4);
                $themename = clean_param($themename, PARAM_THEME);
                $component = clean_param($component, PARAM_COMPONENT);
                $rev = clean_param($rev, PARAM_INT);
                $image = clean_param($image, PARAM_SAFEPATH);

                $theme = theme_config::load($themename);

                // We do not account for revision or caching here.
                $filepath = $theme->resolve_image_location($image, $component, $usesvg);
                $replacements[$imageurl] = $filepath;
            }
        }

        return $replacements;
    }

    /**
     *
     */
    protected function get_activity_plugin_files_replacements($content, $replacements, $tmpdir) {
        global $CFG;

        $contextid = $this->df->context->id;
        if (preg_match_all("%$CFG->wwwroot/pluginfile.php/$contextid/dataformview_pdf/([^\"]+)%", $content, $matches)) {

            $fs = get_file_storage();
            foreach ($matches[1] as $path) {
                $fileurl = "$CFG->wwwroot/pluginfile.php/$contextid/dataformview_pdf/$path";

                // Process only once.
                if (array_key_exists($fileurl, $replacements)) {
                    continue;
                }

                $normalpath = "/$contextid/dataformview_pdf/$path";
                if (!$file = $fs->get_file_by_hash(sha1($normalpath)) or $file->is_directory()) {
                    continue;
                }

                $filename = $file->get_filename();
                $filepath = "$tmpdir/$filename";
                if ($file->copy_content_to($filepath)) {
                    $replacements[$fileurl] = $filepath;
                    $this->_tmpfiles[] = $filepath;
                }
            }
        }

        return $replacements;
    }

    /**
     *
     */
    protected function get_content_plugin_files_replacements($content, $replacements, $tmpdir) {
        global $CFG;

        $contextid = $this->df->context->id;
        if (preg_match_all("%$CFG->wwwroot/pluginfile.php/$contextid/mod_dataform/content/([^\"]+)%", $content, $matches)) {

            $fs = get_file_storage();
            foreach ($matches[1] as $path) {
                $fileurl = "$CFG->wwwroot/pluginfile.php/$contextid/mod_dataform/content/$path";

                // Process only once.
                if (array_key_exists($fileurl, $replacements)) {
                    continue;
                }

                $args = explode('/', $path);
                $contentidhash = urldecode(array_shift($args));
                if (!$contentid = $this->df->get_content_id_from_hash($contentidhash)) {
                    continue;
                }

                $args = implode('/', $args);
                $normalpath = "/$contextid/mod_dataform/content/$contentid/$args";
                if (!$file = $fs->get_file_by_hash(sha1($normalpath)) or $file->is_directory()) {
                    continue;
                }

                $filename = $file->get_filename();
                $filepath = "$tmpdir/$filename";
                if ($file->copy_content_to($filepath)) {
                    $replacements[$fileurl] = $filepath;
                    $this->_tmpfiles[] = $filepath;
                }
            }
        }

        return $replacements;
    }

    /**
     *
     */
    protected function write_html($pdf, $content) {

        // Add the Dataform css to content.
        if ($this->df->css) {
            $style = html_writer::tag('style', $this->df->css, array('type' => 'text/css'));
            $content = $style. $content;
        }
        // Add pdfexporthide for hiding elements in export.
        $style = html_writer::tag('style', '.pdfexporthide{display:none;}', array('type' => 'text/css'));
        $content = $style. $content;

        $pdf->writeHTML($content);
    }

    /**
     *
     */
    protected function get_documentname($namepattern) {
        $docname = 'doc';
        if (!empty($namepattern)) {
            $docname = $namepattern;
        }

        return $docname;
    }

    /**
     *
     * @return array
     */
    protected function get_default_no_export_patterns() {
        $entryactions = get_string('fieldname', 'dataformfield_entryactions');
        $patterns = array(
            '##addnewentry##',
            '##viewsmenu##',
            '##filtersmenu##',
            '##quicksearch##',
            '##quickperpage##',
            '##addnewentry##',
            '##exportall##',
            '##paging:bar##',
            "[[$entryactions:edit]]",
            "[[$entryactions:delete]]",
        );
        return $patterns;
    }

}

/**
 * Extend the TCPDF class to create custom Header and Footer.
 *
 */
class dfpdf extends pdf {

    protected $_dfsettings;

    public function __construct($settings) {
        parent::__construct($settings->orientation, $settings->unit, $settings->format);
        $this->_dfsettings = $settings;
    }

    /* Page header */
    public function Header() {
        // Adjust X to override left margin.
        $x = $this->GetX();
        $this->SetX($this->_dfsettings->header->marginleft);
        if (!empty($this->header_string)) {
            $text = $this->set_page_numbers($this->header_string);
            $this->writeHtml($text);
        }
        // Reset X to original.
        $this->SetX($x);
    }

    /* Page footer */
    public function Footer() {
        if (!empty($this->_dfsettings->footer->text)) {
            $text = $this->set_page_numbers($this->_dfsettings->footer->text);
            $this->writeHtml($text);
        }
    }

    protected function set_page_numbers($text) {
        $replacements = array(
            '##pagenumber##' => $this->getAliasNumPage(),
            '##totalpages##' => $this->getAliasNbPages(),
        );
        $text = str_replace(array_keys($replacements), $replacements, $text);
        return $text;
    }
}
