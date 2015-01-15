/*
src:    usePrimaryOnEnter.coffee
author: georg
*/


(function() {
  var $;

  $ = jQuery;

  $(function() {
    var code, highlight, regExp, searchTerm;
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
    $('.regelwerk-translation-bundle-tooltip').tooltip();
    searchTerm = $('.regelwerk-translation-matches').data('translationTerm');
    regExp = new RegExp(searchTerm, 'gim');
    highlight = function(element) {
      var newText;
      newText = $(element).text().replace(regExp, '<mark>$&</mark>');
      return $(element).html(newText);
    };
    return $('.regelwerk-translation-matches .regelwerk-translation-key, .regelwerk-translation-matches .regelwerk-translation-source, .regelwerk-translation-matches .regelwerk-translation-translation').each(function() {
      return highlight(this);
    });
  });

}).call(this);
