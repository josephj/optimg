optimg
-------

A PHP class for optimizing images according to Stoyan Stefanov's [Image Optimization](http://yuiblog.com/blog/2008/10/29/imageopt-1/) articles. It also includes a CLI interface that you can use for build or commit check. Yahoo! has a similar tool called [Smush.it](http://www.smushit.com/ysmush.it/). This tool basicly provides the same functionality as it.

## Dependencies

You need to have the following executable command-line tools installed in your operating system.

* ImageMagick
* jpegtran
* pngcrush
* gifsicle
* pngquant
* pngout

## Installation

```
$ git clone git://github.com/josephj/optimg.git
$ cd optimg
$ chmod +x optimg
$ ./optimg
```

## Usage

```
Usage: optimg [options] image_path

  -c, --check-only   Check if path has fully optimized.
  -r, --report-only  Show report without really optimization.
  --png8             Transform all PNG files to PNG8 format.
```
