/**
 * Autocomplete input.
 */
(function ($) {
    $.fn.autocomplete = function (suggest) {
        // Wrap and extra html to input.
        var input = $(this);
        input.wrap('<span class="autocomplete" style="position: relative;"></span>');
        var html =
            '<span class="overflow" style="position: absolute; z-index: 1;">' +
                '<span class="repeat" style="opacity: 0;"></span>' +
                '<span class="guess"></span></span>';
        $('.autocomplete').prepend(html);

        // Search of input changes.
        var repeat = $('.repeat');
        var guess = $('.guess');
        var search = function (command) {
            var array = [];
            for (var key in suggest) {
                if (!suggest.hasOwnProperty(key))
                    continue;
                var pattern = new RegExp(key);
                if (command.match(pattern)) {
                    array = suggest[key];
                }
            }

            var text = command.split(' ').pop();

            var found = '';
            if (text != '') {
                for (var i = 0; i < array.length; i++) {
                    var value = array[i];
                    if (value.length > text.length &&
                        value.substring(0, text.length) == text) {
                        found = value.substring(text.length, value.length);
                        break;
                    }
                }
            }
            guess.text(found);
        };
        var update = function () {
            var command = input.val();
            repeat.text(command);
            search(command);
        };
        input.change(update);
        input.keyup(update);
        input.keypress(update);
        input.keydown(update);

        input.keydown(function (e) {
            var code = (e.keyCode ? e.keyCode : e.which);
            if (code == 9) {
                var val = input.val();
                input.val(val + guess.text());
                return false;
            }
        });

        return input;
    };
})(jQuery);