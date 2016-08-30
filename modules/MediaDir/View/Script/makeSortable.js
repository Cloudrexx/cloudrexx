/*
 * Create a nested Sortable list
 *
 * @author      Stefan Heinemann <sh@adfinis.com>
 *
     Required options:
     sortable1: id of left sortable
     sortable2: id of right sortable
     className: className of both sortables
     lParent: prefix for the left parents, must end with _, e.g. oParent_
     rParent: prefix for the right parents, must end with_ ,e.g. parent_
     child: prefix for the children, must end with _,  e.g. child_

     Be aware that all these options must be present or the script
     will fail to run.
  */
  var makeSortable = function(options) {
    this.sortable1 = $('#' + options.sortable1);
    this.sortable2 = $('#' + options.sortable2);
    this.options = options;

    var ref = this;
    var props = {
        connectWith: '.' + ref.options.className,
        update : function(event, ui) {
          // sort the list after an update
          ref.sortList(this);
          // remove the dragging classes
          ref.sortable1.find('li').removeClass('dragging');
          ref.sortable2.find('li').removeClass('dragging');
        },
        receive: function(event, ui) {
          // spawned when a list has received a new object

          var item = ui.item;

          if (item.is('[id^=' + ref.options.lParent + ']')
              || item.is('[id^=' + ref.options.rParent + ']')) {
            // take the children with the parent
            var id = item.attr('id').split('_')[1];
            var children = $('.' + ref.options.child + id);
            item.after(children);

            // rename the ids of the parent
            if (item.is('[id^=' + ref.options.rParent + ']')) {
              item.attr('id', ref.options.lParent + id);
            } else {
              item.attr('id', ref.options.rParent + id);
            }

          } else {
            // if all children are on the right side put the parent there too
            var parID = item.attr('id').split('_')[1];

            var oneDifferent = false;

            $('.' + ref.options.child + parID).each(function(i, child) {
              if ($(child).parent().attr('id') != item.parent().attr('id')) {
                oneDifferent = true;
                return;
              }
            });


            var oParent = $('#' + ref.options.lParent + parID);
            var parent = $('#' + ref.options.rParent + parID);

            // the parent on the right side is missing
            if (parent.size() == 0) {
              var clone = oParent.clone();
              clone.attr('id', ref.options.rParent + parID);
              $('#' + ref.options.sortable2).append(clone);
            }

            // the parent on the left side is missing
            if (oParent.size() == 0) {
              var clone = parent.clone();
              clone.attr('id', ref.options.lParent + parID);
              $('#' + ref.options.sortable1).append(clone);
            }



            if (!oneDifferent) {
              // they're all on the same side
              // find out which one and remove the
              // obsolete parent
              var id = $($('.' + ref.options.child + parID)[0]).parent().attr('id');
              if ($('#' + ref.options.sortable1).attr('id') == id) {
                $('#' + ref.options.rParent + parID).remove();
              } else {
                $('#' + ref.options.lParent + parID).remove();
              }
            }
          }

          ref.sortList(ref.sortable1);
          ref.sortList(ref.sortable2);

       },
      /*
         Event when sorting
      */
      sort: function(event, ui) {
        var item = ui.item;
        item.addClass("dragging");
        $('#' + ref.options.sortable1).css('overflow', 'visible');
        $('#' + ref.options.sortable2).css('overflow', 'visible');
      },
      /*
         Event when stop sorting
      */
      stop: function(event, ui) {
        $('#' + ref.options.sortable1).css('overflow', 'auto');
        $('#' + ref.options.sortable2).css('overflow', 'auto');
      }
    }

    this.sortable1.sortable(props).disableSelection();
    this.sortable2.sortable(props).disableSelection();
  }

  makeSortable.prototype = {
    /**
     * Sort an ul list according to their id
     *
     */
    sortList: function(list) {
      var items = $(list).children();

      var idList = new Array();
      var itemList = new Array();
      var re = new RegExp(this.options.child +
          '|' + this.options.lParent + '|' + this.options.rParent);

      $(items).each(function(i, item) {
        // chop off the text
        var key = $(item).attr('id').replace(re, '');;
        itemList[key] = item;
        idList.push(key);
      });

      idList.sort();
      $(list).children().remove(); // clear the list

      $(idList).each(function(i, key) {
        $(list).append(itemList[key]);
      });
    }
  }
