il = il || {};
il.FAU = il.FAU || {};

il.FAU.hardRestrictions = il.FAU.hardRestrictions || {};
(function($, il) {
    il.FAU.hardRestrictions = (function($) {

        // public function
        var toggleModules = function (container_id, filter) {

            var buttons = $("#" + container_id + " button");
            var btn_all = $("#" + container_id + " button.fau-all");
            var btn_fitting = $("#" + container_id + " button.fau-fitting");
            var btn_passed = $("#" + container_id + " button.fau-passed");
            var btn_selected = $("#" + container_id + " button.fau-selected");


            var all = $("#" + container_id + " li.module");
            var fitting = $("#" + container_id + " li.fitting");
            var passed = $("#" + container_id + " li.passed");
            var selected = $("#" + container_id + " li.selected");


            buttons.removeClass('engaged');

            switch (filter) {
                case 'all':
                    all.show();
                    btn_all.addClass('engaged');
                    break;
                case 'fitting':
                    all.hide();
                    fitting.show();
                    btn_fitting.addClass('engaged');
                    break;
                case 'passed':
                    all.hide();
                    passed.show();
                    btn_passed.addClass('engaged');
                    break;
                case 'selected':
                    all.hide();
                    selected.show();
                    btn_selected.addClass('engaged');
                    break;
            }
        };

        // return public interface
        return {
            toggleModules: toggleModules
        };
    })($);
})($, il);