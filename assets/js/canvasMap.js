class MapPoint {
	constructor(x, y) {
		this.x = x;
		this.y = y;
	}
	
	toString() {
		return '(' + this.x + ', ' + this.y + ')';
	}
}

class MapObject {
	constructor(id) {
		this.id = id;
		
		this.path = null;

		this.zIndex = 0;
		
		this.hoverCallback = null;
		this.unhoverCallback = null;
		this.clickCallback = null;
		
		this.hoverPassthrough = false;
		this.clickPassthrough = false;
		
		this.hovering = false;
		this.clicking = false;
	}
	
	draw(context) {
		if (this.type == 'image') {
			context.drawImage(this.image, this.sourceOrigin.x, this.sourceOrigin.y, this.sourceSize.w, this.sourceSize.h, this.origin.x, this.origin.y, this.destSize.w, this.destSize.h);
		} else if (this.type == 'group') {
			for (var o of this.objects) {
				o.draw(context);
			}
		} else if (this.type == 'text') {
			context.font = this.size + 'px ' + this.font;
			context.fillStyle = this.color;
			context.textAlign = this.align;
			context.fillText(this.text, this.origin.x, this.origin.y);
		} else {
			// set the path styles (color & linewidth)
			context.strokeStyle = this.path.stroke;
			context.lineWidth = this.path.lineWidth;

			// stroke this path
			context.stroke(this.path);
		}
    }

	isPointInObject(context, point) {
		if (this.type == 'group') {
			for (var o of this.objects) {
				if (o.isPointInObject(context, point)) {
					return true;
				}
			}
			return false;
		} else {
			return context.isPointInStroke(this.path, point.x, point.y) || 
				   context.isPointInPath(this.path, point.x, point.y);
		}
	}
    
    hover(map) {
    	if (!this.hovering && this.hoverCallback) {
    		this.hoverCallback(map);
    	}
    	this.hovering = true;
    }
    
    unhover(map) {
    	if (this.hovering && this.unhoverCallback) {
    		this.unhoverCallback(map);
    	}
    	this.hovering = false;
    }

	click(map) {
		if (this.clickCallback) {
			this.clickCallback(map);
		}
	}
}

class MapGroupObject extends MapObject {
	constructor(id) {
		super(id);
		this.type = 'group';
		this.objects = [];
	}

	addObject(child) {
		this.objects.push(child);
	}
}

class MapLineObject extends MapObject {
	constructor(id, start, end, color=null, width=1) {
		super(id);
		this.type = 'line';
		this.start = start;
		this.end = end;
		this.color = color ?? 'black';
		this.width = width;
		
		this.path = new Path2D();
		this.path.moveTo(this.start.x, this.start.y);
		this.path.lineTo(this.end.x, this.end.y);
		this.path.stroke = this.color;
		this.path.lineWidth = width;
	}
}

class MapCircleObject extends MapObject {
	constructor(id, center, radius, color=null, width=1) {
		super(id);
		this.type = 'circle';
		this.center = center;
		this.radius = radius;
		this.color = color ?? 'black';
		this.width = width;
		
		this.path = new Path2D();
		this.path.ellipse(center.x, center.y, radius, radius, 0, 0, 2 * Math.PI);
		this.path.stroke = this.color;
		this.path.lineWidth = width;
	}
}

class MapPathObject extends MapObject {
	constructor(id, pathSpec, color=null, width=1) {
		super(id);
		this.type = 'path';
		this.pathSpec = pathSpec;
		this.color = color ?? 'black';
		this.width = width;
		
		this.path = new Path2D(this.pathSpec);
		this.path.stroke = this.color;
		this.path.lineWidth = this.width;
	}
}

class MapImageObject extends MapObject {
	constructor(id, image, origin, destSize=null, sourceOrigin=null, sourceSize=null) {
		super(id);
		this.type = 'image';
		this.image = image;
		this.origin = origin;
		this.destSize = destSize ?? {'w': image.width, 'h': image.height};
		this.sourceOrigin = sourceOrigin ?? new MapPoint(0, 0);
		this.sourceSize = sourceSize ?? {'w': image.width, 'h': image.height};
		
		// Still need a path in order to do hit detection
		this.path = new Path2D();
		this.path.rect(this.origin.x, this.origin.y, this.destSize.w, this.destSize.h);
		this.path.stroke = 'rgba(0,0,0,0)';
		this.path.lineWidth = 0;
	}
}

