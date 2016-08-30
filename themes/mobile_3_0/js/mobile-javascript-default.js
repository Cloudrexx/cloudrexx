/*!
 * jQuery JavaScript Library v1.5.1
 * http://jquery.com/
 *
 * Copyright 2011, John Resig
 * Dual licensed under the MIT or GPL Version 2 licenses.
 * http://jquery.org/license
 *
 * Includes Sizzle.js
 * http://sizzlejs.com/
 * Copyright 2011, The Dojo Foundation
 * Released under the MIT, BSD, and GPL Licenses.
 *
 * Date: Wed Feb 23 13:55:29 2011 -0500
 */
(function(a,b){
    function cg(a){
        return d.isWindow(a)?a:a.nodeType===9?a.defaultView||a.parentWindow:!1
    }
    function cd(a){
        if(!bZ[a]){
            var b=d("<"+a+">").appendTo("body"),c=b.css("display");
            b.remove();
            if(c==="none"||c==="")c="block";
            bZ[a]=c
        }
        return bZ[a]
    }
    function cc(a,b){
        var c={};

        d.each(cb.concat.apply([],cb.slice(0,b)),function(){
            c[this]=a
        });
        return c
    }
    function bY(){
        try{
            return new a.ActiveXObject("Microsoft.XMLHTTP")
        }catch(b){}
    }
    function bX(){
        try{
            return new a.XMLHttpRequest
        }catch(b){}
    }
    function bW(){
        d(a).unload(function(){
            for(var a in bU)bU[a](0,1)
        })
    }
    function bQ(a,c){
        a.dataFilter&&(c=a.dataFilter(c,a.dataType));
        var e=a.dataTypes,f={},g,h,i=e.length,j,k=e[0],l,m,n,o,p;
        for(g=1;g<i;g++){
            if(g===1)for(h in a.converters)typeof h==="string"&&(f[h.toLowerCase()]=a.converters[h]);l=k,k=e[g];
            if(k==="*")k=l;
            else if(l!=="*"&&l!==k){
                m=l+" "+k,n=f[m]||f["* "+k];
                if(!n){
                    p=b;
                    for(o in f){
                        j=o.split(" ");
                        if(j[0]===l||j[0]==="*"){
                            p=f[j[1]+" "+k];
                            if(p){
                                o=f[o],o===!0?n=p:p===!0&&(n=o);
                                break
                            }
                        }
                    }
                }!n&&!p&&d.error("No conversion from "+m.replace(" "," to ")),n!==!0&&(c=n?n(c):p(o(c)))
            }
        }
        return c
    }
    function bP(a,c,d){
        var e=a.contents,f=a.dataTypes,g=a.responseFields,h,i,j,k;
        for(i in g)i in d&&(c[g[i]]=d[i]);while(f[0]==="*")f.shift(),h===b&&(h=a.mimeType||c.getResponseHeader("content-type"));
        if(h)for(i in e)if(e[i]&&e[i].test(h)){
            f.unshift(i);
            break
        }
        if(f[0]in d)j=f[0];
        else{
            for(i in d){
                if(!f[0]||a.converters[i+" "+f[0]]){
                    j=i;
                    break
                }
                k||(k=i)
            }
            j=j||k
        }
        if(j){
            j!==f[0]&&f.unshift(j);
            return d[j]
        }
    }
    function bO(a,b,c,e){
        if(d.isArray(b)&&b.length)d.each(b,function(b,f){
            c||bq.test(a)?e(a,f):bO(a+"["+(typeof f==="object"||d.isArray(f)?b:"")+"]",f,c,e)
        });
        else if(c||b==null||typeof b!=="object")e(a,b);
        else if(d.isArray(b)||d.isEmptyObject(b))e(a,"");else for(var f in b)bO(a+"["+f+"]",b[f],c,e)
    }
    function bN(a,c,d,e,f,g){
        f=f||c.dataTypes[0],g=g||{},g[f]=!0;
        var h=a[f],i=0,j=h?h.length:0,k=a===bH,l;
        for(;i<j&&(k||!l);i++)l=h[i](c,d,e),typeof l==="string"&&(!k||g[l]?l=b:(c.dataTypes.unshift(l),l=bN(a,c,d,e,l,g)));
        (k||!l)&&!g["*"]&&(l=bN(a,c,d,e,"*",g));
        return l
    }
    function bM(a){
        return function(b,c){
            typeof b!=="string"&&(c=b,b="*");
            if(d.isFunction(c)){
                var e=b.toLowerCase().split(bB),f=0,g=e.length,h,i,j;
                for(;f<g;f++)h=e[f],j=/^\+/.test(h),j&&(h=h.substr(1)||"*"),i=a[h]=a[h]||[],i[j?"unshift":"push"](c)
            }
        }
    }
    function bo(a,b,c){
        var e=b==="width"?bi:bj,f=b==="width"?a.offsetWidth:a.offsetHeight;
        if(c==="border")return f;
        d.each(e,function(){
            c||(f-=parseFloat(d.css(a,"padding"+this))||0),c==="margin"?f+=parseFloat(d.css(a,"margin"+this))||0:f-=parseFloat(d.css(a,"border"+this+"Width"))||0
        });
        return f
    }
    function ba(a,b){
        b.src?d.ajax({
            url:b.src,
            async:!1,
            dataType:"script"
        }):d.globalEval(b.text||b.textContent||b.innerHTML||""),b.parentNode&&b.parentNode.removeChild(b)
    }
    function _(a){
        return"getElementsByTagName"in a?a.getElementsByTagName("*"):"querySelectorAll"in a?a.querySelectorAll("*"):[]
    }
    function $(a,b){
        if(b.nodeType===1){
            var c=b.nodeName.toLowerCase();
            b.clearAttributes(),b.mergeAttributes(a);
            if(c==="object")b.outerHTML=a.outerHTML;
            else if(c!=="input"||a.type!=="checkbox"&&a.type!=="radio"){
                if(c==="option")b.selected=a.defaultSelected;
                else if(c==="input"||c==="textarea")b.defaultValue=a.defaultValue
            }else a.checked&&(b.defaultChecked=b.checked=a.checked),b.value!==a.value&&(b.value=a.value);
            b.removeAttribute(d.expando)
        }
    }
    function Z(a,b){
        if(b.nodeType===1&&d.hasData(a)){
            var c=d.expando,e=d.data(a),f=d.data(b,e);
            if(e=e[c]){
                var g=e.events;
                f=f[c]=d.extend({},e);
                if(g){
                    delete f.handle,f.events={};

                    for(var h in g)for(var i=0,j=g[h].length;i<j;i++)d.event.add(b,h+(g[h][i].namespace?".":"")+g[h][i].namespace,g[h][i],g[h][i].data)
                }
            }
        }
    }
    function Y(a,b){
        return d.nodeName(a,"table")?a.getElementsByTagName("tbody")[0]||a.appendChild(a.ownerDocument.createElement("tbody")):a
    }
    function O(a,b,c){
        if(d.isFunction(b))return d.grep(a,function(a,d){
            var e=!!b.call(a,d,a);
            return e===c
        });
        if(b.nodeType)return d.grep(a,function(a,d){
            return a===b===c
        });
        if(typeof b==="string"){
            var e=d.grep(a,function(a){
                return a.nodeType===1
            });
            if(J.test(b))return d.filter(b,e,!c);
            b=d.filter(b,e)
        }
        return d.grep(a,function(a,e){
            return d.inArray(a,b)>=0===c
        })
    }
    function N(a){
        return!a||!a.parentNode||a.parentNode.nodeType===11
    }
    function F(a,b){
        return(a&&a!=="*"?a+".":"")+b.replace(r,"`").replace(s,"&")
    }
    function E(a){
        var b,c,e,f,g,h,i,j,k,l,m,n,o,q=[],r=[],s=d._data(this,"events");
        if(a.liveFired!==this&&s&&s.live&&!a.target.disabled&&(!a.button||a.type!=="click")){
            a.namespace&&(n=new RegExp("(^|\\.)"+a.namespace.split(".").join("\\.(?:.*\\.)?")+"(\\.|$)")),a.liveFired=this;
            var t=s.live.slice(0);
            for(i=0;i<t.length;i++)g=t[i],g.origType.replace(p,"")===a.type?r.push(g.selector):t.splice(i--,1);
            f=d(a.target).closest(r,a.currentTarget);
            for(j=0,k=f.length;j<k;j++){
                m=f[j];
                for(i=0;i<t.length;i++){
                    g=t[i];
                    if(m.selector===g.selector&&(!n||n.test(g.namespace))&&!m.elem.disabled){
                        h=m.elem,e=null;
                        if(g.preType==="mouseenter"||g.preType==="mouseleave")a.type=g.preType,e=d(a.relatedTarget).closest(g.selector)[0];
                        (!e||e!==h)&&q.push({
                            elem:h,
                            handleObj:g,
                            level:m.level
                        })
                    }
                }
            }
            for(j=0,k=q.length;j<k;j++){
                f=q[j];
                if(c&&f.level>c)break;
                a.currentTarget=f.elem,a.data=f.handleObj.data,a.handleObj=f.handleObj,o=f.handleObj.origHandler.apply(f.elem,arguments);
                if(o===!1||a.isPropagationStopped()){
                    c=f.level,o===!1&&(b=!1);
                    if(a.isImmediatePropagationStopped())break
                }
            }
            return b
        }
    }
    function C(a,c,e){
        var f=d.extend({},e[0]);
        f.type=a,f.originalEvent={},f.liveFired=b,d.event.handle.call(c,f),f.isDefaultPrevented()&&e[0].preventDefault()
    }
    function w(){
        return!0
    }
    function v(){
        return!1
    }
    function g(a){
        for(var b in a)if(b!=="toJSON")return!1;return!0
    }
    function f(a,c,f){
        if(f===b&&a.nodeType===1){
            f=a.getAttribute("data-"+c);
            if(typeof f==="string"){
                try{
                    f=f==="true"?!0:f==="false"?!1:f==="null"?null:d.isNaN(f)?e.test(f)?d.parseJSON(f):f:parseFloat(f)
                }catch(g){}
                d.data(a,c,f)
            }else f=b
        }
        return f
    }
    var c=a.document,d=function(){
        function I(){
            if(!d.isReady){
                try{
                    c.documentElement.doScroll("left")
                }catch(a){
                    setTimeout(I,1);
                    return
                }
                d.ready()
            }
        }
        var d=function(a,b){
            return new d.fn.init(a,b,g)
        },e=a.jQuery,f=a.$,g,h=/^(?:[^<]*(<[\w\W]+>)[^>]*$|#([\w\-]+)$)/,i=/\S/,j=/^\s+/,k=/\s+$/,l=/\d/,m=/^<(\w+)\s*\/?>(?:<\/\1>)?$/,n=/^[\],:{}\s]*$/,o=/\\(?:["\\\/bfnrt]|u[0-9a-fA-F]{4})/g,p=/"[^"\\\n\r]*"|true|false|null|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?/g,q=/(?:^|:|,)(?:\s*\[)+/g,r=/(webkit)[ \/]([\w.]+)/,s=/(opera)(?:.*version)?[ \/]([\w.]+)/,t=/(msie) ([\w.]+)/,u=/(mozilla)(?:.*? rv:([\w.]+))?/,v=navigator.userAgent,w,x=!1,y,z="then done fail isResolved isRejected promise".split(" "),A,B=Object.prototype.toString,C=Object.prototype.hasOwnProperty,D=Array.prototype.push,E=Array.prototype.slice,F=String.prototype.trim,G=Array.prototype.indexOf,H={};

        d.fn=d.prototype={
            constructor:d,
            init:function(a,e,f){
                var g,i,j,k;
                if(!a)return this;
                if(a.nodeType){
                    this.context=this[0]=a,this.length=1;
                    return this
                }
                if(a==="body"&&!e&&c.body){
                    this.context=c,this[0]=c.body,this.selector="body",this.length=1;
                    return this
                }
                if(typeof a==="string"){
                    g=h.exec(a);
                    if(!g||!g[1]&&e)return!e||e.jquery?(e||f).find(a):this.constructor(e).find(a);
                    if(g[1]){
                        e=e instanceof d?e[0]:e,k=e?e.ownerDocument||e:c,j=m.exec(a),j?d.isPlainObject(e)?(a=[c.createElement(j[1])],d.fn.attr.call(a,e,!0)):a=[k.createElement(j[1])]:(j=d.buildFragment([g[1]],[k]),a=(j.cacheable?d.clone(j.fragment):j.fragment).childNodes);
                        return d.merge(this,a)
                    }
                    i=c.getElementById(g[2]);
                    if(i&&i.parentNode){
                        if(i.id!==g[2])return f.find(a);
                        this.length=1,this[0]=i
                    }
                    this.context=c,this.selector=a;
                    return this
                }
                if(d.isFunction(a))return f.ready(a);
                a.selector!==b&&(this.selector=a.selector,this.context=a.context);
                return d.makeArray(a,this)
            },
            selector:"",
            jquery:"1.5.1",
            length:0,
            size:function(){
                return this.length
            },
            toArray:function(){
                return E.call(this,0)
            },
            get:function(a){
                return a==null?this.toArray():a<0?this[this.length+a]:this[a]
            },
            pushStack:function(a,b,c){
                var e=this.constructor();
                d.isArray(a)?D.apply(e,a):d.merge(e,a),e.prevObject=this,e.context=this.context,b==="find"?e.selector=this.selector+(this.selector?" ":"")+c:b&&(e.selector=this.selector+"."+b+"("+c+")");
                return e
            },
            each:function(a,b){
                return d.each(this,a,b)
            },
            ready:function(a){
                d.bindReady(),y.done(a);
                return this
            },
            eq:function(a){
                return a===-1?this.slice(a):this.slice(a,+a+1)
            },
            first:function(){
                return this.eq(0)
            },
            last:function(){
                return this.eq(-1)
            },
            slice:function(){
                return this.pushStack(E.apply(this,arguments),"slice",E.call(arguments).join(","))
            },
            map:function(a){
                return this.pushStack(d.map(this,function(b,c){
                    return a.call(b,c,b)
                }))
            },
            end:function(){
                return this.prevObject||this.constructor(null)
            },
            push:D,
            sort:[].sort,
            splice:[].splice
        },d.fn.init.prototype=d.fn,d.extend=d.fn.extend=function(){
            var a,c,e,f,g,h,i=arguments[0]||{},j=1,k=arguments.length,l=!1;
            typeof i==="boolean"&&(l=i,i=arguments[1]||{},j=2),typeof i!=="object"&&!d.isFunction(i)&&(i={}),k===j&&(i=this,--j);
            for(;j<k;j++)if((a=arguments[j])!=null)for(c in a){
                e=i[c],f=a[c];
                if(i===f)continue;
                l&&f&&(d.isPlainObject(f)||(g=d.isArray(f)))?(g?(g=!1,h=e&&d.isArray(e)?e:[]):h=e&&d.isPlainObject(e)?e:{},i[c]=d.extend(l,h,f)):f!==b&&(i[c]=f)
            }
            return i
        },d.extend({
            noConflict:function(b){
                a.$=f,b&&(a.jQuery=e);
                return d
            },
            isReady:!1,
            readyWait:1,
            ready:function(a){
                a===!0&&d.readyWait--;
                if(!d.readyWait||a!==!0&&!d.isReady){
                    if(!c.body)return setTimeout(d.ready,1);
                    d.isReady=!0;
                    if(a!==!0&&--d.readyWait>0)return;
                    y.resolveWith(c,[d]),d.fn.trigger&&d(c).trigger("ready").unbind("ready")
                }
            },
            bindReady:function(){
                if(!x){
                    x=!0;
                    if(c.readyState==="complete")return setTimeout(d.ready,1);
                    if(c.addEventListener)c.addEventListener("DOMContentLoaded",A,!1),a.addEventListener("load",d.ready,!1);
                    else if(c.attachEvent){
                        c.attachEvent("onreadystatechange",A),a.attachEvent("onload",d.ready);
                        var b=!1;
                        try{
                            b=a.frameElement==null
                        }catch(e){}
                        c.documentElement.doScroll&&b&&I()
                    }
                }
            },
            isFunction:function(a){
                return d.type(a)==="function"
            },
            isArray:Array.isArray||function(a){
                return d.type(a)==="array"
            },
            isWindow:function(a){
                return a&&typeof a==="object"&&"setInterval"in a
            },
            isNaN:function(a){
                return a==null||!l.test(a)||isNaN(a)
            },
            type:function(a){
                return a==null?String(a):H[B.call(a)]||"object"
            },
            isPlainObject:function(a){
                if(!a||d.type(a)!=="object"||a.nodeType||d.isWindow(a))return!1;
                if(a.constructor&&!C.call(a,"constructor")&&!C.call(a.constructor.prototype,"isPrototypeOf"))return!1;
                var c;
                for(c in a){}
                return c===b||C.call(a,c)
            },
            isEmptyObject:function(a){
                for(var b in a)return!1;return!0
            },
            error:function(a){
                throw a
            },
            parseJSON:function(b){
                if(typeof b!=="string"||!b)return null;
                b=d.trim(b);
                if(n.test(b.replace(o,"@").replace(p,"]").replace(q,"")))return a.JSON&&a.JSON.parse?a.JSON.parse(b):(new Function("return "+b))();
                d.error("Invalid JSON: "+b)
            },
            parseXML:function(b,c,e){
                a.DOMParser?(e=new DOMParser,c=e.parseFromString(b,"text/xml")):(c=new ActiveXObject("Microsoft.XMLDOM"),c.async="false",c.loadXML(b)),e=c.documentElement,(!e||!e.nodeName||e.nodeName==="parsererror")&&d.error("Invalid XML: "+b);
                return c
            },
            noop:function(){},
            globalEval:function(a){
                if(a&&i.test(a)){
                    var b=c.head||c.getElementsByTagName("head")[0]||c.documentElement,e=c.createElement("script");
                    d.support.scriptEval()?e.appendChild(c.createTextNode(a)):e.text=a,b.insertBefore(e,b.firstChild),b.removeChild(e)
                }
            },
            nodeName:function(a,b){
                return a.nodeName&&a.nodeName.toUpperCase()===b.toUpperCase()
            },
            each:function(a,c,e){
                var f,g=0,h=a.length,i=h===b||d.isFunction(a);
                if(e){
                    if(i){
                        for(f in a)if(c.apply(a[f],e)===!1)break
                    }else for(;g<h;)if(c.apply(a[g++],e)===!1)break
                }else if(i){
                    for(f in a)if(c.call(a[f],f,a[f])===!1)break
                }else for(var j=a[0];g<h&&c.call(j,g,j)!==!1;j=a[++g]){}
                return a
            },
            trim:F?function(a){
                return a==null?"":F.call(a)
            }:function(a){
                return a==null?"":(a+"").replace(j,"").replace(k,"")
            },
            makeArray:function(a,b){
                var c=b||[];
                if(a!=null){
                    var e=d.type(a);
                    a.length==null||e==="string"||e==="function"||e==="regexp"||d.isWindow(a)?D.call(c,a):d.merge(c,a)
                }
                return c
            },
            inArray:function(a,b){
                if(b.indexOf)return b.indexOf(a);
                for(var c=0,d=b.length;c<d;c++)if(b[c]===a)return c;return-1
            },
            merge:function(a,c){
                var d=a.length,e=0;
                if(typeof c.length==="number")for(var f=c.length;e<f;e++)a[d++]=c[e];else while(c[e]!==b)a[d++]=c[e++];
                a.length=d;
                return a
            },
            grep:function(a,b,c){
                var d=[],e;
                c=!!c;
                for(var f=0,g=a.length;f<g;f++)e=!!b(a[f],f),c!==e&&d.push(a[f]);
                return d
            },
            map:function(a,b,c){
                var d=[],e;
                for(var f=0,g=a.length;f<g;f++)e=b(a[f],f,c),e!=null&&(d[d.length]=e);
                return d.concat.apply([],d)
            },
            guid:1,
            proxy:function(a,c,e){
                arguments.length===2&&(typeof c==="string"?(e=a,a=e[c],c=b):c&&!d.isFunction(c)&&(e=c,c=b)),!c&&a&&(c=function(){
                    return a.apply(e||this,arguments)
                }),a&&(c.guid=a.guid=a.guid||c.guid||d.guid++);
                return c
            },
            access:function(a,c,e,f,g,h){
                var i=a.length;
                if(typeof c==="object"){
                    for(var j in c)d.access(a,j,c[j],f,g,e);return a
                }
                if(e!==b){
                    f=!h&&f&&d.isFunction(e);
                    for(var k=0;k<i;k++)g(a[k],c,f?e.call(a[k],k,g(a[k],c)):e,h);
                    return a
                }
                return i?g(a[0],c):b
            },
            now:function(){
                return(new Date).getTime()
            },
            _Deferred:function(){
                var a=[],b,c,e,f={
                    done:function(){
                        if(!e){
                            var c=arguments,g,h,i,j,k;
                            b&&(k=b,b=0);
                            for(g=0,h=c.length;g<h;g++)i=c[g],j=d.type(i),j==="array"?f.done.apply(f,i):j==="function"&&a.push(i);
                            k&&f.resolveWith(k[0],k[1])
                        }
                        return this
                    },
                    resolveWith:function(d,f){
                        if(!e&&!b&&!c){
                            c=1;
                            try{
                                while(a[0])a.shift().apply(d,f)
                            }catch(g){
                                throw g
                            }finally{
                                b=[d,f],c=0
                            }
                        }
                        return this
                    },
                    resolve:function(){
                        f.resolveWith(d.isFunction(this.promise)?this.promise():this,arguments);
                        return this
                    },
                    isResolved:function(){
                        return c||b
                    },
                    cancel:function(){
                        e=1,a=[];
                        return this
                    }
                };

                return f
            },
            Deferred:function(a){
                var b=d._Deferred(),c=d._Deferred(),e;
                d.extend(b,{
                    then:function(a,c){
                        b.done(a).fail(c);
                        return this
                    },
                    fail:c.done,
                    rejectWith:c.resolveWith,
                    reject:c.resolve,
                    isRejected:c.isResolved,
                    promise:function(a){
                        if(a==null){
                            if(e)return e;
                            e=a={}
                        }
                        var c=z.length;
                        while(c--)a[z[c]]=b[z[c]];
                        return a
                    }
                }),b.done(c.cancel).fail(b.cancel),delete b.cancel,a&&a.call(b,b);
                return b
            },
            when:function(a){
                var b=arguments.length,c=b<=1&&a&&d.isFunction(a.promise)?a:d.Deferred(),e=c.promise();
                if(b>1){
                    var f=E.call(arguments,0),g=b,h=function(a){
                        return function(b){
                            f[a]=arguments.length>1?E.call(arguments,0):b,--g||c.resolveWith(e,f)
                        }
                    };
                    while(b--)a=f[b],a&&d.isFunction(a.promise)?a.promise().then(h(b),c.reject):--g;
                    g||c.resolveWith(e,f)
                }else c!==a&&c.resolve(a);
                return e
            },
            uaMatch:function(a){
                a=a.toLowerCase();
                var b=r.exec(a)||s.exec(a)||t.exec(a)||a.indexOf("compatible")<0&&u.exec(a)||[];
                return{
                    browser:b[1]||"",
                    version:b[2]||"0"
                }
            },
            sub:function(){
                function a(b,c){
                    return new a.fn.init(b,c)
                }
                d.extend(!0,a,this),a.superclass=this,a.fn=a.prototype=this(),a.fn.constructor=a,a.subclass=this.subclass,a.fn.init=function b(b,c){
                    c&&c instanceof d&&!(c instanceof a)&&(c=a(c));
                    return d.fn.init.call(this,b,c,e)
                },a.fn.init.prototype=a.fn;
                var e=a(c);
                return a
            },
            browser:{}
        }),y=d._Deferred(),d.each("Boolean Number String Function Array Date RegExp Object".split(" "),function(a,b){
            H["[object "+b+"]"]=b.toLowerCase()
        }),w=d.uaMatch(v),w.browser&&(d.browser[w.browser]=!0,d.browser.version=w.version),d.browser.webkit&&(d.browser.safari=!0),G&&(d.inArray=function(a,b){
            return G.call(b,a)
        }),i.test(" ")&&(j=/^[\s\xA0]+/,k=/[\s\xA0]+$/),g=d(c),c.addEventListener?A=function(){
            c.removeEventListener("DOMContentLoaded",A,!1),d.ready()
        }:c.attachEvent&&(A=function(){
            c.readyState==="complete"&&(c.detachEvent("onreadystatechange",A),d.ready())
        });
        return d
    }();
    (function(){
        d.support={};

        var b=c.createElement("div");
        b.style.display="none",b.innerHTML="   <link/><table></table><a href='/a' style='color:red;float:left;opacity:.55;'>a</a><input type='checkbox'/>";
        var e=b.getElementsByTagName("*"),f=b.getElementsByTagName("a")[0],g=c.createElement("select"),h=g.appendChild(c.createElement("option")),i=b.getElementsByTagName("input")[0];
        if(e&&e.length&&f){
            d.support={
                leadingWhitespace:b.firstChild.nodeType===3,
                tbody:!b.getElementsByTagName("tbody").length,
                htmlSerialize:!!b.getElementsByTagName("link").length,
                style:/red/.test(f.getAttribute("style")),
                hrefNormalized:f.getAttribute("href")==="/a",
                opacity:/^0.55$/.test(f.style.opacity),
                cssFloat:!!f.style.cssFloat,
                checkOn:i.value==="on",
                optSelected:h.selected,
                deleteExpando:!0,
                optDisabled:!1,
                checkClone:!1,
                noCloneEvent:!0,
                noCloneChecked:!0,
                boxModel:null,
                inlineBlockNeedsLayout:!1,
                shrinkWrapBlocks:!1,
                reliableHiddenOffsets:!0
            },i.checked=!0,d.support.noCloneChecked=i.cloneNode(!0).checked,g.disabled=!0,d.support.optDisabled=!h.disabled;
            var j=null;
            d.support.scriptEval=function(){
                if(j===null){
                    var b=c.documentElement,e=c.createElement("script"),f="script"+d.now();
                    try{
                        e.appendChild(c.createTextNode("window."+f+"=1;"))
                    }catch(g){}
                    b.insertBefore(e,b.firstChild),a[f]?(j=!0,delete a[f]):j=!1,b.removeChild(e),b=e=f=null
                }
                return j
            };

            try{
                delete b.test
            }catch(k){
                d.support.deleteExpando=!1
            }!b.addEventListener&&b.attachEvent&&b.fireEvent&&(b.attachEvent("onclick",function l(){
                d.support.noCloneEvent=!1,b.detachEvent("onclick",l)
            }),b.cloneNode(!0).fireEvent("onclick")),b=c.createElement("div"),b.innerHTML="<input type='radio' name='radiotest' checked='checked'/>";
            var m=c.createDocumentFragment();
            m.appendChild(b.firstChild),d.support.checkClone=m.cloneNode(!0).cloneNode(!0).lastChild.checked,d(function(){
                var a=c.createElement("div"),b=c.getElementsByTagName("body")[0];
                if(b){
                    a.style.width=a.style.paddingLeft="1px",b.appendChild(a),d.boxModel=d.support.boxModel=a.offsetWidth===2,"zoom"in a.style&&(a.style.display="inline",a.style.zoom=1,d.support.inlineBlockNeedsLayout=a.offsetWidth===2,a.style.display="",a.innerHTML="<div style='width:4px;'></div>",d.support.shrinkWrapBlocks=a.offsetWidth!==2),a.innerHTML="<table><tr><td style='padding:0;border:0;display:none'></td><td>t</td></tr></table>";
                    var e=a.getElementsByTagName("td");
                    d.support.reliableHiddenOffsets=e[0].offsetHeight===0,e[0].style.display="",e[1].style.display="none",d.support.reliableHiddenOffsets=d.support.reliableHiddenOffsets&&e[0].offsetHeight===0,a.innerHTML="",b.removeChild(a).style.display="none",a=e=null
                }
            });
            var n=function(a){
                var b=c.createElement("div");
                a="on"+a;
                if(!b.attachEvent)return!0;
                var d=a in b;
                d||(b.setAttribute(a,"return;"),d=typeof b[a]==="function"),b=null;
                return d
            };

            d.support.submitBubbles=n("submit"),d.support.changeBubbles=n("change"),b=e=f=null
        }
    })();
    var e=/^(?:\{.*\}|\[.*\])$/;
    d.extend({
        cache:{},
        uuid:0,
        expando:"jQuery"+(d.fn.jquery+Math.random()).replace(/\D/g,""),
        noData:{
            embed:!0,
            object:"clsid:D27CDB6E-AE6D-11cf-96B8-444553540000",
            applet:!0
        },
        hasData:function(a){
            a=a.nodeType?d.cache[a[d.expando]]:a[d.expando];
            return!!a&&!g(a)
        },
        data:function(a,c,e,f){
            if(d.acceptData(a)){
                var g=d.expando,h=typeof c==="string",i,j=a.nodeType,k=j?d.cache:a,l=j?a[d.expando]:a[d.expando]&&d.expando;
                if((!l||f&&l&&!k[l][g])&&h&&e===b)return;
                l||(j?a[d.expando]=l=++d.uuid:l=d.expando),k[l]||(k[l]={},j||(k[l].toJSON=d.noop));
                if(typeof c==="object"||typeof c==="function")f?k[l][g]=d.extend(k[l][g],c):k[l]=d.extend(k[l],c);
                i=k[l],f&&(i[g]||(i[g]={}),i=i[g]),e!==b&&(i[c]=e);
                if(c==="events"&&!i[c])return i[g]&&i[g].events;
                return h?i[c]:i
            }
        },
        removeData:function(b,c,e){
            if(d.acceptData(b)){
                var f=d.expando,h=b.nodeType,i=h?d.cache:b,j=h?b[d.expando]:d.expando;
                if(!i[j])return;
                if(c){
                    var k=e?i[j][f]:i[j];
                    if(k){
                        delete k[c];
                        if(!g(k))return
                    }
                }
                if(e){
                    delete i[j][f];
                    if(!g(i[j]))return
                }
                var l=i[j][f];
                d.support.deleteExpando||i!=a?delete i[j]:i[j]=null,l?(i[j]={},h||(i[j].toJSON=d.noop),i[j][f]=l):h&&(d.support.deleteExpando?delete b[d.expando]:b.removeAttribute?b.removeAttribute(d.expando):b[d.expando]=null)
            }
        },
        _data:function(a,b,c){
            return d.data(a,b,c,!0)
        },
        acceptData:function(a){
            if(a.nodeName){
                var b=d.noData[a.nodeName.toLowerCase()];
                if(b)return b!==!0&&a.getAttribute("classid")===b
            }
            return!0
        }
    }),d.fn.extend({
        data:function(a,c){
            var e=null;
            if(typeof a==="undefined"){
                if(this.length){
                    e=d.data(this[0]);
                    if(this[0].nodeType===1){
                        var g=this[0].attributes,h;
                        for(var i=0,j=g.length;i<j;i++)h=g[i].name,h.indexOf("data-")===0&&(h=h.substr(5),f(this[0],h,e[h]))
                    }
                }
                return e
            }
            if(typeof a==="object")return this.each(function(){
                d.data(this,a)
            });
            var k=a.split(".");
            k[1]=k[1]?"."+k[1]:"";
            if(c===b){
                e=this.triggerHandler("getData"+k[1]+"!",[k[0]]),e===b&&this.length&&(e=d.data(this[0],a),e=f(this[0],a,e));
                return e===b&&k[1]?this.data(k[0]):e
            }
            return this.each(function(){
                var b=d(this),e=[k[0],c];
                b.triggerHandler("setData"+k[1]+"!",e),d.data(this,a,c),b.triggerHandler("changeData"+k[1]+"!",e)
            })
        },
        removeData:function(a){
            return this.each(function(){
                d.removeData(this,a)
            })
        }
    }),d.extend({
        queue:function(a,b,c){
            if(a){
                b=(b||"fx")+"queue";
                var e=d._data(a,b);
                if(!c)return e||[];
                !e||d.isArray(c)?e=d._data(a,b,d.makeArray(c)):e.push(c);
                return e
            }
        },
        dequeue:function(a,b){
            b=b||"fx";
            var c=d.queue(a,b),e=c.shift();
            e==="inprogress"&&(e=c.shift()),e&&(b==="fx"&&c.unshift("inprogress"),e.call(a,function(){
                d.dequeue(a,b)
            })),c.length||d.removeData(a,b+"queue",!0)
        }
    }),d.fn.extend({
        queue:function(a,c){
            typeof a!=="string"&&(c=a,a="fx");
            if(c===b)return d.queue(this[0],a);
            return this.each(function(b){
                var e=d.queue(this,a,c);
                a==="fx"&&e[0]!=="inprogress"&&d.dequeue(this,a)
            })
        },
        dequeue:function(a){
            return this.each(function(){
                d.dequeue(this,a)
            })
        },
        delay:function(a,b){
            a=d.fx?d.fx.speeds[a]||a:a,b=b||"fx";
            return this.queue(b,function(){
                var c=this;
                setTimeout(function(){
                    d.dequeue(c,b)
                },a)
            })
        },
        clearQueue:function(a){
            return this.queue(a||"fx",[])
        }
    });
    var h=/[\n\t\r]/g,i=/\s+/,j=/\r/g,k=/^(?:href|src|style)$/,l=/^(?:button|input)$/i,m=/^(?:button|input|object|select|textarea)$/i,n=/^a(?:rea)?$/i,o=/^(?:radio|checkbox)$/i;
    d.props={
        "for":"htmlFor",
        "class":"className",
        readonly:"readOnly",
        maxlength:"maxLength",
        cellspacing:"cellSpacing",
        rowspan:"rowSpan",
        colspan:"colSpan",
        tabindex:"tabIndex",
        usemap:"useMap",
        frameborder:"frameBorder"
    },d.fn.extend({
        attr:function(a,b){
            return d.access(this,a,b,!0,d.attr)
        },
        removeAttr:function(a,b){
            return this.each(function(){
                d.attr(this,a,""),this.nodeType===1&&this.removeAttribute(a)
            })
        },
        addClass:function(a){
            if(d.isFunction(a))return this.each(function(b){
                var c=d(this);
                c.addClass(a.call(this,b,c.attr("class")))
            });
            if(a&&typeof a==="string"){
                var b=(a||"").split(i);
                for(var c=0,e=this.length;c<e;c++){
                    var f=this[c];
                    if(f.nodeType===1)if(f.className){
                        var g=" "+f.className+" ",h=f.className;
                        for(var j=0,k=b.length;j<k;j++)g.indexOf(" "+b[j]+" ")<0&&(h+=" "+b[j]);
                        f.className=d.trim(h)
                    }else f.className=a
                }
            }
            return this
        },
        removeClass:function(a){
            if(d.isFunction(a))return this.each(function(b){
                var c=d(this);
                c.removeClass(a.call(this,b,c.attr("class")))
            });
            if(a&&typeof a==="string"||a===b){
                var c=(a||"").split(i);
                for(var e=0,f=this.length;e<f;e++){
                    var g=this[e];
                    if(g.nodeType===1&&g.className)if(a){
                        var j=(" "+g.className+" ").replace(h," ");
                        for(var k=0,l=c.length;k<l;k++)j=j.replace(" "+c[k]+" "," ");
                        g.className=d.trim(j)
                    }else g.className=""
                }
            }
            return this
        },
        toggleClass:function(a,b){
            var c=typeof a,e=typeof b==="boolean";
            if(d.isFunction(a))return this.each(function(c){
                var e=d(this);
                e.toggleClass(a.call(this,c,e.attr("class"),b),b)
            });
            return this.each(function(){
                if(c==="string"){
                    var f,g=0,h=d(this),j=b,k=a.split(i);
                    while(f=k[g++])j=e?j:!h.hasClass(f),h[j?"addClass":"removeClass"](f)
                }else if(c==="undefined"||c==="boolean")this.className&&d._data(this,"__className__",this.className),this.className=this.className||a===!1?"":d._data(this,"__className__")||""
            })
        },
        hasClass:function(a){
            var b=" "+a+" ";
            for(var c=0,d=this.length;c<d;c++)if((" "+this[c].className+" ").replace(h," ").indexOf(b)>-1)return!0;return!1
        },
        val:function(a){
            if(!arguments.length){
                var c=this[0];
                if(c){
                    if(d.nodeName(c,"option")){
                        var e=c.attributes.value;
                        return!e||e.specified?c.value:c.text
                    }
                    if(d.nodeName(c,"select")){
                        var f=c.selectedIndex,g=[],h=c.options,i=c.type==="select-one";
                        if(f<0)return null;
                        for(var k=i?f:0,l=i?f+1:h.length;k<l;k++){
                            var m=h[k];
                            if(m.selected&&(d.support.optDisabled?!m.disabled:m.getAttribute("disabled")===null)&&(!m.parentNode.disabled||!d.nodeName(m.parentNode,"optgroup"))){
                                a=d(m).val();
                                if(i)return a;
                                g.push(a)
                            }
                        }
                        if(i&&!g.length&&h.length)return d(h[f]).val();
                        return g
                    }
                    if(o.test(c.type)&&!d.support.checkOn)return c.getAttribute("value")===null?"on":c.value;
                    return(c.value||"").replace(j,"")
                }
                return b
            }
            var n=d.isFunction(a);
            return this.each(function(b){
                var c=d(this),e=a;
                if(this.nodeType===1){
                    n&&(e=a.call(this,b,c.val())),e==null?e="":typeof e==="number"?e+="":d.isArray(e)&&(e=d.map(e,function(a){
                        return a==null?"":a+""
                    }));
                    if(d.isArray(e)&&o.test(this.type))this.checked=d.inArray(c.val(),e)>=0;
                    else if(d.nodeName(this,"select")){
                        var f=d.makeArray(e);
                        d("option",this).each(function(){
                            this.selected=d.inArray(d(this).val(),f)>=0
                        }),f.length||(this.selectedIndex=-1)
                    }else this.value=e
                }
            })
        }
    }),d.extend({
        attrFn:{
            val:!0,
            css:!0,
            html:!0,
            text:!0,
            data:!0,
            width:!0,
            height:!0,
            offset:!0
        },
        attr:function(a,c,e,f){
            if(!a||a.nodeType===3||a.nodeType===8||a.nodeType===2)return b;
            if(f&&c in d.attrFn)return d(a)[c](e);
            var g=a.nodeType!==1||!d.isXMLDoc(a),h=e!==b;
            c=g&&d.props[c]||c;
            if(a.nodeType===1){
                var i=k.test(c);
                if(c==="selected"&&!d.support.optSelected){
                    var j=a.parentNode;
                    j&&(j.selectedIndex,j.parentNode&&j.parentNode.selectedIndex)
                }
                if((c in a||a[c]!==b)&&g&&!i){
                    h&&(c==="type"&&l.test(a.nodeName)&&a.parentNode&&d.error("type property can't be changed"),e===null?a.nodeType===1&&a.removeAttribute(c):a[c]=e);
                    if(d.nodeName(a,"form")&&a.getAttributeNode(c))return a.getAttributeNode(c).nodeValue;
                    if(c==="tabIndex"){
                        var o=a.getAttributeNode("tabIndex");
                        return o&&o.specified?o.value:m.test(a.nodeName)||n.test(a.nodeName)&&a.href?0:b
                    }
                    return a[c]
                }
                if(!d.support.style&&g&&c==="style"){
                    h&&(a.style.cssText=""+e);
                    return a.style.cssText
                }
                h&&a.setAttribute(c,""+e);
                if(!a.attributes[c]&&(a.hasAttribute&&!a.hasAttribute(c)))return b;
                var p=!d.support.hrefNormalized&&g&&i?a.getAttribute(c,2):a.getAttribute(c);
                return p===null?b:p
            }
            h&&(a[c]=e);
            return a[c]
        }
    });
    var p=/\.(.*)$/,q=/^(?:textarea|input|select)$/i,r=/\./g,s=/ /g,t=/[^\w\s.|`]/g,u=function(a){
        return a.replace(t,"\\$&")
    };

    d.event={
        add:function(c,e,f,g){
            if(c.nodeType!==3&&c.nodeType!==8){
                try{
                    d.isWindow(c)&&(c!==a&&!c.frameElement)&&(c=a)
                }catch(h){}
                if(f===!1)f=v;
                else if(!f)return;
                var i,j;
                f.handler&&(i=f,f=i.handler),f.guid||(f.guid=d.guid++);
                var k=d._data(c);
                if(!k)return;
                var l=k.events,m=k.handle;
                l||(k.events=l={}),m||(k.handle=m=function(){
                    return typeof d!=="undefined"&&!d.event.triggered?d.event.handle.apply(m.elem,arguments):b
                }),m.elem=c,e=e.split(" ");
                var n,o=0,p;
                while(n=e[o++]){
                    j=i?d.extend({},i):{
                        handler:f,
                        data:g
                    },n.indexOf(".")>-1?(p=n.split("."),n=p.shift(),j.namespace=p.slice(0).sort().join(".")):(p=[],j.namespace=""),j.type=n,j.guid||(j.guid=f.guid);
                    var q=l[n],r=d.event.special[n]||{};

                    if(!q){
                        q=l[n]=[];
                        if(!r.setup||r.setup.call(c,g,p,m)===!1)c.addEventListener?c.addEventListener(n,m,!1):c.attachEvent&&c.attachEvent("on"+n,m)
                    }
                    r.add&&(r.add.call(c,j),j.handler.guid||(j.handler.guid=f.guid)),q.push(j),d.event.global[n]=!0
                }
                c=null
            }
        },
        global:{},
        remove:function(a,c,e,f){
            if(a.nodeType!==3&&a.nodeType!==8){
                e===!1&&(e=v);
                var g,h,i,j,k=0,l,m,n,o,p,q,r,s=d.hasData(a)&&d._data(a),t=s&&s.events;
                if(!s||!t)return;
                c&&c.type&&(e=c.handler,c=c.type);
                if(!c||typeof c==="string"&&c.charAt(0)==="."){
                    c=c||"";
                    for(h in t)d.event.remove(a,h+c);return
                }
                c=c.split(" ");
                while(h=c[k++]){
                    r=h,q=null,l=h.indexOf(".")<0,m=[],l||(m=h.split("."),h=m.shift(),n=new RegExp("(^|\\.)"+d.map(m.slice(0).sort(),u).join("\\.(?:.*\\.)?")+"(\\.|$)")),p=t[h];
                    if(!p)continue;
                    if(!e){
                        for(j=0;j<p.length;j++){
                            q=p[j];
                            if(l||n.test(q.namespace))d.event.remove(a,r,q.handler,j),p.splice(j--,1)
                        }
                        continue
                    }
                    o=d.event.special[h]||{};

                    for(j=f||0;j<p.length;j++){
                        q=p[j];
                        if(e.guid===q.guid){
                            if(l||n.test(q.namespace))f==null&&p.splice(j--,1),o.remove&&o.remove.call(a,q);
                            if(f!=null)break
                        }
                    }
                    if(p.length===0||f!=null&&p.length===1)(!o.teardown||o.teardown.call(a,m)===!1)&&d.removeEvent(a,h,s.handle),g=null,delete t[h]
                }
                if(d.isEmptyObject(t)){
                    var w=s.handle;
                    w&&(w.elem=null),delete s.events,delete s.handle,d.isEmptyObject(s)&&d.removeData(a,b,!0)
                }
            }
        },
        trigger:function(a,c,e){
            var f=a.type||a,g=arguments[3];
            if(!g){
                a=typeof a==="object"?a[d.expando]?a:d.extend(d.Event(f),a):d.Event(f),f.indexOf("!")>=0&&(a.type=f=f.slice(0,-1),a.exclusive=!0),e||(a.stopPropagation(),d.event.global[f]&&d.each(d.cache,function(){
                    var b=d.expando,e=this[b];
                    e&&e.events&&e.events[f]&&d.event.trigger(a,c,e.handle.elem)
                }));
                if(!e||e.nodeType===3||e.nodeType===8)return b;
                a.result=b,a.target=e,c=d.makeArray(c),c.unshift(a)
            }
            a.currentTarget=e;
            var h=d._data(e,"handle");
            h&&h.apply(e,c);
            var i=e.parentNode||e.ownerDocument;
            try{
                e&&e.nodeName&&d.noData[e.nodeName.toLowerCase()]||e["on"+f]&&e["on"+f].apply(e,c)===!1&&(a.result=!1,a.preventDefault())
            }catch(j){}
            if(!a.isPropagationStopped()&&i)d.event.trigger(a,c,i,!0);
            else if(!a.isDefaultPrevented()){
                var k,l=a.target,m=f.replace(p,""),n=d.nodeName(l,"a")&&m==="click",o=d.event.special[m]||{};

                if((!o._default||o._default.call(e,a)===!1)&&!n&&!(l&&l.nodeName&&d.noData[l.nodeName.toLowerCase()])){
                    try{
                        l[m]&&(k=l["on"+m],k&&(l["on"+m]=null),d.event.triggered=!0,l[m]())
                    }catch(q){}
                    k&&(l["on"+m]=k),d.event.triggered=!1
                }
            }
        },
        handle:function(c){
            var e,f,g,h,i,j=[],k=d.makeArray(arguments);
            c=k[0]=d.event.fix(c||a.event),c.currentTarget=this,e=c.type.indexOf(".")<0&&!c.exclusive,e||(g=c.type.split("."),c.type=g.shift(),j=g.slice(0).sort(),h=new RegExp("(^|\\.)"+j.join("\\.(?:.*\\.)?")+"(\\.|$)")),c.namespace=c.namespace||j.join("."),i=d._data(this,"events"),f=(i||{})[c.type];
            if(i&&f){
                f=f.slice(0);
                for(var l=0,m=f.length;l<m;l++){
                    var n=f[l];
                    if(e||h.test(n.namespace)){
                        c.handler=n.handler,c.data=n.data,c.handleObj=n;
                        var o=n.handler.apply(this,k);
                        o!==b&&(c.result=o,o===!1&&(c.preventDefault(),c.stopPropagation()));
                        if(c.isImmediatePropagationStopped())break
                    }
                }
            }
            return c.result
        },
        props:"altKey attrChange attrName bubbles button cancelable charCode clientX clientY ctrlKey currentTarget data detail eventPhase fromElement handler keyCode layerX layerY metaKey newValue offsetX offsetY pageX pageY prevValue relatedNode relatedTarget screenX screenY shiftKey srcElement target toElement view wheelDelta which".split(" "),
        fix:function(a){
            if(a[d.expando])return a;
            var e=a;
            a=d.Event(e);
            for(var f=this.props.length,g;f;)g=this.props[--f],a[g]=e[g];
            a.target||(a.target=a.srcElement||c),a.target.nodeType===3&&(a.target=a.target.parentNode),!a.relatedTarget&&a.fromElement&&(a.relatedTarget=a.fromElement===a.target?a.toElement:a.fromElement);
            if(a.pageX==null&&a.clientX!=null){
                var h=c.documentElement,i=c.body;
                a.pageX=a.clientX+(h&&h.scrollLeft||i&&i.scrollLeft||0)-(h&&h.clientLeft||i&&i.clientLeft||0),a.pageY=a.clientY+(h&&h.scrollTop||i&&i.scrollTop||0)-(h&&h.clientTop||i&&i.clientTop||0)
            }
            a.which==null&&(a.charCode!=null||a.keyCode!=null)&&(a.which=a.charCode!=null?a.charCode:a.keyCode),!a.metaKey&&a.ctrlKey&&(a.metaKey=a.ctrlKey),!a.which&&a.button!==b&&(a.which=a.button&1?1:a.button&2?3:a.button&4?2:0);
            return a
        },
        guid:1e8,
        proxy:d.proxy,
        special:{
            ready:{
                setup:d.bindReady,
                teardown:d.noop
            },
            live:{
                add:function(a){
                    d.event.add(this,F(a.origType,a.selector),d.extend({},a,{
                        handler:E,
                        guid:a.handler.guid
                    }))
                },
                remove:function(a){
                    d.event.remove(this,F(a.origType,a.selector),a)
                }
            },
            beforeunload:{
                setup:function(a,b,c){
                    d.isWindow(this)&&(this.onbeforeunload=c)
                },
                teardown:function(a,b){
                    this.onbeforeunload===b&&(this.onbeforeunload=null)
                }
            }
        }
    },d.removeEvent=c.removeEventListener?function(a,b,c){
        a.removeEventListener&&a.removeEventListener(b,c,!1)
    }:function(a,b,c){
        a.detachEvent&&a.detachEvent("on"+b,c)
    },d.Event=function(a){
        if(!this.preventDefault)return new d.Event(a);
        a&&a.type?(this.originalEvent=a,this.type=a.type,this.isDefaultPrevented=a.defaultPrevented||a.returnValue===!1||a.getPreventDefault&&a.getPreventDefault()?w:v):this.type=a,this.timeStamp=d.now(),this[d.expando]=!0
    },d.Event.prototype={
        preventDefault:function(){
            this.isDefaultPrevented=w;
            var a=this.originalEvent;
            a&&(a.preventDefault?a.preventDefault():a.returnValue=!1)
        },
        stopPropagation:function(){
            this.isPropagationStopped=w;
            var a=this.originalEvent;
            a&&(a.stopPropagation&&a.stopPropagation(),a.cancelBubble=!0)
        },
        stopImmediatePropagation:function(){
            this.isImmediatePropagationStopped=w,this.stopPropagation()
        },
        isDefaultPrevented:v,
        isPropagationStopped:v,
        isImmediatePropagationStopped:v
    };

    var x=function(a){
        var b=a.relatedTarget;
        try{
            if(b!==c&&!b.parentNode)return;
            while(b&&b!==this)b=b.parentNode;
            b!==this&&(a.type=a.data,d.event.handle.apply(this,arguments))
        }catch(e){}
    },y=function(a){
        a.type=a.data,d.event.handle.apply(this,arguments)
    };

    d.each({
        mouseenter:"mouseover",
        mouseleave:"mouseout"
    },function(a,b){
        d.event.special[a]={
            setup:function(c){
                d.event.add(this,b,c&&c.selector?y:x,a)
            },
            teardown:function(a){
                d.event.remove(this,b,a&&a.selector?y:x)
            }
        }
    }),d.support.submitBubbles||(d.event.special.submit={
        setup:function(a,b){
            if(this.nodeName&&this.nodeName.toLowerCase()!=="form")d.event.add(this,"click.specialSubmit",function(a){
                var b=a.target,c=b.type;
                (c==="submit"||c==="image")&&d(b).closest("form").length&&C("submit",this,arguments)
            }),d.event.add(this,"keypress.specialSubmit",function(a){
                var b=a.target,c=b.type;
                (c==="text"||c==="password")&&d(b).closest("form").length&&a.keyCode===13&&C("submit",this,arguments)
            });else return!1
        },
        teardown:function(a){
            d.event.remove(this,".specialSubmit")
        }
    });
    if(!d.support.changeBubbles){
        var z,A=function(a){
            var b=a.type,c=a.value;
            b==="radio"||b==="checkbox"?c=a.checked:b==="select-multiple"?c=a.selectedIndex>-1?d.map(a.options,function(a){
                return a.selected
            }).join("-"):"":a.nodeName.toLowerCase()==="select"&&(c=a.selectedIndex);
            return c
        },B=function B(a){
            var c=a.target,e,f;
            if(q.test(c.nodeName)&&!c.readOnly){
                e=d._data(c,"_change_data"),f=A(c),(a.type!=="focusout"||c.type!=="radio")&&d._data(c,"_change_data",f);
                if(e===b||f===e)return;
                if(e!=null||f)a.type="change",a.liveFired=b,d.event.trigger(a,arguments[1],c)
            }
        };

        d.event.special.change={
            filters:{
                focusout:B,
                beforedeactivate:B,
                click:function(a){
                    var b=a.target,c=b.type;
                    (c==="radio"||c==="checkbox"||b.nodeName.toLowerCase()==="select")&&B.call(this,a)
                },
                keydown:function(a){
                    var b=a.target,c=b.type;
                    (a.keyCode===13&&b.nodeName.toLowerCase()!=="textarea"||a.keyCode===32&&(c==="checkbox"||c==="radio")||c==="select-multiple")&&B.call(this,a)
                },
                beforeactivate:function(a){
                    var b=a.target;
                    d._data(b,"_change_data",A(b))
                }
            },
            setup:function(a,b){
                if(this.type==="file")return!1;
                for(var c in z)d.event.add(this,c+".specialChange",z[c]);return q.test(this.nodeName)
            },
            teardown:function(a){
                d.event.remove(this,".specialChange");
                return q.test(this.nodeName)
            }
        },z=d.event.special.change.filters,z.focus=z.beforeactivate
    }
    c.addEventListener&&d.each({
        focus:"focusin",
        blur:"focusout"
    },function(a,b){
        function c(a){
            a=d.event.fix(a),a.type=b;
            return d.event.handle.call(this,a)
        }
        d.event.special[b]={
            setup:function(){
                this.addEventListener(a,c,!0)
            },
            teardown:function(){
                this.removeEventListener(a,c,!0)
            }
        }
    }),d.each(["bind","one"],function(a,c){
        d.fn[c]=function(a,e,f){
            if(typeof a==="object"){
                for(var g in a)this[c](g,e,a[g],f);return this
            }
            if(d.isFunction(e)||e===!1)f=e,e=b;
            var h=c==="one"?d.proxy(f,function(a){
                d(this).unbind(a,h);
                return f.apply(this,arguments)
            }):f;
            if(a==="unload"&&c!=="one")this.one(a,e,f);else for(var i=0,j=this.length;i<j;i++)d.event.add(this[i],a,h,e);
            return this
        }
    }),d.fn.extend({
        unbind:function(a,b){
            if(typeof a!=="object"||a.preventDefault)for(var e=0,f=this.length;e<f;e++)d.event.remove(this[e],a,b);else for(var c in a)this.unbind(c,a[c]);return this
        },
        delegate:function(a,b,c,d){
            return this.live(b,c,d,a)
        },
        undelegate:function(a,b,c){
            return arguments.length===0?this.unbind("live"):this.die(b,null,c,a)
        },
        trigger:function(a,b){
            return this.each(function(){
                d.event.trigger(a,b,this)
            })
        },
        triggerHandler:function(a,b){
            if(this[0]){
                var c=d.Event(a);
                c.preventDefault(),c.stopPropagation(),d.event.trigger(c,b,this[0]);
                return c.result
            }
        },
        toggle:function(a){
            var b=arguments,c=1;
            while(c<b.length)d.proxy(a,b[c++]);
            return this.click(d.proxy(a,function(e){
                var f=(d._data(this,"lastToggle"+a.guid)||0)%c;
                d._data(this,"lastToggle"+a.guid,f+1),e.preventDefault();
                return b[f].apply(this,arguments)||!1
            }))
        },
        hover:function(a,b){
            return this.mouseenter(a).mouseleave(b||a)
        }
    });
    var D={
        focus:"focusin",
        blur:"focusout",
        mouseenter:"mouseover",
        mouseleave:"mouseout"
    };

    d.each(["live","die"],function(a,c){
        d.fn[c]=function(a,e,f,g){
            var h,i=0,j,k,l,m=g||this.selector,n=g?this:d(this.context);
            if(typeof a==="object"&&!a.preventDefault){
                for(var o in a)n[c](o,e,a[o],m);return this
            }
            d.isFunction(e)&&(f=e,e=b),a=(a||"").split(" ");
            while((h=a[i++])!=null){
                j=p.exec(h),k="",j&&(k=j[0],h=h.replace(p,""));
                if(h==="hover"){
                    a.push("mouseenter"+k,"mouseleave"+k);
                    continue
                }
                l=h,h==="focus"||h==="blur"?(a.push(D[h]+k),h=h+k):h=(D[h]||h)+k;
                if(c==="live")for(var q=0,r=n.length;q<r;q++)d.event.add(n[q],"live."+F(h,m),{
                    data:e,
                    selector:m,
                    handler:f,
                    origType:h,
                    origHandler:f,
                    preType:l
                });else n.unbind("live."+F(h,m),f)
            }
            return this
        }
    }),d.each("blur focus focusin focusout load resize scroll unload click dblclick mousedown mouseup mousemove mouseover mouseout mouseenter mouseleave change select submit keydown keypress keyup error".split(" "),function(a,b){
        d.fn[b]=function(a,c){
            c==null&&(c=a,a=null);
            return arguments.length>0?this.bind(b,a,c):this.trigger(b)
        },d.attrFn&&(d.attrFn[b]=!0)
    }),function(){
        function u(a,b,c,d,e,f){
            for(var g=0,h=d.length;g<h;g++){
                var i=d[g];
                if(i){
                    var j=!1;
                    i=i[a];
                    while(i){
                        if(i.sizcache===c){
                            j=d[i.sizset];
                            break
                        }
                        if(i.nodeType===1){
                            f||(i.sizcache=c,i.sizset=g);
                            if(typeof b!=="string"){
                                if(i===b){
                                    j=!0;
                                    break
                                }
                            }else if(k.filter(b,[i]).length>0){
                                j=i;
                                break
                            }
                        }
                        i=i[a]
                    }
                    d[g]=j
                }
            }
        }
        function t(a,b,c,d,e,f){
            for(var g=0,h=d.length;g<h;g++){
                var i=d[g];
                if(i){
                    var j=!1;
                    i=i[a];
                    while(i){
                        if(i.sizcache===c){
                            j=d[i.sizset];
                            break
                        }
                        i.nodeType===1&&!f&&(i.sizcache=c,i.sizset=g);
                        if(i.nodeName.toLowerCase()===b){
                            j=i;
                            break
                        }
                        i=i[a]
                    }
                    d[g]=j
                }
            }
        }
        var a=/((?:\((?:\([^()]+\)|[^()]+)+\)|\[(?:\[[^\[\]]*\]|['"][^'"]*['"]|[^\[\]'"]+)+\]|\\.|[^ >+~,(\[\\]+)+|[>+~])(\s*,\s*)?((?:.|\r|\n)*)/g,e=0,f=Object.prototype.toString,g=!1,h=!0,i=/\\/g,j=/\W/;
        [0,0].sort(function(){
            h=!1;
            return 0
        });
        var k=function(b,d,e,g){
            e=e||[],d=d||c;
            var h=d;
            if(d.nodeType!==1&&d.nodeType!==9)return[];
            if(!b||typeof b!=="string")return e;
            var i,j,n,o,q,r,s,t,u=!0,w=k.isXML(d),x=[],y=b;
            do{
                a.exec(""),i=a.exec(y);
                if(i){
                    y=i[3],x.push(i[1]);
                    if(i[2]){
                        o=i[3];
                        break
                    }
                }
            }while(i);
            if(x.length>1&&m.exec(b))if(x.length===2&&l.relative[x[0]])j=v(x[0]+x[1],d);
                else{
                    j=l.relative[x[0]]?[d]:k(x.shift(),d);
                    while(x.length)b=x.shift(),l.relative[b]&&(b+=x.shift()),j=v(b,j)
                }else{
                !g&&x.length>1&&d.nodeType===9&&!w&&l.match.ID.test(x[0])&&!l.match.ID.test(x[x.length-1])&&(q=k.find(x.shift(),d,w),d=q.expr?k.filter(q.expr,q.set)[0]:q.set[0]);
                if(d){
                    q=g?{
                        expr:x.pop(),
                        set:p(g)
                    }:k.find(x.pop(),x.length===1&&(x[0]==="~"||x[0]==="+")&&d.parentNode?d.parentNode:d,w),j=q.expr?k.filter(q.expr,q.set):q.set,x.length>0?n=p(j):u=!1;
                    while(x.length)r=x.pop(),s=r,l.relative[r]?s=x.pop():r="",s==null&&(s=d),l.relative[r](n,s,w)
                }else n=x=[]
            }
            n||(n=j),n||k.error(r||b);
            if(f.call(n)==="[object Array]")if(u)if(d&&d.nodeType===1)for(t=0;n[t]!=null;t++)n[t]&&(n[t]===!0||n[t].nodeType===1&&k.contains(d,n[t]))&&e.push(j[t]);else for(t=0;n[t]!=null;t++)n[t]&&n[t].nodeType===1&&e.push(j[t]);else e.push.apply(e,n);else p(n,e);
            o&&(k(o,h,e,g),k.uniqueSort(e));
            return e
        };

        k.uniqueSort=function(a){
            if(r){
                g=h,a.sort(r);
                if(g)for(var b=1;b<a.length;b++)a[b]===a[b-1]&&a.splice(b--,1)
            }
            return a
        },k.matches=function(a,b){
            return k(a,null,null,b)
        },k.matchesSelector=function(a,b){
            return k(b,null,null,[a]).length>0
        },k.find=function(a,b,c){
            var d;
            if(!a)return[];
            for(var e=0,f=l.order.length;e<f;e++){
                var g,h=l.order[e];
                if(g=l.leftMatch[h].exec(a)){
                    var j=g[1];
                    g.splice(1,1);
                    if(j.substr(j.length-1)!=="\\"){
                        g[1]=(g[1]||"").replace(i,""),d=l.find[h](g,b,c);
                        if(d!=null){
                            a=a.replace(l.match[h],"");
                            break
                        }
                    }
                }
            }
            d||(d=typeof b.getElementsByTagName!=="undefined"?b.getElementsByTagName("*"):[]);
            return{
                set:d,
                expr:a
            }
        },k.filter=function(a,c,d,e){
            var f,g,h=a,i=[],j=c,m=c&&c[0]&&k.isXML(c[0]);
            while(a&&c.length){
                for(var n in l.filter)if((f=l.leftMatch[n].exec(a))!=null&&f[2]){
                    var o,p,q=l.filter[n],r=f[1];
                    g=!1,f.splice(1,1);
                    if(r.substr(r.length-1)==="\\")continue;
                    j===i&&(i=[]);
                    if(l.preFilter[n]){
                        f=l.preFilter[n](f,j,d,i,e,m);
                        if(f){
                            if(f===!0)continue
                        }else g=o=!0
                    }
                    if(f)for(var s=0;(p=j[s])!=null;s++)if(p){
                        o=q(p,f,s,j);
                        var t=e^!!o;
                        d&&o!=null?t?g=!0:j[s]=!1:t&&(i.push(p),g=!0)
                    }
                    if(o!==b){
                        d||(j=i),a=a.replace(l.match[n],"");
                        if(!g)return[];
                        break
                    }
                }
                if(a===h)if(g==null)k.error(a);else break;
                h=a
            }
            return j
        },k.error=function(a){
            throw"Syntax error, unrecognized expression: "+a
        };

        var l=k.selectors={
            order:["ID","NAME","TAG"],
            match:{
                ID:/#((?:[\w\u00c0-\uFFFF\-]|\\.)+)/,
                CLASS:/\.((?:[\w\u00c0-\uFFFF\-]|\\.)+)/,
                NAME:/\[name=['"]*((?:[\w\u00c0-\uFFFF\-]|\\.)+)['"]*\]/,
                ATTR:/\[\s*((?:[\w\u00c0-\uFFFF\-]|\\.)+)\s*(?:(\S?=)\s*(?:(['"])(.*?)\3|(#?(?:[\w\u00c0-\uFFFF\-]|\\.)*)|)|)\s*\]/,
                TAG:/^((?:[\w\u00c0-\uFFFF\*\-]|\\.)+)/,
                CHILD:/:(only|nth|last|first)-child(?:\(\s*(even|odd|(?:[+\-]?\d+|(?:[+\-]?\d*)?n\s*(?:[+\-]\s*\d+)?))\s*\))?/,
                POS:/:(nth|eq|gt|lt|first|last|even|odd)(?:\((\d*)\))?(?=[^\-]|$)/,
                PSEUDO:/:((?:[\w\u00c0-\uFFFF\-]|\\.)+)(?:\((['"]?)((?:\([^\)]+\)|[^\(\)]*)+)\2\))?/
            },
            leftMatch:{},
            attrMap:{
                "class":"className",
                "for":"htmlFor"
            },
            attrHandle:{
                href:function(a){
                    return a.getAttribute("href")
                },
                type:function(a){
                    return a.getAttribute("type")
                }
            },
            relative:{
                "+":function(a,b){
                    var c=typeof b==="string",d=c&&!j.test(b),e=c&&!d;
                    d&&(b=b.toLowerCase());
                    for(var f=0,g=a.length,h;f<g;f++)if(h=a[f]){
                        while((h=h.previousSibling)&&h.nodeType!==1){}
                        a[f]=e||h&&h.nodeName.toLowerCase()===b?h||!1:h===b
                    }
                    e&&k.filter(b,a,!0)
                },
                ">":function(a,b){
                    var c,d=typeof b==="string",e=0,f=a.length;
                    if(d&&!j.test(b)){
                        b=b.toLowerCase();
                        for(;e<f;e++){
                            c=a[e];
                            if(c){
                                var g=c.parentNode;
                                a[e]=g.nodeName.toLowerCase()===b?g:!1
                            }
                        }
                    }else{
                        for(;e<f;e++)c=a[e],c&&(a[e]=d?c.parentNode:c.parentNode===b);
                        d&&k.filter(b,a,!0)
                    }
                },
                "":function(a,b,c){
                    var d,f=e++,g=u;
                    typeof b==="string"&&!j.test(b)&&(b=b.toLowerCase(),d=b,g=t),g("parentNode",b,f,a,d,c)
                },
                "~":function(a,b,c){
                    var d,f=e++,g=u;
                    typeof b==="string"&&!j.test(b)&&(b=b.toLowerCase(),d=b,g=t),g("previousSibling",b,f,a,d,c)
                }
            },
            find:{
                ID:function(a,b,c){
                    if(typeof b.getElementById!=="undefined"&&!c){
                        var d=b.getElementById(a[1]);
                        return d&&d.parentNode?[d]:[]
                    }
                },
                NAME:function(a,b){
                    if(typeof b.getElementsByName!=="undefined"){
                        var c=[],d=b.getElementsByName(a[1]);
                        for(var e=0,f=d.length;e<f;e++)d[e].getAttribute("name")===a[1]&&c.push(d[e]);
                        return c.length===0?null:c
                    }
                },
                TAG:function(a,b){
                    if(typeof b.getElementsByTagName!=="undefined")return b.getElementsByTagName(a[1])
                }
            },
            preFilter:{
                CLASS:function(a,b,c,d,e,f){
                    a=" "+a[1].replace(i,"")+" ";
                    if(f)return a;
                    for(var g=0,h;(h=b[g])!=null;g++)h&&(e^(h.className&&(" "+h.className+" ").replace(/[\t\n\r]/g," ").indexOf(a)>=0)?c||d.push(h):c&&(b[g]=!1));
                    return!1
                },
                ID:function(a){
                    return a[1].replace(i,"")
                },
                TAG:function(a,b){
                    return a[1].replace(i,"").toLowerCase()
                },
                CHILD:function(a){
                    if(a[1]==="nth"){
                        a[2]||k.error(a[0]),a[2]=a[2].replace(/^\+|\s*/g,"");
                        var b=/(-?)(\d*)(?:n([+\-]?\d*))?/.exec(a[2]==="even"&&"2n"||a[2]==="odd"&&"2n+1"||!/\D/.test(a[2])&&"0n+"+a[2]||a[2]);
                        a[2]=b[1]+(b[2]||1)-0,a[3]=b[3]-0
                    }else a[2]&&k.error(a[0]);
                    a[0]=e++;
                    return a
                },
                ATTR:function(a,b,c,d,e,f){
                    var g=a[1]=a[1].replace(i,"");
                    !f&&l.attrMap[g]&&(a[1]=l.attrMap[g]),a[4]=(a[4]||a[5]||"").replace(i,""),a[2]==="~="&&(a[4]=" "+a[4]+" ");
                    return a
                },
                PSEUDO:function(b,c,d,e,f){
                    if(b[1]==="not")if((a.exec(b[3])||"").length>1||/^\w/.test(b[3]))b[3]=k(b[3],null,null,c);
                        else{
                            var g=k.filter(b[3],c,d,!0^f);
                            d||e.push.apply(e,g);
                            return!1
                        }else if(l.match.POS.test(b[0])||l.match.CHILD.test(b[0]))return!0;
                    return b
                },
                POS:function(a){
                    a.unshift(!0);
                    return a
                }
            },
            filters:{
                enabled:function(a){
                    return a.disabled===!1&&a.type!=="hidden"
                },
                disabled:function(a){
                    return a.disabled===!0
                },
                checked:function(a){
                    return a.checked===!0
                },
                selected:function(a){
                    a.parentNode&&a.parentNode.selectedIndex;
                    return a.selected===!0
                },
                parent:function(a){
                    return!!a.firstChild
                },
                empty:function(a){
                    return!a.firstChild
                },
                has:function(a,b,c){
                    return!!k(c[3],a).length
                },
                header:function(a){
                    return/h\d/i.test(a.nodeName)
                },
                text:function(a){
                    return"text"===a.getAttribute("type")
                },
                radio:function(a){
                    return"radio"===a.type
                },
                checkbox:function(a){
                    return"checkbox"===a.type
                },
                file:function(a){
                    return"file"===a.type
                },
                password:function(a){
                    return"password"===a.type
                },
                submit:function(a){
                    return"submit"===a.type
                },
                image:function(a){
                    return"image"===a.type
                },
                reset:function(a){
                    return"reset"===a.type
                },
                button:function(a){
                    return"button"===a.type||a.nodeName.toLowerCase()==="button"
                },
                input:function(a){
                    return/input|select|textarea|button/i.test(a.nodeName)
                }
            },
            setFilters:{
                first:function(a,b){
                    return b===0
                },
                last:function(a,b,c,d){
                    return b===d.length-1
                },
                even:function(a,b){
                    return b%2===0
                },
                odd:function(a,b){
                    return b%2===1
                },
                lt:function(a,b,c){
                    return b<c[3]-0
                },
                gt:function(a,b,c){
                    return b>c[3]-0
                },
                nth:function(a,b,c){
                    return c[3]-0===b
                },
                eq:function(a,b,c){
                    return c[3]-0===b
                }
            },
            filter:{
                PSEUDO:function(a,b,c,d){
                    var e=b[1],f=l.filters[e];
                    if(f)return f(a,c,b,d);
                    if(e==="contains")return(a.textContent||a.innerText||k.getText([a])||"").indexOf(b[3])>=0;
                    if(e==="not"){
                        var g=b[3];
                        for(var h=0,i=g.length;h<i;h++)if(g[h]===a)return!1;return!0
                    }
                    k.error(e)
                },
                CHILD:function(a,b){
                    var c=b[1],d=a;
                    switch(c){
                        case"only":case"first":
                            while(d=d.previousSibling)if(d.nodeType===1)return!1;
                            if(c==="first")return!0;
                            d=a;
                        case"last":
                            while(d=d.nextSibling)if(d.nodeType===1)return!1;
                            return!0;
                        case"nth":
                            var e=b[2],f=b[3];
                            if(e===1&&f===0)return!0;
                            var g=b[0],h=a.parentNode;
                            if(h&&(h.sizcache!==g||!a.nodeIndex)){
                                var i=0;
                                for(d=h.firstChild;d;d=d.nextSibling)d.nodeType===1&&(d.nodeIndex=++i);
                                h.sizcache=g
                            }
                            var j=a.nodeIndex-f;
                            return e===0?j===0:j%e===0&&j/e>=0
                    }
                },
                ID:function(a,b){
                    return a.nodeType===1&&a.getAttribute("id")===b
                },
                TAG:function(a,b){
                    return b==="*"&&a.nodeType===1||a.nodeName.toLowerCase()===b
                },
                CLASS:function(a,b){
                    return(" "+(a.className||a.getAttribute("class"))+" ").indexOf(b)>-1
                },
                ATTR:function(a,b){
                    var c=b[1],d=l.attrHandle[c]?l.attrHandle[c](a):a[c]!=null?a[c]:a.getAttribute(c),e=d+"",f=b[2],g=b[4];
                    return d==null?f==="!=":f==="="?e===g:f==="*="?e.indexOf(g)>=0:f==="~="?(" "+e+" ").indexOf(g)>=0:g?f==="!="?e!==g:f==="^="?e.indexOf(g)===0:f==="$="?e.substr(e.length-g.length)===g:f==="|="?e===g||e.substr(0,g.length+1)===g+"-":!1:e&&d!==!1
                },
                POS:function(a,b,c,d){
                    var e=b[2],f=l.setFilters[e];
                    if(f)return f(a,c,b,d)
                }
            }
        },m=l.match.POS,n=function(a,b){
            return"\\"+(b-0+1)
        };

        for(var o in l.match)l.match[o]=new RegExp(l.match[o].source+/(?![^\[]*\])(?![^\(]*\))/.source),l.leftMatch[o]=new RegExp(/(^(?:.|\r|\n)*?)/.source+l.match[o].source.replace(/\\(\d+)/g,n));var p=function(a,b){
            a=Array.prototype.slice.call(a,0);
            if(b){
                b.push.apply(b,a);
                return b
            }
            return a
        };

        try{
            Array.prototype.slice.call(c.documentElement.childNodes,0)[0].nodeType
        }catch(q){
            p=function(a,b){
                var c=0,d=b||[];
                if(f.call(a)==="[object Array]")Array.prototype.push.apply(d,a);
                else if(typeof a.length==="number")for(var e=a.length;c<e;c++)d.push(a[c]);else for(;a[c];c++)d.push(a[c]);
                return d
            }
        }
        var r,s;
        c.documentElement.compareDocumentPosition?r=function(a,b){
            if(a===b){
                g=!0;
                return 0
            }
            if(!a.compareDocumentPosition||!b.compareDocumentPosition)return a.compareDocumentPosition?-1:1;
            return a.compareDocumentPosition(b)&4?-1:1
        }:(r=function(a,b){
            var c,d,e=[],f=[],h=a.parentNode,i=b.parentNode,j=h;
            if(a===b){
                g=!0;
                return 0
            }
            if(h===i)return s(a,b);
            if(!h)return-1;
            if(!i)return 1;
            while(j)e.unshift(j),j=j.parentNode;
            j=i;
            while(j)f.unshift(j),j=j.parentNode;
            c=e.length,d=f.length;
            for(var k=0;k<c&&k<d;k++)if(e[k]!==f[k])return s(e[k],f[k]);return k===c?s(a,f[k],-1):s(e[k],b,1)
        },s=function(a,b,c){
            if(a===b)return c;
            var d=a.nextSibling;
            while(d){
                if(d===b)return-1;
                d=d.nextSibling
            }
            return 1
        }),k.getText=function(a){
            var b="",c;
            for(var d=0;a[d];d++)c=a[d],c.nodeType===3||c.nodeType===4?b+=c.nodeValue:c.nodeType!==8&&(b+=k.getText(c.childNodes));
            return b
        },function(){
            var a=c.createElement("div"),d="script"+(new Date).getTime(),e=c.documentElement;
            a.innerHTML="<a name='"+d+"'/>",e.insertBefore(a,e.firstChild),c.getElementById(d)&&(l.find.ID=function(a,c,d){
                if(typeof c.getElementById!=="undefined"&&!d){
                    var e=c.getElementById(a[1]);
                    return e?e.id===a[1]||typeof e.getAttributeNode!=="undefined"&&e.getAttributeNode("id").nodeValue===a[1]?[e]:b:[]
                }
            },l.filter.ID=function(a,b){
                var c=typeof a.getAttributeNode!=="undefined"&&a.getAttributeNode("id");
                return a.nodeType===1&&c&&c.nodeValue===b
            }),e.removeChild(a),e=a=null
        }(),function(){
            var a=c.createElement("div");
            a.appendChild(c.createComment("")),a.getElementsByTagName("*").length>0&&(l.find.TAG=function(a,b){
                var c=b.getElementsByTagName(a[1]);
                if(a[1]==="*"){
                    var d=[];
                    for(var e=0;c[e];e++)c[e].nodeType===1&&d.push(c[e]);
                    c=d
                }
                return c
            }),a.innerHTML="<a href='#'></a>",a.firstChild&&typeof a.firstChild.getAttribute!=="undefined"&&a.firstChild.getAttribute("href")!=="#"&&(l.attrHandle.href=function(a){
                return a.getAttribute("href",2)
            }),a=null
        }(),c.querySelectorAll&&function(){
            var a=k,b=c.createElement("div"),d="__sizzle__";
            b.innerHTML="<p class='TEST'></p>";
            if(!b.querySelectorAll||b.querySelectorAll(".TEST").length!==0){
                k=function(b,e,f,g){
                    e=e||c;
                    if(!g&&!k.isXML(e)){
                        var h=/^(\w+$)|^\.([\w\-]+$)|^#([\w\-]+$)/.exec(b);
                        if(h&&(e.nodeType===1||e.nodeType===9)){
                            if(h[1])return p(e.getElementsByTagName(b),f);
                            if(h[2]&&l.find.CLASS&&e.getElementsByClassName)return p(e.getElementsByClassName(h[2]),f)
                        }
                        if(e.nodeType===9){
                            if(b==="body"&&e.body)return p([e.body],f);
                            if(h&&h[3]){
                                var i=e.getElementById(h[3]);
                                if(!i||!i.parentNode)return p([],f);
                                if(i.id===h[3])return p([i],f)
                            }
                            try{
                                return p(e.querySelectorAll(b),f)
                            }
                            catch(j){}
                        }else if(e.nodeType===1&&e.nodeName.toLowerCase()!=="object"){
                            var m=e,n=e.getAttribute("id"),o=n||d,q=e.parentNode,r=/^\s*[+~]/.test(b);
                            n?o=o.replace(/'/g,"\\$&"):e.setAttribute("id",o),r&&q&&(e=e.parentNode);
                            try{
                                if(!r||q)return p(e.querySelectorAll("[id='"+o+"'] "+b),f)
                            }catch(s){}finally{
                                n||m.removeAttribute("id")
                            }
                        }
                    }
                    return a(b,e,f,g)
                };

                for(var e in a)k[e]=a[e];b=null
            }
        }(),function(){
            var a=c.documentElement,b=a.matchesSelector||a.mozMatchesSelector||a.webkitMatchesSelector||a.msMatchesSelector,d=!1;
            try{
                b.call(c.documentElement,"[test!='']:sizzle")
            }catch(e){
                d=!0
            }
            b&&(k.matchesSelector=function(a,c){
                c=c.replace(/\=\s*([^'"\]]*)\s*\]/g,"='$1']");
                if(!k.isXML(a))try{
                    if(d||!l.match.PSEUDO.test(c)&&!/!=/.test(c))return b.call(a,c)
                }catch(e){}
                return k(c,null,null,[a]).length>0
            })
        }(),function(){
            var a=c.createElement("div");
            a.innerHTML="<div class='test e'></div><div class='test'></div>";
            if(a.getElementsByClassName&&a.getElementsByClassName("e").length!==0){
                a.lastChild.className="e";
                if(a.getElementsByClassName("e").length===1)return;
                l.order.splice(1,0,"CLASS"),l.find.CLASS=function(a,b,c){
                    if(typeof b.getElementsByClassName!=="undefined"&&!c)return b.getElementsByClassName(a[1])
                },a=null
            }
        }(),c.documentElement.contains?k.contains=function(a,b){
            return a!==b&&(a.contains?a.contains(b):!0)
        }:c.documentElement.compareDocumentPosition?k.contains=function(a,b){
            return!!(a.compareDocumentPosition(b)&16)
        }:k.contains=function(){
            return!1
        },k.isXML=function(a){
            var b=(a?a.ownerDocument||a:0).documentElement;
            return b?b.nodeName!=="HTML":!1
        };

        var v=function(a,b){
            var c,d=[],e="",f=b.nodeType?[b]:b;
            while(c=l.match.PSEUDO.exec(a))e+=c[0],a=a.replace(l.match.PSEUDO,"");
            a=l.relative[a]?a+"*":a;
            for(var g=0,h=f.length;g<h;g++)k(a,f[g],d);
            return k.filter(e,d)
        };

        d.find=k,d.expr=k.selectors,d.expr[":"]=d.expr.filters,d.unique=k.uniqueSort,d.text=k.getText,d.isXMLDoc=k.isXML,d.contains=k.contains
    }();
    var G=/Until$/,H=/^(?:parents|prevUntil|prevAll)/,I=/,/,J=/^.[^:#\[\.,]*$/,K=Array.prototype.slice,L=d.expr.match.POS,M={
        children:!0,
        contents:!0,
        next:!0,
        prev:!0
    };

    d.fn.extend({
        find:function(a){
            var b=this.pushStack("","find",a),c=0;
            for(var e=0,f=this.length;e<f;e++){
                c=b.length,d.find(a,this[e],b);
                if(e>0)for(var g=c;g<b.length;g++)for(var h=0;h<c;h++)if(b[h]===b[g]){
                    b.splice(g--,1);
                    break
                }
            }
            return b
        },
        has:function(a){
            var b=d(a);
            return this.filter(function(){
                for(var a=0,c=b.length;a<c;a++)if(d.contains(this,b[a]))return!0
            })
        },
        not:function(a){
            return this.pushStack(O(this,a,!1),"not",a)
        },
        filter:function(a){
            return this.pushStack(O(this,a,!0),"filter",a)
        },
        is:function(a){
            return!!a&&d.filter(a,this).length>0
        },
        closest:function(a,b){
            var c=[],e,f,g=this[0];
            if(d.isArray(a)){
                var h,i,j={},k=1;
                if(g&&a.length){
                    for(e=0,f=a.length;e<f;e++)i=a[e],j[i]||(j[i]=d.expr.match.POS.test(i)?d(i,b||this.context):i);
                    while(g&&g.ownerDocument&&g!==b){
                        for(i in j)h=j[i],(h.jquery?h.index(g)>-1:d(g).is(h))&&c.push({
                            selector:i,
                            elem:g,
                            level:k
                        });g=g.parentNode,k++
                    }
                }
                return c
            }
            var l=L.test(a)?d(a,b||this.context):null;
            for(e=0,f=this.length;e<f;e++){
                g=this[e];
                while(g){
                    if(l?l.index(g)>-1:d.find.matchesSelector(g,a)){
                        c.push(g);
                        break
                    }
                    g=g.parentNode;
                    if(!g||!g.ownerDocument||g===b)break
                }
            }
            c=c.length>1?d.unique(c):c;
            return this.pushStack(c,"closest",a)
        },
        index:function(a){
            if(!a||typeof a==="string")return d.inArray(this[0],a?d(a):this.parent().children());
            return d.inArray(a.jquery?a[0]:a,this)
        },
        add:function(a,b){
            var c=typeof a==="string"?d(a,b):d.makeArray(a),e=d.merge(this.get(),c);
            return this.pushStack(N(c[0])||N(e[0])?e:d.unique(e))
        },
        andSelf:function(){
            return this.add(this.prevObject)
        }
    }),d.each({
        parent:function(a){
            var b=a.parentNode;
            return b&&b.nodeType!==11?b:null
        },
        parents:function(a){
            return d.dir(a,"parentNode")
        },
        parentsUntil:function(a,b,c){
            return d.dir(a,"parentNode",c)
        },
        next:function(a){
            return d.nth(a,2,"nextSibling")
        },
        prev:function(a){
            return d.nth(a,2,"previousSibling")
        },
        nextAll:function(a){
            return d.dir(a,"nextSibling")
        },
        prevAll:function(a){
            return d.dir(a,"previousSibling")
        },
        nextUntil:function(a,b,c){
            return d.dir(a,"nextSibling",c)
        },
        prevUntil:function(a,b,c){
            return d.dir(a,"previousSibling",c)
        },
        siblings:function(a){
            return d.sibling(a.parentNode.firstChild,a)
        },
        children:function(a){
            return d.sibling(a.firstChild)
        },
        contents:function(a){
            return d.nodeName(a,"iframe")?a.contentDocument||a.contentWindow.document:d.makeArray(a.childNodes)
        }
    },function(a,b){
        d.fn[a]=function(c,e){
            var f=d.map(this,b,c),g=K.call(arguments);
            G.test(a)||(e=c),e&&typeof e==="string"&&(f=d.filter(e,f)),f=this.length>1&&!M[a]?d.unique(f):f,(this.length>1||I.test(e))&&H.test(a)&&(f=f.reverse());
            return this.pushStack(f,a,g.join(","))
        }
    }),d.extend({
        filter:function(a,b,c){
            c&&(a=":not("+a+")");
            return b.length===1?d.find.matchesSelector(b[0],a)?[b[0]]:[]:d.find.matches(a,b)
        },
        dir:function(a,c,e){
            var f=[],g=a[c];
            while(g&&g.nodeType!==9&&(e===b||g.nodeType!==1||!d(g).is(e)))g.nodeType===1&&f.push(g),g=g[c];
            return f
        },
        nth:function(a,b,c,d){
            b=b||1;
            var e=0;
            for(;a;a=a[c])if(a.nodeType===1&&++e===b)break;return a
        },
        sibling:function(a,b){
            var c=[];
            for(;a;a=a.nextSibling)a.nodeType===1&&a!==b&&c.push(a);
            return c
        }
    });
    var P=/ jQuery\d+="(?:\d+|null)"/g,Q=/^\s+/,R=/<(?!area|br|col|embed|hr|img|input|link|meta|param)(([\w:]+)[^>]*)\/>/ig,S=/<([\w:]+)/,T=/<tbody/i,U=/<|&#?\w+;/,V=/<(?:script|object|embed|option|style)/i,W=/checked\s*(?:[^=]|=\s*.checked.)/i,X={
        option:[1,"<select multiple='multiple'>","</select>"],
        legend:[1,"<fieldset>","</fieldset>"],
        thead:[1,"<table>","</table>"],
        tr:[2,"<table><tbody>","</tbody></table>"],
        td:[3,"<table><tbody><tr>","</tr></tbody></table>"],
        col:[2,"<table><tbody></tbody><colgroup>","</colgroup></table>"],
        area:[1,"<map>","</map>"],
        _default:[0,"",""]
    };

    X.optgroup=X.option,X.tbody=X.tfoot=X.colgroup=X.caption=X.thead,X.th=X.td,d.support.htmlSerialize||(X._default=[1,"div<div>","</div>"]),d.fn.extend({
        text:function(a){
            if(d.isFunction(a))return this.each(function(b){
                var c=d(this);
                c.text(a.call(this,b,c.text()))
            });
            if(typeof a!=="object"&&a!==b)return this.empty().append((this[0]&&this[0].ownerDocument||c).createTextNode(a));
            return d.text(this)
        },
        wrapAll:function(a){
            if(d.isFunction(a))return this.each(function(b){
                d(this).wrapAll(a.call(this,b))
            });
            if(this[0]){
                var b=d(a,this[0].ownerDocument).eq(0).clone(!0);
                this[0].parentNode&&b.insertBefore(this[0]),b.map(function(){
                    var a=this;
                    while(a.firstChild&&a.firstChild.nodeType===1)a=a.firstChild;
                    return a
                }).append(this)
            }
            return this
        },
        wrapInner:function(a){
            if(d.isFunction(a))return this.each(function(b){
                d(this).wrapInner(a.call(this,b))
            });
            return this.each(function(){
                var b=d(this),c=b.contents();
                c.length?c.wrapAll(a):b.append(a)
            })
        },
        wrap:function(a){
            return this.each(function(){
                d(this).wrapAll(a)
            })
        },
        unwrap:function(){
            return this.parent().each(function(){
                d.nodeName(this,"body")||d(this).replaceWith(this.childNodes)
            }).end()
        },
        append:function(){
            return this.domManip(arguments,!0,function(a){
                this.nodeType===1&&this.appendChild(a)
            })
        },
        prepend:function(){
            return this.domManip(arguments,!0,function(a){
                this.nodeType===1&&this.insertBefore(a,this.firstChild)
            })
        },
        before:function(){
            if(this[0]&&this[0].parentNode)return this.domManip(arguments,!1,function(a){
                this.parentNode.insertBefore(a,this)
            });
            if(arguments.length){
                var a=d(arguments[0]);
                a.push.apply(a,this.toArray());
                return this.pushStack(a,"before",arguments)
            }
        },
        after:function(){
            if(this[0]&&this[0].parentNode)return this.domManip(arguments,!1,function(a){
                this.parentNode.insertBefore(a,this.nextSibling)
            });
            if(arguments.length){
                var a=this.pushStack(this,"after",arguments);
                a.push.apply(a,d(arguments[0]).toArray());
                return a
            }
        },
        remove:function(a,b){
            for(var c=0,e;(e=this[c])!=null;c++)if(!a||d.filter(a,[e]).length)!b&&e.nodeType===1&&(d.cleanData(e.getElementsByTagName("*")),d.cleanData([e])),e.parentNode&&e.parentNode.removeChild(e);return this
        },
        empty:function(){
            for(var a=0,b;(b=this[a])!=null;a++){
                b.nodeType===1&&d.cleanData(b.getElementsByTagName("*"));
                while(b.firstChild)b.removeChild(b.firstChild)
            }
            return this
        },
        clone:function(a,b){
            a=a==null?!1:a,b=b==null?a:b;
            return this.map(function(){
                return d.clone(this,a,b)
            })
        },
        html:function(a){
            if(a===b)return this[0]&&this[0].nodeType===1?this[0].innerHTML.replace(P,""):null;
            if(typeof a!=="string"||V.test(a)||!d.support.leadingWhitespace&&Q.test(a)||X[(S.exec(a)||["",""])[1].toLowerCase()])d.isFunction(a)?this.each(function(b){
                var c=d(this);
                c.html(a.call(this,b,c.html()))
            }):this.empty().append(a);
            else{
                a=a.replace(R,"<$1></$2>");
                try{
                    for(var c=0,e=this.length;c<e;c++)this[c].nodeType===1&&(d.cleanData(this[c].getElementsByTagName("*")),this[c].innerHTML=a)
                }catch(f){
                    this.empty().append(a)
                }
            }
            return this
        },
        replaceWith:function(a){
            if(this[0]&&this[0].parentNode){
                if(d.isFunction(a))return this.each(function(b){
                    var c=d(this),e=c.html();
                    c.replaceWith(a.call(this,b,e))
                });
                typeof a!=="string"&&(a=d(a).detach());
                return this.each(function(){
                    var b=this.nextSibling,c=this.parentNode;
                    d(this).remove(),b?d(b).before(a):d(c).append(a)
                })
            }
            return this.pushStack(d(d.isFunction(a)?a():a),"replaceWith",a)
        },
        detach:function(a){
            return this.remove(a,!0)
        },
        domManip:function(a,c,e){
            var f,g,h,i,j=a[0],k=[];
            if(!d.support.checkClone&&arguments.length===3&&typeof j==="string"&&W.test(j))return this.each(function(){
                d(this).domManip(a,c,e,!0)
            });
            if(d.isFunction(j))return this.each(function(f){
                var g=d(this);
                a[0]=j.call(this,f,c?g.html():b),g.domManip(a,c,e)
            });
            if(this[0]){
                i=j&&j.parentNode,d.support.parentNode&&i&&i.nodeType===11&&i.childNodes.length===this.length?f={
                    fragment:i
                }:f=d.buildFragment(a,this,k),h=f.fragment,h.childNodes.length===1?g=h=h.firstChild:g=h.firstChild;
                if(g){
                    c=c&&d.nodeName(g,"tr");
                    for(var l=0,m=this.length,n=m-1;l<m;l++)e.call(c?Y(this[l],g):this[l],f.cacheable||m>1&&l<n?d.clone(h,!0,!0):h)
                }
                k.length&&d.each(k,ba)
            }
            return this
        }
    }),d.buildFragment=function(a,b,e){
        var f,g,h,i=b&&b[0]?b[0].ownerDocument||b[0]:c;
        a.length===1&&typeof a[0]==="string"&&a[0].length<512&&i===c&&a[0].charAt(0)==="<"&&!V.test(a[0])&&(d.support.checkClone||!W.test(a[0]))&&(g=!0,h=d.fragments[a[0]],h&&(h!==1&&(f=h))),f||(f=i.createDocumentFragment(),d.clean(a,i,f,e)),g&&(d.fragments[a[0]]=h?f:1);
        return{
            fragment:f,
            cacheable:g
        }
    },d.fragments={},d.each({
        appendTo:"append",
        prependTo:"prepend",
        insertBefore:"before",
        insertAfter:"after",
        replaceAll:"replaceWith"
    },function(a,b){
        d.fn[a]=function(c){
            var e=[],f=d(c),g=this.length===1&&this[0].parentNode;
            if(g&&g.nodeType===11&&g.childNodes.length===1&&f.length===1){
                f[b](this[0]);
                return this
            }
            for(var h=0,i=f.length;h<i;h++){
                var j=(h>0?this.clone(!0):this).get();
                d(f[h])[b](j),e=e.concat(j)
            }
            return this.pushStack(e,a,f.selector)
        }
    }),d.extend({
        clone:function(a,b,c){
            var e=a.cloneNode(!0),f,g,h;
            if((!d.support.noCloneEvent||!d.support.noCloneChecked)&&(a.nodeType===1||a.nodeType===11)&&!d.isXMLDoc(a)){
                $(a,e),f=_(a),g=_(e);
                for(h=0;f[h];++h)$(f[h],g[h])
            }
            if(b){
                Z(a,e);
                if(c){
                    f=_(a),g=_(e);
                    for(h=0;f[h];++h)Z(f[h],g[h])
                }
            }
            return e
        },
        clean:function(a,b,e,f){
            b=b||c,typeof b.createElement==="undefined"&&(b=b.ownerDocument||b[0]&&b[0].ownerDocument||c);
            var g=[];
            for(var h=0,i;(i=a[h])!=null;h++){
                typeof i==="number"&&(i+="");
                if(!i)continue;
                if(typeof i!=="string"||U.test(i)){
                    if(typeof i==="string"){
                        i=i.replace(R,"<$1></$2>");
                        var j=(S.exec(i)||["",""])[1].toLowerCase(),k=X[j]||X._default,l=k[0],m=b.createElement("div");
                        m.innerHTML=k[1]+i+k[2];
                        while(l--)m=m.lastChild;
                        if(!d.support.tbody){
                            var n=T.test(i),o=j==="table"&&!n?m.firstChild&&m.firstChild.childNodes:k[1]==="<table>"&&!n?m.childNodes:[];
                            for(var p=o.length-1;p>=0;--p)d.nodeName(o[p],"tbody")&&!o[p].childNodes.length&&o[p].parentNode.removeChild(o[p])
                        }!d.support.leadingWhitespace&&Q.test(i)&&m.insertBefore(b.createTextNode(Q.exec(i)[0]),m.firstChild),i=m.childNodes
                    }
                }else i=b.createTextNode(i);
                i.nodeType?g.push(i):g=d.merge(g,i)
            }
            if(e)for(h=0;g[h];h++)!f||!d.nodeName(g[h],"script")||g[h].type&&g[h].type.toLowerCase()!=="text/javascript"?(g[h].nodeType===1&&g.splice.apply(g,[h+1,0].concat(d.makeArray(g[h].getElementsByTagName("script")))),e.appendChild(g[h])):f.push(g[h].parentNode?g[h].parentNode.removeChild(g[h]):g[h]);
            return g
        },
        cleanData:function(a){
            var b,c,e=d.cache,f=d.expando,g=d.event.special,h=d.support.deleteExpando;
            for(var i=0,j;(j=a[i])!=null;i++){
                if(j.nodeName&&d.noData[j.nodeName.toLowerCase()])continue;
                c=j[d.expando];
                if(c){
                    b=e[c]&&e[c][f];
                    if(b&&b.events){
                        for(var k in b.events)g[k]?d.event.remove(j,k):d.removeEvent(j,k,b.handle);b.handle&&(b.handle.elem=null)
                    }
                    h?delete j[d.expando]:j.removeAttribute&&j.removeAttribute(d.expando),delete e[c]
                }
            }
        }
    });
    var bb=/alpha\([^)]*\)/i,bc=/opacity=([^)]*)/,bd=/-([a-z])/ig,be=/([A-Z])/g,bf=/^-?\d+(?:px)?$/i,bg=/^-?\d/,bh={
        position:"absolute",
        visibility:"hidden",
        display:"block"
    },bi=["Left","Right"],bj=["Top","Bottom"],bk,bl,bm,bn=function(a,b){
        return b.toUpperCase()
    };

    d.fn.css=function(a,c){
        if(arguments.length===2&&c===b)return this;
        return d.access(this,a,c,!0,function(a,c,e){
            return e!==b?d.style(a,c,e):d.css(a,c)
        })
    },d.extend({
        cssHooks:{
            opacity:{
                get:function(a,b){
                    if(b){
                        var c=bk(a,"opacity","opacity");
                        return c===""?"1":c
                    }
                    return a.style.opacity
                }
            }
        },
        cssNumber:{
            zIndex:!0,
            fontWeight:!0,
            opacity:!0,
            zoom:!0,
            lineHeight:!0
        },
        cssProps:{
            "float":d.support.cssFloat?"cssFloat":"styleFloat"
        },
        style:function(a,c,e,f){
            if(a&&a.nodeType!==3&&a.nodeType!==8&&a.style){
                var g,h=d.camelCase(c),i=a.style,j=d.cssHooks[h];
                c=d.cssProps[h]||h;
                if(e===b){
                    if(j&&"get"in j&&(g=j.get(a,!1,f))!==b)return g;
                    return i[c]
                }
                if(typeof e==="number"&&isNaN(e)||e==null)return;
                typeof e==="number"&&!d.cssNumber[h]&&(e+="px");
                if(!j||!("set"in j)||(e=j.set(a,e))!==b)try{
                    i[c]=e
                }catch(k){}
            }
        },
        css:function(a,c,e){
            var f,g=d.camelCase(c),h=d.cssHooks[g];
            c=d.cssProps[g]||g;
            if(h&&"get"in h&&(f=h.get(a,!0,e))!==b)return f;
            if(bk)return bk(a,c,g)
        },
        swap:function(a,b,c){
            var d={};

            for(var e in b)d[e]=a.style[e],a.style[e]=b[e];c.call(a);
            for(e in b)a.style[e]=d[e]
        },
        camelCase:function(a){
            return a.replace(bd,bn)
        }
    }),d.curCSS=d.css,d.each(["height","width"],function(a,b){
        d.cssHooks[b]={
            get:function(a,c,e){
                var f;
                if(c){
                    a.offsetWidth!==0?f=bo(a,b,e):d.swap(a,bh,function(){
                        f=bo(a,b,e)
                    });
                    if(f<=0){
                        f=bk(a,b,b),f==="0px"&&bm&&(f=bm(a,b,b));
                        if(f!=null)return f===""||f==="auto"?"0px":f
                    }
                    if(f<0||f==null){
                        f=a.style[b];
                        return f===""||f==="auto"?"0px":f
                    }
                    return typeof f==="string"?f:f+"px"
                }
            },
            set:function(a,b){
                if(!bf.test(b))return b;
                b=parseFloat(b);
                if(b>=0)return b+"px"
            }
        }
    }),d.support.opacity||(d.cssHooks.opacity={
        get:function(a,b){
            return bc.test((b&&a.currentStyle?a.currentStyle.filter:a.style.filter)||"")?parseFloat(RegExp.$1)/100+"":b?"1":""
        },
        set:function(a,b){
            var c=a.style;
            c.zoom=1;
            var e=d.isNaN(b)?"":"alpha(opacity="+b*100+")",f=c.filter||"";
            c.filter=bb.test(f)?f.replace(bb,e):c.filter+" "+e
        }
    }),c.defaultView&&c.defaultView.getComputedStyle&&(bl=function(a,c,e){
        var f,g,h;
        e=e.replace(be,"-$1").toLowerCase();
        if(!(g=a.ownerDocument.defaultView))return b;
        if(h=g.getComputedStyle(a,null))f=h.getPropertyValue(e),f===""&&!d.contains(a.ownerDocument.documentElement,a)&&(f=d.style(a,e));
        return f
    }),c.documentElement.currentStyle&&(bm=function(a,b){
        var c,d=a.currentStyle&&a.currentStyle[b],e=a.runtimeStyle&&a.runtimeStyle[b],f=a.style;
        !bf.test(d)&&bg.test(d)&&(c=f.left,e&&(a.runtimeStyle.left=a.currentStyle.left),f.left=b==="fontSize"?"1em":d||0,d=f.pixelLeft+"px",f.left=c,e&&(a.runtimeStyle.left=e));
        return d===""?"auto":d
    }),bk=bl||bm,d.expr&&d.expr.filters&&(d.expr.filters.hidden=function(a){
        var b=a.offsetWidth,c=a.offsetHeight;
        return b===0&&c===0||!d.support.reliableHiddenOffsets&&(a.style.display||d.css(a,"display"))==="none"
    },d.expr.filters.visible=function(a){
        return!d.expr.filters.hidden(a)
    });
    var bp=/%20/g,bq=/\[\]$/,br=/\r?\n/g,bs=/#.*$/,bt=/^(.*?):[ \t]*([^\r\n]*)\r?$/mg,bu=/^(?:color|date|datetime|email|hidden|month|number|password|range|search|tel|text|time|url|week)$/i,bv=/(?:^file|^widget|\-extension):$/,bw=/^(?:GET|HEAD)$/,bx=/^\/\//,by=/\?/,bz=/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi,bA=/^(?:select|textarea)/i,bB=/\s+/,bC=/([?&])_=[^&]*/,bD=/(^|\-)([a-z])/g,bE=function(a,b,c){
        return b+c.toUpperCase()
    },bF=/^([\w\+\.\-]+:)\/\/([^\/?#:]*)(?::(\d+))?/,bG=d.fn.load,bH={},bI={},bJ,bK;
    try{
        bJ=c.location.href
    }catch(bL){
        bJ=c.createElement("a"),bJ.href="",bJ=bJ.href
    }
    bK=bF.exec(bJ.toLowerCase()),d.fn.extend({
        load:function(a,c,e){
            if(typeof a!=="string"&&bG)return bG.apply(this,arguments);
            if(!this.length)return this;
            var f=a.indexOf(" ");
            if(f>=0){
                var g=a.slice(f,a.length);
                a=a.slice(0,f)
            }
            var h="GET";
            c&&(d.isFunction(c)?(e=c,c=b):typeof c==="object"&&(c=d.param(c,d.ajaxSettings.traditional),h="POST"));
            var i=this;
            d.ajax({
                url:a,
                type:h,
                dataType:"html",
                data:c,
                complete:function(a,b,c){
                    c=a.responseText,a.isResolved()&&(a.done(function(a){
                        c=a
                    }),i.html(g?d("<div>").append(c.replace(bz,"")).find(g):c)),e&&i.each(e,[c,b,a])
                }
            });
            return this
        },
        serialize:function(){
            return d.param(this.serializeArray())
        },
        serializeArray:function(){
            return this.map(function(){
                return this.elements?d.makeArray(this.elements):this
            }).filter(function(){
                return this.name&&!this.disabled&&(this.checked||bA.test(this.nodeName)||bu.test(this.type))
            }).map(function(a,b){
                var c=d(this).val();
                return c==null?null:d.isArray(c)?d.map(c,function(a,c){
                    return{
                        name:b.name,
                        value:a.replace(br,"\r\n")
                    }
                }):{
                    name:b.name,
                    value:c.replace(br,"\r\n")
                }
            }).get()
        }
    }),d.each("ajaxStart ajaxStop ajaxComplete ajaxError ajaxSuccess ajaxSend".split(" "),function(a,b){
        d.fn[b]=function(a){
            return this.bind(b,a)
        }
    }),d.each(["get","post"],function(a,c){
        d[c]=function(a,e,f,g){
            d.isFunction(e)&&(g=g||f,f=e,e=b);
            return d.ajax({
                type:c,
                url:a,
                data:e,
                success:f,
                dataType:g
            })
        }
    }),d.extend({
        getScript:function(a,c){
            return d.get(a,b,c,"script")
        },
        getJSON:function(a,b,c){
            return d.get(a,b,c,"json")
        },
        ajaxSetup:function(a,b){
            b?d.extend(!0,a,d.ajaxSettings,b):(b=a,a=d.extend(!0,d.ajaxSettings,b));
            for(var c in {
                context:1,
                url:1
            })c in b?a[c]=b[c]:c in d.ajaxSettings&&(a[c]=d.ajaxSettings[c]);return a
        },
        ajaxSettings:{
            url:bJ,
            isLocal:bv.test(bK[1]),
            global:!0,
            type:"GET",
            contentType:"application/x-www-form-urlencoded",
            processData:!0,
            async:!0,
            accepts:{
                xml:"application/xml, text/xml",
                html:"text/html",
                text:"text/plain",
                json:"application/json, text/javascript",
                "*":"*/*"
            },
            contents:{
                xml:/xml/,
                html:/html/,
                json:/json/
            },
            responseFields:{
                xml:"responseXML",
                text:"responseText"
            },
            converters:{
                "* text":a.String,
                "text html":!0,
                "text json":d.parseJSON,
                "text xml":d.parseXML
            }
        },
        ajaxPrefilter:bM(bH),
        ajaxTransport:bM(bI),
        ajax:function(a,c){
            function v(a,c,l,n){
                if(r!==2){
                    r=2,p&&clearTimeout(p),o=b,m=n||"",u.readyState=a?4:0;
                    var q,t,v,w=l?bP(e,u,l):b,x,y;
                    if(a>=200&&a<300||a===304){
                        if(e.ifModified){
                            if(x=u.getResponseHeader("Last-Modified"))d.lastModified[k]=x;
                            if(y=u.getResponseHeader("Etag"))d.etag[k]=y
                        }
                        if(a===304)c="notmodified",q=!0;else try{
                            t=bQ(e,w),c="success",q=!0
                        }catch(z){
                            c="parsererror",v=z
                        }
                    }else{
                        v=c;
                        if(!c||a)c="error",a<0&&(a=0)
                    }
                    u.status=a,u.statusText=c,q?h.resolveWith(f,[t,c,u]):h.rejectWith(f,[u,c,v]),u.statusCode(j),j=b,s&&g.trigger("ajax"+(q?"Success":"Error"),[u,e,q?t:v]),i.resolveWith(f,[u,c]),s&&(g.trigger("ajaxComplete",[u,e]),--d.active||d.event.trigger("ajaxStop"))
                }
            }
            typeof a==="object"&&(c=a,a=b),c=c||{};

            var e=d.ajaxSetup({},c),f=e.context||e,g=f!==e&&(f.nodeType||f instanceof d)?d(f):d.event,h=d.Deferred(),i=d._Deferred(),j=e.statusCode||{},k,l={},m,n,o,p,q,r=0,s,t,u={
                readyState:0,
                setRequestHeader:function(a,b){
                    r||(l[a.toLowerCase().replace(bD,bE)]=b);
                    return this
                },
                getAllResponseHeaders:function(){
                    return r===2?m:null
                },
                getResponseHeader:function(a){
                    var c;
                    if(r===2){
                        if(!n){
                            n={};
                            while(c=bt.exec(m))n[c[1].toLowerCase()]=c[2]
                        }
                        c=n[a.toLowerCase()]
                    }
                    return c===b?null:c
                },
                overrideMimeType:function(a){
                    r||(e.mimeType=a);
                    return this
                },
                abort:function(a){
                    a=a||"abort",o&&o.abort(a),v(0,a);
                    return this
                }
            };

            h.promise(u),u.success=u.done,u.error=u.fail,u.complete=i.done,u.statusCode=function(a){
                if(a){
                    var b;
                    if(r<2)for(b in a)j[b]=[j[b],a[b]];else b=a[u.status],u.then(b,b)
                }
                return this
            },e.url=((a||e.url)+"").replace(bs,"").replace(bx,bK[1]+"//"),e.dataTypes=d.trim(e.dataType||"*").toLowerCase().split(bB),e.crossDomain||(q=bF.exec(e.url.toLowerCase()),e.crossDomain=q&&(q[1]!=bK[1]||q[2]!=bK[2]||(q[3]||(q[1]==="http:"?80:443))!=(bK[3]||(bK[1]==="http:"?80:443)))),e.data&&e.processData&&typeof e.data!=="string"&&(e.data=d.param(e.data,e.traditional)),bN(bH,e,c,u);
            if(r===2)return!1;
            s=e.global,e.type=e.type.toUpperCase(),e.hasContent=!bw.test(e.type),s&&d.active++===0&&d.event.trigger("ajaxStart");
            if(!e.hasContent){
                e.data&&(e.url+=(by.test(e.url)?"&":"?")+e.data),k=e.url;
                if(e.cache===!1){
                    var w=d.now(),x=e.url.replace(bC,"$1_="+w);
                    e.url=x+(x===e.url?(by.test(e.url)?"&":"?")+"_="+w:"")
                }
            }
            if(e.data&&e.hasContent&&e.contentType!==!1||c.contentType)l["Content-Type"]=e.contentType;
            e.ifModified&&(k=k||e.url,d.lastModified[k]&&(l["If-Modified-Since"]=d.lastModified[k]),d.etag[k]&&(l["If-None-Match"]=d.etag[k])),l.Accept=e.dataTypes[0]&&e.accepts[e.dataTypes[0]]?e.accepts[e.dataTypes[0]]+(e.dataTypes[0]!=="*"?", */*; q=0.01":""):e.accepts["*"];
            for(t in e.headers)u.setRequestHeader(t,e.headers[t]);if(e.beforeSend&&(e.beforeSend.call(f,u,e)===!1||r===2)){
                u.abort();
                return!1
            }
            for(t in {
                success:1,
                error:1,
                complete:1
            })u[t](e[t]);o=bN(bI,e,c,u);
            if(o){
                u.readyState=1,s&&g.trigger("ajaxSend",[u,e]),e.async&&e.timeout>0&&(p=setTimeout(function(){
                    u.abort("timeout")
                },e.timeout));
                try{
                    r=1,o.send(l,v)
                }catch(y){
                    status<2?v(-1,y):d.error(y)
                }
            }else v(-1,"No Transport");
            return u
        },
        param:function(a,c){
            var e=[],f=function(a,b){
                b=d.isFunction(b)?b():b,e[e.length]=encodeURIComponent(a)+"="+encodeURIComponent(b)
            };

            c===b&&(c=d.ajaxSettings.traditional);
            if(d.isArray(a)||a.jquery&&!d.isPlainObject(a))d.each(a,function(){
                f(this.name,this.value)
            });else for(var g in a)bO(g,a[g],c,f);return e.join("&").replace(bp,"+")
        }
    }),d.extend({
        active:0,
        lastModified:{},
        etag:{}
    });
    var bR=d.now(),bS=/(\=)\?(&|$)|()\?\?()/i;
    d.ajaxSetup({
        jsonp:"callback",
        jsonpCallback:function(){
            return d.expando+"_"+bR++
        }
    }),d.ajaxPrefilter("json jsonp",function(b,c,e){
        var f=typeof b.data==="string";
        if(b.dataTypes[0]==="jsonp"||c.jsonpCallback||c.jsonp!=null||b.jsonp!==!1&&(bS.test(b.url)||f&&bS.test(b.data))){
            var g,h=b.jsonpCallback=d.isFunction(b.jsonpCallback)?b.jsonpCallback():b.jsonpCallback,i=a[h],j=b.url,k=b.data,l="$1"+h+"$2",m=function(){
                a[h]=i,g&&d.isFunction(i)&&a[h](g[0])
            };

            b.jsonp!==!1&&(j=j.replace(bS,l),b.url===j&&(f&&(k=k.replace(bS,l)),b.data===k&&(j+=(/\?/.test(j)?"&":"?")+b.jsonp+"="+h))),b.url=j,b.data=k,a[h]=function(a){
                g=[a]
            },e.then(m,m),b.converters["script json"]=function(){
                g||d.error(h+" was not called");
                return g[0]
            },b.dataTypes[0]="json";
            return"script"
        }
    }),d.ajaxSetup({
        accepts:{
            script:"text/javascript, application/javascript, application/ecmascript, application/x-ecmascript"
        },
        contents:{
            script:/javascript|ecmascript/
        },
        converters:{
            "text script":function(a){
                d.globalEval(a);
                return a
            }
        }
    }),d.ajaxPrefilter("script",function(a){
        a.cache===b&&(a.cache=!1),a.crossDomain&&(a.type="GET",a.global=!1)
    }),d.ajaxTransport("script",function(a){
        if(a.crossDomain){
            var d,e=c.head||c.getElementsByTagName("head")[0]||c.documentElement;
            return{
                send:function(f,g){
                    d=c.createElement("script"),d.async="async",a.scriptCharset&&(d.charset=a.scriptCharset),d.src=a.url,d.onload=d.onreadystatechange=function(a,c){
                        if(!d.readyState||/loaded|complete/.test(d.readyState))d.onload=d.onreadystatechange=null,e&&d.parentNode&&e.removeChild(d),d=b,c||g(200,"success")
                    },e.insertBefore(d,e.firstChild)
                },
                abort:function(){
                    d&&d.onload(0,1)
                }
            }
        }
    });
    var bT=d.now(),bU,bV;
    d.ajaxSettings.xhr=a.ActiveXObject?function(){
        return!this.isLocal&&bX()||bY()
    }:bX,bV=d.ajaxSettings.xhr(),d.support.ajax=!!bV,d.support.cors=bV&&"withCredentials"in bV,bV=b,d.support.ajax&&d.ajaxTransport(function(a){
        if(!a.crossDomain||d.support.cors){
            var c;
            return{
                send:function(e,f){
                    var g=a.xhr(),h,i;
                    a.username?g.open(a.type,a.url,a.async,a.username,a.password):g.open(a.type,a.url,a.async);
                    if(a.xhrFields)for(i in a.xhrFields)g[i]=a.xhrFields[i];a.mimeType&&g.overrideMimeType&&g.overrideMimeType(a.mimeType),(!a.crossDomain||a.hasContent)&&!e["X-Requested-With"]&&(e["X-Requested-With"]="XMLHttpRequest");
                    try{
                        for(i in e)g.setRequestHeader(i,e[i])
                    }catch(j){}
                    g.send(a.hasContent&&a.data||null),c=function(e,i){
                        var j,k,l,m,n;
                        try{
                            if(c&&(i||g.readyState===4)){
                                c=b,h&&(g.onreadystatechange=d.noop,delete bU[h]);
                                if(i)g.readyState!==4&&g.abort();
                                else{
                                    j=g.status,l=g.getAllResponseHeaders(),m={},n=g.responseXML,n&&n.documentElement&&(m.xml=n),m.text=g.responseText;
                                    try{
                                        k=g.statusText
                                    }catch(o){
                                        k=""
                                    }
                                    j||!a.isLocal||a.crossDomain?j===1223&&(j=204):j=m.text?200:404
                                }
                            }
                        }catch(p){
                            i||f(-1,p)
                        }
                        m&&f(j,k,m,l)
                    },a.async&&g.readyState!==4?(bU||(bU={},bW()),h=bT++,g.onreadystatechange=bU[h]=c):c()
                },
                abort:function(){
                    c&&c(0,1)
                }
            }
        }
    });
    var bZ={},b$=/^(?:toggle|show|hide)$/,b_=/^([+\-]=)?([\d+.\-]+)([a-z%]*)$/i,ca,cb=[["height","marginTop","marginBottom","paddingTop","paddingBottom"],["width","marginLeft","marginRight","paddingLeft","paddingRight"],["opacity"]];
    d.fn.extend({
        show:function(a,b,c){
            var e,f;
            if(a||a===0)return this.animate(cc("show",3),a,b,c);
            for(var g=0,h=this.length;g<h;g++)e=this[g],f=e.style.display,!d._data(e,"olddisplay")&&f==="none"&&(f=e.style.display=""),f===""&&d.css(e,"display")==="none"&&d._data(e,"olddisplay",cd(e.nodeName));
            for(g=0;g<h;g++){
                e=this[g],f=e.style.display;
                if(f===""||f==="none")e.style.display=d._data(e,"olddisplay")||""
            }
            return this
        },
        hide:function(a,b,c){
            if(a||a===0)return this.animate(cc("hide",3),a,b,c);
            for(var e=0,f=this.length;e<f;e++){
                var g=d.css(this[e],"display");
                g!=="none"&&!d._data(this[e],"olddisplay")&&d._data(this[e],"olddisplay",g)
            }
            for(e=0;e<f;e++)this[e].style.display="none";
            return this
        },
        _toggle:d.fn.toggle,
        toggle:function(a,b,c){
            var e=typeof a==="boolean";
            d.isFunction(a)&&d.isFunction(b)?this._toggle.apply(this,arguments):a==null||e?this.each(function(){
                var b=e?a:d(this).is(":hidden");
                d(this)[b?"show":"hide"]()
            }):this.animate(cc("toggle",3),a,b,c);
            return this
        },
        fadeTo:function(a,b,c,d){
            return this.filter(":hidden").css("opacity",0).show().end().animate({
                opacity:b
            },a,c,d)
        },
        animate:function(a,b,c,e){
            var f=d.speed(b,c,e);
            if(d.isEmptyObject(a))return this.each(f.complete);
            return this[f.queue===!1?"each":"queue"](function(){
                var b=d.extend({},f),c,e=this.nodeType===1,g=e&&d(this).is(":hidden"),h=this;
                for(c in a){
                    var i=d.camelCase(c);
                    c!==i&&(a[i]=a[c],delete a[c],c=i);
                    if(a[c]==="hide"&&g||a[c]==="show"&&!g)return b.complete.call(this);
                    if(e&&(c==="height"||c==="width")){
                        b.overflow=[this.style.overflow,this.style.overflowX,this.style.overflowY];
                        if(d.css(this,"display")==="inline"&&d.css(this,"float")==="none")if(d.support.inlineBlockNeedsLayout){
                            var j=cd(this.nodeName);
                            j==="inline"?this.style.display="inline-block":(this.style.display="inline",this.style.zoom=1)
                        }else this.style.display="inline-block"
                    }
                    d.isArray(a[c])&&((b.specialEasing=b.specialEasing||{})[c]=a[c][1],a[c]=a[c][0])
                }
                b.overflow!=null&&(this.style.overflow="hidden"),b.curAnim=d.extend({},a),d.each(a,function(c,e){
                    var f=new d.fx(h,b,c);
                    if(b$.test(e))f[e==="toggle"?g?"show":"hide":e](a);
                    else{
                        var i=b_.exec(e),j=f.cur();
                        if(i){
                            var k=parseFloat(i[2]),l=i[3]||(d.cssNumber[c]?"":"px");
                            l!=="px"&&(d.style(h,c,(k||1)+l),j=(k||1)/f.cur()*j,d.style(h,c,j+l)),i[1]&&(k=(i[1]==="-="?-1:1)*k+j),f.custom(j,k,l)
                        }else f.custom(j,e,"")
                    }
                });
                return!0
            })
        },
        stop:function(a,b){
            var c=d.timers;
            a&&this.queue([]),this.each(function(){
                for(var a=c.length-1;a>=0;a--)c[a].elem===this&&(b&&c[a](!0),c.splice(a,1))
            }),b||this.dequeue();
            return this
        }
    }),d.each({
        slideDown:cc("show",1),
        slideUp:cc("hide",1),
        slideToggle:cc("toggle",1),
        fadeIn:{
            opacity:"show"
        },
        fadeOut:{
            opacity:"hide"
        },
        fadeToggle:{
            opacity:"toggle"
        }
    },function(a,b){
        d.fn[a]=function(a,c,d){
            return this.animate(b,a,c,d)
        }
    }),d.extend({
        speed:function(a,b,c){
            var e=a&&typeof a==="object"?d.extend({},a):{
                complete:c||!c&&b||d.isFunction(a)&&a,
                duration:a,
                easing:c&&b||b&&!d.isFunction(b)&&b
            };

            e.duration=d.fx.off?0:typeof e.duration==="number"?e.duration:e.duration in d.fx.speeds?d.fx.speeds[e.duration]:d.fx.speeds._default,e.old=e.complete,e.complete=function(){
                e.queue!==!1&&d(this).dequeue(),d.isFunction(e.old)&&e.old.call(this)
            };

            return e
        },
        easing:{
            linear:function(a,b,c,d){
                return c+d*a
            },
            swing:function(a,b,c,d){
                return(-Math.cos(a*Math.PI)/2+.5)*d+c
            }
        },
        timers:[],
        fx:function(a,b,c){
            this.options=b,this.elem=a,this.prop=c,b.orig||(b.orig={})
        }
    }),d.fx.prototype={
        update:function(){
            this.options.step&&this.options.step.call(this.elem,this.now,this),(d.fx.step[this.prop]||d.fx.step._default)(this)
        },
        cur:function(){
            if(this.elem[this.prop]!=null&&(!this.elem.style||this.elem.style[this.prop]==null))return this.elem[this.prop];
            var a,b=d.css(this.elem,this.prop);
            return isNaN(a=parseFloat(b))?!b||b==="auto"?0:b:a
        },
        custom:function(a,b,c){
            function g(a){
                return e.step(a)
            }
            var e=this,f=d.fx;
            this.startTime=d.now(),this.start=a,this.end=b,this.unit=c||this.unit||(d.cssNumber[this.prop]?"":"px"),this.now=this.start,this.pos=this.state=0,g.elem=this.elem,g()&&d.timers.push(g)&&!ca&&(ca=setInterval(f.tick,f.interval))
        },
        show:function(){
            this.options.orig[this.prop]=d.style(this.elem,this.prop),this.options.show=!0,this.custom(this.prop==="width"||this.prop==="height"?1:0,this.cur()),d(this.elem).show()
        },
        hide:function(){
            this.options.orig[this.prop]=d.style(this.elem,this.prop),this.options.hide=!0,this.custom(this.cur(),0)
        },
        step:function(a){
            var b=d.now(),c=!0;
            if(a||b>=this.options.duration+this.startTime){
                this.now=this.end,this.pos=this.state=1,this.update(),this.options.curAnim[this.prop]=!0;
                for(var e in this.options.curAnim)this.options.curAnim[e]!==!0&&(c=!1);if(c){
                    if(this.options.overflow!=null&&!d.support.shrinkWrapBlocks){
                        var f=this.elem,g=this.options;
                        d.each(["","X","Y"],function(a,b){
                            f.style["overflow"+b]=g.overflow[a]
                        })
                    }
                    this.options.hide&&d(this.elem).hide();
                    if(this.options.hide||this.options.show)for(var h in this.options.curAnim)d.style(this.elem,h,this.options.orig[h]);this.options.complete.call(this.elem)
                }
                return!1
            }
            var i=b-this.startTime;
            this.state=i/this.options.duration;
            var j=this.options.specialEasing&&this.options.specialEasing[this.prop],k=this.options.easing||(d.easing.swing?"swing":"linear");
            this.pos=d.easing[j||k](this.state,i,0,1,this.options.duration),this.now=this.start+(this.end-this.start)*this.pos,this.update();
            return!0
        }
    },d.extend(d.fx,{
        tick:function(){
            var a=d.timers;
            for(var b=0;b<a.length;b++)a[b]()||a.splice(b--,1);
            a.length||d.fx.stop()
        },
        interval:13,
        stop:function(){
            clearInterval(ca),ca=null
        },
        speeds:{
            slow:600,
            fast:200,
            _default:400
        },
        step:{
            opacity:function(a){
                d.style(a.elem,"opacity",a.now)
            },
            _default:function(a){
                a.elem.style&&a.elem.style[a.prop]!=null?a.elem.style[a.prop]=(a.prop==="width"||a.prop==="height"?Math.max(0,a.now):a.now)+a.unit:a.elem[a.prop]=a.now
            }
        }
    }),d.expr&&d.expr.filters&&(d.expr.filters.animated=function(a){
        return d.grep(d.timers,function(b){
            return a===b.elem
        }).length
    });
    var ce=/^t(?:able|d|h)$/i,cf=/^(?:body|html)$/i;
    "getBoundingClientRect"in c.documentElement?d.fn.offset=function(a){
        var b=this[0],c;
        if(a)return this.each(function(b){
            d.offset.setOffset(this,a,b)
        });
        if(!b||!b.ownerDocument)return null;
        if(b===b.ownerDocument.body)return d.offset.bodyOffset(b);
        try{
            c=b.getBoundingClientRect()
        }catch(e){}
        var f=b.ownerDocument,g=f.documentElement;
        if(!c||!d.contains(g,b))return c?{
            top:c.top,
            left:c.left
        }:{
            top:0,
            left:0
        };

        var h=f.body,i=cg(f),j=g.clientTop||h.clientTop||0,k=g.clientLeft||h.clientLeft||0,l=i.pageYOffset||d.support.boxModel&&g.scrollTop||h.scrollTop,m=i.pageXOffset||d.support.boxModel&&g.scrollLeft||h.scrollLeft,n=c.top+l-j,o=c.left+m-k;
        return{
            top:n,
            left:o
        }
    }:d.fn.offset=function(a){
        var b=this[0];
        if(a)return this.each(function(b){
            d.offset.setOffset(this,a,b)
        });
        if(!b||!b.ownerDocument)return null;
        if(b===b.ownerDocument.body)return d.offset.bodyOffset(b);
        d.offset.initialize();
        var c,e=b.offsetParent,f=b,g=b.ownerDocument,h=g.documentElement,i=g.body,j=g.defaultView,k=j?j.getComputedStyle(b,null):b.currentStyle,l=b.offsetTop,m=b.offsetLeft;
        while((b=b.parentNode)&&b!==i&&b!==h){
            if(d.offset.supportsFixedPosition&&k.position==="fixed")break;
            c=j?j.getComputedStyle(b,null):b.currentStyle,l-=b.scrollTop,m-=b.scrollLeft,b===e&&(l+=b.offsetTop,m+=b.offsetLeft,d.offset.doesNotAddBorder&&(!d.offset.doesAddBorderForTableAndCells||!ce.test(b.nodeName))&&(l+=parseFloat(c.borderTopWidth)||0,m+=parseFloat(c.borderLeftWidth)||0),f=e,e=b.offsetParent),d.offset.subtractsBorderForOverflowNotVisible&&c.overflow!=="visible"&&(l+=parseFloat(c.borderTopWidth)||0,m+=parseFloat(c.borderLeftWidth)||0),k=c
        }
        if(k.position==="relative"||k.position==="static")l+=i.offsetTop,m+=i.offsetLeft;
        d.offset.supportsFixedPosition&&k.position==="fixed"&&(l+=Math.max(h.scrollTop,i.scrollTop),m+=Math.max(h.scrollLeft,i.scrollLeft));
        return{
            top:l,
            left:m
        }
    },d.offset={
        initialize:function(){
            var a=c.body,b=c.createElement("div"),e,f,g,h,i=parseFloat(d.css(a,"marginTop"))||0,j="<div style='position:absolute;top:0;left:0;margin:0;border:5px solid #000;padding:0;width:1px;height:1px;'><div></div></div><table style='position:absolute;top:0;left:0;margin:0;border:5px solid #000;padding:0;width:1px;height:1px;' cellpadding='0' cellspacing='0'><tr><td></td></tr></table>";
            d.extend(b.style,{
                position:"absolute",
                top:0,
                left:0,
                margin:0,
                border:0,
                width:"1px",
                height:"1px",
                visibility:"hidden"
            }),b.innerHTML=j,a.insertBefore(b,a.firstChild),e=b.firstChild,f=e.firstChild,h=e.nextSibling.firstChild.firstChild,this.doesNotAddBorder=f.offsetTop!==5,this.doesAddBorderForTableAndCells=h.offsetTop===5,f.style.position="fixed",f.style.top="20px",this.supportsFixedPosition=f.offsetTop===20||f.offsetTop===15,f.style.position=f.style.top="",e.style.overflow="hidden",e.style.position="relative",this.subtractsBorderForOverflowNotVisible=f.offsetTop===-5,this.doesNotIncludeMarginInBodyOffset=a.offsetTop!==i,a.removeChild(b),a=b=e=f=g=h=null,d.offset.initialize=d.noop
        },
        bodyOffset:function(a){
            var b=a.offsetTop,c=a.offsetLeft;
            d.offset.initialize(),d.offset.doesNotIncludeMarginInBodyOffset&&(b+=parseFloat(d.css(a,"marginTop"))||0,c+=parseFloat(d.css(a,"marginLeft"))||0);
            return{
                top:b,
                left:c
            }
        },
        setOffset:function(a,b,c){
            var e=d.css(a,"position");
            e==="static"&&(a.style.position="relative");
            var f=d(a),g=f.offset(),h=d.css(a,"top"),i=d.css(a,"left"),j=e==="absolute"&&d.inArray("auto",[h,i])>-1,k={},l={},m,n;
            j&&(l=f.position()),m=j?l.top:parseInt(h,10)||0,n=j?l.left:parseInt(i,10)||0,d.isFunction(b)&&(b=b.call(a,c,g)),b.top!=null&&(k.top=b.top-g.top+m),b.left!=null&&(k.left=b.left-g.left+n),"using"in b?b.using.call(a,k):f.css(k)
        }
    },d.fn.extend({
        position:function(){
            if(!this[0])return null;
            var a=this[0],b=this.offsetParent(),c=this.offset(),e=cf.test(b[0].nodeName)?{
                top:0,
                left:0
            }:b.offset();
            c.top-=parseFloat(d.css(a,"marginTop"))||0,c.left-=parseFloat(d.css(a,"marginLeft"))||0,e.top+=parseFloat(d.css(b[0],"borderTopWidth"))||0,e.left+=parseFloat(d.css(b[0],"borderLeftWidth"))||0;
            return{
                top:c.top-e.top,
                left:c.left-e.left
            }
        },
        offsetParent:function(){
            return this.map(function(){
                var a=this.offsetParent||c.body;
                while(a&&(!cf.test(a.nodeName)&&d.css(a,"position")==="static"))a=a.offsetParent;
                return a
            })
        }
    }),d.each(["Left","Top"],function(a,c){
        var e="scroll"+c;
        d.fn[e]=function(c){
            var f=this[0],g;
            if(!f)return null;
            if(c!==b)return this.each(function(){
                g=cg(this),g?g.scrollTo(a?d(g).scrollLeft():c,a?c:d(g).scrollTop()):this[e]=c
            });
            g=cg(f);
            return g?"pageXOffset"in g?g[a?"pageYOffset":"pageXOffset"]:d.support.boxModel&&g.document.documentElement[e]||g.document.body[e]:f[e]
        }
    }),d.each(["Height","Width"],function(a,c){
        var e=c.toLowerCase();
        d.fn["inner"+c]=function(){
            return this[0]?parseFloat(d.css(this[0],e,"padding")):null
        },d.fn["outer"+c]=function(a){
            return this[0]?parseFloat(d.css(this[0],e,a?"margin":"border")):null
        },d.fn[e]=function(a){
            var f=this[0];
            if(!f)return a==null?null:this;
            if(d.isFunction(a))return this.each(function(b){
                var c=d(this);
                c[e](a.call(this,b,c[e]()))
            });
            if(d.isWindow(f)){
                var g=f.document.documentElement["client"+c];
                return f.document.compatMode==="CSS1Compat"&&g||f.document.body["client"+c]||g
            }
            if(f.nodeType===9)return Math.max(f.documentElement["client"+c],f.body["scroll"+c],f.documentElement["scroll"+c],f.body["offset"+c],f.documentElement["offset"+c]);
            if(a===b){
                var h=d.css(f,e),i=parseFloat(h);
                return d.isNaN(i)?h:i
            }
            return this.css(e,typeof a==="string"?a:a+"px")
        }
    }),a.jQuery=a.$=d
})(window);
/*!
 * jQuery Mobile v1.0b2
 * http://jquerymobile.com/
 *
 * Copyright 2010, jQuery Project
 * Dual licensed under the MIT or GPL Version 2 licenses.
 * http://jquery.org/license
 */
(function(a,d){
    if(a.cleanData){
        var c=a.cleanData;
        a.cleanData=function(f){
            for(var b=0,d;(d=f[b])!=null;b++)a(d).triggerHandler("remove");
            c(f)
        }
    }else{
        var b=a.fn.remove;
        a.fn.remove=function(f,c){
            return this.each(function(){
                c||(!f||a.filter(f,[this]).length)&&a("*",this).add([this]).each(function(){
                    a(this).triggerHandler("remove")
                });
                return b.call(a(this),f,c)
            })
        }
    }
    a.widget=function(b,c,d){
        var h=b.split(".")[0],i,b=b.split(".")[1];
        i=h+"-"+b;
        if(!d)d=c,c=a.Widget;
        a.expr[":"][i]=function(c){
            return!!a.data(c,
                b)
        };

        a[h]=a[h]||{};

        a[h][b]=function(a,b){
            arguments.length&&this._createWidget(a,b)
        };

        c=new c;
        c.options=a.extend(!0,{},c.options);
        a[h][b].prototype=a.extend(!0,c,{
            namespace:h,
            widgetName:b,
            widgetEventPrefix:a[h][b].prototype.widgetEventPrefix||b,
            widgetBaseClass:i
        },d);
        a.widget.bridge(b,a[h][b])
    };

    a.widget.bridge=function(b,c){
        a.fn[b]=function(g){
            var h=typeof g==="string",i=Array.prototype.slice.call(arguments,1),k=this,g=!h&&i.length?a.extend.apply(null,[!0,g].concat(i)):g;
            if(h&&g.charAt(0)==="_")return k;
            h?this.each(function(){
                var c=a.data(this,b);
                if(!c)throw"cannot call methods on "+b+" prior to initialization; attempted to call method '"+g+"'";
                if(!a.isFunction(c[g]))throw"no such method '"+g+"' for "+b+" widget instance";
                var e=c[g].apply(c,i);
                if(e!==c&&e!==d)return k=e,!1
            }):this.each(function(){
                var d=a.data(this,b);
                d?d.option(g||{})._init():a.data(this,b,new c(g,this))
            });
            return k
        }
    };

    a.Widget=function(a,b){
        arguments.length&&this._createWidget(a,b)
    };

    a.Widget.prototype={
        widgetName:"widget",
        widgetEventPrefix:"",
        options:{
            disabled:!1
        },
        _createWidget:function(b,c){
            a.data(c,this.widgetName,this);
            this.element=a(c);
            this.options=a.extend(!0,{},this.options,this._getCreateOptions(),b);
            var d=this;
            this.element.bind("remove."+this.widgetName,function(){
                d.destroy()
            });
            this._create();
            this._trigger("create");
            this._init()
        },
        _getCreateOptions:function(){
            var b={};

            a.metadata&&(b=a.metadata.get(element)[this.widgetName]);
            return b
        },
        _create:function(){},
        _init:function(){},
        destroy:function(){
            this.element.unbind("."+this.widgetName).removeData(this.widgetName);
            this.widget().unbind("."+this.widgetName).removeAttr("aria-disabled").removeClass(this.widgetBaseClass+"-disabled ui-state-disabled")
        },
        widget:function(){
            return this.element
        },
        option:function(b,c){
            var g=b;
            if(arguments.length===0)return a.extend({},this.options);
            if(typeof b==="string"){
                if(c===d)return this.options[b];
                g={};

                g[b]=c
            }
            this._setOptions(g);
            return this
        },
        _setOptions:function(b){
            var c=this;
            a.each(b,function(a,b){
                c._setOption(a,b)
            });
            return this
        },
        _setOption:function(a,b){
            this.options[a]=b;
            a==="disabled"&&
            this.widget()[b?"addClass":"removeClass"](this.widgetBaseClass+"-disabled ui-state-disabled").attr("aria-disabled",b);
            return this
        },
        enable:function(){
            return this._setOption("disabled",!1)
        },
        disable:function(){
            return this._setOption("disabled",!0)
        },
        _trigger:function(b,c,d){
            var h=this.options[b],c=a.Event(c);
            c.type=(b===this.widgetEventPrefix?b:this.widgetEventPrefix+b).toLowerCase();
            d=d||{};

            if(c.originalEvent)for(var b=a.event.props.length,i;b;)i=a.event.props[--b],c[i]=c.originalEvent[i];
            this.element.trigger(c,
                d);
            return!(a.isFunction(h)&&h.call(this.element[0],c,d)===!1||c.isDefaultPrevented())
        }
    }
})(jQuery);
(function(a,d){
    a.widget("mobile.widget",{
        _getCreateOptions:function(){
            var c=this.element,b={};

            a.each(this.options,function(a){
                var e=c.jqmData(a.replace(/[A-Z]/g,function(a){
                    return"-"+a.toLowerCase()
                }));
                e!==d&&(b[a]=e)
            });
            return b
        }
    })
})(jQuery);
(function(a){
    a(window);
    var d=a("html");
    a.mobile.media=function(){
        var c={},b=a("<div id='jquery-mediatest'>"),f=a("<body>").append(b);
        return function(a){
            if(!(a in c)){
                var g=document.createElement("style"),h="@media "+a+" { #jquery-mediatest { position:absolute; } }";
                g.type="text/css";
                g.styleSheet?g.styleSheet.cssText=h:g.appendChild(document.createTextNode(h));
                d.prepend(f).prepend(g);
                c[a]=b.css("position")==="absolute";
                f.add(g).remove()
            }
            return c[a]
        }
    }()
})(jQuery);
(function(a,d){
    function c(a){
        var b=a.charAt(0).toUpperCase()+a.substr(1),a=(a+" "+e.join(b+" ")+b).split(" "),c;
        for(c in a)if(f[c]!==d)return!0
    }
    var b=a("<body>").prependTo("html"),f=b[0].style,e=["webkit","moz","o"],g="palmGetResource"in window,h=window.blackberry;
    a.mobile.browser={};

    a.mobile.browser.ie=function(){
        for(var a=3,b=document.createElement("div"),c=b.all||[];b.innerHTML="<\!--[if gt IE "+ ++a+"]><br><![endif]--\>",c[0];);
        return a>4?a:!a
    }();
    a.extend(a.support,{
        orientation:"orientation"in
        window,
        touch:"ontouchend"in document,
        cssTransitions:"WebKitTransitionEvent"in window,
        pushState:!!history.pushState,
        mediaquery:a.mobile.media("only all"),
        cssPseudoElement:!!c("content"),
        boxShadow:!!c("boxShadow")&&!h,
        scrollTop:("pageXOffset"in window||"scrollTop"in document.documentElement||"scrollTop"in b[0])&&!g,
        dynamicBaseTag:function(){
            var c=location.protocol+"//"+location.host+location.pathname+"ui-dir/",f=a("head base"),d=null,e="",g;
            f.length?e=f.attr("href"):f=d=a("<base>",{
                href:c
            }).appendTo("head");
            g=a("<a href='testurl'></a>").prependTo(b)[0].href;
            f[0].href=e?e:location.pathname;
            d&&d.remove();
            return g.indexOf(c)===0
        }(),
        eventCapture:"addEventListener"in document
    });
    b.remove();
    g=function(){
        var a=window.navigator.userAgent;
        return a.indexOf("Nokia")>-1&&(a.indexOf("Symbian/3")>-1||a.indexOf("Series60/5")>-1)&&a.indexOf("AppleWebKit")>-1&&a.match(/(BrowserNG|NokiaBrowser)\/7\.[0-3]/)
    }();
    a.mobile.ajaxBlacklist=window.blackberry&&!window.WebKitPoint||window.operamini&&Object.prototype.toString.call(window.operamini)===
    "[object OperaMini]"||g;
    g&&a(function(){
        a("head link[rel=stylesheet]").attr("rel","alternate stylesheet").attr("rel","stylesheet")
    });
    a.support.boxShadow||a("html").addClass("ui-mobile-nosupport-boxshadow")
})(jQuery);
(function(a,d,c,b){
    function f(a){
        for(;a&&typeof a.originalEvent!=="undefined";)a=a.originalEvent;
        return a
    }
    function e(b){
        for(var c={},f,d;b;){
            f=a.data(b,n);
            for(d in f)if(f[d])c[d]=c.hasVirtualBinding=!0;b=b.parentNode
        }
        return c
    }
    function g(){
        v&&(clearTimeout(v),v=0);
        v=setTimeout(function(){
            B=v=0;
            A.length=0;
            w=!1;
            r=!0
        },a.vmouse.resetTimerDuration)
    }
    function h(c,d,r){
        var e=!1,g;
        if(!(g=r&&r[c])){
            if(r=!r)a:{
                for(r=d.target;r;){
                    if((g=a.data(r,n))&&(!c||g[c]))break a;
                    r=r.parentNode
                }
                r=null
            }
            g=r
        }
        if(g){
            var e=d,r=
            e.type,j,h,e=a.Event(e);
            e.type=c;
            g=e.originalEvent;
            j=a.event.props;
            if(g)for(h=j.length;h;)c=j[--h],e[c]=g[c];
            if(r.search(/^touch/)!==-1&&(c=f(g),r=c.touches,c=c.changedTouches,r=r&&r.length?r[0]:c&&c.length?c[0]:b)){
                g=0;
                for(len=z.length;g<len;g++)c=z[g],e[c]=r[c]
            }
            a(d.target).trigger(e);
            e=e.isDefaultPrevented()
        }
        return e
    }
    function i(b){
        var c=a.data(b.target,x);
        !w&&(!B||B!==c)&&h("v"+b.type,b)
    }
    function k(b){
        var c=f(b).touches,d;
        if(c&&c.length===1&&(d=b.target,c=e(d),c.hasVirtualBinding))B=F++,a.data(d,
            x,B),v&&(clearTimeout(v),v=0),s=r=!1,d=f(b).touches[0],y=d.pageX,u=d.pageY,h("vmouseover",b,c),h("vmousedown",b,c)
    }
    function l(a){
        r||(s||h("vmousecancel",a,e(a.target)),s=!0,g())
    }
    function m(b){
        if(!r){
            var c=f(b).touches[0],d=s,j=a.vmouse.moveDistanceThreshold;
            s=s||Math.abs(c.pageX-y)>j||Math.abs(c.pageY-u)>j;
            flags=e(b.target);
            s&&!d&&h("vmousecancel",b,flags);
            h("vmousemove",b,flags);
            g()
        }
    }
    function p(a){
        if(!r){
            r=!0;
            var b=e(a.target),c;
            h("vmouseup",a,b);
            !s&&h("vclick",a,b)&&(c=f(a).changedTouches[0],A.push({
                touchID:B,
                x:c.clientX,
                y:c.clientY
            }),w=!0);
            h("vmouseout",a,b);
            s=!1;
            g()
        }
    }
    function o(b){
        var b=a.data(b,n),c;
        if(b)for(c in b)if(b[c])return!0;return!1
    }
    function j(){}
    function q(b){
        var c=b.substr(1);
        return{
            setup:function(){
                o(this)||a.data(this,n,{});
                a.data(this,n)[b]=!0;
                t[b]=(t[b]||0)+1;
                t[b]===1&&C.bind(c,i);
                a(this).bind(c,j);
                if(E)t.touchstart=(t.touchstart||0)+1,t.touchstart===1&&C.bind("touchstart",k).bind("touchend",p).bind("touchmove",m).bind("scroll",l)
            },
            teardown:function(){
                --t[b];
                t[b]||C.unbind(c,i);
                E&&(--t.touchstart,
                    t.touchstart||C.unbind("touchstart",k).unbind("touchmove",m).unbind("touchend",p).unbind("scroll",l));
                var f=a(this),d=a.data(this,n);
                d&&(d[b]=!1);
                f.unbind(c,j);
                o(this)||f.removeData(n)
            }
        }
    }
    var n="virtualMouseBindings",x="virtualTouchID",d="vmouseover vmousedown vmousemove vmouseup vclick vmouseout vmousecancel".split(" "),z="clientX clientY pageX pageY screenX screenY".split(" "),t={},v=0,y=0,u=0,s=!1,A=[],w=!1,r=!1,E=a.support.eventCapture,C=a(c),F=1,B=0;
    a.vmouse={
        moveDistanceThreshold:10,
        clickDistanceThreshold:10,
        resetTimerDuration:1500
    };

    for(var D=0;D<d.length;D++)a.event.special[d[D]]=q(d[D]);
    E&&c.addEventListener("click",function(b){
        var c=A.length,f=b.target,d,r,e,g,j;
        if(c){
            d=b.clientX;
            r=b.clientY;
            threshold=a.vmouse.clickDistanceThreshold;
            for(e=f;e;){
                for(g=0;g<c;g++)if(j=A[g],e===f&&Math.abs(j.x-d)<threshold&&Math.abs(j.y-r)<threshold||a.data(e,x)===j.touchID){
                    b.preventDefault();
                    b.stopPropagation();
                    return
                }
                e=e.parentNode
            }
        }
    },!0)
})(jQuery,window,document);
(function(a,d,c){
    function b(b,c,f){
        var d=f.type;
        f.type=c;
        a.event.handle.call(b,f);
        f.type=d
    }
    a.each("touchstart touchmove touchend orientationchange throttledresize tap taphold swipe swipeleft swiperight scrollstart scrollstop".split(" "),function(b,c){
        a.fn[c]=function(a){
            return a?this.bind(c,a):this.trigger(c)
        };

        a.attrFn[c]=!0
    });
    var f=a.support.touch,e=f?"touchstart":"mousedown",g=f?"touchend":"mouseup",h=f?"touchmove":"mousemove";
    a.event.special.scrollstart={
        enabled:!0,
        setup:function(){
            function c(a,
                e){
                d=e;
                b(f,d?"scrollstart":"scrollstop",a)
            }
            var f=this,d,e;
            a(f).bind("touchmove scroll",function(b){
                a.event.special.scrollstart.enabled&&(d||c(b,!0),clearTimeout(e),e=setTimeout(function(){
                    c(b,!1)
                },50))
            })
        }
    };

    a.event.special.tap={
        setup:function(){
            var c=this,f=a(c);
            f.bind("vmousedown",function(a){
                function d(){
                    g=!1;
                    clearTimeout(h);
                    f.unbind("vclick",e).unbind("vmousecancel",d)
                }
                function e(a){
                    d();
                    j==a.target&&b(c,"tap",a)
                }
                if(a.which&&a.which!==1)return!1;
                var g=!0,j=a.target,h;
                f.bind("vmousecancel",d).bind("vclick",
                    e);
                h=setTimeout(function(){
                    g&&b(c,"taphold",a)
                },750)
            })
        }
    };

    a.event.special.swipe={
        scrollSupressionThreshold:10,
        durationThreshold:1E3,
        horizontalDistanceThreshold:30,
        verticalDistanceThreshold:75,
        setup:function(){
            var b=a(this);
            b.bind(e,function(f){
                function d(b){
                    if(p){
                        var c=b.originalEvent.touches?b.originalEvent.touches[0]:b;
                        o={
                            time:(new Date).getTime(),
                            coords:[c.pageX,c.pageY]
                        };

                        Math.abs(p.coords[0]-o.coords[0])>a.event.special.swipe.scrollSupressionThreshold&&b.preventDefault()
                    }
                }
                var e=f.originalEvent.touches?
                f.originalEvent.touches[0]:f,p={
                    time:(new Date).getTime(),
                    coords:[e.pageX,e.pageY],
                    origin:a(f.target)
                },o;
                b.bind(h,d).one(g,function(){
                    b.unbind(h,d);
                    p&&o&&o.time-p.time<a.event.special.swipe.durationThreshold&&Math.abs(p.coords[0]-o.coords[0])>a.event.special.swipe.horizontalDistanceThreshold&&Math.abs(p.coords[1]-o.coords[1])<a.event.special.swipe.verticalDistanceThreshold&&p.origin.trigger("swipe").trigger(p.coords[0]>o.coords[0]?"swipeleft":"swiperight");
                    p=o=c
                })
            })
        }
    };
    (function(a,b){
        function c(){
            var a=
            d();
            a!==e&&(e=a,f.trigger("orientationchange"))
        }
        var f=a(b),d,e;
        a.event.special.orientationchange={
            setup:function(){
                if(a.support.orientation)return!1;
                e=d();
                f.bind("throttledresize",c)
            },
            teardown:function(){
                if(a.support.orientation)return!1;
                f.unbind("throttledresize",c)
            },
            add:function(a){
                var b=a.handler;
                a.handler=function(a){
                    a.orientation=d();
                    return b.apply(this,arguments)
                }
            }
        };

        a.event.special.orientationchange.orientation=d=function(){
            var a=document.documentElement;
            return a&&a.clientWidth/a.clientHeight<
            1.1?"portrait":"landscape"
        }
    })(jQuery,d);
    (function(){
        a.event.special.throttledresize={
            setup:function(){
                a(this).bind("resize",b)
            },
            teardown:function(){
                a(this).unbind("resize",b)
            }
        };

        var b=function(){
            d=(new Date).getTime();
            e=d-c;
            e>=250?(c=d,a(this).trigger("throttledresize")):(f&&clearTimeout(f),f=setTimeout(b,250-e))
        },c=0,f,d,e
    })();
    a.each({
        scrollstop:"scrollstart",
        taphold:"tap",
        swipeleft:"swipe",
        swiperight:"swipe"
    },function(b,c){
        a.event.special[b]={
            setup:function(){
                a(this).bind(c,a.noop)
            }
        }
    })
})(jQuery,
    this);
(function(a,d,c){
    function b(a){
        a=a||location.href;
        return"#"+a.replace(/^[^#]*#?(.*)$/,"$1")
    }
    var f="hashchange",e=document,g,h=a.event.special,i=e.documentMode,k="on"+f in d&&(i===c||i>7);
    a.fn[f]=function(a){
        return a?this.bind(f,a):this.trigger(f)
    };

    a.fn[f].delay=50;
    h[f]=a.extend(h[f],{
        setup:function(){
            if(k)return!1;
            a(g.start)
        },
        teardown:function(){
            if(k)return!1;
            a(g.stop)
        }
    });
    g=function(){
        function g(){
            var c=b(),e=n(o);
            if(c!==o)q(o=c,e),a(d).trigger(f);
            else if(e!==o)location.href=location.href.replace(/#.*/,"")+
                e;
            i=setTimeout(g,a.fn[f].delay)
        }
        var h={},i,o=b(),j=function(a){
            return a
        },q=j,n=j;
        h.start=function(){
            i||g()
        };

        h.stop=function(){
            i&&clearTimeout(i);
            i=c
        };

        a.browser.msie&&!k&&function(){
            var c,d;
            h.start=function(){
                if(!c)d=(d=a.fn[f].src)&&d+b(),c=a('<iframe tabindex="-1" title="empty"/>').hide().one("load",function(){
                    d||q(b());
                    g()
                    }).attr("src",d||"javascript:0").insertAfter("body")[0].contentWindow,e.onpropertychange=function(){
                    try{
                        if(event.propertyName==="title")c.document.title=e.title
                    }catch(a){}
                }
            };

            h.stop=
            j;
            n=function(){
                return b(c.location.href)
            };

            q=function(b,d){
                var g=c.document,h=a.fn[f].domain;
                if(b!==d)g.title=e.title,g.open(),h&&g.write('<script>document.domain="'+h+'"<\/script>'),g.close(),c.location.hash=b
            }
        }();
        return h
    }()
})(jQuery,this);
(function(a){
    a.widget("mobile.page",a.mobile.widget,{
        options:{
            theme:"c",
            domCache:!1
        },
        _create:function(){
            var a=this.element,c=this.options;
            this._trigger("beforeCreate")!==!1&&a.addClass("ui-page ui-body-"+c.theme)
        }
    })
})(jQuery);
(function(a,d){
    a.extend(a.mobile,{
        ns:"",
        subPageUrlKey:"ui-page",
        activePageClass:"ui-page-active",
        activeBtnClass:"ui-btn-active",
        ajaxEnabled:!0,
        hashListeningEnabled:!0,
        defaultPageTransition:"slide",
        minScrollBack:screen.height/2,
        defaultDialogTransition:"pop",
        loadingMessage:"loading",
        pageLoadErrorMessage:"Error Loading Page",
        autoInitializePage:!0,
        gradeA:function(){
            return a.support.mediaquery||a.mobile.browser.ie&&a.mobile.browser.ie>=7
        },
        keyCode:{
            ALT:18,
            BACKSPACE:8,
            CAPS_LOCK:20,
            COMMA:188,
            COMMAND:91,
            COMMAND_LEFT:91,
            COMMAND_RIGHT:93,
            CONTROL:17,
            DELETE:46,
            DOWN:40,
            END:35,
            ENTER:13,
            ESCAPE:27,
            HOME:36,
            INSERT:45,
            LEFT:37,
            MENU:93,
            NUMPAD_ADD:107,
            NUMPAD_DECIMAL:110,
            NUMPAD_DIVIDE:111,
            NUMPAD_ENTER:108,
            NUMPAD_MULTIPLY:106,
            NUMPAD_SUBTRACT:109,
            PAGE_DOWN:34,
            PAGE_UP:33,
            PERIOD:190,
            RIGHT:39,
            SHIFT:16,
            SPACE:32,
            TAB:9,
            UP:38,
            WINDOWS:91
        },
        silentScroll:function(b){
            if(a.type(b)!=="number")b=a.mobile.defaultHomeScroll;
            a.event.special.scrollstart.enabled=!1;
            setTimeout(function(){
                d.scrollTo(0,b);
                a(document).trigger("silentscroll",{
                    x:0,
                    y:b
                })
            },
            20);
            setTimeout(function(){
                a.event.special.scrollstart.enabled=!0
            },150)
        },
        nsNormalize:function(b){
            if(b)return a.camelCase(a.mobile.ns+b)
        }
    });
    a.fn.jqmData=function(b,c){
        return this.data(b?a.mobile.nsNormalize(b):b,c)
    };

    a.jqmData=function(b,c,d){
        return a.data(b,a.mobile.nsNormalize(c),d)
    };

    a.fn.jqmRemoveData=function(b){
        return this.removeData(a.mobile.nsNormalize(b))
    };

    a.jqmRemoveData=function(b,c){
        return a.removeData(b,a.mobile.nsNormalize(c))
    };

    a.jqmHasData=function(b,c){
        return a.hasData(b,a.mobile.nsNormalize(c))
    };
    var c=a.find;
    a.find=function(b,d,e,g){
        b=b.replace(/:jqmData\(([^)]*)\)/g,"[data-"+(a.mobile.ns||"")+"$1]");
        return c.call(this,b,d,e,g)
    };

    a.extend(a.find,c);
    a.find.matches=function(b,c){
        return a.find(b,null,null,c)
    };

    a.find.matchesSelector=function(b,c){
        return a.find(c,null,null,[b]).length>0
    }
})(jQuery,this);
(function(a,d){
    function c(a){
        var b=a.jqmData("lastClicked");
        b&&b.length?b.focus():(b=a.find(".ui-title:eq(0)"),b.length?b.focus():a.find(x).eq(0).focus())
    }
    function b(b){
        q&&(!q.closest(".ui-page-active").length||b)&&q.removeClass(a.mobile.activeBtnClass);
        q=null
    }
    function f(){
        t=!1;
        z.length>0&&a.mobile.changePage.apply(null,z.pop())
    }
    function e(b,d,f,e){
        var h=a.support.scrollTop?m.scrollTop():!0,j=b.data("lastScroll")||a.mobile.defaultHomeScroll,i=g();
        h&&window.scrollTo(0,a.mobile.defaultHomeScroll);
        j<
        a.mobile.minScrollBack&&(j=0);
        d&&(d.height(i+h).jqmData("lastScroll",h).jqmData("lastClicked",q),d.data("page")._trigger("beforehide",null,{
            nextPage:b
        }));
        b.height(i+j).data("page")._trigger("beforeshow",null,{
            prevPage:d||a("")
        });
        a.mobile.hidePageLoadingMsg();
        f=(a.mobile.transitionHandlers[f||"none"]||a.mobile.defaultTransitionHandler)(f,e,b,d);
        f.done(function(){
            b.height("");
            j?(a.mobile.silentScroll(j),a(document).one("silentscroll",function(){
                c(b)
            })):c(b);
            d&&d.height("").data("page")._trigger("hide",
                null,{
                    nextPage:b
                });
            b.data("page")._trigger("show",null,{
                prevPage:d||a("")
            })
        });
        return f
    }
    function g(){
        var b=jQuery.event.special.orientationchange.orientation()==="portrait",c=b?screen.availHeight:screen.availWidth,b=Math.max(b?480:320,a(window).height());
        return Math.min(c,b)
    }
    function h(){
        a("."+a.mobile.activePageClass).css("min-height",g())
    }
    function i(b,c){
        c&&b.attr("data-"+a.mobile.ns+"role",c);
        b.page()
    }
    function k(a){
        for(;a;){
            if(a.nodeName.toLowerCase()=="a")break;
            a=a.parentNode
        }
        return a
    }
    function l(b){
        var b=
        a(b).closest(".ui-page").jqmData("url"),c=s.hrefNoHash;
        if(!b||!j.isPath(b))b=c;
        return j.makeUrlAbsolute(b,c)
    }
    var m=a(window),p=a("html"),o=a("head"),j={
        urlParseRE:/^(((([^:\/#\?]+:)?(?:\/\/((?:(([^:@\/#\?]+)(?:\:([^:@\/#\?]+))?)@)?(([^:\/#\?]+)(?:\:([0-9]+))?))?)?)?((\/?(?:[^\/\?#]+\/+)*)([^\?#]*)))?(\?[^#]+)?)(#.*)?/,
        parseUrl:function(b){
            if(a.type(b)==="object")return b;
            var b=j.urlParseRE.exec(b),c;
            b&&(c={
                href:b[0]||"",
                hrefNoHash:b[1]||"",
                hrefNoSearch:b[2]||"",
                domain:b[3]||"",
                protocol:b[4]||"",
                authority:b[5]||
                "",
                username:b[7]||"",
                password:b[8]||"",
                host:b[9]||"",
                hostname:b[10]||"",
                port:b[11]||"",
                pathname:b[12]||"",
                directory:b[13]||"",
                filename:b[14]||"",
                search:b[15]||"",
                hash:b[16]||""
            });
            return c||{}
        },
        makePathAbsolute:function(a,b){
            if(a&&a.charAt(0)==="/")return a;
            for(var a=a||"",c=(b=b?b.replace(/^\/|(\/[^\/]*|[^\/]+)$/g,""):"")?b.split("/"):[],d=a.split("/"),f=0;f<d.length;f++){
                var e=d[f];
                switch(e){
                    case ".":
                        break;
                    case "..":
                        c.length&&c.pop();
                        break;
                    default:
                        c.push(e)
                }
            }
            return"/"+c.join("/")
        },
        isSameDomain:function(a,
            b){
            return j.parseUrl(a).domain===j.parseUrl(b).domain
        },
        isRelativeUrl:function(a){
            return j.parseUrl(a).protocol===""
        },
        isAbsoluteUrl:function(a){
            return j.parseUrl(a).protocol!==""
        },
        makeUrlAbsolute:function(a,b){
            if(!j.isRelativeUrl(a))return a;
            var c=j.parseUrl(a),d=j.parseUrl(b),f=c.protocol||d.protocol,e=c.authority||d.authority,g=c.pathname!=="",h=j.makePathAbsolute(c.pathname||d.filename,d.pathname);
            return f+"//"+e+h+(c.search||!g&&d.search||"")+c.hash
        },
        addSearchParams:function(b,c){
            var d=j.parseUrl(b),
            f=typeof c==="object"?a.param(c):c,e=d.search||"?";
            return d.hrefNoSearch+e+(e.charAt(e.length-1)!=="?"?"&":"")+f+(d.hash||"")
        },
        convertUrlToDataUrl:function(a){
            var b=j.parseUrl(a);
            if(j.isEmbeddedPage(b))return b.hash.split(v)[0].replace(/^#/,"");
            else if(j.isSameDomain(b,s))return b.hrefNoHash.replace(s.domain,"");
            return a
        },
        get:function(a){
            if(a===d)a=location.hash;
            return j.stripHash(a).replace(/[^\/]*\.[^\/*]+$/,"")
        },
        getFilePath:function(b){
            var c="&"+a.mobile.subPageUrlKey;
            return b&&b.split(c)[0].split(v)[0]
        },
        set:function(a){
            location.hash=a
        },
        isPath:function(a){
            return/\//.test(a)},
        clean:function(a){
            return a.replace(s.domain,"")
            },
        stripHash:function(a){
            return a.replace(/^#/,"")
            },
        cleanHash:function(a){
            return j.stripHash(a.replace(/\?.*$/,"").replace(v,""))
            },
        isExternal:function(a){
            a=j.parseUrl(a);
            return a.protocol&&a.domain!==u.domain?!0:!1
            },
        hasProtocol:function(a){
            return/^(:?\w+:)/.test(a)
            },
        isEmbeddedPage:function(a){
            a=j.parseUrl(a);
            if(a.protocol!=="")return a.hash&&(a.hrefNoHash===u.hrefNoHash||A&&a.hrefNoHash===
                s.hrefNoHash);
            return/^#/.test(a.href)
        }
    },q=null,n={
        stack:[],
        activeIndex:0,
        getActive:function(){
            return n.stack[n.activeIndex]
        },
        getPrev:function(){
            return n.stack[n.activeIndex-1]
        },
        getNext:function(){
            return n.stack[n.activeIndex+1]
        },
        addNew:function(a,b,c,d){
            n.getNext()&&n.clearForward();
            n.stack.push({
                url:a,
                transition:b,
                title:c,
                pageUrl:d
            });
            n.activeIndex=n.stack.length-1
        },
        clearForward:function(){
            n.stack=n.stack.slice(0,n.activeIndex+1)
        },
        directHashChange:function(b){
            var c,f,e;
            a.each(n.stack,function(a,d){
                b.currentUrl===
                d.url&&(c=a<n.activeIndex,f=!c,e=a)
            });
            this.activeIndex=e!==d?e:this.activeIndex;
            c?b.isBack():f&&b.isForward()
        },
        ignoreNextHashChange:!1
    },x="[tabindex],a,button:visible,select:visible,input",z=[],t=!1,v="&ui-state=dialog",y=o.children("base"),u=j.parseUrl(location.href),s=y.length?j.parseUrl(j.makeUrlAbsolute(y.attr("href"),u.href)):u,A=u.hrefNoHash!==s.hrefNoHash,w=a.support.dynamicBaseTag?{
        element:y.length?y:a("<base>",{
            href:s.hrefNoHash
        }).prependTo(o),
        set:function(a){
            w.element.attr("href",j.makeUrlAbsolute(a,
                s))
        },
        reset:function(){
            w.element.attr("href",s.hrefNoHash)
        }
    }:d;
    a.fn.animationComplete=function(b){
        return a.support.cssTransitions?a(this).one("webkitAnimationEnd",b):(setTimeout(b,0),a(this))
    };

    a.mobile.updateHash=j.set;
    a.mobile.path=j;
    a.mobile.base=w;
    a.mobile.urlstack=n.stack;
    a.mobile.urlHistory=n;
    a.mobile.noneTransitionHandler=function(b,c,d,f){
        f&&f.removeClass(a.mobile.activePageClass);
        d.addClass(a.mobile.activePageClass);
        return a.Deferred().resolve(b,c,d,f).promise()
    };

    a.mobile.defaultTransitionHandler=
    a.mobile.noneTransitionHandler;
    a.mobile.transitionHandlers={
        none:a.mobile.defaultTransitionHandler
    };

    a.mobile.allowCrossDomainPages=!1;
    a.mobile.getDocumentUrl=function(b){
        return b?a.extend({},u):u.href
    };

    a.mobile.getDocumentBase=function(b){
        return b?a.extend({},s):s.href
    };

    a.mobile.loadPage=function(b,c){
        var f=a.Deferred(),e=a.extend({},a.mobile.loadPage.defaults,c),g=null,h=null,p=j.makeUrlAbsolute(b,a.mobile.activePage&&l(a.mobile.activePage)||s.hrefNoHash);
        if(e.data&&e.type==="get")p=j.addSearchParams(p,
            e.data),e.data=d;
        var n=j.getFilePath(p),o=j.convertUrlToDataUrl(p);
        e.pageContainer=e.pageContainer||a.mobile.pageContainer;
        g=e.pageContainer.children(":jqmData(url='"+o+"')");
        w&&w.reset();
        if(g.length){
            if(!e.reloadPage)return i(g,e.role),f.resolve(p,c,g),f.promise();
            h=g
        }
        if(e.showLoadMsg)var k=setTimeout(function(){
            a.mobile.showPageLoadingMsg()
        },e.loadMsgDelay);
        !a.mobile.allowCrossDomainPages&&!j.isSameDomain(u,p)?f.reject(p,c):a.ajax({
            url:n,
            type:e.type,
            data:e.data,
            dataType:"html",
            success:function(d){
                var l=
                a("<div></div>"),q=d.match(/<title[^>]*>([^<]*)/)&&RegExp.$1,m=RegExp("\\bdata-"+a.mobile.ns+"url=[\"']?([^\"'>]*)[\"']?");
                RegExp(".*(<[^>]+\\bdata-"+a.mobile.ns+"role=[\"']?page[\"']?[^>]*>).*").test(d)&&RegExp.$1&&m.test(RegExp.$1)&&RegExp.$1&&(b=n=j.getFilePath(RegExp.$1));
                w&&w.set(n);
                l.get(0).innerHTML=d;
                g=l.find(":jqmData(role='page'), :jqmData(role='dialog')").first();
                g.length||(g=a("<div data-"+a.mobile.ns+"role='page'>"+d.split(/<\/?body[^>]*>/gmi)[1]+"</div>"));
                q&&!g.jqmData("title")&&g.jqmData("title",
                    q);
                if(!a.support.dynamicBaseTag){
                    var s=j.get(n);
                    g.find("[src], link[href], a[rel='external'], :jqmData(ajax='false'), a[target]").each(function(){
                        var b=a(this).is("[href]")?"href":a(this).is("[src]")?"src":"action",c=a(this).attr(b),c=c.replace(location.protocol+"//"+location.host+location.pathname,"");
                        /^(\w+:|#|\/)/.test(c)||a(this).attr(b,s+c)
                    })
                }
                g.attr("data-"+a.mobile.ns+"url",j.convertUrlToDataUrl(n)).appendTo(e.pageContainer);
                g.one("pagecreate",function(){
                    g.data("page").options.domCache||g.bind("pagehide.remove",
                        function(){
                            a(this).remove()
                        })
                });
                i(g,e.role);
                p.indexOf("&"+a.mobile.subPageUrlKey)>-1&&(g=e.pageContainer.children(":jqmData(url='"+o+"')"));
                e.showLoadMsg&&(clearTimeout(k),a.mobile.hidePageLoadingMsg());
                f.resolve(p,c,g,h)
            },
            error:function(){
                w&&w.set(j.get());
                e.showLoadMsg&&(clearTimeout(k),a.mobile.hidePageLoadingMsg(),a("<div class='ui-loader ui-overlay-shadow ui-body-e ui-corner-all'><h1>"+a.mobile.pageLoadErrorMessage+"</h1></div>").css({
                    display:"block",
                    opacity:0.96,
                    top:m.scrollTop()+100
                }).appendTo(e.pageContainer).delay(800).fadeOut(400,
                    function(){
                        a(this).remove()
                    }));
                f.reject(p,c)
            }
        });
        return f.promise()
    };

    a.mobile.loadPage.defaults={
        type:"get",
        data:d,
        reloadPage:!1,
        role:d,
        showLoadMsg:!1,
        pageContainer:d,
        loadMsgDelay:50
    };

    a.mobile.changePage=function(c,g){
        if(typeof g!=="object"){
            var h=null;
            if(typeof c==="object"&&c.url&&c.type)h={
                type:c.type,
                data:c.data,
                forcePageLoad:!0
            },c=c.url;
            var o=arguments.length;
            if(o>1){
                var k=["transition","reverse","changeHash","fromHashChange"],l;
                for(l=1;l<o;l++){
                    var q=arguments[l];
                    typeof q!=="undefined"&&(h=h||

                    {},h[k[l-1]]=q)
                }
            }
            if(h)return a.mobile.changePage(c,h)
        }
        if(t)z.unshift(arguments);
        else{
            t=!0;
            var m=a.extend({},a.mobile.changePage.defaults,g);
            m.pageContainer=m.pageContainer||a.mobile.pageContainer;
            if(typeof c=="string")a.mobile.loadPage(c,m).done(function(b,c,d,f){
                t=!1;
                c.duplicateCachedPage=f;
                a.mobile.changePage(d,c)
            }).fail(function(){
                t=!1;
                b(!0);
                f();
                m.pageContainer.trigger("changepagefailed")
            });
            else{
                var s=m.pageContainer,h=a.mobile.activePage,k=o=c.jqmData("url");
                j.getFilePath(o);
                l=n.getActive();
                var q=
                n.activeIndex===0,y=0,u=document.title,x=m.role==="dialog"||c.jqmData("role")==="dialog";
                s.trigger("beforechangepage");
                if(h&&h[0]===c[0])t=!1,s.trigger("changepage");
                else{
                    i(c,m.role);
                    m.fromHashChange&&n.directHashChange({
                        currentUrl:o,
                        isBack:function(){
                            y=-1
                        },
                        isForward:function(){
                            y=1
                        }
                    });
                    try{
                        a(document.activeElement||"").add("input:focus, textarea:focus, select:focus").blur()
                    }catch(w){}
                    x&&l&&(o=l.url+v);
                    if(m.changeHash!==!1&&o)n.ignoreNextHashChange=!0,j.set(o);
                    var A=c.jqmData("title")||c.children(":jqmData(role='header')").find(".ui-title").text();
                    A&&u==document.title&&(u=A);
                    y||n.addNew(o,m.transition,u,k);
                    document.title=n.getActive().title;
                    a.mobile.activePage=c;
                    m.transition=m.transition||(y&&!q?l.transition:d)||(x?a.mobile.defaultDialogTransition:a.mobile.defaultPageTransition);
                    m.reverse=m.reverse||y<0;
                    e(c,h,m.transition,m.reverse).done(function(){
                        b();
                        m.duplicateCachedPage&&m.duplicateCachedPage.remove();
                        p.removeClass("ui-mobile-rendering");
                        f();
                        s.trigger("changepage")
                    })
                }
            }
        }
    };

    a.mobile.changePage.defaults={
        transition:d,
        reverse:!1,
        changeHash:!0,
        fromHashChange:!1,
        role:d,
        duplicateCachedPage:d,
        pageContainer:d,
        showLoadMsg:!0
    };

    a.mobile._registerInternalEvents=function(){
        a("form").live("submit",function(b){
            var c=a(this);
            if(a.mobile.ajaxEnabled&&!c.is(":jqmData(ajax='false')")){
                var d=c.attr("method"),f=c.attr("target"),e=c.attr("action");
                if(!e&&(e=l(c),e===s.hrefNoHash))e=u.hrefNoSearch;
                e=j.makeUrlAbsolute(e,l(c));
                !j.isExternal(e)&&!f&&(a.mobile.changePage(e,{
                    type:d&&d.length&&d.toLowerCase()||"get",
                    data:c.serialize(),
                    transition:c.jqmData("transition"),
                    direction:c.jqmData("direction"),
                    reloadPage:!0
                }),b.preventDefault())
            }
        });
        a(document).bind("vclick",function(b){
            if((b=k(b.target))&&j.parseUrl(b.getAttribute("href")||"#").hash!=="#")a(b).closest(".ui-btn").not(".ui-disabled").addClass(a.mobile.activeBtnClass),a("."+a.mobile.activePageClass+" .ui-btn").not(b).blur()
        });
        a(document).bind("click",function(c){
            var f=k(c.target);
            if(f){
                var e=a(f),g=function(){
                    window.setTimeout(function(){
                        b(!0)
                    },200)
                };

                if(e.is(":jqmData(rel='back')"))return window.history.back(),
                    !1;
                if(a.mobile.ajaxEnabled){
                    var h=l(e),f=j.makeUrlAbsolute(e.attr("href")||"#",h);
                    if(f.search("#")!=-1)if(f=f.replace(/[^#]*#/,""))f=j.isPath(f)?j.makeUrlAbsolute(f,h):j.makeUrlAbsolute("#"+f,u.hrefNoHash);
                        else{
                            c.preventDefault();
                            return
                        }
                    var h=e.is("[rel='external']")||e.is(":jqmData(ajax='false')")||e.is("[target]"),i=a.mobile.allowCrossDomainPages&&u.protocol==="file:"&&f.search(/^https?:/)!=-1,h=h||j.isExternal(f)&&!i;
                    q=e.closest(".ui-btn");
                    h?g():(g=e.jqmData("transition"),h=(h=e.jqmData("direction"))&&
                        h==="reverse"||e.jqmData("back"),e=e.attr("data-"+a.mobile.ns+"rel")||d,a.mobile.changePage(f,{
                            transition:g,
                            reverse:h,
                            role:e
                        }),c.preventDefault())
                }else g()
            }
        });
        a(".ui-page").live("pageshow.prefetch",function(){
            var b=[];
            a(this).find("a:jqmData(prefetch)").each(function(){
                var c=a(this).attr("href");
                c&&a.inArray(c,b)===-1&&(b.push(c),a.mobile.loadPage(c))
            })
        });
        m.bind("hashchange",function(){
            var b=j.stripHash(location.hash),c=a.mobile.urlHistory.stack.length===0?"none":d;
            if(!a.mobile.hashListeningEnabled||
                n.ignoreNextHashChange)n.ignoreNextHashChange=!1;
            else{
                if(n.stack.length>1&&b.indexOf(v)>-1)if(a.mobile.activePage.is(".ui-dialog")){
                    var f=function(){
                        b=a.mobile.urlHistory.getActive().pageUrl
                    };

                    n.directHashChange({
                        currentUrl:b,
                        isBack:f,
                        isForward:f
                    })
                }else{
                    n.directHashChange({
                        currentUrl:b,
                        isBack:function(){
                            window.history.back()
                        },
                        isForward:function(){
                            window.history.forward()
                        }
                    });
                    return
                }
                b?(b=typeof b==="string"&&!j.isPath(b)?"#"+b:b,a.mobile.changePage(b,{
                    transition:c,
                    changeHash:!1,
                    fromHashChange:!0
                })):
                a.mobile.changePage(a.mobile.firstPage,{
                    transition:c,
                    changeHash:!1,
                    fromHashChange:!0
                })
            }
        });
        a(document).bind("pageshow",h);
        a(window).bind("throttledresize",h)
    }
})(jQuery);
(function(a){
    function d(c,b,d,e){
        var g=new a.Deferred,h=b?" reverse":"",i="ui-mobile-viewport-transitioning viewport-"+c;
        d.animationComplete(function(){
            d.add(e).removeClass("out in reverse "+c);
            e&&e.removeClass(a.mobile.activePageClass);
            d.parent().removeClass(i);
            g.resolve(c,b,d,e)
        });
        d.parent().addClass(i);
        e&&e.addClass(c+" out"+h);
        d.addClass(a.mobile.activePageClass+" "+c+" in"+h);
        return g.promise()
    }
    a.mobile.css3TransitionHandler=d;
    if(a.mobile.defaultTransitionHandler===a.mobile.noneTransitionHandler)a.mobile.defaultTransitionHandler=
        d
})(jQuery,this);
(function(a){
    a.mobile.page.prototype.options.degradeInputs={
        color:!1,
        date:!1,
        datetime:!1,
        "datetime-local":!1,
        email:!1,
        month:!1,
        number:!1,
        range:"number",
        search:!0,
        tel:!1,
        time:!1,
        url:!1,
        week:!1
    };

    a.mobile.page.prototype.options.keepNative=":jqmData(role='none'), :jqmData(role='nojs')";
    a(document).bind("pagecreate enhance",function(d){
        var c=a(d.target).data("page").options;
        a(d.target).find("input").not(c.keepNative).each(function(){
            var b=a(this),d=this.getAttribute("type"),e=c.degradeInputs[d]||"text";
            c.degradeInputs[d]&&b.replaceWith(a("<div>").html(b.clone()).html().replace(/\s+type=["']?\w+['"]?/,' type="'+e+'" data-'+a.mobile.ns+'type="'+d+'" '))
        })
    })
})(jQuery);
(function(a,d){
    a.widget("mobile.dialog",a.mobile.widget,{
        options:{
            closeBtnText:"Close",
            theme:"a",
            initSelector:":jqmData(role='dialog')"
        },
        _create:function(){
            var c=this.element,b=c.attr("class").match(/ui-body-[a-z]/);
            b.length&&c.removeClass(b[0]);
            c.addClass("ui-body-"+this.options.theme);
            c.attr("role","dialog").addClass("ui-dialog").find(":jqmData(role='header')").addClass("ui-corner-top ui-overlay-shadow").prepend("<a href='#' data-"+a.mobile.ns+"icon='delete' data-"+a.mobile.ns+"rel='back' data-"+
                a.mobile.ns+"iconpos='notext'>"+this.options.closeBtnText+"</a>").end().find(":jqmData(role='content'),:jqmData(role='footer')").last().addClass("ui-corner-bottom ui-overlay-shadow");
            c.bind("vclick submit",function(b){
                var b=a(b.target).closest(b.type==="vclick"?"a":"form"),c;
                b.length&&!b.jqmData("transition")&&(c=a.mobile.urlHistory.getActive()||{},b.attr("data-"+a.mobile.ns+"transition",c.transition||a.mobile.defaultDialogTransition).attr("data-"+a.mobile.ns+"direction","reverse"))
            }).bind("pagehide",
                function(){
                    a(this).find("."+a.mobile.activeBtnClass).removeClass(a.mobile.activeBtnClass)
                })
        },
        close:function(){
            d.history.back()
        }
    });
    a(a.mobile.dialog.prototype.options.initSelector).live("pagecreate",function(){
        a(this).dialog()
    })
})(jQuery,this);
(function(a){
    a.mobile.page.prototype.options.backBtnText="Back";
    a.mobile.page.prototype.options.addBackBtn=!1;
    a.mobile.page.prototype.options.backBtnTheme=null;
    a.mobile.page.prototype.options.headerTheme="a";
    a.mobile.page.prototype.options.footerTheme="a";
    a.mobile.page.prototype.options.contentTheme=null;
    a(":jqmData(role='page'), :jqmData(role='dialog')").live("pagecreate",function(){
        var d=a(this).data("page").options,c=d.theme;
        a(":jqmData(role='header'), :jqmData(role='footer'), :jqmData(role='content')",
            this).each(function(){
            var b=a(this),f=b.jqmData("role"),e=b.jqmData("theme"),g,h,i;
            b.addClass("ui-"+f);
            if(f==="header"||f==="footer"){
                e=e||(f==="header"?d.headerTheme:d.footerTheme)||c;
                b.addClass("ui-bar-"+e);
                b.attr("role",f==="header"?"banner":"contentinfo");
                g=b.children("a");
                h=g.hasClass("ui-btn-left");
                i=g.hasClass("ui-btn-right");
                if(!h)h=g.eq(0).not(".ui-btn-right").addClass("ui-btn-left").length;
                i||g.eq(1).addClass("ui-btn-right");
                d.addBackBtn&&f==="header"&&a(".ui-page").length>1&&b.jqmData("url")!==
                a.mobile.path.stripHash(location.hash)&&!h&&(f=a("<a href='#' class='ui-btn-left' data-"+a.mobile.ns+"rel='back' data-"+a.mobile.ns+"icon='arrow-l'>"+d.backBtnText+"</a>").prependTo(b),f.attr("data-"+a.mobile.ns+"theme",d.backBtnTheme||e));
                b.children("h1, h2, h3, h4, h5, h6").addClass("ui-title").attr({
                    tabindex:"0",
                    role:"heading",
                    "aria-level":"1"
                })
            }else f==="content"&&(b.addClass("ui-body-"+(e||c||d.contentTheme)),b.attr("role","main"))
        })
    })
})(jQuery);
(function(a){
    a.widget("mobile.collapsible",a.mobile.widget,{
        options:{
            expandCueText:" click to expand contents",
            collapseCueText:" click to collapse contents",
            collapsed:!1,
            heading:">:header,>legend",
            theme:null,
            iconTheme:"d",
            initSelector:":jqmData(role='collapsible')"
        },
        _create:function(){
            var d=this.element,c=this.options,b=d.addClass("ui-collapsible-contain"),f=d.find(c.heading).eq(0),e=b.wrapInner("<div class='ui-collapsible-content'></div>").find(".ui-collapsible-content"),d=d.closest(":jqmData(role='collapsible-set')").addClass("ui-collapsible-set");
            f.is("legend")&&(f=a("<div role='heading'>"+f.html()+"</div>").insertBefore(f),f.next().remove());
            f.insertBefore(e).addClass("ui-collapsible-heading").append("<span class='ui-collapsible-heading-status'></span>").wrapInner("<a href='#' class='ui-collapsible-heading-toggle'></a>").find("a:eq(0)").buttonMarkup({
                shadow:!d.length,
                corners:!1,
                iconPos:"left",
                icon:"plus",
                theme:c.theme
            }).find(".ui-icon").removeAttr("class").buttonMarkup({
                shadow:!0,
                corners:!0,
                iconPos:"notext",
                icon:"plus",
                theme:c.iconTheme
            });
            d.length?b.jqmData("collapsible-last")&&f.find("a:eq(0), .ui-btn-inner").addClass("ui-corner-bottom"):f.find("a:eq(0)").addClass("ui-corner-all").find(".ui-btn-inner").addClass("ui-corner-all");
            b.bind("collapse",function(d){
                !d.isDefaultPrevented()&&a(d.target).closest(".ui-collapsible-contain").is(b)&&(d.preventDefault(),f.addClass("ui-collapsible-heading-collapsed").find(".ui-collapsible-heading-status").text(c.expandCueText).end().find(".ui-icon").removeClass("ui-icon-minus").addClass("ui-icon-plus"),
                    e.addClass("ui-collapsible-content-collapsed").attr("aria-hidden",!0),b.jqmData("collapsible-last")&&f.find("a:eq(0), .ui-btn-inner").addClass("ui-corner-bottom"))
            }).bind("expand",function(a){
                a.isDefaultPrevented()||(a.preventDefault(),f.removeClass("ui-collapsible-heading-collapsed").find(".ui-collapsible-heading-status").text(c.collapseCueText),f.find(".ui-icon").removeClass("ui-icon-plus").addClass("ui-icon-minus"),e.removeClass("ui-collapsible-content-collapsed").attr("aria-hidden",!1),b.jqmData("collapsible-last")&&
                    f.find("a:eq(0), .ui-btn-inner").removeClass("ui-corner-bottom"))
            }).trigger(c.collapsed?"collapse":"expand");
            d.length&&!d.jqmData("collapsiblebound")&&(d.jqmData("collapsiblebound",!0).bind("expand",function(b){
                a(b.target).closest(".ui-collapsible-contain").siblings(".ui-collapsible-contain").trigger("collapse")
            }),d=d.children(":jqmData(role='collapsible')"),d.first().find("a:eq(0)").addClass("ui-corner-top").find(".ui-btn-inner").addClass("ui-corner-top"),d.last().jqmData("collapsible-last",!0));
            f.bind("vclick",function(a){
                var c=f.is(".ui-collapsible-heading-collapsed")?"expand":"collapse";
                b.trigger(c);
                a.preventDefault()
            })
        }
    });
    a(document).bind("pagecreate create",function(d){
        a(a.mobile.collapsible.prototype.options.initSelector,d.target).collapsible()
    })
})(jQuery);
(function(a){
    a.fn.fieldcontain=function(){
        return this.addClass("ui-field-contain ui-body ui-br")
    };

    a(document).bind("pagecreate create",function(d){
        a(":jqmData(role='fieldcontain')",d.target).fieldcontain()
    })
})(jQuery);
(function(a){
    a.fn.grid=function(d){
        return this.each(function(){
            var c=a(this),b=a.extend({
                grid:null
            },d),f=c.children(),e={
                solo:1,
                a:2,
                b:3,
                c:4,
                d:5
            },b=b.grid;
            if(!b)if(f.length<=5)for(var g in e)e[g]===f.length&&(b=g);else b="a";
            e=e[b];
            c.addClass("ui-grid-"+b);
            f.filter(":nth-child("+e+"n+1)").addClass("ui-block-a");
            e>1&&f.filter(":nth-child("+e+"n+2)").addClass("ui-block-b");
            e>2&&f.filter(":nth-child(3n+3)").addClass("ui-block-c");
            e>3&&f.filter(":nth-child(4n+4)").addClass("ui-block-d");
            e>4&&f.filter(":nth-child(5n+5)").addClass("ui-block-e")
        })
    }
})(jQuery);
(function(a,d){
    a.widget("mobile.navbar",a.mobile.widget,{
        options:{
            iconpos:"top",
            grid:null,
            initSelector:":jqmData(role='navbar')"
        },
        _create:function(){
            var c=this.element,b=c.find("a"),f=b.filter(":jqmData(icon)").length?this.options.iconpos:d;
            c.addClass("ui-navbar").attr("role","navigation").find("ul").grid({
                grid:this.options.grid
            });
            f||c.addClass("ui-navbar-noicons");
            b.buttonMarkup({
                corners:!1,
                shadow:!1,
                iconpos:f
            });
            c.delegate("a","vclick",function(){
                b.not(".ui-state-persist").removeClass(a.mobile.activeBtnClass);
                a(this).addClass(a.mobile.activeBtnClass)
            })
        }
    });
    a(document).bind("pagecreate create",function(c){
        a(a.mobile.navbar.prototype.options.initSelector,c.target).navbar()
    })
})(jQuery);
(function(a){
    var d={};

    a.widget("mobile.listview",a.mobile.widget,{
        options:{
            theme:"c",
            countTheme:"c",
            headerTheme:"b",
            dividerTheme:"b",
            splitIcon:"arrow-r",
            splitTheme:"b",
            inset:!1,
            initSelector:":jqmData(role='listview')"
        },
        _create:function(){
            var a=this;
            a.element.addClass(function(b,d){
                return d+" ui-listview "+(a.options.inset?" ui-listview-inset ui-corner-all ui-shadow ":"")
            });
            a.refresh()
        },
        _itemApply:function(c,b){
            b.find(".ui-li-count").addClass("ui-btn-up-"+(c.jqmData("counttheme")||this.options.countTheme)+
                " ui-btn-corner-all").end().find("h1, h2, h3, h4, h5, h6").addClass("ui-li-heading").end().find("p, dl").addClass("ui-li-desc").end().find(">img:eq(0), .ui-link-inherit>img:eq(0)").addClass("ui-li-thumb").each(function(){
                b.addClass(a(this).is(".ui-li-icon")?"ui-li-has-icon":"ui-li-has-thumb")
            }).end().find(".ui-li-aside").each(function(){
                var b=a(this);
                b.prependTo(b.parent())
            })
        },
        _removeCorners:function(a,b){
            a=a.add(a.find(".ui-btn-inner, .ui-li-link-alt, .ui-li-thumb"));
            b==="top"?a.removeClass("ui-corner-top ui-corner-tr ui-corner-tl"):
            b==="bottom"?a.removeClass("ui-corner-bottom ui-corner-br ui-corner-bl"):a.removeClass("ui-corner-top ui-corner-tr ui-corner-tl ui-corner-bottom ui-corner-br ui-corner-bl")
        },
        refresh:function(c){
            this.parentPage=this.element.closest(".ui-page");
            this._createSubPages();
            var b=this.options,d=this.element,e=d.jqmData("dividertheme")||b.dividerTheme,g=d.jqmData("splittheme"),h=d.jqmData("spliticon"),i=d.children("li"),k=a.support.cssPseudoElement||!a.nodeName(d[0],"ol")?0:1,l,m,p,o,j;
            k&&d.find(".ui-li-dec").remove();
            for(var q=0,n=i.length;q<n;q++){
                l=i.eq(q);
                m="ui-li";
                if(c||!l.hasClass("ui-li"))p=l.jqmData("theme")||b.theme,o=l.children("a"),o.length?(j=l.jqmData("icon"),l.buttonMarkup({
                    wrapperEls:"div",
                    shadow:!1,
                    corners:!1,
                    iconpos:"right",
                    icon:o.length>1||j===!1?!1:j||"arrow-r",
                    theme:p
                }),o.first().addClass("ui-link-inherit"),o.length>1&&(m+=" ui-li-has-alt",o=o.last(),j=g||o.jqmData("theme")||b.splitTheme,o.appendTo(l).attr("title",o.text()).addClass("ui-li-link-alt").empty().buttonMarkup({
                    shadow:!1,
                    corners:!1,
                    theme:p,
                    icon:!1,
                    iconpos:!1
                }).find(".ui-btn-inner").append(a("<span />").buttonMarkup({
                    shadow:!0,
                    corners:!0,
                    theme:j,
                    iconpos:"notext",
                    icon:h||o.jqmData("icon")||b.splitIcon
                })))):l.jqmData("role")==="list-divider"?(m+=" ui-li-divider ui-btn ui-bar-"+e,l.attr("role","heading"),k&&(k=1)):m+=" ui-li-static ui-body-"+p;
                b.inset&&(q===0&&(m+=" ui-corner-top",l.add(l.find(".ui-btn-inner")).find(".ui-li-link-alt").addClass("ui-corner-tr").end().find(".ui-li-thumb").addClass("ui-corner-tl"),l.next().next().length&&
                    this._removeCorners(l.next())),q===i.length-1&&(m+=" ui-corner-bottom",l.add(l.find(".ui-btn-inner")).find(".ui-li-link-alt").addClass("ui-corner-br").end().find(".ui-li-thumb").addClass("ui-corner-bl"),l.prev().prev().length?this._removeCorners(l.prev()):l.prev().length&&this._removeCorners(l.prev(),"bottom")));
                k&&m.indexOf("ui-li-divider")<0&&(p=l.is(".ui-li-static:first")?l:l.find(".ui-link-inherit"),p.addClass("ui-li-jsnumbering").prepend("<span class='ui-li-dec'>"+k++ +". </span>"));
                l.add(l.children(".ui-btn-inner")).addClass(m);
                c||this._itemApply(d,l)
            }
        },
        _idStringEscape:function(a){
            return a.replace(/[^a-zA-Z0-9]/g,"-")
        },
        _createSubPages:function(){
            var c=this.element,b=c.closest(".ui-page"),f=b.jqmData("url"),e=f||b[0][a.expando],g=c.attr("id"),h=this.options,i="data-"+a.mobile.ns,k=this,l=b.find(":jqmData(role='footer')").jqmData("id"),m;
            typeof d[e]==="undefined"&&(d[e]=-1);
            g=g||++d[e];
            a(c.find("li>ul, li>ol").toArray().reverse()).each(function(b){
                var d=a(this),e=d.attr("id")||g+"-"+b,b=d.parent(),k=a(d.prevAll().toArray().reverse()),
                k=k.length?k:a("<span>"+a.trim(b.contents()[0].nodeValue)+"</span>"),n=k.first().text(),e=(f||"")+"&"+a.mobile.subPageUrlKey+"="+e,x=d.jqmData("theme")||h.theme,z=d.jqmData("counttheme")||c.jqmData("counttheme")||h.countTheme;
                m=!0;
                d.detach().wrap("<div "+i+"role='page' "+i+"url='"+e+"' "+i+"theme='"+x+"' "+i+"count-theme='"+z+"'><div "+i+"role='content'></div></div>").parent().before("<div "+i+"role='header' "+i+"theme='"+h.headerTheme+"'><div class='ui-title'>"+n+"</div></div>").after(l?a("<div "+
                    i+"role='footer' "+i+"id='"+l+"'>"):"").parent().appendTo(a.mobile.pageContainer).page();
                d=b.find("a:first");
                d.length||(d=a("<a/>").html(k||n).prependTo(b.empty()));
                d.attr("href","#"+e)
            }).listview();
            m&&b.data("page").options.domCache===!1&&b.unbind("pagehide.remove").bind("pagehide.remove",function(c,d){
                var e=d.nextPage;
                d.nextPage&&(e=e.jqmData("url"),e.indexOf(f+"&"+a.mobile.subPageUrlKey)!==0&&(k.childPages().remove(),b.remove()))
            })
        },
        childPages:function(){
            var c=this.parentPage.jqmData("url");
            return a(":jqmData(url^='"+
                c+"&"+a.mobile.subPageUrlKey+"')")
        }
    });
    a(document).bind("pagecreate create",function(c){
        a(a.mobile.listview.prototype.options.initSelector,c.target).listview()
    })
})(jQuery);
(function(a){
    a.mobile.listview.prototype.options.filter=!1;
    a.mobile.listview.prototype.options.filterPlaceholder="Filter items...";
    a.mobile.listview.prototype.options.filterTheme="c";
    a(":jqmData(role='listview')").live("listviewcreate",function(){
        var d=a(this),c=d.data("listview");
        if(c.options.filter){
            var b=a("<form>",{
                "class":"ui-listview-filter ui-bar-"+c.options.filterTheme,
                role:"search"
            });
            a("<input>",{
                placeholder:c.options.filterPlaceholder
            }).attr("data-"+a.mobile.ns+"type","search").jqmData("lastval",
                "").bind("keyup change",function(){
                var b=a(this),c=this.value.toLowerCase(),g=null,g=b.jqmData("lastval")+"",h=!1,i="";
                b.jqmData("lastval",c);
                change=c.replace(RegExp("^"+g),"");
                g=c.length<g.length||change.length!=c.length-g.length?d.children():d.children(":not(.ui-screen-hidden)");
                if(c){
                    for(var k=g.length-1;k>=0;k--)b=a(g[k]),i=b.jqmData("filtertext")||b.text(),b.is("li:jqmData(role=list-divider)")?(b.toggleClass("ui-filter-hidequeue",!h),h=!1):i.toLowerCase().indexOf(c)===-1?b.toggleClass("ui-filter-hidequeue",
                        !0):h=!0;
                    g.filter(":not(.ui-filter-hidequeue)").toggleClass("ui-screen-hidden",!1);
                    g.filter(".ui-filter-hidequeue").toggleClass("ui-screen-hidden",!0).toggleClass("ui-filter-hidequeue",!1)
                }else g.toggleClass("ui-screen-hidden",!1)
            }).appendTo(b).textinput();
            a(this).jqmData("inset")&&b.addClass("ui-listview-filter-inset");
            b.bind("submit",function(){
                return!1
            }).insertBefore(d)
        }
    })
})(jQuery);
(function(a){
    a(document).bind("pagecreate create",function(d){
        a(":jqmData(role='nojs')",d.target).addClass("ui-nojs")
    })
})(jQuery);
(function(a,d){
    a.widget("mobile.checkboxradio",a.mobile.widget,{
        options:{
            theme:null,
            initSelector:"input[type='checkbox'],input[type='radio']"
        },
        _create:function(){
            var c=this,b=this.element,f=b.closest("form,fieldset,:jqmData(role='page')").find("label").filter("[for='"+b[0].id+"']"),e=b.attr("type"),g=e+"-on",h=e+"-off",i=b.parents(":jqmData(type='horizontal')").length?d:h;
            if(!(e!=="checkbox"&&e!=="radio")){
                a.extend(this,{
                    label:f,
                    inputtype:e,
                    checkedClass:"ui-"+g+(i?"":" "+a.mobile.activeBtnClass),
                    uncheckedClass:"ui-"+h,
                    checkedicon:"ui-icon-"+g,
                    uncheckedicon:"ui-icon-"+h
                });
                if(!this.options.theme)this.options.theme=this.element.jqmData("theme");
                f.buttonMarkup({
                    theme:this.options.theme,
                    icon:i,
                    shadow:!1
                });
                b.add(f).wrapAll("<div class='ui-"+e+"'></div>");
                f.bind({
                    vmouseover:function(){
                        if(a(this).parent().is(".ui-disabled"))return!1
                    },
                    vclick:function(a){
                        if(b.is(":disabled"))a.preventDefault();else return c._cacheVals(),b.prop("checked",e==="radio"&&!0||!b.prop("checked")),c._getInputSet().not(b).prop("checked",
                            !1),c._updateAll(),!1
                    }
                });
                b.bind({
                    vmousedown:function(){
                        this._cacheVals()
                    },
                    vclick:function(){
                        var b=a(this);
                        b.is(":checked")?(b.prop("checked",!0),c._getInputSet().not(b).prop("checked",!1)):b.prop("checked",!1);
                        c._updateAll()
                    },
                    focus:function(){
                        f.addClass("ui-focus")
                    },
                    blur:function(){
                        f.removeClass("ui-focus")
                    }
                });
                this.refresh()
            }
        },
        _cacheVals:function(){
            this._getInputSet().each(function(){
                var c=a(this);
                c.jqmData("cacheVal",c.is(":checked"))
            })
        },
        _getInputSet:function(){
            if(this.inputtype=="checkbox")return this.element;
            return this.element.closest("form,fieldset,:jqmData(role='page')").find("input[name='"+this.element.attr("name")+"'][type='"+this.inputtype+"']")
        },
        _updateAll:function(){
            var c=this;
            this._getInputSet().each(function(){
                var b=a(this);
                (b.is(":checked")||c.inputtype==="checkbox")&&b.trigger("change")
            }).checkboxradio("refresh")
        },
        refresh:function(){
            var c=this.element,b=this.label,d=b.find(".ui-icon");
            a(c[0]).prop("checked")?(b.addClass(this.checkedClass).removeClass(this.uncheckedClass),d.addClass(this.checkedicon).removeClass(this.uncheckedicon)):
            (b.removeClass(this.checkedClass).addClass(this.uncheckedClass),d.removeClass(this.checkedicon).addClass(this.uncheckedicon));
            c.is(":disabled")?this.disable():this.enable()
        },
        disable:function(){
            this.element.prop("disabled",!0).parent().addClass("ui-disabled")
        },
        enable:function(){
            this.element.prop("disabled",!1).parent().removeClass("ui-disabled")
        }
    });
    a(document).bind("pagecreate create",function(c){
        a(a.mobile.checkboxradio.prototype.options.initSelector,c.target).not(":jqmData(role='none'), :jqmData(role='nojs')").checkboxradio()
    })
})(jQuery);
(function(a){
    a.widget("mobile.button",a.mobile.widget,{
        options:{
            theme:null,
            icon:null,
            iconpos:null,
            inline:null,
            corners:!0,
            shadow:!0,
            iconshadow:!0,
            initSelector:"button, [type='button'], [type='submit'], [type='reset'], [type='image']"
        },
        _create:function(){
            var d=this.element,c=this.options;
            this.button=a("<div></div>").text(d.text()||d.val()).buttonMarkup({
                theme:c.theme,
                icon:c.icon,
                iconpos:c.iconpos,
                inline:c.inline,
                corners:c.corners,
                shadow:c.shadow,
                iconshadow:c.iconshadow
            }).insertBefore(d).append(d.addClass("ui-btn-hidden"));
            c=d.attr("type");
            c!=="button"&&c!=="reset"&&d.bind("vclick",function(){
                var b=a("<input>",{
                    type:"hidden",
                    name:d.attr("name"),
                    value:d.attr("value")
                }).insertBefore(d);
                a(document).submit(function(){
                    b.remove()
                })
            });
            this.refresh()
        },
        enable:function(){
            this.element.attr("disabled",!1);
            this.button.removeClass("ui-disabled").attr("aria-disabled",!1);
            return this._setOption("disabled",!1)
        },
        disable:function(){
            this.element.attr("disabled",!0);
            this.button.addClass("ui-disabled").attr("aria-disabled",!0);
            return this._setOption("disabled",
                !0)
        },
        refresh:function(){
            this.element.attr("disabled")?this.disable():this.enable()
        }
    });
    a(document).bind("pagecreate create",function(d){
        a(a.mobile.button.prototype.options.initSelector,d.target).not(":jqmData(role='none'), :jqmData(role='nojs')").button()
    })
})(jQuery);
(function(a,d){
    a.widget("mobile.slider",a.mobile.widget,{
        options:{
            theme:null,
            trackTheme:null,
            disabled:!1,
            initSelector:"input[type='range'], :jqmData(type='range'), :jqmData(role='slider')"
        },
        _create:function(){
            var c=this,b=this.element,f=b.parents("[class*='ui-bar-'],[class*='ui-body-']").eq(0),f=f.length?f.attr("class").match(/ui-(bar|body)-([a-z])/)[2]:"c",e=this.options.theme?this.options.theme:f,g=this.options.trackTheme?this.options.trackTheme:f,h=b[0].nodeName.toLowerCase(),f=h=="select"?"ui-slider-switch":
            "",i=b.attr("id"),k=i+"-label",i=a("[for='"+i+"']").attr("id",k),l=function(){
                return h=="input"?parseFloat(b.val()):b[0].selectedIndex
            },m=h=="input"?parseFloat(b.attr("min")):0,p=h=="input"?parseFloat(b.attr("max")):b.find("option").length-1,o=window.parseFloat(b.attr("step")||1),j=a("<div class='ui-slider "+f+" ui-btn-down-"+g+" ui-btn-corner-all' role='application'></div>"),q=a("<a href='#' class='ui-slider-handle'></a>").appendTo(j).buttonMarkup({
                corners:!0,
                theme:e,
                shadow:!0
            }).attr({
                role:"slider",
                "aria-valuemin":m,
                "aria-valuemax":p,
                "aria-valuenow":l(),
                "aria-valuetext":l(),
                title:l(),
                "aria-labelledby":k
            });
            a.extend(this,{
                slider:j,
                handle:q,
                dragging:!1,
                beforeStart:null
            });
            h=="select"&&(j.wrapInner("<div class='ui-slider-inneroffset'></div>"),b.find("option"),b.find("option").each(function(b){
                var c=!b?"b":"a",d=!b?"right":"left",b=!b?" ui-btn-down-"+g:" ui-btn-active";
                a("<div class='ui-slider-labelbg ui-slider-labelbg-"+c+b+" ui-btn-corner-"+d+"'></div>").prependTo(j);
                a("<span class='ui-slider-label ui-slider-label-"+
                    c+b+" ui-btn-corner-"+d+"' role='img'>"+a(this).text()+"</span>").prependTo(q)
            }));
            i.addClass("ui-slider");
            b.addClass(h==="input"?"ui-slider-input":"ui-slider-switch").change(function(){
                c.refresh(l(),!0)
            }).keyup(function(){
                c.refresh(l(),!0,!0)
            }).blur(function(){
                c.refresh(l(),!0)
            });
            a(document).bind("vmousemove",function(a){
                if(c.dragging)return c.refresh(a),!1
            });
            j.bind("vmousedown",function(a){
                c.dragging=!0;
                if(h==="select")c.beforeStart=b[0].selectedIndex;
                c.refresh(a);
                return!1
            });
            j.add(document).bind("vmouseup",
                function(){
                    if(c.dragging){
                        c.dragging=!1;
                        if(h==="select"){
                            c.beforeStart===b[0].selectedIndex&&c.refresh(!c.beforeStart?1:0);
                            var a=l(),a=Math.round(a/(p-m)*100);
                            q.addClass("ui-slider-handle-snapping").css("left",a+"%").animationComplete(function(){
                                q.removeClass("ui-slider-handle-snapping")
                            })
                        }
                        return!1
                    }
                });
            j.insertAfter(b);
            this.handle.bind("vmousedown",function(){
                a(this).focus()
            }).bind("vclick",!1);
            this.handle.bind("keydown",function(b){
                var d=l();
                if(!c.options.disabled){
                    switch(b.keyCode){
                        case a.mobile.keyCode.HOME:case a.mobile.keyCode.END:case a.mobile.keyCode.PAGE_UP:case a.mobile.keyCode.PAGE_DOWN:case a.mobile.keyCode.UP:case a.mobile.keyCode.RIGHT:case a.mobile.keyCode.DOWN:case a.mobile.keyCode.LEFT:
                            if(b.preventDefault(),
                                !c._keySliding)c._keySliding=!0,a(this).addClass("ui-state-active")
                    }
                    switch(b.keyCode){
                        case a.mobile.keyCode.HOME:
                            c.refresh(m);
                            break;
                        case a.mobile.keyCode.END:
                            c.refresh(p);
                            break;
                        case a.mobile.keyCode.PAGE_UP:case a.mobile.keyCode.UP:case a.mobile.keyCode.RIGHT:
                            c.refresh(d+o);
                            break;
                        case a.mobile.keyCode.PAGE_DOWN:case a.mobile.keyCode.DOWN:case a.mobile.keyCode.LEFT:
                            c.refresh(d-o)
                    }
                }
            }).keyup(function(){
                if(c._keySliding)c._keySliding=!1,a(this).removeClass("ui-state-active")
            });
            this.refresh(d,d,!0)
        },
        refresh:function(a,
            b,d){
            if(!this.options.disabled){
                var e=this.element,g=e[0].nodeName.toLowerCase(),h=g==="input"?parseFloat(e.attr("min")):0,i=g==="input"?parseFloat(e.attr("max")):e.find("option").length-1;
                if(typeof a==="object"){
                    if(!this.dragging||a.pageX<this.slider.offset().left-8||a.pageX>this.slider.offset().left+this.slider.width()+8)return;
                    a=Math.round((a.pageX-this.slider.offset().left)/this.slider.width()*100)
                }else a==null&&(a=g==="input"?parseFloat(e.val()):e[0].selectedIndex),a=(parseFloat(a)-h)/(i-h)*
                    100;
                if(!isNaN(a)){
                    a<0&&(a=0);
                    a>100&&(a=100);
                    var k=Math.round(a/100*(i-h))+h;
                    k<h&&(k=h);
                    k>i&&(k=i);
                    this.handle.css("left",a+"%");
                    this.handle.attr({
                        "aria-valuenow":g==="input"?k:e.find("option").eq(k).attr("value"),
                        "aria-valuetext":g==="input"?k:e.find("option").eq(k).text(),
                        title:k
                    });
                    g==="select"&&(k===0?this.slider.addClass("ui-slider-switch-a").removeClass("ui-slider-switch-b"):this.slider.addClass("ui-slider-switch-b").removeClass("ui-slider-switch-a"));
                    if(!d)g==="input"?e.val(k):e[0].selectedIndex=
                        k,b||e.trigger("change")
                }
            }
        },
        enable:function(){
            this.element.attr("disabled",!1);
            this.slider.removeClass("ui-disabled").attr("aria-disabled",!1);
            return this._setOption("disabled",!1)
        },
        disable:function(){
            this.element.attr("disabled",!0);
            this.slider.addClass("ui-disabled").attr("aria-disabled",!0);
            return this._setOption("disabled",!0)
        }
    });
    a(document).bind("pagecreate create",function(c){
        a(a.mobile.slider.prototype.options.initSelector,c.target).not(":jqmData(role='none'), :jqmData(role='nojs')").slider()
    })
})(jQuery);
(function(a){
    a.widget("mobile.textinput",a.mobile.widget,{
        options:{
            theme:null,
            initSelector:"input[type='text'], input[type='search'], :jqmData(type='search'), input[type='number'], :jqmData(type='number'), input[type='password'], input[type='email'], input[type='url'], input[type='tel'], textarea"
        },
        _create:function(){
            var i;
            var d=this.element,c=this.options,b=c.theme,f,e;
            b||(b=this.element.closest("[class*='ui-bar-'],[class*='ui-body-']"),i=(b=b.length&&/ui-(bar|body)-([a-z])/.exec(b.attr("class")))&&
                b[2]||"c",b=i);
            b=" ui-body-"+b;
            a("label[for='"+d.attr("id")+"']").addClass("ui-input-text");
            d.addClass("ui-input-text ui-body-"+c.theme);
            f=d;
            typeof d[0].autocorrect!=="undefined"&&(d[0].setAttribute("autocorrect","off"),d[0].setAttribute("autocomplete","off"));
            d.is("[type='search'],:jqmData(type='search')")?(f=d.wrap("<div class='ui-input-search ui-shadow-inset ui-btn-corner-all ui-btn-shadow ui-icon-searchfield"+b+"'></div>").parent(),e=a("<a href='#' class='ui-input-clear' title='clear text'>clear text</a>").tap(function(a){
                d.val("").focus();
                d.trigger("change");
                e.addClass("ui-input-clear-hidden");
                a.preventDefault()
            }).appendTo(f).buttonMarkup({
                icon:"delete",
                iconpos:"notext",
                corners:!0,
                shadow:!0
            }),c=function(){
                d.val()?e.removeClass("ui-input-clear-hidden"):e.addClass("ui-input-clear-hidden")
            },c(),d.keyup(c).focus(c)):d.addClass("ui-corner-all ui-shadow-inset"+b);
            d.focus(function(){
                f.addClass("ui-focus")
            }).blur(function(){
                f.removeClass("ui-focus")
            });
            if(d.is("textarea")){
                var g=function(){
                    var a=d[0].scrollHeight;
                    d[0].clientHeight<a&&d.css({
                        height:a+
                        15
                    })
                },h;
                d.keyup(function(){
                    clearTimeout(h);
                    h=setTimeout(g,100)
                })
            }
        },
        disable:function(){
            (this.element.attr("disabled",!0).is("[type='search'],:jqmData(type='search')")?this.element.parent():this.element).addClass("ui-disabled")
        },
        enable:function(){
            (this.element.attr("disabled",!1).is("[type='search'],:jqmData(type='search')")?this.element.parent():this.element).removeClass("ui-disabled")
        }
    });
    a(document).bind("pagecreate create",function(d){
        a(a.mobile.textinput.prototype.options.initSelector,d.target).not(":jqmData(role='none'), :jqmData(role='nojs')").textinput()
    })
})(jQuery);
(function(a){
    a.widget("mobile.selectmenu",a.mobile.widget,{
        options:{
            theme:null,
            disabled:!1,
            icon:"arrow-d",
            iconpos:"right",
            inline:null,
            corners:!0,
            shadow:!0,
            iconshadow:!0,
            menuPageTheme:"b",
            overlayTheme:"a",
            hidePlaceholderMenuItems:!0,
            closeText:"Close",
            nativeMenu:!0,
            initSelector:"select:not(:jqmData(role='slider'))"
        },
        _create:function(){
            var d=this,c=this.options,b=this.element.wrap("<div class='ui-select'>"),f=b.attr("id"),e=a("label[for='"+f+"']").addClass("ui-select"),g=b[0].selectedIndex==-1?0:b[0].selectedIndex,
            h=(d.options.nativeMenu?a("<div/>"):a("<a>",{
                href:"#",
                role:"button",
                id:l,
                "aria-haspopup":"true",
                "aria-owns":m
            })).text(a(b[0].options.item(g)).text()).insertBefore(b).buttonMarkup({
                theme:c.theme,
                icon:c.icon,
                iconpos:c.iconpos,
                inline:c.inline,
                corners:c.corners,
                shadow:c.shadow,
                iconshadow:c.iconshadow
            }),i=d.isMultiple=b[0].multiple;
            c.nativeMenu&&window.opera&&window.opera.version&&b.addClass("ui-select-nativeonly");
            if(!c.nativeMenu){
                var k=b.find("option"),l=f+"-button",m=f+"-menu",p=b.closest(".ui-page"),
                g=/ui-btn-up-([a-z])/.exec(h.attr("class"))[1],o=a("<div data-"+a.mobile.ns+"role='dialog' data-"+a.mobile.ns+"theme='"+c.menuPageTheme+"'><div data-"+a.mobile.ns+"role='header'><div class='ui-title'>"+e.text()+"</div></div><div data-"+a.mobile.ns+"role='content'></div></div>").appendTo(a.mobile.pageContainer).page(),j=o.find(".ui-content");
                o.find(".ui-header a");
                var q=a("<div>",{
                    "class":"ui-selectmenu-screen ui-screen-hidden"
                }).appendTo(p),n=a("<div>",{
                    "class":"ui-selectmenu ui-selectmenu-hidden ui-overlay-shadow ui-corner-all ui-body-"+
                    c.overlayTheme+" "+a.mobile.defaultDialogTransition
                }).insertAfter(q),x=a("<ul>",{
                    "class":"ui-selectmenu-list",
                    id:m,
                    role:"listbox",
                    "aria-labelledby":l
                }).attr("data-"+a.mobile.ns+"theme",g).appendTo(n),z=a("<div>",{
                    "class":"ui-header ui-bar-"+g
                }).prependTo(n),t=a("<h1>",{
                    "class":"ui-title"
                }).appendTo(z),v=a("<a>",{
                    text:c.closeText,
                    href:"#",
                    "class":"ui-btn-left"
                }).attr("data-"+a.mobile.ns+"iconpos","notext").attr("data-"+a.mobile.ns+"icon","delete").appendTo(z).buttonMarkup()
            }
            if(i)d.buttonCount=a("<span>").addClass("ui-li-count ui-btn-up-c ui-btn-corner-all").hide().appendTo(h);
            c.disabled&&this.disable();
            b.change(function(){
                d.refresh()
            });
            a.extend(d,{
                select:b,
                optionElems:k,
                selectID:f,
                label:e,
                buttonId:l,
                menuId:m,
                thisPage:p,
                button:h,
                menuPage:o,
                menuPageContent:j,
                screen:q,
                listbox:n,
                list:x,
                menuType:void 0,
                header:z,
                headerClose:v,
                headerTitle:t,
                placeholder:""
            });
            c.nativeMenu?b.appendTo(h).bind("vmousedown",function(){
                h.addClass(a.mobile.activeBtnClass)
            }).bind("focus vmouseover",function(){
                h.trigger("vmouseover")
            }).bind("vmousemove",function(){
                h.removeClass(a.mobile.activeBtnClass)
            }).bind("change blur vmouseout",
                function(){
                    h.trigger("vmouseout").removeClass(a.mobile.activeBtnClass)
                }):(d.refresh(),b.attr("tabindex","-1").focus(function(){
                a(this).blur();
                h.focus()
            }),h.bind("vclick keydown",function(b){
                if(b.type=="vclick"||b.keyCode&&(b.keyCode===a.mobile.keyCode.ENTER||b.keyCode===a.mobile.keyCode.SPACE))d.open(),b.preventDefault()
            }),x.attr("role","listbox").delegate(".ui-li>a","focusin",function(){
                a(this).attr("tabindex","0")
            }).delegate(".ui-li>a","focusout",function(){
                a(this).attr("tabindex","-1")
            }).delegate("li:not(.ui-disabled, .ui-li-divider)",
                "vclick",function(c){
                    var e=a(this),f=b[0].selectedIndex,g=e.jqmData("option-index"),h=d.optionElems[g];
                    h.selected=i?!h.selected:!0;
                    i&&e.find(".ui-icon").toggleClass("ui-icon-checkbox-on",h.selected).toggleClass("ui-icon-checkbox-off",!h.selected);
                    (i||f!==g)&&b.trigger("change");
                    i||d.close();
                    c.preventDefault()
                }).keydown(function(b){
                var c=a(b.target),d=c.closest("li");
                switch(b.keyCode){
                    case 38:
                        return b=d.prev(),b.length&&(c.blur().attr("tabindex","-1"),b.find("a").first().focus()),!1;
                    case 40:
                        return b=
                        d.next(),b.length&&(c.blur().attr("tabindex","-1"),b.find("a").first().focus()),!1;
                    case 13:case 32:
                        return c.trigger("vclick"),!1
                }
            }),d.menuPage.bind("pagehide",function(){
                d.list.appendTo(d.listbox);
                d._focusButton()
            }),q.bind("vclick",function(){
                d.close()
            }),d.headerClose.click(function(){
                if(d.menuType=="overlay")return d.close(),!1
            }))
        },
        _buildList:function(){
            var d=this,c=this.options,b=this.placeholder,f=[],e=[],g=d.isMultiple?"checkbox-off":"false";
            d.list.empty().filter(".ui-listview").listview("destroy");
            d.select.find("option").each(function(h){
                var i=a(this),k=i.parent(),l=i.text(),m="<a href='#'>"+l+"</a>",p=[],o=[];
                k.is("optgroup")&&(k=k.attr("label"),a.inArray(k,f)===-1&&(e.push("<li data-"+a.mobile.ns+"role='list-divider'>"+k+"</li>"),f.push(k)));
                if(!this.getAttribute("value")||l.length==0||i.jqmData("placeholder"))c.hidePlaceholderMenuItems&&p.push("ui-selectmenu-placeholder"),b=d.placeholder=l;
                this.disabled&&(p.push("ui-disabled"),o.push("aria-disabled='true'"));
                e.push("<li data-"+a.mobile.ns+
                    "option-index='"+h+"' data-"+a.mobile.ns+"icon='"+g+"' class='"+p.join(" ")+"' "+o.join(" ")+">"+m+"</li>")
            });
            d.list.html(e.join(" "));
            d.list.find("li").attr({
                role:"option",
                tabindex:"-1"
            }).first().attr("tabindex","0");
            this.isMultiple||this.headerClose.hide();
            !this.isMultiple&&!b.length?this.header.hide():this.headerTitle.text(this.placeholder);
            d.list.listview()
        },
        refresh:function(d){
            var c=this,b=this.element,f=this.isMultiple,e=this.optionElems=b.find("option"),g=e.filter(":selected"),h=g.map(function(){
                return e.index(this)
            }).get();
            !c.options.nativeMenu&&(d||b[0].options.length!=c.list.find("li").length)&&c._buildList();
            c.button.find(".ui-btn-text").text(function(){
                if(!f)return g.text();
                return g.length?g.map(function(){
                    return a(this).text()
                }).get().join(", "):c.placeholder
            });
            f&&c.buttonCount[g.length>1?"show":"hide"]().text(g.length);
            c.options.nativeMenu||c.list.find("li:not(.ui-li-divider)").removeClass(a.mobile.activeBtnClass).attr("aria-selected",!1).each(function(b){
                a.inArray(b,h)>-1&&(b=a(this).addClass(a.mobile.activeBtnClass),
                    b.find("a").attr("aria-selected",!0),f&&b.find(".ui-icon").removeClass("ui-icon-checkbox-off").addClass("ui-icon-checkbox-on"))
            })
        },
        open:function(){
            function d(){
                c.list.find(".ui-btn-active").focus()
            }
            if(!this.options.disabled&&!this.options.nativeMenu){
                var c=this,b=c.list.parent().outerHeight(),f=c.list.parent().outerWidth(),e=a(window).scrollTop(),g=c.button.offset().top,h=window.innerHeight,i=window.innerWidth;
                c.button.addClass(a.mobile.activeBtnClass);
                setTimeout(function(){
                    c.button.removeClass(a.mobile.activeBtnClass)
                },
                300);
                if(b>h-80||!a.support.scrollTop){
                    c.thisPage.unbind("pagehide.remove");
                    if(e==0&&g>h)c.thisPage.one("pagehide",function(){
                        a(this).jqmData("lastScroll",g)
                    });
                    c.menuPage.one("pageshow",function(){
                        a(window).one("silentscroll",function(){
                            d()
                        });
                        c.isOpen=!0
                    });
                    c.menuType="page";
                    c.menuPageContent.append(c.list);
                    a.mobile.changePage(c.menuPage,{
                        transition:a.mobile.defaultDialogTransition
                    })
                }else{
                    c.menuType="overlay";
                    c.screen.height(a(document).height()).removeClass("ui-screen-hidden");
                    var k=g-e,l=e+h-g,m=
                    b/2,p=parseFloat(c.list.parent().css("max-width")),b=k>b/2&&l>b/2?g+c.button.outerHeight()/2-m:k>l?e+h-b-30:e+30;
                    f<p?p=(i-f)/2:(p=c.button.offset().left+c.button.outerWidth()/2-f/2,p<30?p=30:p+f>i&&(p=i-f-30));
                    c.listbox.append(c.list).removeClass("ui-selectmenu-hidden").css({
                        top:b,
                        left:p
                    }).addClass("in");
                    d();
                    c.isOpen=!0
                }
            }
        },
        _focusButton:function(){
            var a=this;
            setTimeout(function(){
                a.button.focus()
            },40)
        },
        close:function(){
            if(!this.options.disabled&&this.isOpen&&!this.options.nativeMenu)this.menuType==
                "page"?(this.thisPage.bind("pagehide.remove",function(){
                    a(this).remove()
                }),window.history.back()):(this.screen.addClass("ui-screen-hidden"),this.listbox.addClass("ui-selectmenu-hidden").removeAttr("style").removeClass("in"),this.list.appendTo(this.listbox),this._focusButton()),this.isOpen=!1
        },
        disable:function(){
            this.element.attr("disabled",!0);
            this.button.addClass("ui-disabled").attr("aria-disabled",!0);
            return this._setOption("disabled",!0)
        },
        enable:function(){
            this.element.attr("disabled",!1);
            this.button.removeClass("ui-disabled").attr("aria-disabled",
                !1);
            return this._setOption("disabled",!1)
        }
    });
    a(document).bind("pagecreate create",function(d){
        a(a.mobile.selectmenu.prototype.options.initSelector,d.target).not(":jqmData(role='none'), :jqmData(role='nojs')").selectmenu()
    })
})(jQuery);
(function(a){
    function d(b){
        for(;b;){
            var c=a(b);
            if(c.hasClass("ui-btn")&&!c.hasClass("ui-disabled"))break;
            b=b.parentNode
        }
        return b
    }
    a.fn.buttonMarkup=function(b){
        return this.each(function(){
            var d=a(this),e=a.extend({},a.fn.buttonMarkup.defaults,d.jqmData(),b),g="ui-btn-inner",h,i;
            c&&c();
            if(!e.theme)h=d.closest("[class*='ui-bar-'],[class*='ui-body-']"),e.theme=h.length?/ui-(bar|body)-([a-z])/.exec(h.attr("class"))[2]:"c";
            h="ui-btn ui-btn-up-"+e.theme;
            e.inline&&(h+=" ui-btn-inline");
            if(e.icon)e.icon="ui-icon-"+
                e.icon,e.iconpos=e.iconpos||"left",i="ui-icon "+e.icon,e.iconshadow&&(i+=" ui-icon-shadow");
            e.iconpos&&(h+=" ui-btn-icon-"+e.iconpos,e.iconpos=="notext"&&!d.attr("title")&&d.attr("title",d.text()));
            e.corners&&(h+=" ui-btn-corner-all",g+=" ui-btn-corner-all");
            e.shadow&&(h+=" ui-shadow");
            d.attr("data-"+a.mobile.ns+"theme",e.theme).addClass(h);
            e=("<D class='"+g+"'><D class='ui-btn-text'></D>"+(e.icon?"<span class='"+i+"'></span>":"")+"</D>").replace(/D/g,e.wrapperEls);
            d.wrapInner(e)
        })
    };

    a.fn.buttonMarkup.defaults=

    {
        corners:!0,
        shadow:!0,
        iconshadow:!0,
        wrapperEls:"span"
    };

    var c=function(){
        a(document).bind({
            vmousedown:function(b){
                var b=d(b.target),c;
                b&&(b=a(b),c=b.attr("data-"+a.mobile.ns+"theme"),b.removeClass("ui-btn-up-"+c).addClass("ui-btn-down-"+c))
            },
            "vmousecancel vmouseup":function(b){
                var b=d(b.target),c;
                b&&(b=a(b),c=b.attr("data-"+a.mobile.ns+"theme"),b.removeClass("ui-btn-down-"+c).addClass("ui-btn-up-"+c))
            },
            "vmouseover focus":function(b){
                var b=d(b.target),c;
                b&&(b=a(b),c=b.attr("data-"+a.mobile.ns+"theme"),
                    b.removeClass("ui-btn-up-"+c).addClass("ui-btn-hover-"+c))
            },
            "vmouseout blur":function(b){
                var b=d(b.target),c;
                b&&(b=a(b),c=b.attr("data-"+a.mobile.ns+"theme"),b.removeClass("ui-btn-hover-"+c).addClass("ui-btn-up-"+c))
            }
        });
        c=null
    };

    a(document).bind("pagecreate create",function(b){
        a(":jqmData(role='button'), .ui-bar > a, .ui-header > a, .ui-footer > a, .ui-bar > :jqmData(role='controlgroup') > a",b.target).not(".ui-btn, :jqmData(role='none'), :jqmData(role='nojs')").buttonMarkup()
    })
})(jQuery);
(function(a){
    a.fn.controlgroup=function(d){
        return this.each(function(){
            function c(a){
                a.removeClass("ui-btn-corner-all ui-shadow").eq(0).addClass(g[0]).end().filter(":last").addClass(g[1]).addClass("ui-controlgroup-last")
            }
            var b=a(this),f=a.extend({
                direction:b.jqmData("type")||"vertical",
                shadow:!1,
                excludeInvisible:!0
            },d),e=b.find(">legend"),g=f.direction=="horizontal"?["ui-corner-left","ui-corner-right"]:["ui-corner-top","ui-corner-bottom"];
            b.find("input:eq(0)").attr("type");
            e.length&&(b.wrapInner("<div class='ui-controlgroup-controls'></div>"),
                a("<div role='heading' class='ui-controlgroup-label'>"+e.html()+"</div>").insertBefore(b.children(0)),e.remove());
            b.addClass("ui-corner-all ui-controlgroup ui-controlgroup-"+f.direction);
            c(b.find(".ui-btn"+(f.excludeInvisible?":visible":"")));
            c(b.find(".ui-btn-inner"));
            f.shadow&&b.addClass("ui-shadow")
        })
    };

    a(document).bind("pagecreate create",function(d){
        a(":jqmData(role='controlgroup')",d.target).controlgroup({
            excludeInvisible:!1
        })
    })
})(jQuery);
(function(a){
    a(document).bind("pagecreate create",function(d){
        a(d.target).find("a").not(".ui-btn, .ui-link-inherit, :jqmData(role='none'), :jqmData(role='nojs')").addClass("ui-link")
    })
})(jQuery);
(function(a,d){
    a.fn.fixHeaderFooter=function(){
        if(!a.support.scrollTop)return this;
        return this.each(function(){
            var c=a(this);
            c.jqmData("fullscreen")&&c.addClass("ui-page-fullscreen");
            c.find(".ui-header:jqmData(position='fixed')").addClass("ui-header-fixed ui-fixed-inline fade");
            c.find(".ui-footer:jqmData(position='fixed')").addClass("ui-footer-fixed ui-fixed-inline fade")
        })
    };

    a.mobile.fixedToolbars=function(){
        function c(){
            !i&&h==="overlay"&&(g||a.mobile.fixedToolbars.hide(!0),a.mobile.fixedToolbars.startShowTimer())
        }
        function b(a){
            var b=0,c,d;
            if(a){
                d=document.body;
                c=a.offsetParent;
                for(b=a.offsetTop;a&&a!=d;){
                    b+=a.scrollTop||0;
                    if(a==c)b+=c.offsetTop,c=a.offsetParent;
                    a=a.parentNode
                }
            }
            return b
        }
        function f(c){
            var d=a(window).scrollTop(),e=b(c[0]),f=c.css("top")=="auto"?0:parseFloat(c.css("top")),g=window.innerHeight,h=c.outerHeight(),i=c.parents(".ui-page:not(.ui-page-fullscreen)").length;
            return c.is(".ui-header-fixed")?(f=d-e+f,f<e&&(f=0),c.css("top",i?f:d)):c.css("top",i?d+g-h-(e-f):d+g-h)
        }
        if(a.support.scrollTop){
            var e,
            g,h="inline",i=!1,k=null,l=!1,m=!0;
            a(function(){
                var b=a(document),d=a(window);
                b.bind("vmousedown",function(){
                    m&&(k=h)
                }).bind("vclick",function(b){
                    m&&!a(b.target).closest("a,input,textarea,select,button,label,.ui-header-fixed,.ui-footer-fixed").length&&!l&&(a.mobile.fixedToolbars.toggle(k),k=null)
                }).bind("silentscroll",c);
                (b.scrollTop()===0?d:b).bind("scrollstart",function(){
                    l=!0;
                    k===null&&(k=h);
                    var b=k=="overlay";
                    if(i=b||!!g)a.mobile.fixedToolbars.clearShowTimer(),b&&a.mobile.fixedToolbars.hide(!0)
                }).bind("scrollstop",
                    function(b){
                        a(b.target).closest("a,input,textarea,select,button,label,.ui-header-fixed,.ui-footer-fixed").length||(l=!1,i&&(a.mobile.fixedToolbars.startShowTimer(),i=!1),k=null)
                    });
                d.bind("resize",c)
            });
            a(".ui-page").live("pagebeforeshow",function(b,c){
                var d=a(b.target).find(":jqmData(role='footer')"),g=d.data("id"),h=c.prevPage,h=h&&h.find(":jqmData(role='footer')"),h=h.length&&h.jqmData("id")===g;
                g&&h&&(e=d,f(e.removeClass("fade in out").appendTo(a.mobile.pageContainer)))
            }).live("pageshow",function(){
                var b=
                a(this);
                e&&e.length&&setTimeout(function(){
                    f(e.appendTo(b).addClass("fade"));
                    e=null
                },500);
                a.mobile.fixedToolbars.show(!0,this)
            });
            a(".ui-collapsible-contain").live("collapse expand",c);
            return{
                show:function(c,d){
                    a.mobile.fixedToolbars.clearShowTimer();
                    h="overlay";
                    return(d?a(d):a.mobile.activePage?a.mobile.activePage:a(".ui-page-active")).children(".ui-header-fixed:first, .ui-footer-fixed:not(.ui-footer-duplicate):last").each(function(){
                        var d=a(this),e=a(window).scrollTop(),g=b(d[0]),h=window.innerHeight,
                        i=d.outerHeight(),e=d.is(".ui-header-fixed")&&e<=g+i||d.is(".ui-footer-fixed")&&g<=e+h;
                        d.addClass("ui-fixed-overlay").removeClass("ui-fixed-inline");
                        !e&&!c&&d.animationComplete(function(){
                            d.removeClass("in")
                        }).addClass("in");
                        f(d)
                    })
                },
                hide:function(b){
                    h="inline";
                    return(a.mobile.activePage?a.mobile.activePage:a(".ui-page-active")).children(".ui-header-fixed:first, .ui-footer-fixed:not(.ui-footer-duplicate):last").each(function(){
                        var c=a(this),d=c.css("top"),d=d=="auto"?0:parseFloat(d);
                        c.addClass("ui-fixed-inline").removeClass("ui-fixed-overlay");
                        if(d<0||c.is(".ui-header-fixed")&&d!==0)b?c.css("top",0):c.css("top")!=="auto"&&parseFloat(c.css("top"))!==0&&c.animationComplete(function(){
                            c.removeClass("out reverse").css("top",0)
                        }).addClass("out reverse")
                    })
                },
                startShowTimer:function(){
                    a.mobile.fixedToolbars.clearShowTimer();
                    var b=[].slice.call(arguments);
                    g=setTimeout(function(){
                        g=d;
                        a.mobile.fixedToolbars.show.apply(null,b)
                    },100)
                },
                clearShowTimer:function(){
                    g&&clearTimeout(g);
                    g=d
                },
                toggle:function(b){
                    b&&(h=b);
                    return h==="overlay"?a.mobile.fixedToolbars.hide():
                    a.mobile.fixedToolbars.show()
                },
                setTouchToggleEnabled:function(a){
                    m=a
                }
            }
        }
    }();
    a.fixedToolbars=a.mobile.fixedToolbars;
    a(document).bind("pagecreate create",function(c){
        a(":jqmData(position='fixed')",c.target).length&&a(c.target).each(function(){
            if(!a.support.scrollTop)return this;
            var b=a(this);
            b.jqmData("fullscreen")&&b.addClass("ui-page-fullscreen");
            b.find(".ui-header:jqmData(position='fixed')").addClass("ui-header-fixed ui-fixed-inline fade");
            b.find(".ui-footer:jqmData(position='fixed')").addClass("ui-footer-fixed ui-fixed-inline fade")
        })
    })
})(jQuery);
(function(a){
    function d(){
        var d=c.width(),g=[],h=[],i;
        b.removeClass("min-width-"+f.join("px min-width-")+"px max-width-"+f.join("px max-width-")+"px");
        a.each(f,function(a,b){
            d>=b&&g.push("min-width-"+b+"px");
            d<=b&&h.push("max-width-"+b+"px")
        });
        g.length&&(i=g.join(" "));
        h.length&&(i+=" "+h.join(" "));
        b.addClass(i)
    }
    var c=a(window),b=a("html"),f=[320,480,768,1024];
    a.mobile.addResolutionBreakpoints=function(b){
        a.type(b)==="array"?f=f.concat(b):f.push(b);
        f.sort(function(a,b){
            return a-b
        });
        d()
    };

    a(document).bind("mobileinit.htmlclass",
        function(){
            c.bind("orientationchange.htmlclass throttledResize.htmlclass",function(a){
                a.orientation&&b.removeClass("portrait landscape").addClass(a.orientation);
                d()
            })
        });
    a(function(){
        c.trigger("orientationchange.htmlclass")
    })
})(jQuery);
(function(a,d){
    var c=a("html");
    a("head");
    var b=a(d);
    a(d.document).trigger("mobileinit");
    if(a.mobile.gradeA()){
        if(a.mobile.ajaxBlacklist)a.mobile.ajaxEnabled=!1;
        c.addClass("ui-mobile ui-mobile-rendering");
        var f=a("<div class='ui-loader ui-body-a ui-corner-all'><span class='ui-icon ui-icon-loading spin'></span><h1></h1></div>");
        a.extend(a.mobile,{
            showPageLoadingMsg:function(){
                if(a.mobile.loadingMessage){
                    var b=a("."+a.mobile.activeBtnClass).first();
                    f.find("h1").text(a.mobile.loadingMessage).end().appendTo(a.mobile.pageContainer).css({
                        top:a.support.scrollTop&&
                        a(d).scrollTop()+a(d).height()/2||b.length&&b.offset().top||100
                    })
                }
                c.addClass("ui-loading")
            },
            hidePageLoadingMsg:function(){
                c.removeClass("ui-loading")
            },
            pageLoading:function(b){
                b?a.mobile.hidePageLoadingMsg():a.mobile.showPageLoadingMsg()
            },
            initializePage:function(){
                var c=a(":jqmData(role='page')");
                c.length||(c=a("body").wrapInner("<div data-"+a.mobile.ns+"role='page'></div>").children(0));
                c.add(":jqmData(role='dialog')").each(function(){
                    var b=a(this);
                    b.jqmData("url")||b.attr("data-"+a.mobile.ns+"url",
                        b.attr("id"))
                });
                a.mobile.firstPage=c.first();
                a.mobile.pageContainer=c.first().parent().addClass("ui-mobile-viewport");
                a.mobile.showPageLoadingMsg();
                !a.mobile.hashListeningEnabled||!a.mobile.path.stripHash(location.hash)?a.mobile.changePage(a.mobile.firstPage,{
                    transition:"none",
                    reverse:!0,
                    changeHash:!1,
                    fromHashChange:!0
                }):b.trigger("hashchange",[!0])
            }
        });
        a.mobile._registerInternalEvents();
        a(function(){
            d.scrollTo(0,1);
            a.mobile.defaultHomeScroll=!a.support.scrollTop||a(d).scrollTop()===1?0:1;
            a.mobile.autoInitializePage&&
            a(a.mobile.initializePage);
            b.load(a.mobile.silentScroll)
        })
    }
})(jQuery,this);

jQuery.noConflict();

(function($,window){
    var searchBtn, touchSlider, ajaxList, showMoreContent, showSearchbutton, addressBtn;

    /* toggles the search under the header (by toggeling a class)
   * @param:    toggler button (jQ Object), element to toggle (jQ Object)
   * @return:   toggler button
   */
    searchBtn = function( $searchBtn,$searchField ){
        $searchBtn.each(function(){
            var $that = $(this);

            return $that.click(function( e ){
                e.preventDefault();
                $searchField.toggleClass("target");
            });

        });
    };

    addressBtn = function($addressBtn,$addressField){
        $addressBtn.each(function(){
            var $that = $(this);

            return $that.click(function( e ){
                e.preventDefault();
                $addressField.toggleClass("contact_block");
            });

        });
    };


    /* handles the slider (needs jQ Mobile for the swipes)
   * @param: slide container (jQ object), activeClass (optional)
   * @return: slide container (jQ object)
   * Last edited: 17.08.2011
   */
    touchSlider = function( $slideContainer,ac ){
        /* private functions and scope vars */
        var moveSlider, activeClass, adaptSize;

        activeClass = ac || "active";

        /* adapts the slider size if the screen gets resized */
        adaptSize = function(){
            var $cn, cnWidth, $list, $elements, elements;

            $cn = $(this);
            cnWidth = $cn.width();
            $list = $cn.find("ul");
            $elements = $list.find("li");
            elements = $elements.length;

            $elements.width( cnWidth );
            $list.width( cnWidth * elements );

        };

        /* moves the slider stage to the right position and resets the active class */
        moveSlider = function( e,$cn,$next,$prev ){
            var eventType, move, $listElements, listElements, $active;

            $listElements = $cn.find("li");
            listElements = $listElements.length;
            $active = $listElements.filter("." + activeClass);

            move = function( direction ){
                var $coming;

                if ( direction === "next") {
                    if ( $active.index() + 1 === listElements ) {
                        return false;
                    }
                    $coming = $active.next();
                }else{
                    if ( !$active.index() ) {
                        return false;
                    }
                    $coming = $active.prev();
                }

                $cn.css( "margin-left", -1 * (($coming.index()) * 100) + "%"  );

                $active.removeClass(activeClass);
                $coming.addClass(activeClass);

                $prev.filter(":hidden").stop(false,true).fadeIn(300);
                $next.filter(":hidden").stop(false,true).fadeIn(300);

                if( !$coming.next().length ) {
                    $prev.stop(false,true).fadeOut(300);
                }else if ( !$coming.prev().length ){
                    $next.stop(false,true).fadeOut(300);
                }
            };

            eventType = e.type.toLowerCase();

            if(eventType === "click") {

                if( $(e.currentTarget).is(".prev") ){
                    move( "prev" );
                }else{
                    move( "next" );
                }

            }else if(eventType === "swipeleft" ){
                move( "next" );
            }else{
                //swiperight
                move( "prev" );
            }

        };

        /* constructs the slider and inits the functions */
        $slideContainer.each(function(){
            var $cn, $next, $prev, $list;

            $next = $("<a/>", {
                "class": "controls",
                //"href": "#next",
                "click": function(e){
                    e.preventDefault();
                    moveSlider( e,$list,$next,$prev );
                }
            }).addClass("prev").hide();

            $prev = $("<a/>", {
                "class": "controls",
                //"href": "#prev",
                "click": function(e){
                    e.preventDefault();
                    moveSlider( e,$list,$next,$prev );
                }
            }).addClass("next");

            $cn = $(this);
            $cn.append( $next ).append( $prev );
            $list = $cn.find("ul").eq(0);

            $cn.find("li").eq(0).addClass(activeClass);

            $cn.resize(adaptSize);
            adaptSize.call( $cn[0] );

            $cn.bind("swipeleft swiperight", function(e){
                moveSlider( e,$list,$next,$prev );
            });

        });
    };

    /* ajax lists (gets more elements based on a URL)
   * @param: list that get extendet (jQ object), link containing that triggers the ajax reload (must contain target URL), callback (gets called after each successfull ajax call)
   * @return: list
   * Last edited: 17.08.2011
   */
    ajaxList = function( $list,$trigger,callback ){
        if( $list.length ){
            var ajaxURL, $highlitedElement, linktext, $loadImg, loadingClass;
            ajaxURL = $trigger.attr("href");
            linktext = $trigger.html();
            $highlitedElement = $list.find(".highlight:last-child");

            loadingClass = "ajaxLoading";

            $loadImg = $("<img />", {
                "src": $trigger.attr("data-loading-image"),
                "alt": "loading...",
                "class": "ajaxLoading"
            });
            $loadImg.load();



            $trigger.click(function(e){
                var $that = $(this);

                $that.addClass( loadingClass );

                e.preventDefault();
                $trigger.text("").append($loadImg);

                var bla = $.get(ajaxURL, function(d, s){
                    var check = d.trim().length;
                    if (check == 0) {
                        $trigger.remove();
                    }
                    var $more, stillMore, $d = $(d);

                    var $offers;
                    $offers = $d.filter(".offerIds");
                    offerIds = $offers.length;
                    if (offerIds) {
                        var PAA$OfferIds = PAA$OfferIds;
                        if (PAA$OfferIds) {
                            var $new = eval($offers.html());
                            var $old = eval(PAA$OfferIds);
                            PAA$OfferIds = $.merge($new,$old);
                        }
                        $d = $d.not(".offerIds");
                    }

                    $more = $d.filter(".moreAjax");
                    stillMore = $more.length;
                    if (stillMore) {
                        ajaxURL = $more.find("a").attr("href");

                        $d = $d.not(".moreAjax");
                        $trigger.html( linktext );
                        $that.removeClass( loadingClass );
                    }else{
                        $trigger.remove();
                    }
                    if( $highlitedElement.length ){
                        $highlitedElement.before($d);
                    }else{
                        $list.append($d);
                    }
                });
            });

        }
        return false;
    };

    /* shows more contents on click on a defined trigger
   * @param: trigger (jQ object), target selector (the element hides)
   * @return: toggled element
   * Last edited: 17.08.2011
   */
    showMoreContent = function( $trigger,toggleSelector ){
        $trigger.click(function(e){
            e.preventDefault();
            return $(this).hide().nextAll( toggleSelector ).eq(0).fadeIn(300);
        });
    };

    /* shows more contents on click on a defined trigger
   * @param: trigger (jQ object), target selector (the element hides)
   * @return: toggled element
   * Last edited: 17.08.2011
   */
    showMoreParents = function( $trigger,toggleSelector ){
        $trigger.click(function(e){
            e.preventDefault();
            return $(this).hide().parent().nextAll( toggleSelector ).fadeIn(300);
        });
    };

    /* shows search button in header as soon as js is loaded
   * Last edited: 1.09.2011
   */
    showSearchbutton = function(){
        var oBtn = $("div#container > header > a:last-of-type");
        if (oBtn && !oBtn.hasClass("popup")) {
            oBtn.show();
        }
    };


    $(function(){

        /* add data-role="none" to form element to prevent auto-initialization*/
        $("select,button,a,input:not(#searchfield)").attr("data-role", "none");

        /*configurationg jQ Mobile*/
        $.mobile.ajaxEnabled = false;
        $.mobile.autoInitializePage = false;
        $.mobile.hashListeningEnabled = false;
        $.mobile.loadingMessage = null;

        /* function Init */
        searchBtn( $("#search-button"), $("#search") );
        addressBtn( $("#contact-button"), $("#address") );
        touchSlider( $("div.slider") );
        ajaxList( $(".linklist ul"),$(".showmore a") );
        showMoreContent( $("span.moreContent"), ".morecontent" );
        showMoreContent( $("span.moreCategories"), "#keywordsearch" );
        showMoreContent( $("span.moreDownloads"), ".moredownloads" );
        showMoreParents( $("span.moreTimetable"), ".moretimetable" );
        showSearchbutton();
    // $("input#REQ0JourneyDate").scroller({"theme":"ios",  "dateOrder": "ddmmy" });
    // $("input#REQ0JourneyTime").scroller({"preset": "time", "theme":"ios", "ampm": false, "timeFormat": "HH:ii"});

    });

})( jQuery,window );
