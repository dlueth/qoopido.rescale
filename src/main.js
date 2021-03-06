(function(global, document, demand, provide) {
	'use strict';

	function definition(DomElement, DomElementAppear, ComponentSense, functionMerge, functionDebounce) {
		var storage     = {},
			instances   = [],
			matchUrl    = /(.+?).(jpe?g|png|gif)$/i,
			placeholder = 'data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACwAAAAAAQABAAACAkQBADs=';

		function globalOnResize() {
			var i = 0, instance;

			for(; instance = instances[i]; i++) {
				instance.isVisible() && localOnResize.call(instance);
			}
		}

		function localOnResize() {
			var self       = this,
				properties = storage[self.uuid],
				parent     = self.getParent(),
				i = 0, candidate, image, boundingbox, dimensions, quality, url;

			for(; candidate = properties.candidates[i]; i++) {
				if((!candidate.mql || candidate.mql.matches) && (!candidate.selector || checkSelector.call(self, candidate.selector))) {
					image = properties.image;

					if(properties.visible) {
						boundingbox = image.node.getBoundingClientRect();
						dimensions  = properties.settings.modifier({
							width:  boundingbox.width,
							height: boundingbox.width * (candidate.ratio || properties.ratio || parent.offsetHeight / parent.offsetWidth),
							scale:  (global.devicePixelRatio || 1) * 100
						});
						quality     = candidate.quality || properties.quality;
						url         = candidate.url.replace(matchUrl, '$1.' + Math.round(dimensions.width) + 'x' + Math.round(dimensions.height) + '@' + Math.round(dimensions.scale) + (quality ? '.' + quality : '') + '.$2');

						image
							.setAttribute('src', url)
							.emit('rescale', candidate.url, url);
					}

					setRatio.call(self, candidate.ratio);

					return;
				}
			}

			setRatio.call(self);
		}

		function checkSelector(selector) {
			var self    = this,
				matches = document.querySelectorAll(selector),
				element, i, match;

			if(matches.length) {
				element = self.node;

				for(i = 0; match = matches[i]; i++) {
					if(match === element) {
						return true;
					}
				}
			}

			return self.getParent(selector);
		}

		function processSources() {
			var self       = this,
				sources    = self.getChildren('[itemprop="contentUrl"]'),
				candidates = [],
				i = 0, candidate, width, height, ratio, media;

			for(; candidate = sources[i]; i++) {
				width  = (width = candidate.getAttribute('width')) ? parseInt(width) : null;
				height = (height = candidate.getAttribute('height')) ? parseInt(height) : null;
				ratio  = (width && height) ? height / width : null;
				media  = (media = candidate.getAttribute('media')) ? new ComponentSense(media).on('match unmatch', function() { localOnResize.call(self); }) : null;

				candidates.push({
					url:      candidate.getAttribute('content'),
					ratio:    ratio,
					quality:  candidate.getAttribute('quality'),
					mql:      media,
					selector: candidate.getAttribute('selector')
				});
			}

			return candidates;
		}

		function setRatio(ratio) {
			var self       = this,
				properties = storage[self.uuid],
				parent     = properties.parent;

			properties.container.setStyle('paddingBottom', ((ratio || properties.ratio || parent.offsetHeight / parent.offsetWidth) * 100) + '%');

			return self;
		}

		function Rescale(element, settings) {
			var self = DomElementAppear.call(this, element, settings),
				uuid = self.uuid,
				temp, parent, container, width, height, quality, caption, properties;

			parent     = self.getParent();
			container  = new DomElement('<div />').setStyles({ position: 'relative', display: 'block', width: '100%', height: 0, padding: 0 }).appendTo(self);
			width      = (temp = self.getChildren('[itemprop="width"]')[0]) ? parseInt(temp.getAttribute('content')) : null;
			height     = (temp = self.getChildren('[itemprop="height"]')[0]) ? parseInt(temp.getAttribute('content')) : null;
			quality    = (temp = self.getChildren('[itemprop="quality"]')[0]) ? parseInt(temp.getAttribute('content')) : null;
			caption    = (temp = self.getChildren('[itemprop="caption"]')[0]) ? temp.getAttribute('content') : null;

			while(parent.offsetHeight === 0) {
				parent = parent.parentNode;
			}

			properties = storage[uuid] = {
				visible:    false,
				settings:   functionMerge({}, Rescale.settings, settings),
				ratio:      (width && height) ? height / width : null,
				quality:    quality,
				parent:     parent,
				container:  container,
				image:      new DomElement('<img />', { src: placeholder, alt: caption || '' }, { position: 'absolute', display: 'block', width: '100%', height: '100%', top: '0', left: '0', margin: '0', padding: '0' }).appendTo(container),
				candidates: processSources.call(self),
				caption:    caption
			};

			instances.push(self);

			return setRatio
				.call(self)
				.on('appear disappear', function(event) {
					properties.visible = (event.type === 'appear');

					localOnResize.call(self);
				});
		}
		
		Rescale.settings = {
			modifier: function(dimensions) {
				/*
				 var ratio = dimensions.height / dimensions.width;
				 
				 dimensions.width  = Math.ceil(dimensions.width / 50) * 50;
				 dimensions.height = dimensions.width * ratio;
				 */
				dimensions.scale  = Math.min(200, dimensions.scale);
				
				return dimensions;
			}
		};
		
		new DomElement(global)
			.on('resize orientationchange', functionDebounce(globalOnResize));
		
		return Rescale.extends(DomElementAppear);
	}

	provide([ '/nucleus/dom/element', '/nucleus/dom/element/appear', '/nucleus/component/sense', '/nucleus/function/merge', '/nucleus/function/debounce' ], definition);
}(this, document, demand, provide));