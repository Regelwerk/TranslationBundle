###
src:    usePrimaryOnEnter.coffee
author: georg
###
$ = jQuery

$ ->
    code = (event) ->
        if event.which? then event.which else event.keyCode

    $("body.regelwerk-translation-bundle form input").keypress (event) ->
        form = $(@).closest('form')
        # Only one submit button or more then one primary button? Don't do anything!
        return true if form.find(':submit').length < 2 or form.find('.btn-primary').length != 1
        # We have an enter, prevent default and click on the primary button
        if code(event) == 13
            $(@).closest('form').find(".btn-primary").click()
            false
        else
            true
            
    $('#regelwerk_translation_form_translation').focus ->
        $(@).select().one 'mouseup', (e) -> 
            e.preventDefault()
            
    $('.regelwerk-translation-bundle-tooltip').tooltip()
    
    searchTerm = $('.regelwerk-translation-matches').data('translationTerm')
    regExp = new RegExp(searchTerm, 'gim')
    highlight = (element) ->
        newText = $(element).text().replace(regExp, '<mark>$&</mark>')
        $(element).html(newText)
        
    $('.regelwerk-translation-matches .regelwerk-translation-key, .regelwerk-translation-matches .regelwerk-translation-source, .regelwerk-translation-matches .regelwerk-translation-translation')
    .each ->
        highlight @