class MapTextObject extends MapObject {
	constructor(id, text, origin, font = null, size = null, color = 'black', align = 'left') {
		super(id);
		this.type = 'text';
		this.origin = new MapPoint(origin.x, origin.y);
		this.text = text;
		this.color = color ?? 'black';
		this.font = font;
		this.size = size;
		this.align = align;

		this.textWidth = text.length * size / 2;
		var pathOriginX;
		if (this.align == 'left') {
			pathOriginX = this.origin.x;
		} else if (this.align = 'center') {
			pathOriginX = this.origin.x - this.textWidth / 2;
		} else if (this.align = 'right') {
			pathOriginX = this.origin.x - this.textWidth;
		}

		this.path = new Path2D();
		this.path.rect(pathOriginX, this.origin.y, this.textWidth, this.size);
		this.path.stroke = 'rgba(0,0,0,0)';
		this.path.lineWidth = 0;
	}
}


class CanvasMap {
	constructor(elementId) {
		var element = document.getElementById(elementId);
		if (element.tagName == 'CANVAS') {
			this.element = element;
		} else {
			this.element = document.createElement('canvas');
			element.appendChild(this.element);
		}
		
		this.context = this.element.getContext('2d');
		
		this.objects = [];
		this.objectsById = {};
		
		this.min = new MapPoint(0, 0);
		this.max = new MapPoint(this.element.width, this.element.height);
		
		var root = this;
		this.element.addEventListener('mousemove', function(event) { root.hoverListener(event); });
		this.element.addEventListener('mousedown', function(event) { root.mouseDownListener(event); });
		this.element.addEventListener('mouseup', function(event) { root.mouseUpListener(event); });
		this.element.addEventListener('click', function(event) { root.clickListener(event); });
		
		this.hoverPoint = null;
		this.hoverMode = 'hover';
		
		this.grab = new MapPoint(0, 0);
		this.gPanOrigin = new MapPoint(0, 0);
		this.panOrigin = new MapPoint(0, 0);

		this.settingUp = true;

		this.thisClickDragged = false;
	}
	
	setSize(size, displaySize=null, checkDPI=false) {
		this.element.width = size.w;
		this.element.height = size.h;
		if (displaySize) {
			this.element.style.width = displaySize.w;
			this.element.style.height = displaySize.h;
		}
		if (checkDPI) {
			// Get the DPR and size of the canvas
			const dpr = window.devicePixelRatio;
			const rect = this.element.getBoundingClientRect();
			
			// Set the "actual" size of the canvas
			this.element.width = rect.width * dpr;
			this.element.height = rect.height * dpr;
			
			// Scale the context to ensure correct drawing operations
			this.context.scale(dpr, dpr);
			
			// Set the "drawn" size of the canvas
			this.element.style.width = `${rect.width}px`;
			this.element.style.height = `${rect.height}px`;
		}
		this.rect = this.element.getBoundingClientRect();
	}
	
	setPan(panOrigin) {
		this.panOrigin = panOrigin;
		this.update();
	}

	centerOn(centerPoint) {
		var newOrigin = new MapPoint(-centerPoint.x, centerPoint.y);
		newOrigin.x += this.element.width / 2;
		//newOrigin.y += this.element.height / 2;
		console.log("For center, setting pan to " + newOrigin);
		this.setPan(newOrigin);
	}
	
	addLine(id, start, end, color=null, width=1, groupId = null) {
		var line = new MapLineObject(id, start, end, color, width);
		if (!groupId) {
			this.objects.push(line);
			this.objectsById[id] = line;
		} else {
			this.objectsById[groupId].addObject(line);
		}
		this.update();

		return line;
	}
	
	addCircle(id, center, radius, color=null, width=1, groupId = null) {
		var circle = new MapCircleObject(id, center, radius, color, width);
		if (!groupId) {
			this.objects.push(circle);
			this.objectsById[id] = circle;
		} else {
			this.objectsById[groupId].addObject(circle);
		}
		this.update();

		return circle;
	}
	
	addPath(id, path, color=null, width=1, groupId = null) {
		var path = new MapPathObject(id, path, color, width);
		if (!groupId) {
			this.objects.push(path);
			this.objectsById[id] = path;
		} else {
			this.objectsById[groupId].addObject(path);
		}
		this.update();

		return path;
	}
	
	addImage(id, image, origin, destSize=null, sourceOrigin=null, sourceSize=null, groupId = null) {
		var image = new MapImageObject(id, image, origin, destSize, sourceOrigin, sourceSize);
		if (!groupId) {
			this.objects.push(image);
			this.objectsById[id] = image;
		} else {
			this.objectsById[groupId].addObject(image);
		}
		this.update();

		return image;
	}

	addText(id, text, origin, font = null, size = null, color = 'black', align = 'left', groupId = null) {
		var text = new MapTextObject(id, text, origin, font, size, color, align);
		if (!groupId) {
			this.objects.push(text);
			this.objectsById[id] = text;
		} else {
			this.objectsById[groupId].addObject(text);
		}
		this.update();

		return text;
	}

