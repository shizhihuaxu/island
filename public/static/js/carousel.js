;(function($){
var myCarousel=function(obj){

	// 保存操作对象
	this.obj 			= obj;
	this.list   		= this.obj.find(".carousel-list");//幻灯片包裹元素
	this.items 			= this.obj.find(".carousel-item");//图片列表
	// 偶数帧解决方案 将第一帧克隆放在左边第一个
	if(this.items.length%2==0){
		this.items.eq(this.items.length/2).after(this.items.eq(0).clone());
		this.items = this.list.children();//图片列表
	}
	this.prev_next_btn	= this.obj.find(".prev-next-btn");
	this.prev_btn       = this.obj.find(".prev");//前一个 按钮
	this.next_btn       = this.obj.find(".next");//下一个 按钮
	this.firstItem		= this.obj.find(".carousel-item").first();//第一帧
	this.lastItem		= this.obj.find(".carousel-item").last();//最后一帧
	this.len 			= this.items.length;//列表长度
	this.rotateFlag		= true;//解决迅速点击时的bug 开始执行时为允许执行函数

	var _this=this;
	

	//默认配置参数 
	this.setting={
		"height"    : 250,//幻灯片高度
		"width"     : 1010,//幻灯片宽度
		"imgHeight" : 250,//第一帧高度
		"imgWidth"  : 1010,//第一帧宽度
		"verticalAlign" : "middle",//幻灯片位置效果
		"scale"     : 0.9,//缩放比例
		"speed"     : 500,//切换速度
		"autoPlay":false,//是否自动播放
		"delay":500//自动播放速度
	}
	// 重置配置参数
	$.extend(this.setting,this.getSetting())

	// 初始化页面配置
	this.setSettingValue();
	this.setItemPos();

	// 切换按钮的事件
	this.prev_btn.click(function(){
		//解决迅速点击时的bug 点击事件结束时设置为true 允许下一次点击 否则为false
		if(_this.rotateFlag){
			_this.rotateFlag=false;
			_this.carouselRotate("right");//右旋转
		}
	});
	this.next_btn.click(function(){
		if(_this.rotateFlag){
			_this.rotateFlag=false;
			_this.carouselRotate("left");//左旋转
		}
	});
	// 判断是否为自动播放
	if(this.setting.autoPlay){
		var _this=this;

		this.autoPlay();

		this.list.hover(function(){
			clearInterval(_this.timer);//清除自动播放定时器
		},function(){
			_this.timer=window.setInterval(function(){
				// 执行左旋转的按钮就行了
				_this.next_btn.click();

			},_this.setting.delay);
		})
	}

}
// 初始化
myCarousel.init=function(objs){
	var _self=this;
	objs.each(function(){
		new _self($(this));
	})

}
// 扩展对象
myCarousel.prototype={
	// 获取人工配置参数
	getSetting : function(){
		var setting=this.obj.attr("data-setting");
		if(setting && setting!==""){
			return $.parseJSON(setting);
		}else{
			return {};
		}		
	},
	// 将配置参数添加到对象上
	setSettingValue : function(){
		// 计算切换按钮的宽度
		var btnWidth=(this.setting.width-this.setting.imgWidth)/2;

		this.obj.css({
			height : this.setting.height,
			width  : this.setting.width

		});

		this.list.css({
			height : this.setting.height,
			width  : this.setting.width
		});

		// 切换按钮的样式
		this.prev_next_btn.css({
			width  : btnWidth,
			height : this.setting.height,
			zIndex : Math.ceil(this.len/2)
		});

		// 设置第一帧位置
		this.firstItem.css({
			height : this.setting.imgHeight,
			width  : this.setting.imgWidth,
			left   : btnWidth,
			zIndex : Math.ceil(this.len/2)
		});

	},
	// 设置除第一帧外的其它帧的位置关系
	setItemPos : function(){

		var _self	   = this,
			restItems  = this.items.slice(1),
			len 	   = restItems.length,
			seperater  = Math.ceil(len/2),
			leftItems  = restItems.slice(seperater),
			rightItems = restItems.slice(0,seperater),
			level	   = Math.ceil(this.len/2);

		// 右边帧相关变量
		var rw		  = this.setting.imgWidth,
			rh  	  = this.setting.imgHeight,
			gap 	  = ((this.setting.width - this.setting.imgWidth)/2)/level, //图片间间隙	
			firstLeft = (this.setting.width-this.setting.imgWidth)/2,
			offset	  = firstLeft + rw;

		// 设置右边帧的位置
		rightItems.each(function(i){

			level--;
			rw*= _self.setting.scale;
			rh*=_self.setting.scale;

			$(this).css({
				zIndex  : level,
				width   : rw,
				height  : rh,
				left    : offset+(++i)*gap-rw,//若设置透明度时使用了++i 那么这里的i已经是自增过的了
				top		: _self.setVerticalAlign(rh)//(_self.setting.height-rh)/2

			})

		});

		// 左边帧相关变量
		var lw	= rightItems.last().width(),
			lh  = rightItems.last().height();

		// 左边帧位置
		leftItems.each(function(i){

			$(this).css({
				zIndex  : level,//这个level初始值是右侧最后一帧的值
				width   : lw,
				height  : lh,
				left    : ++i*gap,
				top		: _self.setVerticalAlign(lh)//(_self.setting.height-lh)/2

			});

			level++;
			lw/= _self.setting.scale;
			lh/= _self.setting.scale;
		});

	},
	// 设置垂直排列方式
	setVerticalAlign:function(itemHeight){
		var type = this.setting.verticalAlign,
			top  = 0;

		switch(type){
			case "top"    : top = 0;break;
			case "middle" : top = (this.setting.height - itemHeight)/2;break;
			case "bottom" : top= this.setting.height - itemHeight;break;
			default 	  : top = (this.setting.height - itemHeight)/2;
		}

		return top;
	},
	// 按钮切换
	carouselRotate:function(dir){
		var _this=this,
			zIndexArr=[];

		if(dir==="left"){//左旋转

			_this.items.each(function(){
				var _self   = $(this),
					prevImg = _self.prev().get(0) ? _self.prev() : _this.lastItem,//找到上一帧 若不存在则为最后一帧
					width   = prevImg.width(),
					height  = prevImg.height(),
					zIndex  = prevImg.css("zIndex"),
					left 	= prevImg.css("left"),
					top 	= prevImg.css("top");

				// 设置过渡
				_self.animate({
					width   : width,
					height  : height,
					left    : left,
					top     : top				
				},_this.setting.speed,function(){
					_this.rotateFlag=true;//优化点击事件
				});

				zIndexArr.push(zIndex);
			});
			// 单独设置层级 以便先显示 再置换层级 优化体验
			_this.items.each(function(i){
				$(this).css("zIndex",zIndexArr[i]);
			});

		}else if(dir==="right"){
			_this.items.each(function(){
				var _self   = $(this),
					nextImg = _self.next().get(0) ? _self.next() : _this.firstItem,//找到下一帧 若不存在则为第一帧
					width   =nextImg.width(),
					height  =nextImg.height(),
					zIndex  =nextImg.css("zIndex"),
					left 	=nextImg.css("left"),
					top 	=nextImg.css("top");

				// 设置过渡
				_self.animate({
					width   : width,
					height  : height,
					left    : left,
					top     : top				
				},_this.setting.speed,function(){
					_this.rotateFlag=true;//优化点击事件
				});

				zIndexArr.push(zIndex);
			});
			_this.items.each(function(i){
				$(this).css("zIndex",zIndexArr[i]);
			});
		}
	},
	// 自动执行轮播
	autoPlay:function(){
		var _self = this;
		_self.timer=window.setInterval(function(){
			// 执行左旋转的按钮就行了
			_self.next_btn.click();

		},this.setting.delay);
	}

}	
// 因为是在闭包内 需要添加到全局变量以供外部使用
window["myCarousel"]=myCarousel;

})(jQuery);
