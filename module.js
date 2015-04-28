M.block_course_overview_ext = M.block_course_overview_ext || {
		//M.block_course_overview_ext.superclass.constructor.call(this,Y);
}

M.block_course_overview_ext.tiles = {
	init: function() {
		M.block_course_overview_ext.Y = Y;
	  
		window.addEventListener("resize", resize, true);
		resize();
	  
	  function resize(){
		  var course_list = Y.one(".course_list");
		  if(course_list){
			 var array = course_list.all(".coursebox");
			 var columns = 3;
			 //var height = 81;
		  	var margin = 20;
		  	
		  if(array){
			  if(course_list.get("offsetWidth") < 700){
				  var columns = 1;
			  }else if(course_list.get("offsetWidth") < 970){
				  var columns = 2;
			  }
			  var width = (course_list.get("offsetWidth") - margin*columns) /columns -5;			//columns-1 //die -10 sind nur um rechts ein bisschen platz zu haben..damit ein scrollbalken nicht alles zerstört
			  var sizearray = [width];
			  
			  for(var i = 1; i < columns; ++i){
				  sizearray[sizearray.length]=sizearray[i-1]+width+margin;
			  }			  			
		  		function setneededWidth(o){
		  			var width = o.one("a").get("offsetWidth");
		  				var i = 0;
		  				while(i<sizearray.length && sizearray[i] < width){
		  					++i;
		  				}
		  				o.set("offsetWidth",sizearray[i]);
		  				//o.set("offsetHeight",height);
		  		}
		  			
		  			array.each(function (node){
		  				setneededWidth(node);	//.one(".course_title .title a") und dafpr das bei der fkt weg
		  			});
				  		
		  			
//						  array.each(function (node){
//				  				node.remove("width");		//funktioniert nicht
//				  			});
		  			
		  			//LÖSUNG-> berechne einmalig für jeden Sprung die prozente von einem Block!
		  			
		  	}
		  
		  }

	  }
	 
 //------------------------- 
  
  }
};

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