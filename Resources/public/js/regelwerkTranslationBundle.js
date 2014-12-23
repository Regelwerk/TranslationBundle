/*
src:    usePrimaryOnEnter.coffee
author: georg
*/


(function() {
  var $;

  $ = jQuery;

  $(function() {
    var code;
    code = function(event) {
      if (event.which != null) {
        return event.which;
      } else {
        return event.keyCode;
      }
    };
    $("body.regelwerk-translation-bundle form input").keypress(function(event) {
      var form;
      form = $(this).closest('form');
      if (form.find(':submit').length < 2 || form.find('.btn-primary').length !== 1) {
        return true;
      }
      if (code(event) === 13) {
        $(this).closest('form').find(".btn-primary").click();
        return false;
      } else {
        return true;
      }
    });
    $('#regelwerk_translation_form_translation').focus(function() {
      return $(this).select().one('mouseup', function(e) {
        return e.preventDefault();
      });
    });
    return $('.regelwerk-translation-bundle-tooltip').tooltip();
  });

}).call(this);
