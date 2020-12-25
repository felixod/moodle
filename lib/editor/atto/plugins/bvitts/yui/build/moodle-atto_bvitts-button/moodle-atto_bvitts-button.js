YUI.add('moodle-atto_bvitts-button', function (Y, NAME) {

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

/*
 * @package    atto_bvitts
 * @copyright  2020 Gorbatov Sergey  <s.gorbatov@samgups.ru>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * @module moodle-atto_bvitts-button
 */

/**
 * Atto text editor tts plugin.
 *
 * @namespace M.atto_bvitts
 * @class button
 * @extends M.editor_atto.EditorPlugin
 */
var COMPONENTNAME = 'atto_bvitts';
Y.namespace('M.atto_bvitts').Button = Y.Base.create('button', Y.M.editor_atto.EditorPlugin, [], {
    initializer: function() {
        this.addButton({
            icon: 'icon',
            iconComponent: COMPONENTNAME,
            callback: this._BVITTS,
            inlineFormat: true,
            tags: '.bvi-tts'
        });
    },

    /**
     * Добавляем для выделенного фрагмента возможность озвучивания.
     *
     * @method _BVITTS
     * @param {EventFacade} e
     * @private
     */
    _BVITTS: function() {
        // Вставляем класс озвучивания текста
        this.get('host').toggleInlineSelectionClass(['bvi-tts']);
    }
});


}, '@VERSION@', {"requires": ["moodle-editor_atto-plugin"]});
