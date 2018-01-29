    $.extend($.fn, {
        fnTimeCountDown: function(d, isFinishing) {
            this.each(function() {
                var $this = $(this);
                var server_now = $this.data("now");
                var offset = new Date().getTime() - server_now;
                console.log('offset ' + offset);
                var o = {
                    hm: $this.find(".hm"),
                    sec: $this.find(".sec"),
                    mini: $this.find(".mini"),
                    hour: $this.find(".hour"),
                    day: $this.find(".day"),
                    month: $this.find(".month"),
                    year: $this.find(".year")
                };
                var f = {
                    haomiao: function(n) {
                        if (n < 10) return "00" + n.toString();
                        if (n < 100) return "0" + n.toString();
                        return n.toString();
                    },
                    zero: function(n) {
                        var _n = parseInt(n, 10); //解析字符串,返回整数
                        if (_n > 0) {
                            if (_n <= 9) {
                                _n = "0" + _n
                            }
                            return String(_n);
                        } else {
                            return "00";
                        }
                    },
                    dv: function() {
                        // server_now += 1;
                        //d = d || Date.UTC(2050, 0, 1); //如果未定义时间，则我们设定倒计时日期是2050年1月1日
                        var _d = $this.data("end") || d;
                        var diff = new Date().getTime() - server_now;
                        var now = server_now + diff ,endDate = _d;//new Date(_d);
                        // console.log(offset);
                        //现在将来秒差值
                        //alert(future.getTimezoneOffset());
                        var dur = (endDate - now) / 1000,
                            mss = endDate - now,
                            pms = {
                                hm: "000",
                                sec: "00",
                                mini: "00",
                                hour: "00",
                                day: "00",
                                month: "00",
                                year: "0"
                            };
                        if (mss > 0) {
                            pms.hm = f.haomiao(mss % 1000);
                            pms.sec = f.zero(dur % 60);
                            pms.mini = Math.floor((dur / 60)) > 0 ? f.zero(Math.floor((dur / 60)) % 60) : "00";
                            pms.hour = Math.floor((dur / 3600)) > 0 ? f.zero(Math.floor((dur / 3600)) % 24) : "00";
                            pms.day = Math.floor((dur / 86400)) > 0 ? f.zero(Math.floor((dur / 86400)) % 30) : "00";
                            //月份，以实际平均每月秒数计算
                            pms.month = Math.floor((dur / 2629744)) > 0 ? f.zero(Math.floor((dur / 2629744)) % 12) : "00";
                            //年份，按按回归年365天5时48分46秒算
                            pms.year = Math.floor((dur / 31556926)) > 0 ? Math.floor((dur / 31556926)) : "0";
                        } else {
                            pms.year = pms.month = pms.day = pms.hour = pms.mini = pms.sec = "00";
                            pms.hm = "000";
                            $this.find(".hm").html("000");
                            //alert('结束了');
                            if($.isFunction(isFinishing)){  
                                isFinishing();
                            }
                            clearTimeout(f.ui);
                            clearTimeout(f.serverTime);
                            return;
                        }
                        return pms;
                    },
                    ui: function() {
                        var dv = f.dv();
                        if(dv !== undefined){
                            if (o.hm) {
                                o.hm.html(f.dv().hm);
                            }
                            if (o.sec) {
                                o.sec.html(f.dv().sec);
                            }
                            if (o.mini) {
                                o.mini.html(f.dv().mini);
                            }
                            if (o.hour) {
                                o.hour.html(f.dv().hour);
                            }
                            if (o.day) {
                                o.day.html(f.dv().day);
                            }
                            if (o.month) {
                                o.month.html(f.dv().month);
                            }
                            if (o.year) {
                                o.year.html(f.dv().year);
                            }
                            setTimeout(f.ui, 1);
                        }
                    },
                    serverTime:function(){
                        server_now = server_now + 1000;
                        setTimeout(f.serverTime, 1000);
                    }
                };
                f.ui();
                f.serverTime();
            });
        }
    });