/**
 * @license Copyright (c) 2003-2016, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.md or http://ckeditor.com/license
 */

CKEDITOR.editorConfig = function( config ) {
	// Define changes to default configuration here. For example:
	// config.language = 'fr';
	config.uiColor = '#fcfcfc';
	config.height = 384;
	config.baseFloatZIndex = 10;
	config.startupOutlineBlocks = false;
	config.resize_enabled = false;
	config.format_tags = 'p;h1;h2;h3;pre';
	config.entities = false;
	config.htmlEncodeOutput = false;
	config.extraPlugins = 'codesnippet';
	config.image_previewText=' '; //预览区域显示内容
	config.removeDialogTabs = "image:advanced;image:Link;";//去掉上传图片时超链接和高级这两个选项卡 
	config.autoUpdateElement = true;//当提交包含有此编辑器的表单时，是否自动更新元素内的数据
	config.tabSpaces = 4;  //tab键
	//自定义菜单栏
	config.toolbar = 'Full';
 
	config.toolbar_Full =
	[
	    { name: 'document', items : ['Save','DocProps'] },
	    { name: 'insert', items : [ 'Image','Table','HorizontalRule','SpecialChar'] },
	    { name: 'clipboard', items : [ 'Cut','Copy','Paste','PasteText','-','Undo','Redo' ] },
	    { name: 'editing', items : [ 'Find','Replace','-','SelectAll'] },
	    { name: 'basicstyles', items : [ 'Bold','Italic','Underline','Strike','Subscript','Superscript','-','RemoveFormat' ] },
	    { name: 'paragraph', items : [ 'NumberedList','BulletedList','-','Outdent','Indent','-',
	    '-','JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock'] },
	    { name: 'tools', items : [ 'Maximize'] },
	    { name: 'links', items : [ 'Link','Unlink'] },
	    { name: 'colors', items : [ 'TextColor','BGColor' ] },
	    { name: 'styles', items : [ 'Styles','Format','FontSize' ] }
	];

	config.toolbar_Basic =
	[
	    ['Bold', 'Italic', '-', 'NumberedList', 'BulletedList', '-', 'Link', 'Unlink','-','About']
	];

	config.keystrokes = [
		[ CKEDITOR.ALT + 121 /*F10*/, 'toolbarFocus' ], //获取焦点
		[ CKEDITOR.ALT + 122 /*F11*/, 'elementsPathFocus' ], //元素焦点
		[ CKEDITOR.SHIFT + 121 /*F10*/, 'contextMenu' ], //文本菜单
		[ CKEDITOR.CTRL + 90 /*Z*/, 'undo' ], //撤销
		[ CKEDITOR.CTRL + 89 /*Y*/, 'redo' ], //重做
		[ CKEDITOR.CTRL + CKEDITOR.SHIFT + 90 /*Z*/, 'redo' ], //
		[ CKEDITOR.CTRL + 76 /*L*/, 'link' ], //链接
		[ CKEDITOR.CTRL + 66 /*B*/, 'bold' ], //粗体
		[ CKEDITOR.CTRL + 73 /*I*/, 'italic' ], //斜体
		[ CKEDITOR.CTRL + 85 /*U*/, 'underline' ], //下划线
		[ CKEDITOR.ALT + 109 /*-*/, 'toolbarCollapse' ]
	] 
};
