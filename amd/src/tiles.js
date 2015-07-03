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
		window.console.log(prozentarray[i]+'%');
		o.width(prozentarray[i]+'%');
		//o.outerWidth(prozentarray[i]+'%');
		//o.css("width",sizearray[i]);
		//o.set("offsetHeight",height);
		
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
			window.console.log('courseliste exestiert');
			var array = course_list.children(".coursebox");
			 //var height = 81;
		  	var margin = 20;
		  	var padding = 35; //padding of 30 and border of 2 +3 wegen dem scrollbalken
		  	
		  	window.console.log(array);
		  if(array && setColumns()){
			  window.console.log('courseboxen exestieren');
			  
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
			  
			  window.console.log(course_list.width());
			  window.console.log(sizearray);
			  window.console.log(prozentarray);
			  array.each(function (){
		  			setneededWidth($(this));	//.one(".course_title .title a") und dafür das bei der fkt weg
		  		});
				  		
		  			
//						  array.each(function (node){
//				  				node.remove("width");		//funktioniert nicht
//				  			});
		  			
		  			//LÖSUNG-> berechne einmalig für jeden Sprung die prozente von einem Block!
		  			
		  	}
		  
		  }

	  
     };
 
    /**
     * @constructor
     * @alias module:block_overview/tiles
     */
    /* var Tiles = function() {
    	
    	this.addresize = function(){
        	
//        	M.block_course_overview_ext.Y = Y;
        	  
        	window.addEventListener("resize", resize, true);
        	resize();
        	  
        	//die resize-funktion wird 3mal drangehangen....warum auch immer....
    	
    	};
    }; */
 
    return {
    	init: function(){
    		window.console.log('init wurde aufgerufen');
    		resize();
    		$(window).resize(resize);
    		
    	}
    };
});