/**
 * 导航栏
 */
!function(window) {
	var _nav = new Array();
	var commonNav = function(options){
		_nav[_nav.length]='<nav id="nav" class="nav">';
		_nav[_nav.length]='<a href="javascript:void(0);" class="nav-back"></a>';
		_nav[_nav.length]='<span id="title" class="nav-title">'+options.title+'</span>';
		if(options.right){
			_nav[_nav.length]='<a href="'+options.right.href+'" class="nav-right">'+options.right.text+'</a>';
		}
		_nav[_nav.length]='</nav>';
		$("body").prepend(_nav.join(""));
		//默认为回退
		$("#nav .nav-back").on('click',function(){
			if(options.fn){
				options.fn();
			}else{
				history.back();
			}
		})
	};
	var priceNav = function(options){
		_nav[_nav.length]='<nav id="nav" class="nav nav2">';
		if(options.left){
			_nav[_nav.length]='<a href="javascript:void(0);" class="nav-back"></a>';
		}
		_nav[_nav.length]='<span id="title" class="nav-title">'+options.title+'</span>';
		if(options.right){
			_nav[_nav.length]='<a href="'+options.right.href+'" class="nav-right">'+options.right.text+'</a>';
		}
		_nav[_nav.length]='</nav>';
		$("body").prepend(_nav.join(""));
		if($("#nav .nav-back").length > 0){
			$("#nav .nav-back").on('click',function(){
				if(options.fn){
					options.fn();
				}else{
					history.back();
				}
			})
		}
	}
	if (typeof define == 'function' && define.amd){
    	define('common/nav', function() { // 返回对象
			return {
				commonNav: function(options) {
					commonNav(options);
				},
				priceNav:function(options) {
					priceNav(options);
				}
			}
		})
	}else{
    	window.commonNav = commonNav;
    	window.priceNav = priceNav;
	}
}(window);