var allSlider = new Hash();

var Slider = function(id, counter)
{
  this.opened = false;
  this.id = id+counter;
  this.blankId = id;
  this.row = $('title_row_'+this.id);
  this.className = "question_active";
  allSlider.set(this.id, this);
  this.slideDuration = 0.5;
    /*this.slideDuration = 3;  */

  this.openIcon = "modules/Knowledge/View/Media/open.png";
  this.closeIcon = "modules/Knowledge/View/Media/close.png";
}

Slider.prototype.toggle = function()
{
  if (this.opened) {
    this.close();
  } else {
    this.open();
  }
}

Slider.prototype.open = function()
{
  if (!this.opened) {
      Effect.SlideDown('answer_'+this.id, {duration : this.slideDuration});
    this.opened = true;
    this.hit();
    this.row.addClassName(this.className);
    $('img_'+this.id).src = this.closeIcon;
  }
  this.closeOthers();
}

Slider.prototype.close = function()
{
  if (this.opened) {
    Effect.SlideUp('answer_'+this.id, {duration : this.slideDuration});
    this.opened = false;
    this.row.removeClassName(this.className);
    $('img_'+this.id).src = this.openIcon;
  }
}

/*
  Effect.toggle('answer_'+this.id, 'slide');
  if (this.open) {
    this.row.removeClassName(this.className);
    this.open = false;
  } else {
    this.row.addClassName(this.className);
    this.open = true;
  }

  this.hit();
}
*/

Slider.prototype.closeOthers = function()
{
  var ref = this;
  allSlider.each(function(pair) {
    if (ref.id != pair.key) {
        pair.value.close();
    }
  });
}

Slider.prototype.hit = function()
{
  new Ajax.Request("index.php",
  {
     method : "get",
     parameters : {
         section : "Knowledge",
         act : "hitArticle",
         id : this.blankId
     }
  });
}
