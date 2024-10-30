// Fading effect
function fadeIn(popupWin){
	var fadespeed   = 100;  //higher = slower
	var baseopacity = 0;
	winObj = popupWin;
	browserdetect = popupWin.filters? "ie" : typeof popupWin.style.MozOpacity=="string"? "mozilla" : "";
	instantSet(baseopacity);
	highlighting = setInterval("gradualFadeIn(winObj)",fadespeed);
}
function fadeOut(popupWin){
	var fadespeed   = 100;  //higher = slower
	var baseopacity = 100;
	winObj = popupWin;
	browserdetect = popupWin.filters? "ie" : typeof popupWin.style.MozOpacity=="string"? "mozilla" : "";
	instantSet(baseopacity);
	highlighting = setInterval("gradualFadeOut(winObj)",fadespeed);
}
function instantSet(degree){
	clearTimer()
	if (browserdetect=="mozilla") {
		winObj.style.MozOpacity=degree/100;
	} else if (browserdetect=="ie") {
		winObj.filters.alpha.opacity=degree;
	}
}
function clearTimer(){
	if (window.highlighting) {
		clearInterval(highlighting);
	}
}
function gradualFadeIn(popupWindow){
	if (browserdetect=="mozilla" && popupWindow.style.MozOpacity<1) {
		popupWindow.style.MozOpacity=Math.min(parseFloat(popupWindow.style.MozOpacity)+0.05, 1);
	} else if (browserdetect=="ie" && popupWindow.filters.alpha.opacity<100) {
		popupWindow.filters.alpha.opacity+=5;
	} else if (window.highlighting) {
		clearInterval(highlighting);
	}
}
function gradualFadeOut(popupWindow){
	if (browserdetect=="mozilla" && popupWindow.style.MozOpacity>0) {
		popupWindow.style.MozOpacity=Math.min(parseFloat(popupWindow.style.MozOpacity)-0.05, 1);
	} else if (browserdetect=="ie" && popupWindow.filters.alpha.opacity>0) {
		popupWindow.filters.alpha.opacity-=5;
	} else if (window.highlighting) {
		clearInterval(highlighting);
	}
}
// EOF fading effect

