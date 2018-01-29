$(function() {
	$(".partkake, .take_now a").click(function(){
		$(".input_box").show();
		$(".ib_con").animate({bottom:"0"},200);
	});
	$(".input_box").click(function(){
		$(".ib_con").animate({bottom:"-267px"},200,function(){
			$(".input_box").hide();
		});
	});
	$(".ib_con").click(function(){
		return false;
	});
	$(".dib_1").click(function(){
		$(".cal_box").show();
	});
	$(".dis_none, .cal_box").click(function(){
		$(".cal_box").hide();
	});
	$(".cb_con,.tb_con").click(function(){
		return false;
	})
	$(".li_play").click(function(){
		$(".take_box").show();
	});
	$(".dis_none, .take_box").click(function(){
		$(".take_box").hide();
	});
	$(".li_dl").click(function(){
		$(".del_box").show();
	});
	$(".dis_none, .del_box").click(function(){
		$(".del_box").hide();
	});
	$(".tbox, .close").click(function(){
		$(".tbox").css("display","none");
		$(".dc_fee").css("display","none");
		$(".dc_w").css("display","none");
		$(".dg_fee").css("display","none");
		$(".dg_w").css("display","none");
	});
	// document.body.addEventListener('touchstart', function () {});   //...空函数即可 解决ios active状态失效问题
});

function vkBehavior($vk,$ipt,funSubmit){
	$vk.on("click",function(){
		// var $ipt = $("#buy_count");
		var str = $ipt.html();
		var max = 99999;

		switch ($(this).html()) {
			case "确定":
				if(parseInt(str) > 0 && parseInt(str) < max){
					if($.isFunction(funSubmit)){  
						funSubmit();
					}
				}
				break;
			case "删除":
				if(str != "0"){
					var t = str.substring(0,str.length-1);
					if(t == ""){
						$ipt.html("0");
					}
					else{
						$ipt.html(t);
					}
				}
				break;	
			case "+1":
				if(parseInt(str) >= max){
					$ipt.html(max);
				}
				else{
					$ipt.html(parseInt(str)+1);
				}
				break;	
			case "-1":
				if(parseInt(str) > 0){
					$ipt.html(parseInt(str)-1);
				}
				break;					
			default:
				if($ipt.html() == "0"){
					$ipt.html('');	
				}

				var total = $ipt.html() + $(this).html()

				if(parseInt(total) >= max){
					$ipt.html(max);
				}
				else{
					$ipt.html(total);
				}
				break;
		}
	});
}
