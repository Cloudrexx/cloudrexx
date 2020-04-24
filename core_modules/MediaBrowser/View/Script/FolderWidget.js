/* global cx */
cx.ready(function () {
    angular.module('FolderWidget', [])
        .config(['$httpProvider', function ($httpProvider) {
            $httpProvider.defaults.headers.common["X-Requested-With"] = 'XMLHttpRequest';
            $httpProvider.defaults.headers.common["Check-CSRF"] = 'false';
        }])
        .controller('MediaBrowserFolderWidgetCtrl', [
            '$scope', 'mediabrowserFiles', 'folderWidgetConfig',
            function ($scope, mediabrowserFiles, folderWidgetConfig) {
                $scope.files = [];
                $scope.isEditable = false;
                $scope.refreshBrowser = refreshBrowser;
                $scope.removeFile = removeFile;
                $scope.isEmpty = isEmpty;

                function refreshBrowser() {
                    mediabrowserFiles.getMedia(folderWidgetConfig.get('widgetId')).then(
                        function (data) {
                            $scope.files = data;
                        }
                    );
                }

                function removeFile(file) {
                    mediabrowserFiles.removeMedia(file, folderWidgetConfig.get('widgetId')).then(
                        function () {
                            $scope.refreshBrowser();
                        }
                    );
                }

                function isEmpty() {
                    return $scope.files.length === 0;
                }
            }
        ])
        .factory('folderWidgetConfig', function () {
            var config = {};
            return {
                set: set,
                get: get
            };
            function set(key, value) {
                config[key] = value;
            }

            function get(key) {
                return config[key];
            }
        })
        .factory('mediabrowserFiles', function ($http, $q) {
            return {
                get: get,
                getMedia: getMedia,
                removeMedia: removeMedia
            };
            function get(type) {
                var deferred = $q.defer();
                $http.get(cx.variables.get("cadminPath", "contrexx") + 'index.php?cmd=JsonData&object=MediaBrowser&act=' + type + '&csrf=' + cx.variables.get('csrf')).success(function (jsonadapter) {
                    if (jsonadapter.data instanceof Object) {
                        deferred.resolve(jsonadapter.data);
                    } else {
                        deferred.reject("An error occured while fetching items for " + type);
                    }
                }).error(function () {
                    deferred.reject("An error occured while fetching items for " + type);
                });
                return deferred.promise;
            }

            function getMedia(widgetId) {
                return this.get('folderWidget&id=' + widgetId);
            }

            function removeMedia(file, widgetId) {
                return this.get('removeFileFromFolderWidget&file=' + file + '&widget=' + widgetId);
            }
        });

    jQuery('.mediaBrowserfolderWidget').each(function () {
        angular.bootstrap(jQuery(this), ['FolderWidget']);
        var scope = angular.element(jQuery(this)).injector();
        var folderWidgetConfig = scope.get('folderWidgetConfig');
        var attrs = jQuery(this).data();
        folderWidgetConfig.set('widgetId', attrs.widgetId);
        folderWidgetConfig.set('isEditable', attrs.isEditable);
        var controllerScope = angular.element(jQuery(this)).scope();
        controllerScope.$apply(function () {
            controllerScope.isEditable = folderWidgetConfig.get('isEditable');
        });
        controllerScope.refreshBrowser();
    });
});
