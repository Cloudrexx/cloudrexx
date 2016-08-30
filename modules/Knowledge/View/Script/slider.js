/**

    Depends on Prototype >= 1.6.0.2 and scriptaculous >= 1.8.1
    2008 by Stefan Heinemann, Cloudrexx AG
    Last changed: 2008-11-05

*/

var allSlider = new Hash();

/**
    Possible things for data are:
    id:         id of the element which is to be slided
    counter:    sometimes a counter is needed to distinguish the items
                so that there is not the same id twice
    slideAll:   if this is true, only one item can be open. The others close
                when opening an item
    divPrefix:  the id prefix of the block which slides (the id and counter will
                be appended to that)
    imgPrefix:  the id prefix of the image which toggles the effect. Can be left
                empty
    titleRowPrefix: the id prefix of the title row, where the image to fire the
                event off lies, so it can change its class. Can be left empty
    className:  mentioned class
    openedIcon:   the icon that shall appear when the block is open...
    closedIcon:  ... and when it's closed
    isOpened:   if the element is already opened
 */
var Slider = function(id, data)
{
    // default values
    this.divPrefix = "";
    this.imgPrefix = "";
    this.slideAll = false;
    this.titleRowPrefix = "";
  this.opened = false;
  this.counter = "";
  this.blankId = id;
  this.className = "";
  this.slideDuration = 0.5;

  this.openedIcon = "";
  this.closedIcon = "";

  if (data.counter !== null) {
      this.counter = data.counter;
  }

  if (data.slideAll !== null) {
      this.slideAll = data.slideAll;
  }

  if (data.divPrefix !== null) {
      this.divPrefix = data.divPrefix;
  }

  if (data.imgPrefix !== null) {
      this.imgPrefix = data.imgPrefix;
  }

  if (data.titleRowPrefix !== null) {
      this.titleRowPrefix = data.titleRowPrefix;
  }

  if (data.className !== null) {
      this.className = data.className;
  }

  if (data.openedIcon !== null) {
      this.openedIcon = data.openedIcon;
  }

  if (data.closedIcon !== null) {
      this.closedIcon = data.closedIcon;
  }

  if (data.isOpened !== null) {
      this.opened = data.isOpened;
  }

    this.id = id+this.counter;
    this.row = $(this.titleRowPrefix+this.id);
    allSlider.set(this.id, this);
};

Slider.prototype.toggle = function()
{
  if (this.opened) {
    this.close();
  } else {
    this.open();
  }
};

Slider.prototype.open = function()
{
  if (!this.opened) {
      Effect.SlideDown(this.divPrefix+this.id, {duration : this.slideDuration});
    this.opened = true;
    if (this.row !== null) {
       this.row.addClassName(this.className);
    }
    if (this.imgPrefix !== "") {
      $(this.imgPrefix+this.id).src = this.openedIcon;
    }
  }
  if (this.slideAll) {
     this.closeOthers();
  }
};

Slider.prototype.close = function()
{
  if (this.opened) {
    Effect.SlideUp(this.divPrefix+this.id, {duration : this.slideDuration});
    this.opened = false;
    if (this.row !== null) {
      this.row.removeClassName(this.className);
    }
    if (this.imgPrefix !== "") {
      $(this.imgPrefix+this.id).src = this.closedIcon;
    }
  }
};

Slider.prototype.closeOthers = function()
{
  var ref = this;
  allSlider.each(function(pair) {
    if (ref.id != pair.key) {
        pair.value.close();
    }
  });
};
