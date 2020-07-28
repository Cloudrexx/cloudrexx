function Tags(id, lang)
{
    /**
     * The input field with the tags
     */
    this.tags = $(id);

    /**
     * The used language id
     */
    this.lang = lang;

    /**
     * Get the tags
     */
    this.getTags('popularity');

    /**
     * Highlight those that are in the input field
     */
    this.typing();

    /**
     * Array of available tags
     *
     * Not sure what's this for anymore
     */
    this.availableTags = Array();

    /**
     * The loading state
     */
    this.loaded = false;

    /**
     * The typing state
     */
    this.currently_tpying = false;

    /**
     * The event handler
     */
    var ref = this;

    this.tags.onkeyup = function() {
        ref.typing();
    }
}

/**
 * Get the tags through ajax
 */
Tags.prototype.getTags = function(sort)
{
    var ref = this;
    new Ajax.Request('index.php', {
        method: "get",
        parameters : {  cmd  : "Knowledge",
                        act  : "articles",
                        tpl  : "getTags",
                        sort : sort,
                        lang : ref.lang
        },
        onSuccess : function(transport) {
            var response = transport.responseText.evalJSON();
            $('taglist_'+ref.lang).update(response.html);
            ref.availableTags = $H(response.available_tags);
            ref.loaded = true;
            ref.typing();
        }
    });

}

/**
 * The event when an available tag was clicked
 */
Tags.prototype.tagClicked = function(id, name, obj)
{
    var pattern = new RegExp("\s*"+name+"\s*", "ig");
    if (pattern.exec(this.tags.value)) {
        this.removeTag(id, name, obj);
    } else {
        this.addTag(id, name, obj);
    }
}

/**
 * Add a tag to the input field
 */
Tags.prototype.addTag = function(id, name, caller)
{
    if (this.tags.value == "") {
        this.tags.value = this.tags.value + name;
    } else {
        if (this.currently_typing) {
            if (this.tags.value.search(/,/) > 0) {
                this.tags.value = this.tags.value.replace(/,[^,]+$/, "");
                this.tags.value = this.tags.value + ", "+name;
            } else {
                this.tags.value = name;
            }
            this.currently_typing = false;
        } else {
            this.tags.value = this.tags.value + ", "+name;
        }
    }

    $('tag_'+id).addClassName("chosen");
    /*
    var ref = this;
    caller.onclick = function() {
        ref.removeTag(id, name, caller);
        ref.typing();
    }
    */
};

/**
 * Remove a tag from the input field
 */
Tags.prototype.removeTag = function(id, name, caller)
{
    var pattern = new RegExp(name+',?\\s*', 'gi');
    this.tags.value = this.tags.value.replace(pattern, "");
    this.tags.value = this.tags.value.replace(/,\s*$/, '');

    /*
    var ref = this;
    caller.onclick = function() {
        ref.addTag(id, name, caller);
        ref.typing();
    }
    */
}

/**
 * The typin event
 */
Tags.prototype.typing = function()
{
    if (this.loaded) {
        this.resetHighlights();
        var value = this.tags.value;
        if (value != "") {
            var tags = value.split(/\s*,\s*/);
            for (var i = 0; i < tags.length; i++) {
                if (tags[i] != "") {
                    tags[i] = this.trim(tags[i]);
                    var result = this.searchValue(tags[i]);
                    if (result.get('type') == 1) {
                        // highlight it as chosen
                        this.highlight(result.get('id'), "chosen");
                    } else if (result.get('type') == 2) {
                        // highlight is as typing
                        this.highlight(result.get('id'), "typing");
                        this.currently_typing = true;
                    }
                }
            }
        }
    }
};

/**
 * Documentation needed
 */
Tags.prototype.searchValue = function(val)
{
    var keys = this.availableTags.keys();
    var response = $H();
    for (var i = 0; i < keys.length; i++) {
        if (this.availableTags.get(keys[i]).toUpperCase() == val.toUpperCase()) {
            response.set('id', keys[i]);
            response.set('type', 1);
            return response;
        } else {
            var pattern = new RegExp("^"+val+".*", "i");
            if (pattern.exec(this.availableTags.get(keys[i]))) {
                response.set('id', keys[i]);
                response.set('type', 2);
                return response;
            }
        }
    }
    response.set('type', 0);
    return response;
};

/**
 * Highlight an available tag
 */
Tags.prototype.highlight = function(id, type)
{
    this.currently_typing = false;
    var obj = $('tag_'+id);
    if (obj) {
        if (type == "chosen") {
            obj.removeClassName("typing");
            obj.addClassName("chosen");
        } else if (type == "none") {
            obj.removeClassName("chosen");
            obj.removeClassName("typing");
        }else {
            obj.removeClassName("chosen");
            obj.addClassName("typing");
        }
    }
};

/**
 * Reset the highlights
 */
Tags.prototype.resetHighlights = function()
{
    var ref = this;
    this.availableTags.each(function(e) {
        ref.highlight(e.key, "none");
    });
};

/**
 * Remove trailing white spaces
 */
Tags.prototype.trim = function(str) {
    str = str.replace(/^\s*/, '').replace(/\s*$/, '');
    return str;
}
