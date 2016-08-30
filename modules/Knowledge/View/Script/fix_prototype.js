// This is a very ugly hack to get prototype working here.
// It overwrites prototype's setStyle() function with a custom
// version, as the original version crashes IE6. Also, we can't
// just patch prototype, as it would break other stuff.
//
// Feel free to contact us if you have a proper fix, thanks!
//   -> http://contrexx.com/forum/

Element.addMethods({
  setStyle: function(element, styles) {
    element = $(element);
    var elementStyle = element.style, match;
    if (Object.isString(styles)) {
      element.style.cssText += ';' + styles;
      return styles.include('opacity') ?
        element.setOpacity(styles.match(/opacity:\s*(\d?\.?\d*)/)[1]) : element;
    }
    for (var property in styles)
      if (property == 'opacity') element.setOpacity(styles[property]);
      else {
        var prop_name = (property == 'float' || property == 'cssFloat')
          ? (Object.isUndefined(elementStyle.styleFloat) ? 'cssFloat' : 'styleFloat')
          : property ;

        if(styles[property] != null && styles[property] != '')
          elementStyle[prop_name] = styles[property];
      }
    return element;
  }
});
