!function (jQuery) {
    var $ = jQuery;

    var mediaBrowserApp = angular.module('MediaBrowser', ['plupload.module', 'ngAnimate', 'ui.bootstrap', 'ui.bootstrap.tpls']);

    angular.module('plupload.module', []).config(['$httpProvider', function ($httpProvider) {
        $httpProvider.defaults.headers.common["X-Requested-With"] = 'XMLHttpRequest';
    }]);

    mediaBrowserApp.config(['$provide', function ($provide) {
        $provide.decorator('$browser', ['$delegate', function ($delegate) {
            $delegate.onUrlChange = function (a) {
            };
            $delegate.history = false;
            $delegate.url = function () {
                return "";
            };
            return $delegate;
        }]);
    }]);

    mediaBrowserApp.filter('translate', function () {
        return function (key) {
            return cx.variables.get(key, 'mediabrowser');
        }
    });

    mediaBrowserApp.config(['$httpProvider', function ($httpProvider) {
        $httpProvider.defaults.headers.common["X-Requested-With"] = 'XMLHttpRequest';
    }]);

    mediaBrowserApp.factory('mediabrowserFiles', function ($http, $q) {
        return {
            get: function (type) {
                var deferred = $q.defer();
                $http.get(cx.variables.get("cadminPath", "contrexx") + 'index.php?cmd=jsondata&object=MediaBrowser&act=' + type + '&csrf=' + cx.variables.get('csrf')).success(function (jsonadapter) {
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
            getByMediaType: function (mediatype) {
                return this.get('getFiles&mediatype=' + mediatype)
            }
        }
    });

    mediaBrowserApp.factory('mediabrowserConfig', function () {
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

    /* CONTROLLERS */
    mediaBrowserApp.controller('MainCtrl', ['$scope', '$modalInstance', '$modal', '$location', '$http', 'mediabrowserConfig', 'mediabrowserFiles', '$timeout',
        function ($scope, $modalInstance, $modal, $location, $http, mediabrowserConfig, mediabrowserFiles, $timeout) {
            /**
             * Sorting and searching
             */
            $scope.sorting = 'cleansize';
            $scope.searchString = '';
            $scope.reverse = false;
            $scope.sources = [];
            $scope.fileCallBack = '';
            $scope.files = [];
            $scope.dataFiles = [];
            $scope.sites = [];
            $scope.inRootDirectory = true;

            $scope.path = [
                {name: cx.variables.get('TXT_FILEBROWSER_FILES', 'mediabrowser'), path: 'files', standard: true}
            ];

            $scope.activeController = mediabrowserConfig.get('startView');

            mediabrowserFiles.get('getSites').then(
                function getSites(data) {
                    $scope.sites = data;
                }
            );

            mediabrowserFiles.get('getSources').then(
                function getSources(data) {
                    $scope.sources = data;

                    if (mediabrowserConfig.get('startMedia')) {
                        data.forEach(function (source) {
                            if (source.value == mediabrowserConfig.get('startMedia')) {
                                $scope.selectedSource = source;
                                return false;
                            }
                        });
                    }
                    else {
                        $scope.selectedSource = data[0];
                    }
                    $scope.path[0].path = $scope.selectedSource.value;
                    if (mediabrowserConfig.get('mediatypes') != 'all') {
                        var i = data.length;
                        while (i--) {
                            if (!(mediabrowserConfig.get('mediatypes').indexOf(data[i].value) > -1)) {
                                data.splice(i, 1);
                            }
                        }
                    }

                    mediabrowserFiles.getByMediaType($scope.selectedSource.value).then(
                        function getFiles(data) {
                            $scope.dataFiles = data;
                            $scope.files = $scope.dataFiles;
                        }
                    );
                }, function (reason) {
                    bootbox.dialog({
                        title: "An error has occurred.",
                        message: reason
                    });
                }
            );

            $scope.ok = function () {
                $modalInstance.close();
            };

            $scope.cancel = function () {
                $scope.closeModal();
            };

            $scope.changeSorting = function (newSorting) {
                if (newSorting == $scope.sorting) {
                    $scope.reverse = !$scope.reverse;
                }
                else {
                    $scope.sorting = newSorting;
                    $scope.reverse = false;
                }
            };

            $scope.closeModal = function () {
                $modalInstance.dismiss('cancel');
                var fn = mediabrowserConfig.get('callbackWrapper');
                if (typeof fn === 'function') {
                    fn({type: 'close', data: []});
                    $scope.selectedFiles = [];
                }
            };


            $scope.dataTabs = [
                {
                    label: cx.variables.get('TXT_FILEBROWSER_UPLOADER', 'mediabrowser'),
                    icon: 'icon-upload',
                    controller: 'UploaderCtrl',
                    name: 'uploader'
                },
                {
                    label: cx.variables.get('TXT_FILEBROWSER_FILEBROWSER', 'mediabrowser'),
                    icon: 'icon-folder',
                    controller: 'MediaBrowserListCtrl',
                    name: 'filebrowser'
                },
                {
                    label: cx.variables.get('TXT_FILEBROWSER_SITESTRUCTURE', 'mediabrowser'),
                    icon: 'icon-sitestructure',
                    controller: 'SitestructureCtrl',
                    name: 'sitestructure'
                }
            ];
            $scope.tabs = $scope.dataTabs;

            $scope.go = function (path) {
                $scope.activeController = path;
            };

            $scope.getPathAsString = function () {
                var pathstring = '';
                $scope.path.forEach(function (path) {
                    pathstring += path.path + '/';
                });
                return pathstring;
            };

            //// cx-mb-views
            if (mediabrowserConfig.get('views') != 'all') {
                var isStartviewInViews = false;
                var newTabNames = mediabrowserConfig.get('views');

                var newTabs = [];

                newTabNames.forEach(function (newTabName) {
                    $scope.dataTabs.forEach(function (tab) {
                        if (tab.name === newTabName) {
                            if (newTabName === mediabrowserConfig.get('startView'))
                                isStartviewInViews = true;
                            newTabs.push(tab);
                        }
                    });
                });
                $scope.tabs = newTabs;
                if (!isStartviewInViews) {
                    $scope.go($scope.tabs[0].controller);
                }

                if (newTabs.length === 1) {
                    //jQuery(".mediaBrowserMain").addClass('no-nav');
                }
            }

            $scope.selectedTab = $scope.tabs[0];
            $scope.setSelectedTab = function (tab) {
                $scope.activeController = tab.controller;
            };

            $scope.updateSource = function () {
                var oldpath = $scope.path;
                $scope.path = [
                    {name: "" + $scope.selectedSource.name, path: $scope.selectedSource.value, standard: true}
                ];
                jQuery(".loadingPlatform").fadeIn();
                jQuery(".filelist").fadeOut();
                mediabrowserFiles.getByMediaType($scope.selectedSource.value).then(
                    function getFiles(data) {
                        jQuery(".loadingPlatform").fadeOut();
                        $scope.dataFiles = data;
                        $scope.files = data;
                        $timeout(function () {
                            for (var i in oldpath){
                                if (i > 0){
                                    $scope.extendPath(oldpath[i].path);
                                }
                            }
                            $scope.$apply();
                            jQuery(".filelist").fadeIn();
                        });
                    }
                );
            };

            $scope.refreshBrowser = function () {
                var files = $scope.dataFiles;
                $scope.selectedFiles = [];
                $scope.setFiles($scope.dataFiles);
                $scope.path.forEach(function (pathpart) {
                    if (!pathpart.standard) {
                        files = files[pathpart.path];
                    }
                });
                $scope.setFiles(files);
                $scope.inRootDirectory = ($scope.path.length == 1);
            };
            /**
             * Move up in the directory structure to the specified directory
             * @param dirName
             */
            $scope.extendPath = function (dirName) {
                if (Array.isArray($scope.path)) {
                    $scope.path.push({name: dirName, path: dirName, standard: false});
                }
                $scope.inRootDirectory = ($scope.path.length == 1);
                $scope.searchString = '';
                $scope.refreshBrowser();
            };

            /**
             * Move down in the directory structure
             * @param countDirs
             */
            $scope.shrinkPath = function (countDirs) {
                if (Array.isArray($scope.path)) {
                    for (var i = 0; i < countDirs; i++) {
                        if ($scope.path[$scope.path.length - 1].standard === false)
                            $scope.path = $scope.path.slice(0, -1);
                    }
                }
                $scope.inRootDirectory = ($scope.path.length == 1);
                $scope.refreshBrowser();
            };

            $scope.setFiles = function (files) {
                $scope.files = files;
            };

            $scope.changeLocation = function (url, forceReload) {
                $scope = $scope || angular.element(document).scope();
                if (forceReload || $scope.$$phase) {
                    window.location = url;
                }
                else {
                    $location.path(url);
                    $scope.$apply();
                }
            };



            $scope.afterUpload = function () {
                $scope.updateSource();
            };

        }]);


    mediaBrowserApp.controller('UploaderCtrl', ['$scope',
        function ($scope) {

            $scope.uploaderData = {
                filesToUpload: []
            };

            $scope.progress = 0;
            $scope.progressMessage = '';
            $scope.finishedUpload = false;

            $scope.template = {
                url: cx.variables.get('basePath','contrexx')+'core_modules/MediaBrowser/View/Template/_Uploader.html'
            };

            $scope.loadedTemplate = function () {
                // PLUPLOADER INTEGRATION
                $scope.uploader = new plupload.Uploader({
                    runtimes: 'flash,html5,silverlight,html4',
                    browse_button: 'selectFileFromComputer',
                    container: 'uploader',
                    drop_element: "uploader",
                    url: '?csrf=' + cx.variables.get('csrf') + '&cmd=jsondata&object=Uploader&act=upload',
                    flash_swf_url: '/lib/plupload/js/Moxie.swf',
                    silverlight_xap_url: '/lib/plupload/js/Moxie.xap',
                    chunk_size: '500kb',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    filters: {
                        max_file_size: '50cxMb',
                        mime_types: [
                            {title: "Allowed files", extensions: "jpg,gif,png,bmp,jpeg,tif,tiff"},
                            {title: "Compressed files", extensions: "zip,tar,gz"},
                            {title: "PDF files", extensions: "pdf"},
                            {title: "Audio Files", extensions: "3gp,act,aiff,aac,amr,ape,au,awb,dct,dss,flac,gsm,m4a,m4p,mp3,mpc,ogg,oga,opus,ra,rm,raw,sln,tta,vox,wav,wma,wv,webm"},
                            {title: "Words files", extensions: "doc,docx"}
                        ]
                    },
                    multipart_params: {
                        "path": ''
                    },
                    init: {
                        Init: function(){
                            $J('#selectFileFromComputer').prop("disabled", false);
                        },
                        FilesAdded: function (up, files) {
                            if ($scope.finishedUpload) {
                                $scope.uploaderData.filesToUpload = [];
                                $scope.finishedUpload = false;
                            }
                            for (var file in files) {
                                if (files.hasOwnProperty(file)){
                                    $scope.uploaderData.filesToUpload.push(files[file]);
                                }
                            }
                            $scope.uploader.settings.multipart_params.path = $scope.getPathAsString();

                            $scope.$digest();
                        },
                        UploadProgress: function () {
                            $scope.$digest();
                        },
                        UploadComplete: function () {
                            $scope.finishedUpload = true;
                            jQuery('.uploadFilesAdded').hide();
                            $scope.afterUpload();
                        },
                        Error: function (up, err) {
                            var mediaUploaderListCtrl = $('.mediaUploaderListCtrl');
                            mediaUploaderListCtrl.find('.uploadPlatform').addClass('fileError');
                            mediaUploaderListCtrl.find('.uploadPlatform .error').html(cx.variables.get('TXT_CORE_MODULE_UPLOADER_ERROR_' + /[0-9]+/.exec(err.code), 'mediabrowser'));
                            setTimeout(function () {
                                mediaUploaderListCtrl.find(' .uploadPlatform').removeClass('fileError');
                            }, 3000);
                            up.refresh(); // Reposition Flash/Silverlight
                        }
                    }
                });
                $scope.uploader.init();
            };

            $scope.startUpload = function () {
                $scope.uploader.start();
                jQuery('.uploadFilesAdded').show();
            };

            $scope.removeFile = function (file) {
                jQuery.each($scope.uploaderData.filesToUpload, function (i) {
                    if ($scope.uploaderData.filesToUpload[i] === file) {
                        $scope.uploaderData.filesToUpload.splice(i, 1);
                        $scope.uploader.removeFile(file);
                        return false;
                    }
                });
            };

        }]);

    mediaBrowserApp.controller('MediaBrowserListCtrl', ['$scope', '$http', 'mediabrowserConfig',
        function ($scope, $http, mediabrowserConfig) {

            $scope.lastActiveFile = {};
            $scope.noFileSelected = true;
            $scope.selectedFiles = [];

            $scope.searchConfig = {
                isRegex: false,
                string: ""
            };

            $scope.template = {
                url: cx.variables.get('basePath','contrexx')+'core_modules/MediaBrowser/View/Template/_FileBrowser.html'
            };
            // __construct



            /**
             * Get the full path string.
             * @returns {string}
             */
            $scope.getPathString = function () {
                var returnValue = '/';
                $scope.path.forEach(function (pathpart) {
                    returnValue += pathpart.path + '/';
                });
                return returnValue;
            };



            /**
             * @param file Array The clicked file.
             * @param file.datainfo Array
             * @param index Integer Index of the file
             * @param selectFile Boolean If the file has been double clicked, this is true.
             */
            $scope.clickFile = function (file, index, selectFile) {
                if (file.datainfo.extension == 'Dir') {
                    $scope.extendPath(file.datainfo.name);
                }
                else {
                    if (file.datainfo.active === true && !selectFile) {
                        for (var i in $scope.selectedFiles) {
                            if ($scope.selectedFiles[i].datainfo.id == file.datainfo.id) {
                                $scope.selectedFiles.splice(i, 1);
                            }
                        }
                        file.datainfo.active = false;
                    }
                    else {
                        file.datainfo.active = true;
                        $scope.selectedFiles.push(file);
                        if (!mediabrowserConfig.get('multipleSelect') && selectFile) {
                            $scope.selectedFiles = [];
                            file.datainfo.active = false;

                            var fn = mediabrowserConfig.get('callbackWrapper');

                            if (typeof fn === 'function') {
                                fn({type: 'file', data: [file]});
                            }
                            if (!jQuery.isEmptyObject($scope.lastActiveFile)) {
                                $scope.lastActiveFile.datainfo.active = false;
                            }
                            $scope.lastActiveFile = file;
                            $scope.closeModal();
                        }
                    }

                    $scope.noFileSelected = $scope.selectedFiles.length == 0;
                }
            };

            $scope.escapeString = function(string){
                return string.replace(/&/g, '&amp;').replace(/'/g, '&apos;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
            };

            $scope.renameFile = function (file, index) {
                var splittedFileName = [];

                splittedFileName[0] = file.datainfo.name;
                if (file.datainfo.extension !== 'Dir') {
                    splittedFileName = file.datainfo.name.split('.');
                }

                var fileExtension = '';
                var fileName = file.datainfo.name;
                if (splittedFileName.length != 1) {
                    fileExtension = splittedFileName.pop();
                    fileName = splittedFileName.join('.');
                }


                var renameForm = '<div id="mediabrowser-renamefile">' +
                    '<div class="file-name"><input type="text" class="form-control" value="' + $scope.escapeString(fileName) + '"/></div>' +
                    '<div class="file-dot">.</div>' +
                    '<div class="file-extension"><input type="text" class="form-control" value="' + fileExtension + '" disabled/></div>  </div>';
                var renameDialog = bootbox.dialog({
                    title: cx.variables.get('TXT_FILEBROWSER_FILE_RENAME', 'mediabrowser'),
                    message: renameForm,
                    buttons: {

                        danger: {
                            label: cx.variables.get('TXT_FILEBROWSER_CANCEL', 'mediabrowser'),
                            className: "btn-danger",
                            callback: function () {

                            }
                        },
                        success: {
                            label: cx.variables.get('TXT_FILEBROWSER_FILE_RENAME', 'mediabrowser'),
                            className: "btn-success",
                            callback: function () {
                                var newName = $('#mediabrowser-renamefile').find('.file-name input').val();
                                if (newName === null) {

                                } else {
                                    $http({
                                        method: 'POST',
                                        url: 'index.php?cmd=jsondata&object=MediaBrowser&act=renameFile&path=' + encodeURI($scope.getPathAsString()) + '&csrf=' + cx.variables.get('csrf'),
                                        data: $.param({
                                            oldName: file.datainfo.name,
                                            newName: newName
                                        }),
                                        headers: {
                                            'Content-Type': 'application/x-www-form-urlencoded',
                                            'X-Requested-With': 'XMLHttpRequest'
                                        }

                                    }).success(function (jsonadapter) {
                                        if (jsonadapter.message) {
                                            bootbox.alert(jsonadapter.message);
                                        }
                                        else {
                                            bootbox.alert('Ein Fehler ist aufgetreten');
                                        }
                                        $scope.updateSource();
                                    });
                                }
                            }
                        }
                    }
                });
                renameDialog.bind('shown.bs.modal', function(){
                    renameDialog.find("input").select().keypress(function (e) {
                        if (e.which == 13) {
                            e.preventDefault();
                            jQuery(this).blur();
                            jQuery(renameDialog).find('.btn-success').focus().click();
                        }
                    });
                });
            };


            $scope.removeFile = function (file, index) {
                var removeDialog = bootbox.dialog({
                    title: cx.variables.get('TXT_FILEBROWSER_FILE_REMOVE_FILE', 'mediabrowser'),
                    message: cx.variables.get('TXT_FILEBROWSER_ARE_YOU_SURE', 'mediabrowser'),
                    buttons: {
                        danger: {
                            label: cx.variables.get('TXT_FILEBROWSER_CANCEL', 'mediabrowser'),
                            className: "btn-default",
                            callback: function () {

                            }
                        },
                        success: {
                            label: cx.variables.get('TXT_FILEBROWSER_FILE_REMOVE', 'mediabrowser'),
                            className: "btn-danger",
                            callback: function () {
                                $http({
                                    method: 'POST',
                                    url: 'index.php?cmd=jsondata&object=MediaBrowser&act=removeFile&path=' + encodeURI($scope.getPathAsString())  + '&csrf=' + cx.variables.get('csrf'),
                                    data: $.param({
                                        file: file
                                    }),
                                    headers: {
                                        'Content-Type': 'application/x-www-form-urlencoded',
                                        'X-Requested-With': 'XMLHttpRequest'
                                    }
                                }).success(function (jsonadapter) {
                                    $scope.updateSource();
                                });

                                $scope.refreshBrowser();
                            }
                        }
                    }
                });
                removeDialog.bind('shown.bs.modal', function(){
                    removeDialog.find(".btn-danger").focus();
                });
            };


            $scope.clickPath = function (index) {
                var shrinkby = $scope.path.length - index - 1;
                if (shrinkby > 0) {
                    $scope.shrinkPath(shrinkby);
                }
            };

            $scope.createFolder = function () {
                bootbox.prompt(cx.variables.get('TXT_FILEBROWSER_DIRECTORY_NAME', 'mediabrowser'), function (dirName) {
                    if (dirName === null) {

                    } else {
                        $http({
                            method: 'POST',
                            url: 'cadmin/index.php?cmd=jsondata&object=MediaBrowser&act=createDir&path=' + encodeURI($scope.getPathAsString())  + '&csrf=' + cx.variables.get('csrf'),
                            data: $.param({dir: dirName}),
                            headers: {'Content-Type': 'application/x-www-form-urlencoded'}
                        }).success(function (jsonadapter) {
                            $scope.updateSource();
                            bootbox.alert(jsonadapter.message);
                        });
                    }
                });
            };

            $scope.choosePictures = function () {
                var fn = mediabrowserConfig.get('callbackWrapper');
                $scope.closeModal();
                for (var i = 0; i < $scope.selectedFiles.length; i++) {
                    var item = $scope.selectedFiles[i];
                    item.datainfo.active = false;
                }
                if (typeof fn === 'function') {
                    fn({type: 'file', data: $scope.selectedFiles});
                    $scope.selectedFiles = [];
                }


            };
        }
    ]);

    mediaBrowserApp.controller('SitestructureCtrl', ['$scope', 'mediabrowserConfig',
        function ($scope, mediabrowserConfig) {

            $scope.template = {
                url: cx.variables.get('basePath','contrexx')+'core_modules/MediaBrowser/View/Template/_Sitestructure.html'
            };

            $scope.clickPage = function (site) {

                var fn = mediabrowserConfig.get('callbackWrapper');
                if (typeof fn === 'function') {
                    fn({type: 'page', data: [site]});
                }
                $scope.closeModal();
            }

        }
    ]);


    /* DIRECTIVES */
    /* preview function */
    mediaBrowserApp.directive('previewImage', ['Thumbnail', function (Thumbnail) {
        return {
            restrict: 'A',
            link: function (scope, el, attrs) {
                if (attrs.previewImage !== 'none') {
                    Thumbnail.isImage(attrs.previewImage).then(function () {
                        jQuery(el).popover({
                            trigger: 'hover',
                            html: true,
                            content: '<img src="' + attrs.previewImage + '"  />',
                            placement: 'right'
                        });
                    }, function () {
                        //$http.get('index.php?cmd=jsondata&object=MediaBrowser&act=createThumbnails&file=" + attrs.previewImage').
                        //    success(function (data, status, headers, config) {
                        //        jQuery(el).popover({
                        //            trigger: 'hover',
                        //            html: true,
                        //            content: '<img src="' + attrs.previewImage + '"  />',
                        //            placement: 'right'
                        //        });
                        //    })
                    });


                }
            }
        };
    }]);


    mediaBrowserApp.factory('Thumbnail', function ($q) {
        return {
            isImage: function (src) {
                var deferred = $q.defer();

                var image = new Image();
                image.onerror = function () {
                    deferred.resolve(false);
                };
                image.onload = function () {
                    deferred.resolve(true);
                };
                image.src = src;
                return deferred.promise;
            }
        };
    });

    mediaBrowserApp.directive('sglclick', ['$parse', function ($parse) {
        return {
            restrict: 'A',
            link: function (scope, element, attr) {
                var fn = $parse(attr['sglclick']);
                var delay = 300, clicks = 0, timer = null;
                element.on('click', function (event) {
                    clicks++;  //count clicks
                    if (clicks === 1) {
                        timer = setTimeout(function () {
                            scope.$apply(function () {
                                fn(scope, {$event: event});
                            });
                            clicks = 0;             //after action performed, reset counter
                        }, delay);
                    } else {
                        clearTimeout(timer);    //prevent single-click action
                        clicks = 0;             //after action performed, reset counter
                    }
                });
            }
        };
    }]);

    mediaBrowserApp.filter('findPage', function () {
        return function (items, search) {
            if (!items) {
                return [];
            }
            var filtered = [];
            var letterMatch = new RegExp(search, 'i');
            for (var i = 0; i < items.length; i++) {
                var item = items[i];
                if (letterMatch.test(item.name)) {
                    filtered.push(item);
                }
            }
            return filtered;
        };
    });

    mediaBrowserApp.filter('orderAndSearchFiles', function () {
        return function (input, attribute, reverse, searchFile, isRegex) {
            if (!angular.isObject(input)) return input;
            var array = [];
            for (var objectKey in input) {
                array.push(input[objectKey]);
            }
            array.sort(function (a, b) {
                if (a.datainfo && b.datainfo) {
                    a = (a['datainfo'][attribute]);
                    b = (b['datainfo'][attribute]);
                    if (reverse) {
                        if (a < b) return -1;
                        if (a > b) return 1;
                    }
                    else {
                        if (a < b) return 1;
                        if (a > b) return -1;
                    }
                }
                return 0;
            });
            if (searchFile != '') {
                var searchObject;
                if (isRegex) {
                    try {
                        searchObject = function () {
                            var regex = new RegExp(searchFile, 'i');
                            return function (string) {
                                return regex.test(string);
                            }
                        }();
                    }
                    catch (e) {
                        return [];
                    }
                }
                else {
                    searchObject = function () {
                        var fileSearchWords = searchFile.split(" ");
                        return function (string) {
                            for (var key in fileSearchWords) {
                                if (!(string.toLowerCase().indexOf(fileSearchWords[key].toLowerCase()) >= 0)) {
                                    return false;
                                }
                            }
                            return true;
                        }
                    }()
                }
                return recursiveSearch(searchObject, array, 0);
            }
            return array;
        }

    });


    function recursiveSearch(searchObject, searchArray, level) {
        if (level > 6) { //TODO Is this really necessary?
            return [];
        }
        var resultArray = [];
        for (var key in searchArray) {
            if (key == 'datainfo') {
                continue;
            }
            if (searchArray[key]['datainfo'] != undefined
                && searchArray[key]['datainfo']['name'] != undefined
                && searchArray[key]['datainfo']['extension'] != 'Dir'
                && searchObject(searchArray[key]['datainfo']['name'])) {
                resultArray.push(searchArray[key]);
            }
            if (searchArray[key] instanceof Object) {
                resultArray = resultArray.concat(recursiveSearch(searchObject, searchArray[key], level++));
            }
        }
        return resultArray;
    }


    jQuery(function () {

        jQuery('button.mediabrowser-button').each(function () {

            angular.bootstrap(jQuery(this).next('.mediaBrowserScope')[0], ['MediaBrowser']);
            var scope = angular.element(jQuery(this).next()[0]).injector();
            if (!scope){
                console.warn('.mediaBrowserScope Element is missing, please generate the button only with the mediabrowser class and not by yourself!');
                return;
            }

            jQuery(this).on('click', function (event, config) {
                var mediabrowserConfig = scope.get('mediabrowserConfig');
                var $modal = scope.get('$modal');

                var attrs = jQuery(this).data();

                for (var i in config) {
                    attrs[i] = config[i];
                }

                /**
                 * Set all options and default values
                 */
                mediabrowserConfig.set('startView', 'MediaBrowserListCtrl');
                if (attrs.cxMbStartview) {
                    mediabrowserConfig.set('startView', attrs.cxMbStartview.charAt(0).toUpperCase() + attrs.cxMbStartview.slice(1) + "Ctrl");
                }

                mediabrowserConfig.set('views', 'all');
                if (attrs.cxMbViews) {
                    mediabrowserConfig.set('views', attrs.cxMbViews.trim().split(","));
                }

                mediabrowserConfig.set('startMedia', 'files');
                if (attrs.cxMbStartmediatype) {
                    mediabrowserConfig.set('startMedia', attrs.cxMbStartmediatype);
                }

                mediabrowserConfig.set('mediatypes', 'all');
                if (attrs.cxMbMediatypes) {
                    mediabrowserConfig.set('mediatypes', attrs.cxMbMediatypes.split(/[\s,]+/));
                }

                mediabrowserConfig.set('multipleSelect', false);
                if (attrs.cxMbMultipleselect) {
                    mediabrowserConfig.set('multipleSelect', attrs.cxMbMultipleselect);
                }

                mediabrowserConfig.set('modalOpened', false);
                if (attrs.cxMbCbJsModalopened) {
                    mediabrowserConfig.set('modalOpened', attrs.cxMbCbJsModalopened);
                }

                if (typeof(config) !== 'undefined' && typeof(config.callback) !== 'undefined') {
                    mediabrowserConfig.set('modalClosed', config.callback);
                }
                else {
                    mediabrowserConfig.set('modalClosed', false);
                    if (attrs.cxMbCbJsModalclosed) {
                        mediabrowserConfig.set('modalClosed', attrs.cxMbCbJsModalclosed);
                    }
                }

                $modal.open({
                    templateUrl: cx.variables.get('basePath','contrexx')+'core_modules/MediaBrowser/View/Template/MediaBrowserModal.html',
                    controller: 'MainCtrl',
                    dialogClass: 'media-browser-modal',
                    size: 'lg',
                    backdrop: 'static',
                    backdropClass: 'media-browser-modal-backdrop',
                    windowClass: 'media-browser-modal-window'
                });

                /**
                 * Configuring Callbacks
                 */
                if (mediabrowserConfig.get('modalOpened') !== false) {
                    var fn = window[mediabrowserConfig.get('modalOpened')];
                    var data = {type: 'modalopened', data: []};
                    if (typeof fn === 'function') {
                        fn(data);
                    }
                }

                mediabrowserConfig.set('callbackWrapper', function (e) {
                    scope.tabs = scope.dataTabs;
                    if (mediabrowserConfig.get('modalClosed') !== false) {
                        if (typeof mediabrowserConfig.get('modalClosed') === 'function') {
                            mediabrowserConfig.get('modalClosed')(e);
                        }
                        else {
                            var windowScope = window;
                            var scopeSplit = mediabrowserConfig.get('modalClosed').split('.');
                            for (var i = 0; i < scopeSplit.length - 1; i++) {
                                windowScope = windowScope[scopeSplit[i]];
                                if (scope == undefined) return;
                            }
                            var fn = windowScope[scopeSplit[scopeSplit.length - 1]];
                            if (typeof fn === 'function') {
                                fn(e);
                            }
                        }
                    }
                });
            });
            jQuery(this).removeAttr('disabled');
        });
    });
}(cx.variables.get('jquery', 'mediabrowser'));