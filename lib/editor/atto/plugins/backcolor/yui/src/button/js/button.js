// This file is part of Moodle - http://moodle.org/
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
 * Atto text editor backcolor plugin.
 *
 * @package    editor-atto
 * @copyright  2014 Rossiani Wijaya  <rwijaya@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
M.atto_backcolor = M.atto_backcolor || {
    dialogue : null,
    selection : null,
    init : function(params) {
        var display_chooser = function(e, elementid) {
            e.preventDefault();
            if (!M.editor_atto.is_active(elementid)) {
                M.editor_atto.focus(elementid);
            }
            M.atto_backcolor.selection = M.editor_atto.get_selection();
            if (M.atto_backcolor.selection !== false) {
                var dialogue;
                if (!M.atto_backcolor.dialogue) {
                    dialogue = new M.core.dialogue({
                        visible: false,
                        modal: true,
                        close: true,
                        draggable: true
                    });
                } else {
                    dialogue = M.atto_backcolor.dialogue;
                }

                dialogue.set('bodyContent', M.atto_backcolor.get_form_content(elementid));
                dialogue.set('headerContent', M.util.get_string('choosecolor', 'atto_backcolor'));
                dialogue.render();
                dialogue.centerDialogue();
                M.atto_backcolor.dialogue = dialogue;

                var selectedText = M.editor_atto.get_selection_text();
                var i = 0;

                dialogue.show();
            }
        };

        var iconurl = M.util.image_url('e/insert_edit_image', 'core');
        M.editor_atto.add_toolbar_button(params.elementid, 'image', iconurl, params.group, display_chooser, this);
    },
    get_form_content : function(elementid) {
        var html = '<form class="atto_form">' +
            '<label for="atto_backcolor_fontcolor">' + M.util.get_string('fontcolor', 'atto_backcolor') +
            '</label>' +
            '<input class="fullwidth" type="text" value="" id="atto_backcolor_fontcolor" size="32"/>' +
            '<br/>';
        //html += Y.Color.getComplementary('blue');
        var colorfont = Y.Harmony;

        html += colorfont.getComplementary('red', 'blue');

        html += '<div style="display:none" role="alert" id="atto_backcolor_altwarning" class="warning">' +
            M.util.get_string('presentationoraltrequired', 'atto_backcolor') +
            '</div>' +
            '<label for="atto_backcolor_altentry">' + M.util.get_string('enteralt', 'atto_backcolor') +
            '</label>' +
            '<input class="fullwidth" type="text" value="" id="atto_backcolor_altentry" size="32"/>' +
            '<br/>' +
            '<input type="checkbox" id="atto_backcolor_presentation"/>' +
            '<label class="sameline" for="atto_backcolor_presentation">' + M.util.get_string('presentation', 'atto_backcolor') +
            '</label>' +
            '<br/>' +
            '<label class="sameline" for="atto_backcolor_widthentry">' + M.util.get_string('width', 'atto_backcolor') +
            '</label>' +
            '<input type="text" value="" id="atto_backcolor_widthentry" size="10"/>' +
            '<br/>' +
            '<label class="sameline" for="atto_backcolor_heightentry">' + M.util.get_string('height', 'atto_backcolor') +
            '</label>' +
            '<input type="text" value="" id="atto_backcolor_heightentry" size="10"/>' +
            '<br/>' +
            '<label for="atto_backcolor_preview">' + M.util.get_string('preview', 'atto_backcolor') +
            '</label>' +
            '<img src="#" width="200" id="atto_backcolor_preview" alt="" style="display: none;"/>' +
            '<div class="mdl-align">' +
            '<br/>' +
            '<button id="atto_backcolor_urlentrysubmit">' +
            M.util.get_string('createimage', 'atto_backcolor') +
            '</button>' +
            '</div>' +
            '</form>' +
            '<hr/>' + M.util.get_string('accessibilityhint', 'atto_backcolor');

        var content = Y.Node.create(html);

        /*content.one('#atto_backcolor_urlentry').on('blur', M.atto_backcolor.url_changed, this);
        content.one('#atto_backcolor_urlentrysubmit').on('click', M.atto_backcolor.set_image, this, elementid);
        if (M.editor_atto.can_show_filepicker(elementid, 'image')) {
            content.one('#openimagebrowser').on('click', M.atto_backcolor.open_filepicker);
        }*/
        return content;
    }
};
