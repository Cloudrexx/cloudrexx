CKEDITOR.on('instanceReady',function() {
  var removedButtons;
  // An user group is edited and therefore the buttons removed by the default
  // configuration need to be removed as well
  // Get the buttons that shall be removed
  if (window.location.pathname == cx.variables.get('cadminPath') + 'Access/group') {
    removedButtons = cx.variables.get('removedButtons', 'wysiwyg/groups');
  } else {
    removedButtons = cx.variables.get('removedButtons', 'wysiwyg/default');
  }
  // Create an array from the removed button string
  removedButtons = removedButtons.split(',');
  // Verify that there are any buttons to be removed
  if (removedButtons.length) {
    var emptyGroups = [], hasStyles = false;
    // Loop through all the buttons
    removedButtons.forEach(function (button) {
      var selector = '[data-name="' + button + '"]';
      // Hide all the buttons that need to be removed
      cx.jQuery(selector).css('display', 'none');
      if (cx.jQuery(selector).find('[type="checkbox"]').prop('checked')) {
        cx.jQuery(selector).children('label').click();
      }
    });
    removedButtons.forEach(function (button) {
      // Loop through all buttons in the current subgroup
      cx.jQuery('[data-name="' + button + '"]').parent('ul').find('li').each(function () {
        hasStyles = cx.jQuery(this).attr('style');
        // Check if a button isn't hidden
        if (!hasStyles) {
          // Exit immediately if a button is not hidden to avoid hiding
          // it due to the next button being hidden
          return false;
        }
      });
      // Check if every button in the subgroup is hidden
      if (hasStyles) {
        // Add the current subgroup to the emptyGroups array
        var subgroup = cx.jQuery('[data-name="' + button + '"]').parent().parent();
        emptyGroups.push(subgroup);
      }
    });
    if (emptyGroups.length) {
      emptyGroups.forEach(function (emptyButtonGroup) {
        cx.jQuery(emptyButtonGroup).addClass('empty');
      });
    }
  }
});
