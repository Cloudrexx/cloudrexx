/* global cx */
cx.ready(function () {
    angular.module('MediaBrowser')
        .provider('dataTabs', dataTabsProvider);
    var dataTabs = [];

    function dataTabsProvider() {
        this.add = add;
        this.$get = $get;
        function add(tabObj) {
            dataTabs.push(tabObj);
            return this;
        }

        function $get() {
            return new dataTabsService();
        }
    }

    function dataTabsService() {
        return {
            get: get
        };
        function get() {
            return dataTabs;
        }
    }
});
