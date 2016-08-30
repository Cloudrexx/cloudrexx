
var folderWidgetApp = angular.module('FolderWidget', []);

folderWidgetApp.factory('folderWidgetConfig', function () {
    var config = {};
    return {
        set: function (key, value) {
            config[key] = value;
        },
        get: function (key) {
            return config[key];
        }
    };
});

folderWidgetApp.config(['$httpProvider', function ($httpProvider) {
    $httpProvider.defaults.headers.common["X-Requested-With"] = 'XMLHttpRequest';
    $httpProvider.defaults.headers.common["Check-CSRF"] = 'false';
}]);

folderWidgetApp.factory('mediabrowserFiles', function ($http, $q) {
  return {
      get: function (type) {
          var deferred = $q.defer();
          $http.get(cx.variables.get("cadminPath", "contrexx") + 'index.php?cmd=JsonData&object=MediaBrowser&act=' + type + '&csrf=' + cx.variables.get('csrf')).success(function (jsonadapter) {
              if (jsonadapter.data instanceof Object) {
                  deferred.resolve(jsonadapter.data);
              }
              else {
                  deferred.reject("An error occured while fetching items for " + type);
              }
          }).error(function () {
              deferred.reject("An error occured while fetching items for " + type);
          });
          return deferred.promise;
      },
      getMedia: function (widgetId) {
          return this.get('folderWidget&id=' + widgetId);
      },
      removeMedia: function (file, widgetId) {
          return this.get('removeFileFromFolderWidget&file=' + file + '&widget=' + widgetId);
      }
  };
});

folderWidgetApp.controller('MediaBrowserFolderWidgetCtrl', ['$scope', 'mediabrowserFiles', 'folderWidgetConfig',
  function($scope, mediabrowserFiles, folderWidgetConfig){
  $scope.files = [];
  $scope.isEditable = false;
  $scope.refreshBrowser = function () {
    mediabrowserFiles.getMedia(folderWidgetConfig.get('widgetId')).then(
        function getFiles(data) {
            $scope.files = data;
        }
    );
  };
  $scope.removeFile = function (file) {
    mediabrowserFiles.removeMedia(file, folderWidgetConfig.get('widgetId')).then(
        function loadFiles(data) {
            $scope.refreshBrowser();
        }
    );
  };
  $scope.isEmpty = function () {
      return $scope.files.length === 0;
  };
}]);

jQuery(function () {
  jQuery('.mediaBrowserfolderWidget').each(function(){
    angular.bootstrap(jQuery(this), ['FolderWidget']);
    var scope = angular.element(jQuery(this)).injector();

    var folderWidgetConfig = scope.get('folderWidgetConfig');
    var attrs = jQuery(this).data();
    folderWidgetConfig.set('widgetId', attrs.widgetId);
    folderWidgetConfig.set('isEditable', attrs.isEditable);

    var controllerScope = angular.element(jQuery(this)).scope();
    controllerScope.$apply(function() {
      controllerScope.isEditable = folderWidgetConfig.get('isEditable');
    });
    controllerScope.refreshBrowser();
  });
});
