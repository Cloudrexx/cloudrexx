/* START CUSTOM JQUERY =================================================================== */
$jq(document).ready(function(){
/*----------------------------------*/
/*        Navigation
/*----------------------------------*/
  $jq('ul.sf-menu').supersubs({
    minWidth: 14,
    maxWidth: 28,
    extraWidth: 1
  }).superfish({
    delay: 0,
    speed: 'fast',
    disableHI: true,
    animation: {opacity:'show', height:'show'}
  });
  var $responsive_nav = $jq("<select />");
  $jq("<option />", {"selected": "selected", "value": "", "text": "Select a page"}).appendTo($responsive_nav);
  $responsive_nav.appendTo(".navigation-wrapper");
  $jq(".navigation-wrapper ul li a").each(function(){
    var nav_url = $jq(this).attr("href");
    var nav_text = $jq(this).text();
    if ($jq(this).parents("li").length == 2) { nav_text = '- ' + nav_text; }
    if ($jq(this).parents("li").length == 3) { nav_text = "-- " + nav_text; }
    if ($jq(this).parents("li").length > 3) { nav_text = "--- " + nav_text; }
    $jq("<option />", {"value": nav_url, "text": nav_text}).appendTo($responsive_nav)
  })
  field_id = ".navigation-wrapper select";
  $jq(field_id).change(function()
  {
	 value = $jq(this).val();
     window.location = value;
  });
/*----------------------------------*/
/*       Image overlays
/*----------------------------------*/
  $jq(".lightbox-photo .icon").css({ opacity: 0 });
  $jq(".lightbox-photo .icon").css({ top: -100 });
  $jq('.lightbox-photo a').bind('mouseenter',function(){
    var $ele = $jq(this);
      // portfolio icon
    $ele.find('.icon').stop(true).animate({ 'opacity': 1, 'top':'50%' }, 300).andSelf()
      .find('img').stop(true).animate({ 'opacity': 0 }, 300).andSelf()
  }).bind('mouseleave',function(){
    var $ele = $jq(this);
      // portfolio icon
    $ele.find('.icon').stop(true).animate({ 'opacity': 0, 'top':'-100px' }, 200).andSelf()
      .find('img').stop(true).animate({ 'opacity': 1 }, 300).andSelf()
  });
  $jq('.lightbox-content-photo a').bind('mouseenter',function(){
    var $ele = $jq(this);
      // portfolio icon
    $ele.find('img').stop(true).animate({ 'opacity': 0.5 }, 300).andSelf()
  }).bind('mouseleave',function(){
    var $ele = $jq(this);
      // portfolio icon
    $ele.find('img').stop(true).animate({ 'opacity': 1 }, 300).andSelf()
  });
// For latest section and portfolio items
  $jq(".item-hover .hlp-description, .item-hover .portfolio-item-description").css({ opacity: 0 });
  $jq(".item-hover .hlp-description, .item-hover .portfolio-item-description").css({ top: -100 });
  $jq('.item-hover').bind('mouseenter',function(){
    var $ele = $jq(this);
      // portfolio description
    $ele.find('.hlp-description, .portfolio-item-description').stop(true).animate({ 'opacity': 1, 'top':'0px' }, 300).andSelf()
  }).bind('mouseleave',function(){
    var $ele = $jq(this);
      // portfolio description
    $ele.find('.hlp-description, .portfolio-item-description').stop(true).animate({ 'opacity': 0, 'top':'-100px' }, 200).andSelf()
  });
/*----------------------------------*/
/*       jQuery UI Tools
/*----------------------------------*/
//  $jq(".accordion").accordion ({
//    header: "h3"
//  });
  $jq(".toggle div").hide(); // hide div on default
  $jq(".toggle h3").click(function(){ // set the trigger
    $jq(this).toggleClass("active").next().slideToggle(300); // add class active and toggle speed
    return false;
  });
  $jq(".tabs").tabs({ fx: { opacity: 'show' } });
/*----------------------------------*/
/*      FitVids plugin
/*----------------------------------*/
  $jq(".container").fitVids();
/*----------------------------------*/
/*      Content Slider
/*----------------------------------*/
  $jq('.content-slider').carousel({
    nextSlide : '.next',
    prevSlide : '.prev',
    addNav : false
  });
/*----------------------------------*/
/*         Tooltip
/*----------------------------------*/
  $jq('.socials ul li').mouseenter(function(){
    $jq(this).find('.tooltip').stop().fadeIn();
  });
  $jq('.socials ul li').mouseleave(function(){
    $jq(this).find('.tooltip').stop().fadeOut();
  });
/*----------------------------------*/
/*        Scroll To Top
/*----------------------------------*/
  $jq('.scrollup').click(function(){
        $jq("html, body").animate({ scrollTop: 0 }, 600);
        return false;
    });
/*----------------------------------*/
/*        Flickr Feed
/*----------------------------------*/
  $jq('#flickr_badges ul').jflickrfeed({
    limit: 6,
    qstrings: {
      id: '67664457@N06'
    },
    itemTemplate:
    '<li>' +
      '<a data-rel="prettyPhoto[flickr-feed]" href="{{image_b}}" title="{{title}}">' +
        '<img src="{{image_s}}" alt="{{title}}" />' +
      '</a>' +
    '</li>'
  }, function(data) {
    $jq("a[data-rel^='prettyPhoto']").prettyPhoto({
      overlay_gallery: false
    });
  });
/*----------------------------------*/
/*        First Word Wrap
/*----------------------------------*/
  $jq('h6').each(function(index) {
    var firstWord = $jq(this).text().split(' ')[0];
    var replaceWord = "<span class='first-word'>" + firstWord + "</span>";
    var newString = $jq(this).html().replace(firstWord, replaceWord);
    $jq(this).html(newString);
  });
/*----------------------------------*/
/*       Responsive table
/*----------------------------------*/
  var switched = false;
  var updateTables = function() {
  if (($jq(window).width() < 959) && !switched ){
    switched = true;
    $jq("table.responsive-table").each(function(i, element) {
      splitTable($(element));
    });
    return true;
  }
  else if (switched && ($jq(window).width() > 959)) {
    switched = false;
    $jq("table.responsive-table").each(function(i, element) {
      unsplitTable($(element));
    });
  }
  };
  $jq(window).load(updateTables);
  $jq(window).bind("resize", updateTables);
  function splitTable(original)
  {
    original.wrap("<div class='table-wrapper' />");
    var copy = original.clone();
    copy.find("td:not(:first-child), th:not(:first-child)").css("display", "none");
    copy.removeClass("responsive-table");
    original.closest(".table-wrapper").append(copy);
    copy.wrap("<div class='pinned' />");
    original.wrap("<div class='scrollable' />");
  }
  function unsplitTable(original) {
  original.closest(".table-wrapper").find(".pinned").remove();
  original.unwrap();
  original.unwrap();
  }
/*----------------------------------*/
/*      Portfolio Filterable
/*----------------------------------*/
  // BEGIN isotope filtering
  // filter items when filter link is clicked
  $jq('#portfolio-filters li').click(function(){
  $jq('#portfolio-filters li').removeClass('current');
    $jq(this).addClass('current');
      var selector = $jq(this).find('a').attr('data-filter');
      $container.isotope({ filter: selector });
    return false;
  });
  $jq(".item-hover .portfolio-item-description").css({ opacity: 0 });
  $jq(".item-hover .portfolio-item-description").css({ top: -100 });
  $jq('.item-hover').bind('mouseenter',function(){
    var $ele = $jq(this);
      // portfolio icon
    $ele.find('.portfolio-item-description').stop(true).animate({ 'opacity': 1, 'top':'0px' }, 300).andSelf()
  }).bind('mouseleave',function(){
    var $ele = $jq(this);
      // portfolio icon
    $ele.find('.portfolio-item-description').stop(true).animate({ 'opacity': 0, 'top':'-100px' }, 200).andSelf()
  });
/* END CUSTOM JQUERY =================================================================== */
});