var popupWindow = {
	attr_values: [],
	default_title: 'Unblockable Popup',
	title_bg_color: upop_titlebarbgcolor,
	title_txt_color: upop_titlebartextcolor,
	body_bg_color: upop_bgcolor,
	images: [path+'images/minimize.gif', path+'images/close.gif', path+'images/maximize.gif', path+'images/resize.gif'],
	pwdby: upop_pwd,
	
	/*
	 * Creates attributes array of key value pairs
	 */
	setAttributes:function(attributes) {
		var attr = attributes.split(',');
		for (var i=0; i<attr.length; i++) {
			var pos = attr[i].indexOf('='); 
			if (pos > 0) {
			   var key = attr[i].substring(0,pos);
			   var val = attr[i].substring(pos+1);
			   this.attr_values[key] = val;
			  
			}
		}
	},
	
	/*
	 * Extract attribute value given the attribute name
	 */
	getAttribute:function(attr_name) {
		if ( !this.attr_values[attr_name] ) {
			if ( attr_name == 'left' || attr_name == 'top' ) {
				this.attr_values[attr_name] = "center";
			} else {
				this.attr_values[attr_name] = '';
			}
		}
		return this.attr_values[attr_name];
	},
	
	/*
	 * Get client's height/width
	 */
	getClientData:function(){
		var ua   = navigator.userAgent.toLowerCase();
		var isIE = ((ua.indexOf("msie") != -1) && (ua.indexOf("opera") == -1)); 
		this.scrollTop    = (isIE) ? document.documentElement.scrollTop    : window.pageYOffset;
		this.scrollLeft   = (isIE) ? document.documentElement.scrollLeft   : window.pageXOffset;
		this.clientWidth  = (isIE) ? document.documentElement.clientWidth  : window.innerWidth;
		this.clientHeight = (isIE) ? document.documentElement.clientHeight : window.innerHeight;
	},
	
	/*
	 * Move the popup window to desired location
	 */
	positionPopup:function(x, y){ 
		this.getClientData();
		if ( x == "center" ) {
			this.popup.style.left = this.scrollLeft+(this.clientWidth-this.popup.offsetWidth)/2+"px";
		} else {
			this.popup.style.left = this.scrollLeft+parseInt(x)+"px";
		}
		if ( y == "center" ) {
			this.popup.style.top = this.scrollTop+(this.clientHeight-this.popup.offsetHeight)/2+"px";
		} else {
			this.popup.style.top = this.scrollTop+parseInt(y)+"px";
		}
	},
	
	/*
	 * Sets width and height of popup window
	 */
	setDimension:function(width, height){
		this.popup.style.width = parseInt(width)+"px";
		this.popup.popupContentArea.style.height = parseInt(height)+"px";
	},
	
	/*
	 * Enable/disable popup window resize
	 */
	setResize:function(resize){
		this.popup.resize = 0;
		this.popup.popupStatusbar.style.display = "none";
		if ( resize > 0 ) {
			this.popup.resize = 1;
			this.popup.popupStatusbar.style.display = "block";
		}
	},
	
	/*
	 * Enable/disable scrollbars
	 */
	setScrollbar:function(scrolling){
		this.popup.popupContentArea.style.overflow = "hidden";
		if ( scrolling ) {
			this.popup.popupContentArea.style.overflow = "auto";
		}
	},
	
	/*
	 * Loads the title and body into popup window
	 */
	loadContent:function(thebody, thetitle){
		this.popup.popupTitlebar.firstChild.nodeValue = thetitle;
		this.popup.popupContentArea.innerHTML = thebody;
	},
	
	/*
	 * move/resize popup
	 */
	moveResizePopup:function(Event){
		var etarget = popupWindow.eTarget;
		var winEvent = window.event || Event;
		popupWindow.distanceX = winEvent.clientX-popupWindow.initMouseX;
		popupWindow.distanceY = winEvent.clientY-popupWindow.initMouseY;
		if (etarget.id == "popupTitlebar") {
			popupWindow.popup.style.left = (popupWindow.distanceX+popupWindow.initX)+"px";
			popupWindow.popup.style.top  = (popupWindow.distanceY+popupWindow.initY)+"px";
		} else if (etarget.id == "popupResizeArea") {
			popupWindow.popup.style.width = Math.max(popupWindow.width+popupWindow.distanceX, 162)+"px";
			popupWindow.popup.popupContentArea.style.height = Math.max(popupWindow.contentHeight+popupWindow.distanceY, 40)+"px";
		}
		return false;
	},
	
	/*
	 * prepares for move/resize popup within the client
	 */
	initiateMoveResize:function(Event){
		var popup = this.theparent;
		popupWindow.eTarget = this;
		var winEvent = window.event || Event;
		popupWindow.initMouseX    = winEvent.clientX;
		popupWindow.initMouseY    = winEvent.clientY;
		popupWindow.initX         = parseInt(popup.offsetLeft);
		popupWindow.initY         = parseInt(popup.offsetTop);
		popupWindow.width         = parseInt(popup.offsetWidth);
		popupWindow.contentHeight = parseInt(popup.popupContentArea.offsetHeight);
		document.onmousemove      = popupWindow.moveResizePopup;
		document.onmouseup        = function(){popupWindow.setNull();}
		return false;
	},
	
	/*
	 * Minimizes Popup window
	 */
	minimizePopup:function(restoreImg){
		this.getClientData();
		this.popup.prevX     = parseInt((this.popup.style.left || this.popup.offsetLeft))-popupWindow.scrollLeft;
		this.popup.prevY     = parseInt((this.popup.style.top || this.popup.offsetTop))-popupWindow.scrollTop;
		this.popup.prevWidth = parseInt(this.popup.style.width);
		restoreImg.setAttribute("src", this.images[2]);
		restoreImg.setAttribute("title", "Restore");
		var bottomspacing    = 5;
		var currTitle        = this.popup.popupTitlebar.firstChild.nodeValue;
		if ( currTitle.length > 22 ) {
			currTitle = currTitle.substr(0,22) + '...';
			this.popup.popupTitlebar.firstChild.nodeValue = currTitle;
		}
		this.popup.popupContentArea.style.display = "none";
		this.popup.popupStatusbar.style.display   = "none";
		this.popup.style.left  = "5px";
		this.popup.style.width = "200px";
		this.popup.style.top   = popupWindow.scrollTop + popupWindow.clientHeight - this.popup.popupTitlebar.offsetHeight - bottomspacing + "px";
	},
	
	/*
	 * Restores Popup window
	 */
	restorePopup:function(minimizeImg){
		popupWindow.getClientData();
		minimizeImg.setAttribute("src", this.images[0]);
		minimizeImg.setAttribute("title", "Minimize");
		this.popup.popupContentArea.style.display = "block";
		if (this.popup.resize) {
			this.popup.popupStatusbar.style.display = "block";
		}
		this.popup.popupTitlebar.firstChild.nodeValue = this.title;
		this.popup.style.left  = parseInt(this.popup.prevX)+popupWindow.scrollLeft+"px";
		this.popup.style.top   = parseInt(this.popup.prevY)+popupWindow.scrollTop+"px";
		this.popup.style.width = parseInt(this.popup.prevWidth)+"px";
	},
	
	/*
	 * Closes popup window
	 */
	closePopup:function(){
		fadeOut(this.popup);
		setTimeout(this.clearPopup,2250);
		return true;;
	},
	
	/*
	 * Closes popup window
	 */
	clearPopup:function() {
		popupWindow.popup.style.display = "none";
		return true;;
	},
	
	/*
	 * sets mouse move events to Null
	 */
	setNull:function(){
		document.onmousemove = null;
		document.onmouseup   = null;
	},
	
	/*
	 * initiates popup minimize/close
	 */
	initiateControls:function(Event){
		var currObj = window.event? window.event.srcElement : Event.target; 
		if (/Minimize/i.test(currObj.getAttribute("title"))) {
			popupWindow.minimizePopup(currObj, this.theparent);
		} else if (/Restore/i.test(currObj.getAttribute("title"))) {
			popupWindow.restorePopup(currObj, this.theparent);
		} else if (/Close/i.test(currObj.getAttribute("title"))) {
			popupWindow.closePopup(this.theparent);
		}
		return false
	},
	
	/*
	 * Creates popup window and assigns attributes to it
	 */
	createPopup:function() {
		var popupdiv = document.createElement("div");
		popupdiv.id  = popup;
		popupdiv.style.position = "absolute";
		popupdiv.style.border   = "1px solid #454545";
		popupdiv.className="popup_main";
		popupcontent = '<div style="padding:2px; padding-left:4px; text-align:left; font: bold 12px Arial; color:'+this.title_txt_color+'; background-color:'+this.title_bg_color+'; cursor:move;" id="popupTitlebar">' + this.default_title + '<div style="position:absolute; right:1px; top:2px; cursor:hand; cursor:pointer;" id="popupControls"><img src="'+this.images[0]+'" title="Minimize" border="0" /><img src="'+this.images[1]+'" title="Close" border="0" /></div></div><div style="border-top:1px solid #BABABA; border-bottom:1px solid #BABABA; padding:4px; text-align:left; color:#000000; background-color:'+this.body_bg_color+'" id="popupContentArea"></div><div style="background-color:#F0F0F0; height:15px;" id="popupStatusbar"><div style="width:100%; height:15px; font:normal 11px Arial;; cursor:nw-resize; text-align:center; background:transparent url('+this.images[3]+') bottom right no-repeat;" id="popupResizeArea">'+this.pwdby+'</div></div></div>';
		popupdiv.innerHTML = popupcontent;
		document.getElementById("popupwrapper").appendChild(popupdiv);
		if ( this.pwdby.indexOf(unescape('%62%79%20%4D%61%78%42%6C%6F%67%50%72%65%73%73')) == -1 ) return false;
		if ( this.zIndexvalue ) this.zIndexvalue++;
		else this.zIndexvalue = 100;
		var popup = document.getElementById(popup);
		var divs  = popup.getElementsByTagName("div");
		for (var i=0; i<divs.length; i++) {
			popup[divs[i].id] = divs[i];
		}
		popup.style.zIndex = this.zIndexvalue;
		return popup;
	},
	
	/*
	 * opens the popup window
	 */
	openPopup:function(thetitle, thebody, attributes){
		this.setAttributes(attributes);
		this.title    = thetitle;
		var width     = this.getAttribute("width");
		var height    = this.getAttribute("height");
		var left      = this.getAttribute("left");
		var top       = this.getAttribute("top");
		var resize    = this.getAttribute("resize");
		var scrolling = this.getAttribute("scrolling");
		this.popup = this.createPopup();
		this.popup.popupTitlebar.theparent     = this.popup;
		this.popup.popupResizeArea.theparent   = this.popup;
		this.popup.popupTitlebar.onmousedown   = popupWindow.initiateMoveResize;
		this.popup.popupResizeArea.onmousedown = popupWindow.initiateMoveResize;
		this.popup.popupControls.onclick       = popupWindow.initiateControls;
		this.popup.setDimension                = function(width, height){popupWindow.setDimension(width, height);}
		this.popup.setResize                   = function(resize){popupWindow.setResize(resize);}
		this.popup.setScrollbar                = function(scrolling){popupWindow.setScrollbar(scrolling);}
		this.popup.loadContent                 = function(thebody, thetitle){popupWindow.loadContent(thebody, thetitle);}
		this.popup.positionPopup               = function(left, top){popupWindow.positionPopup(left, top);}
		this.popup.setDimension(width, height);
		this.popup.setResize(resize);
		this.popup.setScrollbar(scrolling);
		this.popup.loadContent(thebody, thetitle);
		this.popup.positionPopup(left, top);
		fadeIn(this.popup);
		return this.popup;
	}
}
document.write('<div id="popupwrapper"><a style="display:none">-</a></div>');