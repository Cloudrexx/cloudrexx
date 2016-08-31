function toggle(target){
    obj = document.getElementById(target);
    obj.style.display = (obj.style.display=='none') ? 'inline' : 'none';
}
function popdown(name) {
    var e = document.getElementById(name);
    var search = document.getElementById('select_lang');
    var leftpx = fetch_object_posleft(search);
    var toppx = fetch_object_postop(search);
    toppx = toppx+25;
    e.style.position = "absolute";
    e.style.left = leftpx+'px';
    e.style.top = toppx+'px';
    e.style.display = (e.style.display == 'block') ? 'none' : 'block';
}
function fetch_object_posleft(elm){
    var left = elm.offsetLeft;
    while((elm = elm.offsetParent) != null)
    {
        left += elm.offsetLeft;
    }
    return left;
}
function fetch_object_postop(elm){
    var top = elm.offsetTop;
    while((elm = elm.offsetParent) != null)
    {
        top += elm.offsetTop;
    }
    return top;
}
