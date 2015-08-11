// Standard license block omitted.
/*
 * @package    block_overview
 * @copyright  2015 Someone cool
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 
 /**
  * @module block_overview/tiles
  */
define(['jquery'], function($) {
	
	var sizearray;
	var prozentarray;
	var columns;
	var course_list = $(".block_course_overview_ext .course_list");
	
	var setneededWidth = function(o){
		var width = o.children("a").outerWidth();
		var i = 0;
		while(i < sizearray.length && sizearray[i] < width){
			++i;
		}
		o.width(prozentarray[i]+'%');		
	};
	
	var setColumns = function(){
		var alt_columns = columns;
		if(course_list.width() < 700){
			columns = 1;
		}else if(course_list.width() < 970){
			columns = 2;
		}else{
			columns = 3;
		}
		return (columns != alt_columns);
	};
	
    var resize = function(){

		  if(course_list){
			var array = course_list.children(".coursebox");
			 //var height = 81;
		  	var margin = 20;
		  	var padding = 35; //padding of 30 and border of 2 +3 wegen dem scrollbalken
		  	
		  if(array && setColumns()){			  
			  var width = (course_list.width()/columns)-margin;		//-5			
			  //columns-1 //die -10 sind nur um rechts ein bisschen platz zu haben..damit ein scrollbalken nicht alles zerstört
			  sizearray = [width - padding];
			  
			  var i;
			  for(i = 1; i < columns; ++i){
				  sizearray[sizearray.length]=sizearray[i-1]+width+margin;
			  }
			  prozentarray = [(sizearray[0]/course_list.width())*100];
			  //berechne die prozente
			  for(i = 1; i < sizearray.length; ++i){
				  prozentarray[i] = (sizearray[i]/course_list.width())*100;
			  }
			  
			  array.each(function (){
		  			setneededWidth($(this));
		  		});
		  			
		  	}
		  
		  }

	  
     };
 
     var popup = function(event){
    	 //schau on das eigene popup visible ist
    	 var bool = $("#"+event.data.id+" .co_popup").hasClass("visible");
    	//mache alle popups invisible
    	 $(".co_popup").removeClass("visible");	//:not(#"+event.data.id+")
    	 //änder die sichtbarkeit des eigenen popups
    	 if(bool === false){
    		 $("#"+event.data.id+" .co_popup").toggleClass("visible");
    	 }
    	 //stop propagation
    	 return false;
     };
     
     var close_all_popups = function(){
    	 $(".co_popup").removeClass("visible");
     };
     
     var is_color = function(event){
    	 var color = $(event.target).css("backgroundColor");
    	 //funktioniert nicht, nachdem der Kurs verschoben wurde (.parents/.closest holt nicht mehr das richtige
    	 //Dom Element!!)
    	 //$("#"+event.data.id).parents(".coursebox").css("backgroundColor",color);
    	 var str = event.data.id;
    	 var id = str.slice(str.indexOf("_")+1,str.lastIndexOf("_"));
    	 $("#course-"+id).closest(".coursebox").css("backgroundColor",color);

    	 if(event.data.save === true){
    		 save_color();
    	 }else{
        	 $(".co_saveColor .singlebutton").removeClass("btn-disabled");
    	 }
    	 return false;
     };  
     
     var fadeOut = function(notification){
    	 $(notification).css("display","block");
    	 $(notification).fadeOut("slow");
     };
     
     var rgb2hex = function(rgb){
    	 if (  rgb.search("rgb") == -1 ) {
    	      return rgb;
    	 } else {
    		 rgb = rgb.match(/^rgba?\((\d+),\s*(\d+),\s*(\d+)(?:,\s*(\d+))?\)$/);
    	     return "#" + hex(rgb[1]) + hex(rgb[2]) + hex(rgb[3]); 
    	 } 
     };
     
     var hex = function(x){
    	  return ("0" + parseInt(x).toString(16)).slice(-2);
     };
     
     var save_color = function(){
    	 
       	var courselist = [];
       	var colorlist = [];
       	
       	$('.course_list').children().each(function(index,element){
       		courselist[courselist.length] = $(element).attr('id').substring(7);
       		colorlist[colorlist.length] = rgb2hex($(element).css('backgroundColor')).toUpperCase();
       	});
       	
       	var params = {
               sesskey : M.cfg.sesskey,
               courselist : courselist,
               colorlist : colorlist
        };
       	 
       	var callback = function(){};
       	//window.console.log($.param(params));
       	 
       	$.ajax(M.cfg.wwwroot+'/blocks/course_overview_ext/color_save.php', {
       		method: 'POST',
       		data: $.param(params),
       		context: M.block_course_overview_ext,
       		success: function(){
       			//window.console.log("success");
       		},
       		error: function(){		//xhr,text,error
       			//window.console.log("error");
       			//window.console.log(error);
       			//window.console.log(xhr.getResponseHeader());
       		},
       		complete: function(){
       			callback();
       		}
       	});
    	return false;	// notwendig?
     };
     
     var save_color_button = function(event){
    	 
    	 //speicher nur zeug, wenn der button anklickbar ist
    	var disabled = $(event.data.button).hasClass("btn-disabled");
    	if(!disabled){
    		var courselist = [];
       	 	var colorlist = [];
       	 
       	 	$('.course_list').children().each(function(index,element){
       	 		courselist[courselist.length] = $(element).attr('id').substring(7);
       	 		colorlist[colorlist.length] = rgb2hex($(element).css('backgroundColor')).toUpperCase();
       	 	});
       	 
       	 	var params = {
         	       sesskey : M.cfg.sesskey,
         	       courselist : courselist,
         	       colorlist : colorlist
         	 };
       	 
       	 	var callback = function(){};
       	 	if($(".co-notification")){
       	 		callback = function(){
       	 			fadeOut(event.data.notification);
       	 			$(event.data.button).addClass("btn-disabled");
       	 		};
       	 	}
       	     
       	 //window.console.log($.param(params));
       	 
       	 	$.ajax(M.cfg.wwwroot+'/blocks/course_overview_ext/color_save.php', {
       	 		method: 'POST',
       	 		data: $.param(params),
       	 		context: M.block_course_overview_ext,
       	 		success: function(){
       	 			//window.console.log("success");
       	 		},
       	 		error: function(){		//xhr,text,error
       	 			//window.console.log("error");
       	 			//window.console.log(error);
       	 			//window.console.log(xhr.getResponseHeader());
       	 		},
       	 		complete: function(){
       	 			callback();
       	 		}
       	 	});
    	}
    	 return false;	// notwendig?
     };
     
    return {
    	init: function(){
    		resize();
    		$(window).resize(resize);
    		
    	},
    	pop: function(id){
    		//statt jeder id diesem event zu geben, nutze jquerys ".on()" mit der selector möglichkeit
    		$("#"+id).click({id: id},popup);
    	},
    	closeAllPopups: function(){
    		$(window).click(close_all_popups);
    	},    	
    	isColor: function(id,save){
    		$("#"+id).on("click",".color",{id: id, save: save},is_color);
    	},
    	saveColorButton: function(button,notification){
    		$(button).click({button: button, notification: notification},save_color_button);
    	},
    	setColor: function(courses,colors){
    		for (var i = 0; i < courses.length; i++) {
    			$('#course-'+courses[i]).css('backgroundColor',colors[i]);
    		}
    	}
    	
    };
});