	addGroup(id) {
		var group = new MapGroupObject(id);
		this.objects.push(group);
		this.objectsById[id] = group;
		this.update();

		return group;
	}
	
	update() {
		if (this.settingUp) {
			return;
		}
		this.objects.sort(function(a, b) { if (a.zIndex < b.zIndex) { return -1; } else if (a.zIndex > b.zIndex) { return 1; } else { return 0; } });
		this.context.clearRect(-this.panOrigin.x, -this.panOrigin.y, this.element.width, this.element.height);
		this.context.setTransform(1, 0, 0, 1, this.panOrigin.x, this.panOrigin.y);
		//console.log("Redrawing " + this.objects.length + " object(s)");
		for (var i=0; i<this.objects.length; i++) {
			this.objects[i].draw(this.context);
		}
		
		if (this.hoverPoint !== null) {
			this.displayHoverPoint = this.hoverPoint;
			this.displayHoverPoint.x -= this.panOrigin.x;
			this.displayHoverPoint.y -= this.panOrigin.y;
			this.context.fillStyle = 'black';
		}
	}
	
	mouseDownListener(event) {
		if (this.hoverMode != 'drag') {
			this.gPanOrigin = new MapPoint(this.panOrigin.x, this.panOrigin.y);
			this.grab.x = event.clientX;
			this.grab.y = event.clientY;
			console.log('Grabbing at ' + this.grab);
			this.hoverMode = 'drag';
			this.element.style.cursor = 'grabbing';
		}
	}
	
	mouseUpListener(event) {
		if (this.hoverMode == 'drag') {
			this.grab.x = 0;
			this.grab.y = 0;
			this.hoverMode = 'hover';
			this.element.style.cursor = 'grab';
		}
	}

	clickListener(event) {
		if (this.thisClickDragged) {
			this.thisClickDragged = false;
			return;
		}
		var clickPoint = new MapPoint(event.clientX, event.clientY);
		clickPoint.x -= this.rect.x;
		clickPoint.y -= this.rect.y;
		var change = false;
		var stop = false;
		for (var i=this.objects.length-1; i>=0; i--) {
			var object = this.objects[i];
			if (!stop && object.isPointInObject(this.context, clickPoint)) {
// 					console.log('applying click to ' + object.id);
				object.click(this);
				change = true;
				if (!object.clickPassthrough) {
// 						console.log('stopping click after ' + object.id);
					stop = true;
				}
			}
		}
		if (change) {
			this.update();
		}
	}
	
	hoverListener(event) {
		var hoverPoint = new MapPoint(event.clientX, event.clientY);
		hoverPoint.x -= this.rect.x;
		hoverPoint.y -= this.rect.y;
		if (this.hoverMode == 'hover') {
			//console.log('checking hover for ' + hoverPoint);
			var change = false;
			var stop = false;
			for (var i=this.objects.length-1; i>=0; i--) {
				var object = this.objects[i];
				if (!stop && object.isPointInObject(this.context, hoverPoint)) {
// 					console.log('applying hover to ' + object.id);
					object.hover(this);
					change = true;
					if (!object.hoverPassthrough) {
// 						console.log('stopping hover after ' + object.id);
						stop = true;
					}
				} else {
					if (object.hovering) {
// 						console.log('removing hover from ' + object.id);
						object.unhover(this);
						change = true;
					}
				}
			}
		} else if (this.hoverMode == 'drag') {
			var drag = new MapPoint(event.clientX - this.grab.x, event.clientY - this.grab.y);
			this.panOrigin.x = this.gPanOrigin.x + drag.x;
			this.panOrigin.y = this.gPanOrigin.y + drag.y;
			console.log('With grab pan origin at ' + this.gPanOrigin + ' and drag at ' + drag + ', panning to ' + this.panOrigin);
			//console.log('With hover at ' + new MapPoint(event.clientX, event.clientY) + ' and drag at ' + drag + ', panning to ' + this.panOrigin);
			change = true;
			this.thisClickDragged = true;
		}
		if (change) {
			this.hoverPoint = hoverPoint;
    		this.update();
    	}
	}
	
	// drawLine(start, end, color=null, width=1) {
// 		this.context.beginPath();
// 		this.context.moveTo(start.x, start.y);
// 		this.context.lineTo(end.x, end.y);
// 		
// 		this.context.strokeStyle = color ?? 'black';
// 		this.context.lineWidth = width;
// 		this.context.stroke();
// 	}
// 	
// 	drawCircle(center, radius, color=null, width=1) {
// 		this.context.beginPath();
// 		this.context.ellipse(center.x, center.y, radius, radius, 0, 0, 2 * Math.PI);
// 		
// 		this.context.strokeStyle = color ?? 'black';
// 		this.context.lineWidth = width;
// 		this.context.stroke();
// 
// 	}
	
	
}

export { MapPoint, CanvasMap };