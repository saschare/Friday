/*
Based on the demo of Recreating the Nikebetterworld.com Parallax Demo by Ian Lunn - http://www.ianlunn.co.uk/demos/recreate-nikebetterworld-parallax/
Author: David Cable and Jeff Rainey
Author URL: http://themespectrum.com/
Demo URL: http://themespectrum.com/parallax-demo

License: http://creativecommons.org/licenses/by-sa/3.0/ (Attribution Share Alike). 

Dual licensed under the MIT and GPL licenses:
http://www.opensource.org/licenses/mit-license.php
http://www.gnu.org/licenses/gpl.html
*/

$(document).ready(function() { //when the document is ready...
	
	//save selectors as variables to increase performance
	var $window = $(window);	
	var windowHeight = $window.height(); //get the height of the window
	
	//apply the class "inview" to a section that is in the viewport
	$('.parallax-container').bind('inview', function (event, visible) {
			if (visible == true) {
			$(this).addClass("inview");
			} else {
			$(this).removeClass("inview");
			}
		});
	function removeActive() {
			$("a.active").removeClass("active");
	}
			
	//function that places the navigation in the center of the window
	function RepositionNav(){
		var windowHeight = $window.height(); //get the height of the window
		var navHeight = $('#nav').height() / 2;
		var windowCenter = (windowHeight / 2); 
		var newtop = windowCenter - navHeight;
		$('#nav').css({"top": newtop}); //set the new top position of the navigation list
	}
	
	//function that is called for every pixel the user scrolls. Determines the position of the background
	/*arguments: 
		x = horizontal position of background
		windowHeight = height of the viewport
		pos = position of the scrollbar
		adjuster = adjust the position of the background
		inertia = how fast the background moves in relation to scrolling
	*/
	function newPos(x, windowHeight, pos, adjuster, inertia){
		return x + "% " + (-((parallaxHeight + pos) - adjuster) * inertia)  + "px";
	}
	
	//function to be called whenever the window is scrolled or resized

	function Move(){ 
		var pos = $window.scrollTop(); //position of the scrollbar
		var blockAdjust = null;
		$('.parallax-container').each(function(i) {
	    	var blockAdjust=0;
	    	$('#pixels').html(pos); //display the number of pixels scrolled at the bottom of the page
	    	if($(this).hasClass("inview"))
	    	{
	        	$(this).css({'backgroundPosition': newPos(50, windowHeight, pos, (parallaxHeight * (i+1)), 0.3)});
	        	$(this).find('.bg1').css({'backgroundPosition': newPos(50, windowHeight, pos, (parallaxHeight * (i+1))+trainerBump, 0.6)});
	    	}
		});
		var scrollPosition = $("body").scrollTop();
		var firstBlockId = $('.inview').first().attr('id');

		
		firstBlockLink = firstBlockId.replace("block","li#blockLink") + ' a' ;
		removeActive();
		$(firstBlockLink).addClass("active");
			
		
	}
		
	RepositionNav(); //Reposition the Navigation to center it in the window when the script loads
	
	$window.resize(function(){ //if the user resizes the window...
		Move(); //move the background images in relation to the movement of the scrollbar
		RepositionNav(); //reposition the navigation list so it remains vertically central
	});		
	
	$window.bind('scroll', function(){ //when the user is scrolling...
		Move(); //move the background images in relation to the movement of the scrollbar
	});
	
});