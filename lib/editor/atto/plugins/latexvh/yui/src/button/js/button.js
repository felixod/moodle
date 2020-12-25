/*
 * @package    atto_latexvh
 * @copyright  2016 primat.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @extends M.editor_atto.EditorPlugin
 */

var COMPONENTNAME = 'atto_latexvh';

var uncache = (function(date){
    return date.getFullYear() + '.' + date.getMonth() + '.' + date.getDate();
})(new Date());

var IFrame_URL = 'http://primat.org/moodle/index.html?v=' + uncache;
var IFrameBridge_URL = 'http://primat.org/moodle/bridge.js?v=' + uncache;
var IFrameStyle = 'width: 1225px; margin: -22px -7px -65px -25px; height: 572px; border: none;';
var IFrameID = 'latexvh_iframe';
var ErrorLoadMessage = 'IFrameBridge not loaded';

Y.namespace('M.atto_latexvh').Button = Y.Base.create('button', Y.M.editor_atto.EditorPlugin, [], {

    initializer: function() {
        this.addButton({
            icon: 'btn-icon',
            iconComponent: 'atto_latexvh',
            buttonName: 'btn-icon',
            callback: this._displayDialogue,
            callbackArgs: 'btn-icon'
        });
    },

    _displayDialogue: function(e, clickedIcon) {
        e.preventDefault();


        YUI.namespace(COMPONENTNAME).host = this.get('host');
        YUI.namespace(COMPONENTNAME).clickedIcon = clickedIcon;

        Y.Get.js(IFrameBridge_URL, (function(notLoaded){
            if(notLoaded){
                Y.log(ErrorLoadMessage, "error",  COMPONENTNAME);
            }else{
                this.initIframe(IFrameBridge);
            }
        }).bind(this));

        this.markUpdated();
    },

    getSender: function(){
        return (function(source){
            return function(message){
                source.contentWindow.postMessage(message, '*');
            };
        })(document.getElementById(IFrameID));
    },

    initIframe : function(Bridge){
        this.showIframe();

        var bridge = Bridge(this.getSender());

        bridge.addRoute('set', function(data){
            data = data.split('~');

            YUI.namespace(COMPONENTNAME)[data[0]] = data[1];
        });

        bridge.addRoute('get', function(data, send){
            data = data.split('~');

            send(data[1], YUI.namespace(COMPONENTNAME)[data[0]]);
        });

        bridge.addRoute('insert to editor', function(value){
            YUI.namespace(COMPONENTNAME).host.insertContentAtFocusPoint(value);
            YUI.namespace(COMPONENTNAME).dialogue.hide();
        });

        bridge.init();
    },

    showIframe : function(){
        var width = 1200;

        var dialogue = this.getDialogue({
            headerContent: M.util.get_string('dialogtitle', COMPONENTNAME),
            width: width + 'px',
            focusAfterHide: YUI.namespace(COMPONENTNAME).clickedIcon
        });

        if(dialogue.width !== width + 'px'){
            dialogue.set('width', width + 'px');
        }

        var iframe = '<iframe id="{0}" style="{1}" src="{2}"></iframe>'
                                                                        .replace('{0}', IFrameID)
                                                                        .replace('{1}', IFrameStyle)
                                                                        .replace('{2}', IFrame_URL);

        dialogue.set('bodyContent', iframe);
        dialogue.show();

        YUI.namespace(COMPONENTNAME).dialogue = dialogue;
    }
});