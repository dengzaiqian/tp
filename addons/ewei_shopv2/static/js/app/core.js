define(["jquery", "foxui", "tpl"],
function(l, a, i) {
    window.FoxUI = a;
    var e = {
        baseUrl: "",
        siteUrl: "",
        staticUrl: "../addons/ewei_shopv2/static/"
    },
    s = {
        options: {},
        init: function(t) {
            this.options = l.extend({},
            e, t || {})
        },
        toQueryPair: function(t, e) {
            return void 0 === e ? t: t + "=" + encodeURIComponent(null === e ? "": String(e))
        },
        number_format: function(t, e) {
            e = e || 2;
            var n = "";
            t = t.toFixed(e),
            zsw = t.split(".")[0],
            xsw = t.split(".")[1],
            zswarr = zsw.split("");
            for (var r = 1; r <= zswarr.length; r++) n = zswarr[zswarr.length - r] + n,
            r % 3 == 0 && (n = "," + n);
            return n = zsw.length % 3 == 0 ? n.substr(1) : n,
            zsw = n + "." + xsw,
            zsw
        },
        toQueryString: function(t) {
            var e = [];
            for (var n in t) {
                var r = t[n = encodeURIComponent(n)];
                if (r && r.constructor == Array) {
                    for (var o, i = [], a = 0, s = r.length; a < s; a++) o = r[a],
                    i.push(this.toQueryPair(n, o));
                    e = e.concat(i)
                } else e.push(this.toQueryPair(n, r))
            }
            return e.join("&")
        },
        getUrl: function(t, e, n) {
            t = t.replace(/\//gi, ".");
            var r = this.options.baseUrl.replace("ROUTES", t);
            return e && ("object" == typeof e ? r += "&" + this.toQueryString(e) : "string" == typeof e && (r += "&" + e)),
            n ? this.options.siteUrl + "app/" + r: r
        },
        json: function(t, e, n, r, o) {
            var i = {
                url: o ? this.getUrl(t) : this.getUrl(t, e),
                type: o ? "post": "get",
                dataType: "json",
                cache: !1,
                beforeSend: function() {
                    r && a.loader.show("mini")
                },
                error: function(t) {
                    r && a.loader.hide()
                }
            };
            e && o && (i.data = e),
            n && (i.success = function(t) {
                r && a.loader.hide(),
                n(t)
            }),
            l.ajax(i)
        },
        post: function(t, e, n, r) {
            this.json(t, e, n, r, !0)
        },
        html: function(t, e, n, r, o) {
            var i = {
                url: this.getUrl(t, e),
                type: "get",
                cache: !1,
                dataType: "html",
                async: o,
                beforeSend: function() {
                    r && a.loader.show("mini")
                },
                error: function() {
                    s.removeLoading(),
                    r && a.loader.hide()
                }
            };
            n && (i.success = function(t) {
                r && a.loader.hide(),
                n(t)
            }),
            l.ajax(i)
        },
        tpl: function(t, e, n, r) {
            var o = i(e, n);
            r ? l(t).append(o) : l(t).html(o),
            setTimeout(function() {
                l(t).closest(".fui-content").lazyload("render")
            },
            10)
        },
        getNumber: function(t) {
            return "" == (t = l.trim(t)) ? 0 : parseFloat(t.replace(/,/g, ""))
        },
        showIframe: function(t) {
            var e = l(document.body).height();
            l("<iframe width='100%' height='" + e + "' id='mainFrame' name='mainFrame' style='position:absolute;z-index:4;'  frameborder='no' marginheight='0' marginwidth='0' ></iframe>").prependTo("body");
            var n = document.documentElement.scrollTop || document.body.scrollTop,
            r = document.documentElement.scrollLeft || document.body.scrollLeft,
            o = document.documentElement.clientHeight,
            i = document.documentElement.clientWidth,
            a = l("#mainFrame").height(),
            s = l("#mainFrame").width(),
            c = Number(n) + (Number(o) - Number(a)) / 2,
            u = Number(r) + (Number(i) - Number(s)) / 2;
            l("#mainFrame").css("left", u),
            l("#mainFrame").css("top", c),
            l("#mainFrame").attr("src", t)
        },
        getDistanceByLnglat: function(t, e, n, r) {
            function o(t) {
                return t * Math.PI / 180
            }
            var i = o(e),
            a = o(r),
            s = i - a,
            c = o(t) - o(n),
            u = 2 * Math.asin(Math.sqrt(Math.pow(Math.sin(s / 2), 2) + Math.cos(i) * Math.cos(a) * Math.pow(Math.sin(c / 2), 2)));
            return u *= 6378137,
            u = Math.round(1e4 * u) / 1e7
        },
        showImages: function(e) {
            var t = "micromessenger" == navigator.userAgent.toLowerCase().match(/MicroMessenger/i),
            o = [];
            l(e).each(function() {
                var t = l(this).attr("data-lazy");
                o.push(t || l(this).attr("src"))
            }),
            t && l(e).unbind("click").click(function(t) {
                t.preventDefault();
                var n = l(e).index(l(t.currentTarget)),
                r = null;
                l(e).each(function(t, e) {
                    t === n && (r = l(e).attr("data-lazy") ? l(e).attr("data-lazy") : l(e).attr("src"))
                }),
                WeixinJSBridge.invoke("imagePreview", {
                    current: r,
                    urls: o
                })
            })
        },
        ish5app: function() {
            return - 1 < navigator.userAgent.indexOf("Html5Plus")
        },
        isWeixin: function() {
            return "micromessenger" == navigator.userAgent.toLowerCase().match(/MicroMessenger/i)
        }
    };
    return document.body.addEventListener("focusout",
    function() {
        window.scroll(0, 0)
    }),
    window.core = s
});