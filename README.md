> **If you would like to support this project feel free to star or fork it, or both. By doing so it will be easier to get it into some of the usual CDNs :)**

> And if you like it and want to help even more, spread the word as well!

# Qoopido.rescale
Rescale is my personal approach at responsive images using [Qoopido.demand](https://github.com/dlueth/qoopido.demand) as its (localStorage caching) module loader as well as some modules from [Qoopido.nucleus](https://github.com/dlueth/qoopido.nucleus), my personal & modular JavaScript utility library. 

Image variants will get generated server-side via PHP (which is included on GitHub) and cached. The class comes with cache validation and adjustable garbage collection as well.

The JavaScript client part will lazyload perfectly sized images matching either a mediaquery and/or a simple selector. Images not currently visible (or within an adjustable threshold around the currently visible area) will have their aspect ratio changed accordingly but will not get loaded as long as they are not visible.

Each image candidate can have its own width & height allowing different image aspect ratios for a single element depending on mediaquery and/or selector. Keep in mind though that width and height will only be used to calculate a ratio. The image itself will always auto fit into its own CSS constraints or the ones defined on its parent.


## Compatibility
Qoopido.rescale is officially developed for Chrome, Firefox, Safari, Opera and IE9+.

I do test on OSX El Capitan and Rescale is fully working on Chrome, Firefox, Safari and Opera there. To test IE9, 10, 11 as well as Edge the official Microsoft VMs in combination with VirtualBox are being used.


## External dependencies
To load Rescale [Qoopido.demand](https://github.com/dlueth/qoopido.demand) and some modules from [Qoopido.nucleus](https://github.com/dlueth/qoopido.nucleus) are required.


## Availability
Qoopido.rescale is available on GitHub as well as jsdelivr, npm and bower at the moment. CDNJS might follow in the near future.


## Loading Rescale & usage
To be able to use Rescale you will need to load [Qoopido.demand](https://github.com/dlueth/qoopido.demand) which comes with extensive documentation within its own repo. If you need a working example of a main.js for Qoopido.demand simply look at the [CodePen demo](http://codepen.io/dlueth/pen/VeGrMe/) I officially provide for Qoopido.rescale.