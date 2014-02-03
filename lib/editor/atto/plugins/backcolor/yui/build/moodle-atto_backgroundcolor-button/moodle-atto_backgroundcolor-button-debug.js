YUI.add('moodle-atto_backgroundcolor-button', function (Y, NAME) {

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
 * Atto text editor background color plugin.
 *
 * @package editor-atto
 * @copyright  2014 Rossiani Wijaya  <rwijaya@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
M.atto_backcolor = M.atto_backcolor || {
    init : function(params) {
        var click_red = function(e, elementid) {
            M.atto_backcolor.change_color(e, elementid, 'red');
        };
        var click_blue = function(e, elementid) {
            M.atto_backcolor.change_color(e, elementid, 'blue');
        };
        var click_yellow = function(e, elementid) {
            M.atto_backcolor.change_color(e, elementid, 'yellow');
        };

        var red = M.util.get_string('red', 'atto_backcolor');
        var blue = M.util.get_string('blue', 'atto_backcolor');
        var yellow = M.util.get_string('yellow', 'atto_backcolor');

        var iconurl = M.util.image_url('e/styleprops', 'core');

        M.editor_atto.add_toolbar_menu(params.elementid,
            'backgroundcolor',
            iconurl,
            params.group,
            [
                {'text' : red, 'handler' : click_red},
                {'text' : blue, 'handler' : click_blue},
                {'text' : yellow, 'handler' : click_yellow}
            ]);
    },

    /**
     * Handle a choice from the menu (insert the node in the text editor matching elementid).
     * @param event e - The event that triggered this.
     * @param string elementid - The id of the editor
     * @param string node - The html to insert
     */
    change_color : function(e, elementid, node) {
        e.preventDefault();
        if (!M.editor_atto.is_active(elementid)) {
            M.editor_atto.focus(elementid);
        }
        document.execCommand('backColor', 0, node);
        // Clean the YUI ids from the HTML.
        M.editor_atto.text_updated(elementid);
    }
};

}, '@VERSION@');
