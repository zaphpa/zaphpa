$('document').ready(function(){	
  //$('#column').toc({exclude: 'h6', context: '#content', autoId: true});
  
  var safeTocString = function(text) {
    var nText = text.replace(/\s/gi,"_");
    nText = nText.replace(/[,.!?;:\/\$\&]/gi, "");
    return nText;    
  }
  
  $('#toc').toc({
      'selectors': 'h2,h3,h4', //elements to use as headings
      'container': '#content', //element to find all selectors in
      'smoothScrolling': true, //enable or disable smooth scrolling on click
      'prefix': 'toc', //prefix for anchor tags and class names
      'highlightOnScroll': true, //add class to heading that is currently in focus
      //'highlightOffset': 10, //offset to trigger the next headline
      'anchorName': function(i, heading, prefix) { //custom function for anchor name
        return safeTocString(heading.textContent);        
      }
  });
    
  $("pre").addClass("prettyprint linenums");
  prettyPrint();
});

$(function(){
  $('toc-h3').hide();
  $('toc-h3').toggle(function(){
    $(this).siblings('ul').fadeIn(300);
    return false;
  }, function(){
    $(this).siblings('ul').fadeOut(300);
    return false;
  });
});
