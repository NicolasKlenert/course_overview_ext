



M.block_course_overview_ext = M.block_course_overview_ext || {
		//M.block_course_overview_ext.superclass.constructor.call(this,Y);
}

M.block_course_overview_ext.pop = function(Y, id, cid) {
    Y.use('anim', function(Y) {
        new M.block_course_overview_ext.PopUp(Y, id, cid);
    });
};

M.block_course_overview_ext.PopUp = function(Y, id, cid) {
    this.div = Y.one('#'+id);
//    this.div.one("a").setAttribute('href','#');
//    this.div.one("a").removeAttribute('href');
    //not necessary, because we prevent the default behaviour

    this.div.on('click',function(e){
    	 e.stopPropagation();
    	 e.preventDefault();
//    	this.one('.co_popup').toggleView();		
    	
    	function setMaxIndex(o){
    		var list = Y.all('.co_popup');
    		var max = 100;
    		list.each(function(node){
    			if(max < parseInt(node.getStyle('z-index'))){
    				max = parseInt(node.getStyle('z-index'));
    			}
    		});
    		o.setStyle('z-index',max+1);
    	}
    	 
    	 function closeTilePops(){
    		 var list = Y.one('#course-'+cid).all('.co_popup');
    		 list.each(function(node){
     			closePop(node);
     		});
    	 }
    	 
    	 function closePop(o){
    		 o.replaceClass('visible','invisible');
    		 o.setStyle('z-index',100);
    	 }
    	
    	if(this.one('.co_popup').hasClass('visible')){
    		closePop(this.one('.co_popup'));
    	}else{
    		closeTilePops();
    		this.one('.co_popup').replaceClass('invisible','visible');
    		setMaxIndex(this.one('.co_popup'));	
    	}
    });
}

M.block_course_overview_ext.PopUp.prototype.div = null;

M.block_course_overview_ext.resetPop = function(Y) {
	var list = Y.all('.co_popup');
	Y.one('body').on('click',function(e){
		list.each(function(node){
			node.replaceClass('visible','invisible');
			node.setStyle('z-index',100);
		});
	});	
}

//id of the color, color as #000000 and cid ist the id of the target
M.block_course_overview_ext.isColor = function(Y, id , color, cid, button) {
	this.div = Y.one('#'+id);
//	this.color = color;
//	this.target = Y.one('#'+cid);
	if(Y.one(button)){
		this.div.on('click', function(e) {
			Y.one('#'+cid).setStyle('backgroundColor',color);
			Y.one(button).removeClass('btn-disabled');
			},this);
	}else{
		this.div.on('click', function(e) {
			Y.one('#'+cid).setStyle('backgroundColor',color);
			//closing the popup menu of the colors is not needed because popup does this automaticly
//			this.ancestor('.co_popup').replaceClass('visible','invisible');
//			this.ancestor('.co_popup').setStyle('z-index',100);
			},this);
	}
	
	
	}

M.block_course_overview_ext.isColor.prototype.div = null;

M.block_course_overview_ext.saveColor = function(Y, button, notification) {
		
	Y.one(button +' input').on('click', function(e) {
		
    	 e.preventDefault();
    	 
		//Y = M.block_course_overview_ext.Y;
		var courselist = Y.one('.course_list').get('children').getAttribute('id');
		var colorlist = Y.one('.course_list').get('children').getStyle('backgroundColor');
		for (var i = 0; i < courselist.length; i++) {
			courselist[i] = courselist[i].substring(7);
			
			var rgbString = colorlist[i]; //is always in rgb format, dont know why (example:rgb(0, 70, 255))

			var parts = rgbString.match(/^rgb\((\d+),\s*(\d+),\s*(\d+)\)$/);
			// parts now should be ["rgb(0, 70, 255", "0", "70", "255"]

			delete (parts[0]);
			for (var j = 1; j <= 3; ++j) {
			    parts[j] = parseInt(parts[j]).toString(16);
			    if (parts[j].length == 1) parts[j] = '0' + parts[j];
			} 
			colorlist[i] = '#' + parts.join('').toUpperCase(); // "#0070FF"
		}
		
		var params = {
	        sesskey : M.cfg.sesskey,
	        courselist : courselist,
	        colorlist : colorlist
	    };
	    Y.io(M.cfg.wwwroot+'/blocks/course_overview_ext/color_save.php', {
	        method: 'POST',
	        data: build_querystring(params),
	        context: this
	    });
	    
	    if(notification){
	    	//Änder das html, um anzuzeigen, das alles gespeichert wurde (mid-screen)
		    new M.block_course_overview_ext.fadeOut(Y, notification);
	    }
	    
	    //Änder den Button selbst -> not clickable
	    Y.one(button).addClass('btn-disabled');
	    
	});
	
}

M.block_course_overview_ext.setColors = function(Y, courses, colors) {
	for (var i = 0; i < courses.length; i++) {
		Y.one('#course-'+courses[i]).setStyle('backgroundColor',colors[i]);
	}
}

M.block_course_overview_ext.fadeOut = function(Y,notification){
	YUI().use('transition',function (Y){
		 var notebox = Y.one(notification);
		    if(notebox){
		    	
		    	notebox.replaceClass('invisible','visible');
		    	
		    	notebox.transition({
		    		easing: 'ease-out',
		    		duration: 4,
		    		opacity: 0
		    	},function(){
		    		this.replaceClass('visible','invisible');
		    		this.setStyle('opacity',1);
		    	});
		    	
		    }
	});
}