document.addEventListener("DOMContentLoaded", function() {
    const showClass = "responsive_show";

    // Add title attribute to metanavigation entries
    document.querySelectorAll("#metanavigation a").forEach(function(el) {
        el.setAttribute("title", el.textContent);
    });

    // Open / close navigation on click
    document.getElementById("header").onclick = function(e) {
        // this should only be triggered by the pseudo element
        if (e.path[0] != document.getElementById("header")) {
            return;
        }
        if (
            document.getElementById("metanavigation").classList.contains(showClass)
        ) {
            // hide
            document.querySelector(".content_wrapper.left").classList.remove(showClass);
            document.getElementById("metanavigation").classList.remove(showClass);
        } else {
            // show
            document.querySelector(".content_wrapper.left").classList.add(showClass);
            document.getElementById("metanavigation").classList.add(showClass);
        }
    };

    // Open / close language dropdown
    document.querySelector("#metanavigation>ul>li>a").onclick = function() {
        var ul = this.nextSibling.nextSibling;
        if (window.getComputedStyle(ul).display === "none") {
            ul.style.display = "block";
        } else {
            ul.style.display = "";
        }
    };

    // auto-hide language dropdown and navigation on click anywhere else
    document.getElementsByTagName("body")[0].onclick = function(e) {
        if (
            e.path != undefined &&
            e.path.indexOf(document.getElementById("metanavigation")) > -1
        ) {
            return;
        }
        document.querySelector("#metanavigation>ul>li>ul").style.display = "";
        if (
            e.path != undefined &&
            e.path.indexOf(document.querySelector(".content_wrapper.left")) > -1
        ) {
            return;
        }
        if (
            e.path != undefined &&
            e.path.indexOf(document.getElementById("header")) > -1
        ) {
            return;
        }
        document.querySelector(".content_wrapper.left").classList.remove(showClass);
        document.getElementById("metanavigation").classList.remove(showClass);
    };
});
