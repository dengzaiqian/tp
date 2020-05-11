define(["core", "foxui.picker", "jquery.gcjs"],
function(h) {
    var v = {
        backurl: "",
        nohasbindinfo: 1,
        bindrealname: 0,
        bindbirthday: 0,
        bindidnumber: 0,
        bindwechat: 0,
        initLogin: function(t) {
            v.backurl = t.backurl,
            $("#btnSubmit").click(function() {
                $("#btnSubmit").attr("stop") || (0 < $(".agree").length && !$(".agree").is(":checked") ? FoxUI.toast.show("请选择用户协议及隐私政策") : $("#mobile").isMobile() ? $("#pwd").isEmpty() ? FoxUI.toast.show("请输入登录密码") : 0 < $(".agree").length && !$(".agree").is(":checked") ? FoxUI.toast.show("请选择用户协议及隐私政策") : ($("#btnSubmit").html("正在登录...").attr("stop", 1), h.json("account/login", {
                    mobile: $("#mobile").val(),
                    pwd: $("#pwd").val()
                },
                function(t) {
                    FoxUI.toast.show(t.result.message),
                    1 == t.status ? ($("#btnSubmit").html("正在跳转..."), setTimeout(function() {
                        v.backurl ? location.href = v.backurl: location.href = h.getUrl("")
                    },
                    1e3)) : $("#btnSubmit").html("立即登录").removeAttr("stop")
                },
                !1, !0)) : FoxUI.toast.show("请输入11位手机号码"))
            })
        },
        verifycode: function() {
            v.seconds--,
            0 < v.seconds ? ($("#btnCode").html(v.seconds + "秒后重发").addClass("disabled").attr("disabled", "disabled"), setTimeout(function() {
                v.verifycode()
            },
            1e3)) : $("#btnCode").html("获取验证码").removeClass("disabled").removeAttr("disabled")
        },
        initRf: function(t) {
            v.backurl = t.backurl,
            v.type = t.type,
            v.endtime = t.endtime,
            v.imgcode = t.imgcode,
            0 < v.endtime && (v.seconds = v.endtime, v.verifycode()),
            $("#btnCode").click(function() {
                $("#btnCode").hasClass("disabled") || ($("#mobile").isMobile() ? $.trim($("#verifycode2").val()) || 1 != v.imgcode ? (v.seconds = 60, h.json("account/verifycode", {
                    mobile: $("#mobile").val(),
                    imgcode: $.trim($("#verifycode2").val()) || 0,
                    temp: v.type ? "sms_forget": "sms_reg"
                },
                function(t) {
                    FoxUI.toast.show(t.result.message),
                    1 != t.status && $("#btnCode").html("获取验证码").removeClass("disabled").removeAttr("disabled"),
                    -1 == t.status && 1 == v.imgcode && $("#btnCode2").trigger("click"),
                    1 == t.status && v.verifycode()
                },
                !1, !0)) : FoxUI.toast.show("请输入图形验证码") : FoxUI.toast.show("请输入11位手机号码"))
            }),
            $("#btnCode2").click(function() {
                return $(this).prop("src", "../web/index.php?c=utility&a=code&r=" + Math.round((new Date).getTime())),
                !1
            }),
            $("#btnSubmit").click(function() {
                if (!$("#btnSubmit").attr("stop")) if ($("#mobile").isMobile()) if ($("#verifycode").isInt() && 5 == $("#verifycode").len()) if ($("#pwd").isEmpty()) FoxUI.toast.show("请输入登录密码");
                else if ($("#pwd1").isEmpty()) FoxUI.toast.show("请重复输入密码");
                else if ($("#pwd").val() === $("#pwd1").val()) if (0 < $(".agree").length && !$(".agree").is(":checked")) FoxUI.toast.show("请选择用户协议及隐私政策");
                else {
                    $("#btnSubmit").html("正在处理...").attr("stop", 1);
                    var t = v.type ? "account/forget": "account/register";
                    h.json(t, {
                        mobile: $("#mobile").val(),
                        verifycode: $("#verifycode").val(),
                        pwd: $("#pwd").val(),
                        agentid: $("#agentid").val()
                    },
                    function(t) {
                        if (1 == t.status) FoxUI.alert(t.result.message, "",
                        function() {
                            v.backurl ? location.href = h.getUrl("account/login", {
                                mobile: $("#mobile").val(),
                                backurl: v.backurl
                            }) : location.href = h.getUrl("account/login", {
                                mobile: $("#mobile").val()
                            })
                        });
                        else {
                            FoxUI.toast.show(t.result.message);
                            var e = v.type ? "立即找回": "立即注册";
                            $("#btnSubmit").html(e).removeAttr("stop")
                        }
                    },
                    !1, !0)
                } else FoxUI.toast.show("两次密码输入不一致");
                else FoxUI.toast.show("请输入5位数字验证码");
                else FoxUI.toast.show("请输入11位手机号码")
            })
        },
        initBind: function(e) {
            v.endtime = e.endtime,
            v.backurl = e.backurl,
            v.imgcode = e.imgcode || 0,
            0 < v.endtime && (v.seconds = v.endtime, v.verifycode()),
            $("#btnCode").click(function() {
                $("#btnCode").hasClass("disabled") || ($("#mobile").isMobile() ? $.trim($("#verifycode2").val()) || 1 != v.imgcode ? (v.seconds = 60, h.json("account/verifycode", {
                    mobile: $("#mobile").val(),
                    temp: "sms_bind",
                    imgcode: $.trim($("#verifycode2").val()) || 0
                },
                function(t) {
                    1 != t.status && (FoxUI.toast.show(t.result.message), $("#btnCode").html("获取验证码").removeClass("disabled").removeAttr("disabled")),
                    1 == t.status && v.verifycode()
                },
                !1, !0)) : FoxUI.toast.show("请输入图形验证码") : FoxUI.toast.show("请输入11位手机号码!"))
            }),
            $("#btnSubmit").click(function() {
                $("#btnSubmit").attr("stop") || ($("#mobile").isMobile() ? $("#verifycode").isInt() && 5 == $("#verifycode").len() ? $("#pwd").isEmpty() ? FoxUI.toast.show("请输入登录密码") : $("#pwd1").isEmpty() ? FoxUI.toast.show("请重复输入密码") : $("#pwd").val() === $("#pwd1").val() ? ($("#btnSubmit").html("正在绑定...").attr("stop", 1), h.json("member/bind", {
                    mobile: $("#mobile").val(),
                    verifycode: $("#verifycode").val(),
                    pwd: $("#pwd").val()
                },
                function(t) {
                    if (0 == t.status) return FoxUI.toast.show(t.result.message),
                    void $("#btnSubmit").html("立即绑定").removeAttr("stop");
                    t.status < 0 ? FoxUI.confirm(t.result.message, "注意",
                    function() {
                        h.json("member/bind", {
                            mobile: $("#mobile").val(),
                            verifycode: $("#verifycode").val(),
                            pwd: $("#pwd").val(),
                            confirm: 1
                        },
                        function(t) {
                            1 != t.status ? (FoxUI.toast.show(t.result.message), $("#btnSubmit").html("立即绑定").removeAttr("stop")) : FoxUI.alert("绑定成功!", "",
                            function() {
                                location.href = e.backurl ? atob(e.backurl) : h.getUrl("member")
                            })
                        },
                        !0, !0)
                    },
                    function() {
                        $("#btnSubmit").html("立即绑定").removeAttr("stop")
                    }) : FoxUI.alert("绑定成功!", "",
                    function() {
                        location.href = e.backurl ? atob(e.backurl) : h.getUrl("member")
                    })
                },
                !0, !0)) : FoxUI.toast.show("两次密码输入不一致") : FoxUI.toast.show("请输入5位数字验证码") : FoxUI.toast.show("请输入11位手机号码"))
            }),
            $("#btnCode2").click(function() {
                return $(this).prop("src", "../web/index.php?c=utility&a=code&r=" + Math.round((new Date).getTime())),
                !1
            })
        },
        initChange: function(t) {
            v.endtime = t.endtime,
            v.imgcode = t.imgcode,
            0 < v.endtime && (v.seconds = v.endtime, v.verifycode()),
            $("#btnCode").click(function() {
                $("#btnCode").hasClass("disabled") || ($("#mobile").isMobile() ? $.trim($("#verifycode2").val()) || 1 != v.imgcode ? (v.seconds = 60, h.json("account/verifycode", {
                    mobile: $("#mobile").val(),
                    temp: "sms_changepwd",
                    imgcode: $.trim($("#verifycode2").val()) || 0
                },
                function(t) {
                    1 != t.status && (FoxUI.toast.show(t.result.message), $("#btnCode").html("获取验证码").removeClass("disabled").removeAttr("disabled")),
                    1 == t.status && v.verifycode()
                },
                !1, !0)) : FoxUI.toast.show("请输入图形验证码") : FoxUI.toast.show("请输入11位手机号码"))
            }),
            $("#btnSubmit").click(function() {
                $("#btnSubmit").attr("stop") || ($("#mobile").isMobile() ? $("#verifycode").isInt() && 5 == $("#verifycode").len() ? $("#pwd").isEmpty() ? FoxUI.toast.show("请输入登录密码") : $("#pwd1").isEmpty() ? FoxUI.toast.show("请重复输入密码") : $("#pwd").val() === $("#pwd1").val() ? ($("#btnSubmit").html("正在修改...").attr("stop", 1), h.json("member/changepwd", {
                    mobile: $("#mobile").val(),
                    verifycode: $("#verifycode").val(),
                    pwd: $("#pwd").val()
                },
                function(t) {
                    if (1 != t.status) return FoxUI.toast.show(t.result.message),
                    void $("#btnSubmit").html("立即修改").removeAttr("stop");
                    FoxUI.alert("修改成功", "",
                    function() {
                        location.href = h.getUrl("member")
                    })
                },
                !1, !0)) : FoxUI.toast.show("两次密码输入不一致") : FoxUI.toast.show("请输入5位数字验证码") : FoxUI.toast.show("请输入11位手机号码"))
            }),
            $("#btnCode2").click(function() {
                return $(this).prop("src", "../web/index.php?c=utility&a=code&r=" + Math.round((new Date).getTime())),
                !1
            })
        },
        initQuick: function(u) {
            var t = $("#account-layer"),
            i = "登录",
            e = "为了您能及时接收到物流信息<br>请绑定手机号后购买",
            o = "注册",
            n = "填写个人信息",
            m = "请输入密码",
            s = "请设置登录密码",
            a = "请设置登录密码",
            b = new FoxUIModal({
                content: t.html(),
                extraClass: "popup-modal"
            });
            $(".account-close", b.container).unbind("click").click(function() {
                b.close()
            }),
            $(".account-layer", b.container).addClass(u.action);
            var r = "bind" == u.action ? e: i;
            $(".account-title", b.container).html(r),
            $(".input-password", b.container).attr("placeholder", "bind" == u.action ? s: m),
            0 < u.endtime ? (v.seconds = u.endtime, v.verifycode()) : $("#btnCode").removeClass("disabled"),
            1 == u.imgcode && $(".account-layer", b.container).addClass("imgcode"),
            "bind" == u.action ? h.json("member/bind/getbindinfo", {},
            function(t) {
                v.nohasbindinfo = t.result.nohasbindinfo,
                v.bindrealname = t.result.bindrealname,
                v.bindbirthday = t.result.bindbirthday,
                v.bindidnumber = t.result.bindidnumber,
                v.bindwechat = t.result.bindwechat,
                $(".input-password", b.container).show(),
                1 == v.nohasbindinfo && ($(".account-next", b.container).hide(), $(".account-btn", b.container).show(), $(".account-btn", b.container).text("绑定")),
                b.show()
            },
            !1, !1) : b.show(),
            $(".account-btn", b.container).unbind("click").click(function() {
                var e = $(this);
                if (e.attr("stop")) FoxUI.toast.show("操作中...");
                else {
                    var o = $.trim($(".input-mobile", b.container).val());
                    if (o && "" != o) if ($.isMobile(o)) {
                        if ("login" == u.action) {
                            if (! (l = $.trim($(".input-password", b.container).val())) || "" == l) return void FoxUI.toast.show("请填写密码");
                            e.text("登录中...").attr("stop", 1),
                            h.json("account/login", {
                                mobile: o,
                                pwd: l
                            },
                            function(t) {
                                if (1 != t.status) return FoxUI.toast.show(t.result.message),
                                void e.text("登录").removeAttr("stop");
                                b.close(),
                                FoxUI.loader.show("登录成功", "icon icon-check"),
                                setTimeout(function() {
                                    FoxUI.loader.hide(),
                                    u.success && u.success()
                                },
                                500)
                            },
                            !1, !0)
                        } else if ("bind" == u.action) {
                            if (! (d = $.trim($(".input-verify", b.container).val())) || "" == d) return void FoxUI.toast.show("请填写验证码");
                            if (!v.codeLen(d)) return void FoxUI.toast.show("请填写5位验证码");
                            if (! (l = $.trim($(".input-password", b.container).val())) || "" == l) return void FoxUI.toast.show("请填写密码");
                            if (!v.strLen(l)) return void FoxUI.toast.show("密码至少6位");
                            var i = "",
                            n = 0,
                            s = 0,
                            a = 0,
                            r = "",
                            c = "";
                            if (1 == v.bindrealname && (!(i = $.trim($(".input-bindrealname", b.container).val())) || "" == i)) return void FoxUI.toast.show("请填写真实姓名");
                            if (1 == v.bindbirthday) {
                                if (null == (a = $.trim($(".input-bindbirthday", b.container).val())) || "" == a) return void FoxUI.toast.show("请选择出生日期");
                                a = a.split("-");
                                n = a[0],
                                s = a[1],
                                a = a[2]
                            }
                            if (! (1 != v.bindidnumber || (r = $.trim($(".input-bindidnumber", b.container).val())) && "" != r && $(".input-bindidnumber", b.container).isIDCard())) return void FoxUI.toast.show("请填写正确身份证号码");
                            if (1 == v.bindwechat && (!(c = $.trim($(".input-bindwechat", b.container).val())) || "" == c)) return void FoxUI.toast.show("请填写微信号");
                            e.text("绑定中...").attr("stop", 1),
                            h.json("member/bind", {
                                mobile: o,
                                verifycode: d,
                                pwd: l,
                                realname: i,
                                birthyear: n,
                                birthmonth: s,
                                birthday: a,
                                idnumber: r,
                                bindwechat: c
                            },
                            function(t) {
                                return 0 == t.status ? ("验证码错误或已过期" == t.result.message && $(".account-tip span", b.container).click(), FoxUI.toast.show(t.result.message), void e.html("绑定").removeAttr("stop")) : t.status < 0 ? (b.container.hide(), void FoxUI.confirm(t.result.message, "注意",
                                function() {
                                    h.json("member/bind", {
                                        mobile: o,
                                        verifycode: d,
                                        pwd: l,
                                        confirm: 1,
                                        realname: i,
                                        birthyear: n,
                                        birthmonth: s,
                                        birthday: a,
                                        idnumber: r,
                                        bindwechat: c
                                    },
                                    function(t) {
                                        if (1 == t.status) return b.close(),
                                        FoxUI.loader.show("绑定成功", "icon icon-check"),
                                        void setTimeout(function() {
                                            FoxUI.loader.hide(),
                                            u.success && u.success()
                                        },
                                        500);
                                        FoxUI.toast.show(t.result.message),
                                        e.html("绑定").removeAttr("stop")
                                    },
                                    !0, !0)
                                },
                                function() {
                                    e.html("绑定").removeAttr("stop"),
                                    b.container.show(),
                                    $(".fui-mask").remove(),
                                    FoxUI.mask.show()
                                })) : (b.close(), FoxUI.loader.show("绑定成功", "icon icon-check"), void setTimeout(function() {
                                    FoxUI.loader.hide(),
                                    u.success && u.success()
                                },
                                500))
                            },
                            !1, !0)
                        } else if ("reg" == u.action) {
                            var d, l;
                            if (! (d = $.trim($(".input-verify", b.container).val())) || "" == d) return void FoxUI.toast.show("请填写验证码");
                            if (!v.codeLen(d)) return void FoxUI.toast.show("请填写5位验证码");
                            if (! (l = $.trim($(".input-password", b.container).val())) || "" == l) return void FoxUI.toast.show("请填写密码");
                            if (!v.strLen(l)) return void FoxUI.toast.show("密码至少6位");
                            var t = $.trim($(".input-password2", b.container).val());
                            var agentid = $.trim($(".input-agentid", b.container).val());
                            if (!t || "" == t) return void FoxUI.toast.show("请重复填写密码");
                            if (l != t) return void FoxUI.toast.show("两次输入的密码不一致");
                            e.text("注册中...").attr("stop", 1),
                            h.json("account/register", {
                                mobile: o,
                                verifycode: d,
                                pwd: l,
                                agentid: agentid
                            },
                            function(t) {
                                if (1 != t.status) return FoxUI.toast.show(t.result.message),
                                e.text("注册").removeAttr("stop"),
                                void("验证码错误或已过期" == t.result.message && $(".account-layer", b.container).removeClass("reg-next").addClass("reg"));
                                FoxUI.toast.show("注册成功，请登录"),
                                $(".account-layer", b.container).removeClass("reg-next").addClass("login"),
                                u.action = "login",
                                $(".input-password", b.container).attr("placeholder", m),
                                e.text("登录").removeAttr("stop")
                            },
                            !1, !0)
                        }
                    } else FoxUI.toast.show("请填写正确手机号");
                    else FoxUI.toast.show("请填写手机号")
                }
            }),
            $(".btn-send", b.container).unbind("click").click(function() {
                var e = $(this);
                if (!e.hasClass("disabled")) {
                    var t = $.trim($(".input-mobile", b.container).val());
                    if (t && "" != t) {
                        var o = 0;
                        if (1 == u.imgcode) {
                            if (! (o = $.trim($(".input-image", b.container).val())) || "" == o) return void FoxUI.toast.show("请填写图形验证码");
                            if (!v.codeLen(o, !0)) return void FoxUI.toast.show("请填写4位图形验证码")
                        }
                        v.seconds = 60,
                        h.json("account/verifycode", {
                            mobile: t,
                            temp: "bind" == u.action ? "sms_bind": "sms_reg",
                            imgcode: o
                        },
                        function(t) {
                            1 != t.status && (FoxUI.toast.show(t.result.message), e.html("发送验证码").removeClass("disabled"), "此手机号已注册，请直接登录" == t.result.message && ($(".account-layer", b.container).removeClass("reg").addClass("login"), u.action = "login", $(".account-btn", b.container).text("登录").removeAttr("stop"), $(".account-title", b.container).html(i), $(".input-password", b.container).attr("placeholder", m))),
                            1 == t.status && (FoxUI.toast.show("发送成功"), v.verifycode())
                        },
                        !1, !0)
                    } else FoxUI.toast.show("请填写手机号")
                }
            }),
            $(".account-next", b.container).unbind("click").click(function() {
                if ($(this).attr("stop")) FoxUI.toast.show("操作中...");
                else {
                    var t = $.trim($(".input-mobile", b.container).val());
                    if (t && "" != t) if ($.isMobile(t)) {
                        var e = 0;
                        if (1 == u.imgcode) {
                            if (! (e = $.trim($(".input-image", b.container).val())) || "" == e) return void FoxUI.toast.show("请填写图形验证码");
                            if (!v.codeLen(e, !0)) return void FoxUI.toast.show("请填写4位图形验证码")
                        }
                        var o = $.trim($(".input-verify", b.container).val());
                        if (o && "" != o) if (v.codeLen(o)) if ("bind" == u.action) {
                            var i = $.trim($(".input-password", b.container).val());
                            if (!i || "" == i) return void FoxUI.toast.show("请填写密码");
                            if (!v.strLen(i)) return void FoxUI.toast.show("密码至少6位");
                            1 == v.bindrealname && $(".input-bindrealname", b.container).show(),
                            1 == v.bindbirthday && ($(".input-bindbirthday", b.container).show(), $(".input-bindbirthday", b.container).datePicker()),
                            1 == v.bindidnumber && $(".input-bindidnumber", b.container).show(),
                            1 == v.bindwechat && $(".input-bindwechat", b.container).show(),
                            $(".input-password", b.container).hide(),
                            $(".account-layer", b.container).removeClass("bind").addClass("bind-next"),
                            $(".account-title", b.container).html(n),
                            $(".account-btn", b.container).text("绑定")
                        } else "reg" == u.action && ($(".account-layer", b.container).removeClass("reg").addClass("reg-next"), $(".account-title", b.container).html(n), $(".account-btn", b.container).text("注册"));
                        else FoxUI.toast.show("请填写5位短信验证码");
                        else FoxUI.toast.show("请填写短信验证码")
                    } else FoxUI.toast.show("请填写正确手机号");
                    else FoxUI.toast.show("请填写手机号")
                }
            }),
            $(".account-tip span", b.container).unbind("click").click(function() {
                "login" == u.action && ($(".account-title", b.container).html(o), $(".account-layer", b.container).removeClass("login").addClass("reg"), $(".input-password", b.container).attr("placeholder", a), u.action = "reg")
            }),
            $(".account-back", b.container).unbind("click").click(function() {
                var t = $(".account-layer", b.container);
                t.hasClass("reg-next") ? ($(".account-layer", b.container).removeClass("reg-next").addClass("reg"), $(".account-title", b.container).html(o)) : t.hasClass("reg") ? ($(".account-layer", b.container).removeClass("reg").addClass("login"), $(".account-title", b.container).html(i), $(".input-password", b.container).attr("placeholder", m), u.action = "login") : t.hasClass("bind-next") && ($(".account-layer", b.container).removeClass("bind-next").addClass("bind"), $(".account-title", b.container).html(e), $(".input-password", b.container).show(), $(".input-bindrealname", b.container).hide(), $(".input-bindbirthday", b.container).hide(), $(".input-bindidnumber", b.container).hide(), $(".input-bindwechat", b.container).hide())
            }),
            $(".btn-image", b.container).unbind("click").click(function() {
                return $(this).prop("src", "../web/index.php?c=utility&a=code&r=" + Math.round((new Date).getTime())),
                !1
            })
        },
        codeLen: function(t, e) {
            return e ? "" !== $.trim(t) && /^\d{4}$/.test($.trim(t)) : "" !== $.trim(t) && /^\d{5}$/.test($.trim(t))
        },
        strLen: function(t) {
            return "" !== $.trim(t) && /^.{6,}$/.test($.trim(t))
        }
    };
    return v
});