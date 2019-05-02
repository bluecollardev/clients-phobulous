/*
*	ImageZoom - Responsive jQuery Image Zoom Pluin
*	by hkeyjun
*   http://codecanyon.net/user/hkeyjun	
*/
!function(a,b){a.ImageZoom=function(c,d){function f(a){var b=parseInt(a);return b=isNaN(b)?0:b}var e=this;e.$el=a(c),e.$el.data("imagezoom",e),e.init=function(b){e.options=a.extend({},a.ImageZoom.defaults,b),e.$viewer=a('<div class="zm-viewer '+e.options.zoomViewerClass+'"></div>').appendTo("body"),e.$handler=a('<div class="zm-handler'+e.options.zoomHandlerClass+'"></div>').appendTo("body"),e.isBigImageReady=-1,e.$largeImg=null,e.isActive=!1,e.$handlerArea=null,e.isWebkit=/chrome/.test(navigator.userAgent.toLowerCase())||/safari/.test(navigator.userAgent.toLowerCase()),e.evt={x:-1,y:-1},e.options.bigImageSrc=""==e.options.bigImageSrc?e.$el.attr("src"):e.options.bigImageSrc,(new Image).src=e.options.bigImageSrc,e.callIndex=a.ImageZoom._calltimes+1,e.animateTimer=null,a.ImageZoom._calltimes+=1,a(document).bind("mousemove.imagezoom"+e.callIndex,function(a){e.isActive&&e.moveHandler(a.pageX,a.pageY)}),e.$el.bind("mouseover.imagezoom",function(a){e.isActive=!0,e.showViewer(a)})},e.moveHandler=function(a,c){var i,j,k,l,m,n,o,p,d=e.$el.offset(),g=e.$el.outerWidth(!1),h=e.$el.outerHeight(!1);a>=d.left&&a<=d.left+g&&c>=d.top&&c<=d.top+h?(d.left=d.left+f(e.$el.css("borderLeftWidth"))+f(e.$el.css("paddingLeft")),d.top=d.top+f(e.$el.css("borderTopWidth"))+f(e.$el.css("paddingTop")),g=e.$el.width(),h=e.$el.height(),a>=d.left&&a<=d.left+g&&c>=d.top&&c<=d.top+h&&(e.evt={x:a,y:c},"follow"==e.options.type&&e.$viewer.css({top:c-e.$viewer.outerHeight()/2,left:a-e.$viewer.outerWidth()/2}),1==e.isBigImageReady&&(k=c-d.top,l=a-d.left,"inner"==e.options.type?(i=-e.$largeImg.height()*k/h+k,j=-e.$largeImg.width()*l/g+l):"standard"==e.options.type?(m=l-e.$handlerArea.width()/2,n=k-e.$handlerArea.height()/2,o=e.$handlerArea.width(),p=e.$handlerArea.height(),0>m?m=0:m>g-o&&(m=g-o),0>n?n=0:n>h-p&&(n=h-p),j=-m/e.scale,i=-n/e.scale,e.isWebkit?(e.$handlerArea.css({opacity:.99}),setTimeout(function(){e.$handlerArea.css({top:n,left:m,opacity:1})},0)):e.$handlerArea.css({top:n,left:m})):"follow"==e.options.type&&(i=-e.$largeImg.height()/h*k+e.options.zoomSize[1]/2,j=-e.$largeImg.width()/g*l+e.options.zoomSize[0]/2,-i>e.$largeImg.height()-e.options.zoomSize[1]?i=-(e.$largeImg.height()-e.options.zoomSize[1]):i>0&&(i=0),-j>e.$largeImg.width()-e.options.zoomSize[0]?j=-(e.$largeImg.width()-e.options.zoomSize[0]):j>0&&(j=0)),e.options.smoothMove?(b.clearTimeout(e.animateTimer),e.smoothMove(j,i)):e.$viewer.find("img").css({top:i,left:j})))):(e.isActive=!1,e.$viewer.hide(),e.$handler.hide(),e.options.onHide(e),b.clearTimeout(e.animateTimer),e.animateTimer=null)},e.showViewer=function(b){var k,l,m,n,o,c=e.$el.offset().top,d=f(e.$el.css("borderTopWidth")),g=f(e.$el.css("paddingTop")),h=e.$el.offset().left,i=f(e.$el.css("borderLeftWidth")),j=f(e.$el.css("paddingLeft"));c=c+d+g,h=h+i+j,k=e.$el.width(),l=e.$el.height(),e.isBigImageReady<1&&a("div",e.$viewer).remove(),"inner"==e.options.type?e.$viewer.css({top:c,left:h,width:k,height:l}).show():"standard"==e.options.type?(m=""==e.options.alignTo?e.$el:a("#"+e.options.alignTo),"left"==e.options.position?(n=m.offset().left-e.options.zoomSize[0]-e.options.offset[0],o=m.offset().top+e.options.offset[1]):"right"==e.options.position&&(n=m.offset().left+m.width()+e.options.offset[0],o=m.offset().top+e.options.offset[1]),e.$viewer.css({top:o,left:n,width:e.options.zoomSize[0],height:e.options.zoomSize[1]}).show(),e.$handlerArea&&(e.scale=k/e.$largeImg.width(),e.$handlerArea.css({width:e.$viewer.width()*e.scale,height:e.$viewer.height()*e.scale}))):"follow"==e.options.type&&e.$viewer.css({width:e.options.zoomSize[0],height:e.options.zoomSize[1],top:b.pageY-e.options.zoomSize[1]/2,left:b.pageX-e.options.zoomSize[0]/2}).show(),e.$handler.css({top:c,left:h,width:k,height:l}).show(),e.options.onShow(e),-1==e.isBigImageReady&&(e.isBigImageReady=0,fastImg(e.options.bigImageSrc,function(){if(a.trim(a(this).attr("src"))==a.trim(e.options.bigImageSrc)){if(e.$viewer.append('<img src="'+e.$el.attr("src")+'" class="zm-fast" style="position:absolute;width:'+this.width+"px;height:"+this.height+'px">'),e.isBigImageReady=1,e.$largeImg=a('<img src="'+e.options.bigImageSrc+'" style="position:absolute;width:'+this.width+"px;height:"+this.height+'px">'),e.$viewer.append(e.$largeImg),"standard"==e.options.type){var c=k/this.width;e.$handlerArea=a('<div class="zm-handlerarea" style="width:'+e.$viewer.width()*c+"px;height:"+e.$viewer.height()*c+'px"></div>').appendTo(e.$handler),e.scale=c}-1==e.evt.x&&-1==e.evt.y?e.moveHandler(b.pageX,b.pageY):e.moveHandler(e.evt.x,e.evt.y),e.options.showDescription&&e.$el.attr("alt")&&""!=a.trim(e.$el.attr("alt"))&&e.$viewer.append('<div class="'+e.options.descriptionClass+'">'+e.$el.attr("alt")+"</div>")}},function(){},function(){}))},e.changeImage=function(a,b){this.$el.attr("src",a),this.isBigImageReady=-1,this.options.bigImageSrc="string"==typeof b?b:a,e.options.preload&&((new Image).src=this.options.bigImageSrc),this.$viewer.hide().empty(),this.$handler.hide().empty(),this.$handlerArea=null},e.changeZoomSize=function(a,b){e.options.zoomSize=[a,b]},e.destroy=function(){a(document).unbind("mousemove.imagezoom"+e.callIndex),this.$el.unbind(".imagezoom"),this.$viewer.remove(),this.$handler.remove(),this.$el.removeData("imagezoom")},e.smoothMove=function(a,c){var g,h,i,j,k,d=10,f=parseInt(e.$largeImg.css("top"));return f=isNaN(f)?0:f,g=parseInt(e.$largeImg.css("left")),g=isNaN(g)?0:g,c=parseInt(c),a=parseInt(a),f==c&&g==a?(b.clearTimeout(e.animateTimer),e.animateTimer=null,void 0):(h=c-f,i=a-g,j=f+h/Math.abs(h)*Math.ceil(Math.abs(h/d)),k=g+i/Math.abs(i)*Math.ceil(Math.abs(i/d)),e.$viewer.find("img").css({top:j,left:k}),e.animateTimer=setTimeout(function(){e.smoothMove(a,c)},10),void 0)},e.init(d)},a.ImageZoom.defaults={bigImageSrc:"",preload:!0,type:"inner",smoothMove:!0,position:"right",offset:[10,0],alignTo:"",zoomSize:[100,100],descriptionClass:"zm-description",zoomViewerClass:"",zoomHandlerClass:"",showDescription:!0,onShow:function(){},onHide:function(){}},a.ImageZoom._calltimes=0,a.fn.ImageZoom=function(b){return this.each(function(){new a.ImageZoom(this,b)})}}(jQuery,window);var fastImg=function(){var a=[],b=null,c=function(){for(var b=0;b<a.length;b++)a[b].end?a.splice(b--,1):a[b]();!a.length&&d()},d=function(){clearInterval(b),b=null};return function(d,e,f,g){var h,i,j,k,l,m=new Image;return m.src=d,m.complete?(e.call(m),f&&f.call(m),void 0):(i=m.width,j=m.height,m.onerror=function(){g&&g.call(m),h.end=!0,m=m.onload=m.onerror=null},h=function(){k=m.width,l=m.height,(k!==i||l!==j||k*l>1024)&&(e.call(m),h.end=!0)},h(),m.onload=function(){!h.end&&h(),f&&f.call(m),m=m.onload=m.onerror=null},h.end||(a.push(h),null===b&&(b=setInterval(c,40))),void 0)}}();