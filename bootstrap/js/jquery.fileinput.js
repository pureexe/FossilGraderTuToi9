/**
 * --------------------------------------------------------------------
 * jQuery File Input Widget
 * Author: Justin Jones, justin@jstnjns.com
 * Version: 0.0.2
 * Copyright (c) 2011 Justin Jones
 *
 * Styling a <input type="file" /> sucks.  Or, it used to.. before this
 * widget was created. You can now style them just like the text inputs
 * and buttons already being used in your forms.
 *
 * Uses similar method as http://www.quirksmode.org/dom/inputfile.html
 *
 * --------------------------------------------------------------------
 */
(function($) {
  var elements = {},
      methods = {
        init : function() {
          return this.each(function() {
            var $file       = $(this).addClass('ui-file'),
                $wrapper    = $('<div />', {
                                'class' : 'ui-file-wrapper'
                              })
                                .insertBefore($file),
                $fake_group = $('<div />', {
                                'class' : 'ui-file-fake-wrapper'
                              })
                                .appendTo($wrapper),
                $fake_input = (elements.$fake_input = $('<input />', {
                                'class' : 'ui-file-fake-input',
                                'type'  : 'text'
                              }))
                                .appendTo($fake_group),
                $fake_button= (elements.$fake_button = $('<button />', {
                                'class' : 'ui-file-fake-button',
                                'type'  : 'button',
                                'text'  : 'Browse...'
                              }))
                                .appendTo($fake_group);
        
            $file
              .prependTo($wrapper)
              .change(function() {
                $fake_input.val($file.val());
              });

            $file.attr('disabled')&&disable();
          });
        },
    
        disable : function() {
          console.log(elements);
          
          elements.$fake_button
            .attr('disabled', 'disabled')
            .addClass('ui-disabled');
          elements.$fake_input
            .attr('disabled', 'disabled')
            .addClass('ui-disabled');
        },
        enable : function() {
          elements.$fake_button
            .attr('disabled', false)
            .removeClass('ui-disabled');
          elements.$fake_input
            .attr('disabled', false)
            .removeClass('ui-disabled');
        } 
  };
  
  $.fn.file = function(method) {
     
    if ( methods[method] ) {
      return methods[method].apply( this, Array.prototype.slice.call( arguments, 1 ));
    } else if ( typeof method === 'object' || ! method ) {
      return methods.init.apply( this, arguments );
    } else {
      $.error( 'Method ' +  method + ' does not exist on jQuery.tooltip' );
    }
     
  };
})(jQuery);