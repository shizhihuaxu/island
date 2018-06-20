;(function($){
//  公共方法

/**
  [navShowHide  导航栏悬浮效果]

 */
(function navShowHide(){
	var initTop = 0;

	$(window).scroll(function(){
		var scrollTop = $(document).scrollTop();

		if(scrollTop > initTop){
			$(".head").addClass("head-scroll");
			//滚动到顶部按钮显示
	   		$(".back-top").fadeIn();
		}else{
			if($(".head").offset().top==0){
				$(".head").removeClass("head-scroll");
				//滚动到顶部按钮隐藏
	   		$(".back-top").fadeOut();
			}else{
				$(".head").addClass("head-scroll");
				//滚动到顶部按钮显示
	   			$(".back-top").fadeIn();
			}
		}
		
		initTop = scrollTop;
	})
})();

// 工具类函数部分

var tools={
	/**
	 *  [ lazyLoad    可视区域加载]
		@param  function handler [滚动到底部执行的回调函数] 
	 */
	lazyLoad:function(handler){
		var winH 	= $(window).height(); 			//页面可视区域高度

		$(window).bind("scroll",function(){
			pageH 	= $(document).height(),  	//内容高度
	   		scrollT = $(window).scrollTop(); 		//滚动条top 
	   	
	   		if(winH + scrollT == pageH){

	   			if(handler instanceof Function){
		   			handler();
		   		}
	   		}
	    }); 
	},
	/**
	 *  [ warterfall    瀑布流]
		@param  parent  瀑布流父元素(列表包裹元素)选择器
		@param  elem    瀑布流子元素(每一项)选择器 
	 */
	warterfall:function(parent,elem){

	    var iW = $(elem).eq(0).width();						// 一个元素的宽
	    var num = Math.floor($(parent).width() / iW );		//每行中能容纳的元素的个数【窗口宽度除以一个元素的宽度】
	    var iHArr=[];										//用于存储 每列中的所有块框相加的高度。

	    $(elem).each( function( index, value ){

	        var iH = $(elem).eq(index).height();			//每元素的高度
	        if( index < num ){
	            iHArr[index] = iH; 							//第一行中的num个元素 先添加进数组iHArr
	        }else{
	            var minH = Math.min.apply( null, iHArr );	//数组iHArr中的最小值minH
	            var minHIndex = $.inArray( minH, iHArr );
	           
	            $(value).css({
	                'position': 'absolute',
	                'top': minH+10,
	                'left': $(elem).eq( minHIndex ).position().left
	            });
	            //数组 最小高元素的高 + 添加上的元素块框高
	            iHArr[minHIndex] += $(elem).eq(index).height()+10;//更新添加了块框后的列高
	        }
	    });

	    var maxH =Math.max.apply(null,iHArr);		//重置父元素高度
		parent.css("height",maxH+10);
	}

}

window["tools"] = tools;

})(jQuery);
