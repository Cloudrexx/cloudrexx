/**
 * Ajax.Request.abort
 * extend the prototype.js Ajax.Request object so that it supports an abort method
 */
Ajax.Request.prototype.abort = function() {
    // prevent and state change callbacks from being issued
    this.transport.onreadystatechange = Prototype.emptyFunction;
    // abort the XHR
    this.transport.abort();
    // update the request counter
    Ajax.activeRequestCount--;
};



var Search = {
    /**
     * Time in miliseconds since 1970 when a key was pressed
     */
    keyTime : 0,
    /**
     * Time to wait until performing the search
     */
    waitTime : 100,
    /**
     * The id of the result box
     */
    resultBox : "resultbox",
    /**
     * The last request, so we can abort it
     */
    curRequest : null,
    /**
     * Assign the search to an input field
     */
    assign : function(field, resultBox)
    {
        var onKeyPress = $(field).onkeypress;
        $(field).onkeypress = function() {
            if (onKeyPress) {
                onKeyPress();
            }
            Search.keyPress();
        };

        var onBlur = $(field).onblur;
        $(field).onblur = function() {
            if (onBlur) {
                onBlur();
            }
            Search.hideBoxDelayed();
        };

        this.resultBox = resultBox;
    },
    /**
     * Save the current time in milliseconds since 1970. Override
     * the existing time.
     */
    keyPress : function()
    {
        var d = new Date();
        this.keyTime = d.getTime();
        var ref = this;
        setTimeout(function() {
            Search.timeout();
        }, this.waitTime+10);
    },
    /**
     * Compare the current time and the time saved. If it is bigger
     * than the wait time, perform the search
     */
    timeout : function()
    {
        var d = new Date();
        var actTime = d.getTime();
        if ((actTime - this.keyTime) > this.waitTime) {
            this.perform();
        }
    },
    /**
     * Perform the search
     */
    perform : function()
    {
        this.getData();
    },
    /**
     * Get the data
     */
    getData : function()
    {
        var ref = this;

        if (this.curRequest != null && this.curRequest.abort) {
            this.curRequest.abort();
        }

        cx.ready(function() {
            this.curRequest = new Ajax.Request("/modules/Knowledge/Controller/search.php", {
                method : "get",
                parameters : {
                    section : "Knowledge",
                    act : "liveSearch",
                    searchterm : $('searchinput').value,
                    lang : cx.variables.get('language')
                },
                onSuccess : function(transport)
                {
                    var data = transport.responseText.evalJSON();
                    if (data.status == 1) {
                        $(ref.resultBox).replace(data.content);
                        ref.showBox();
                    } else {
                        ref.hideBox();
                    }
                }
            });
        });
    },
    clearBox : function()
    {
      var children = $(this.resultBox).childNodes;
      for (var i = 0; i < children.length; i++) {
          $(this.resultBox).removeChild(children[i]);
      }
    },
    /**
     * Make the box visible
     */
    showBox : function() {
        $(this.resultBox).show();
    },
    /**
     * Hide the result box
     */
    hideBox : function() {
        $(this.resultBox).hide();
    },
    /**
     * Hide a box delayed
     *
     * Hide a box delayed. This is because the link mus be clickable.
     * Without delay the box hides before a link can be clicked.
     */
    hideBoxDelayed : function() {
        setTimeout(function() {
            Search.hideBox();
        }, 100);
   }
};


/** knowledge specific **/


function submitSearch(obj)
{
    var searchinput = $('searchinput');
    var val = searchinput.value;
    $('searchHidden').value = val;
    searchinput.value = "";
    searchinput.name = "";
    return true;
}
