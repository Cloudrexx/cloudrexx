/* global cx */
cx.ready(function() {
  angular.module("MediaBrowser")
    .filter("translate", function() {
      return function(key) {
        return cx.variables.get(key, "mediabrowser");
      };
    });
});
