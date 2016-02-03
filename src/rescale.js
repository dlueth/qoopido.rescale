(function(global, document, demand, provide) {
	'use strict';

	function definition(DomElement, DomElementAppear, ComponentSense, functionMerge, functionDebounce) {
		var storage     = {},
			instances   = [],
			matchUrl    = /(.+?).(jpe?g|png|gif)$/,
			placeholder = 'data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACwAAAAAAQABAAACAkQBADs=',
			prototype;

		function globalOnResize() {
			var i = 0, instance;

			for(; instance = instances[i]; i++) {
				localOnResize.call(instance);
			}
		}

		function localOnResize() {
			var self       = this,
				properties = storage[self.uuid],
				parent     = self.getParent(),
				i = 0, candidate, image, boundingbox, dimensions, url;

			for(; candidate = properties.candidates[i]; i++) {
				if((!candidate.mql || candidate.mql.matches) && (!candidate.selector || document.querySelector(candidate.selector))) {
					image = properties.image;

					if(properties.visible) {
						boundingbox = image.element.getBoundingClientRect();
						dimensions  = properties.settings.modifier({
							width:  boundingbox.width,
							height: boundingbox.width * (candidate.ratio || properties.ratio || parent.offsetHeight / parent.offsetWidth),
							scale:  (global.devicePixelRatio || 1) * 100
						});
						url         = candidate.url.replace(matchUrl, '$1.' + Math.round(dimensions.width) + 'x' + Math.round(dimensions.height) + '@' + Math.round(dimensions.scale) + '.$2');

						image
							.setStyle('visibility', 'hidden')
							.one('load', function() {
								image.setStyle('visibility', 'visible');
							})
							.setAttribute('src', url);
					}

					setRatio.call(self, candidate.ratio);

					return;
				}
			}

			setRatio.call(self);
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
					mql:      media,
					selector: candidate.getAttribute('selector')
				});
			}

			return candidates;
		}

		function setRatio(ratio) {
			var self       = this,
				parent     = self.getParent(),
				properties = storage[self.uuid];

			properties.container.setStyle('paddingBottom', ((ratio || properties.ratio || parent.offsetHeight / parent.offsetWidth) * 100) + '%');

			return self;
		}

		function Adapt(element, settings) {
			var self = DomElementAppear.prototype.constructor.call(this, element, settings),
				uuid = self.uuid,
				temp, container, width, height, caption, properties;

			container  = new DomElement('<div />').setStyles({ position: 'relative', display: 'block', width: '100%', height: 0, padding: 0 }).appendTo(self);
			width      = (temp = self.getChildren('[itemprop="width"]')[0]) ? parseInt(temp.getAttribute('content')) : null;
			height     = (temp = self.getChildren('[itemprop="height"]')[0]) ? parseInt(temp.getAttribute('content')) : null;
			caption    = (temp = self.getChildren('[itemprop="caption"]')[0]) ? temp.getAttribute('content') : null;

			properties = storage[uuid] = {
				visible:    false,
				settings:   functionMerge({}, prototype.settings, settings),
				ratio:      (width && height) ? height / width : null,
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

		new DomElement(global)
			.on('resize orientationchange', functionDebounce(globalOnResize));

		prototype = DomElementAppear.extend(Adapt);
		prototype.settings = {
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

		return prototype;
	}

	provide([ '/nucleus/dom/element', '/nucleus/dom/element/appear', '/nucleus/component/sense', '/nucleus/function/merge', '/nucleus/function/debounce' ], definition);
}(this, document, demand, provide));