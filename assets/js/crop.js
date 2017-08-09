(function( $, Hammer, peepso, factory ) {

    var mobile = /android|webos|iphone|ipad|ipod|blackberry|iemobile|opera mini/i;
    var IS_DESKTOP = !mobile.test( navigator.userAgent );

    var PsCrop = factory( $, Hammer, peepso, IS_DESKTOP );

    ps_crop = {};
    ps_crop.init = function( config ) {
        var elem, inst;

        elem = $( config.elem );
        inst = elem.data('ps-crop');
        if ( inst ) {
            inst.destroy();
        }

        inst = new PsCrop( config );
        elem.data('ps-crop', inst );
    };
    ps_crop.detach = function( elem ) {
        var inst;
        elem = $( elem );
        inst = elem.data('ps-crop');
        inst && inst.detach();
    };

})( jQuery || $, Hammer, peepso, function( $, Hammer, peepso, IS_DESKTOP ) {

function PsCrop( config ) {
    this.$elem = $( config.elem );
    this.$wrapper = $('<div class="ps-crop-wrapper" />');
    this.$cropper = $('<div class="ps-crop-box" />');
    this.$wnd = $( window );
    this.change = config.change || $.noop;
    this.attach();
    this.measurements = this.measure();
    this.change( this.getSelection() );
}

PsCrop.prototype.attach = function() {
    this.$elem.wrap( this.$wrapper );
    this.$cropper.insertAfter( this.$elem );
    this.$wrapper = this.$elem.parent();

    if ( !this.hammertime ) {
        this.hammertime = new Hammer( this.$cropper[0] );
        this.hammertime.on('pan panstart panend', $.proxy(function( e ) {
            e.srcEvent.stopPropagation();
            e.srcEvent.preventDefault();
            if ( e.type === 'panstart' ) {
                this.disableDesktopEvents();
                this.onTouch( e );
            } else if ( e.type === 'pan' ) {
                this.onDragOrResize( e );
            } else {
                this.onRelease( e );
                this.enableDesktopEvents();
            }
        }, this ));
    }

    this.enableDesktopEvents();
};

PsCrop.prototype.detach = function() {
    this.$cropper.detach();
    if ( this.$elem.parent().is( this.$wrapper ) ) {
        this.$elem.unwrap();
    }
};

PsCrop.prototype.measure = function() {
    var img = this.$elem,
        wrp = this.$wrapper[0],
        pos = this.$cropper.position();

    return {
        imageWidth     : img.width(),
        imageHeight    : img.height(),
        wrapperTop     : wrp.scrollTop,
        wrapperLeft    : wrp.scrollLeft,
        wrapperWidth   : this.$wrapper.width(),
        wrapperHeight  : this.$wrapper.height(),
        cropperTop     : pos.top + wrp.scrollTop,
        cropperLeft    : pos.left + wrp.scrollLeft,
        cropperWidth   : this.$cropper.outerWidth(),
        cropperHeight  : this.$cropper.outerHeight()
    };
};

PsCrop.prototype.getPointerPosition = function( x, y ) {
    var offset = this.$cropper.offset();

    return {
        top: y - offset.top + this.$wnd.scrollTop(),
        left: x - offset.left + this.$wnd.scrollLeft()
    };
};

PsCrop.prototype.getResizeDirection = function( e ) {
    var treshhold = IS_DESKTOP ? 15 : 20,
        pos = this.getPointerPosition( e.center.x, e.center.y ),
        mea = this.measurements,
        dir = '';

    if ( pos.top < treshhold ) {
        dir += 'n';
    } else if ( pos.top > mea.cropperHeight - treshhold ) {
        dir += 's';
    }

    if ( pos.left < treshhold ) {
        dir += 'w';
    } else if ( pos.left > mea.cropperWidth - treshhold ) {
        dir += 'e';
    }

    return dir;
};

PsCrop.prototype.getSelection = function() {
    return {
        x: this.measurements.cropperLeft,
        y: this.measurements.cropperTop,
        width: this.measurements.cropperWidth,
        height: this.measurements.cropperHeight
    };
};

PsCrop.prototype.onTouch = function( e ) {
    this.released = false;
    this.measurements = this.measure();
    this.direction = this.getResizeDirection( e );
};

PsCrop.prototype.onDragOrResize = _.throttle(function( e ) {
    this.direction ? this.onResize( e ) : this.onDrag( e );
}, IS_DESKTOP ? 10 : 100 );

PsCrop.prototype.onDrag = function( e ) {
    var mea = this.measurements,
        top = e.deltaY,
        left = e.deltaX,
        value;

    // Stop on `panend` event.
    if ( this.released ) {
        return;
    }

    // Respect horizontal boundaries.
    left = Math.min( left, mea.imageWidth - mea.cropperWidth - mea.cropperLeft );
    left = Math.max( left, 0 - mea.cropperLeft );

    // Respect vertical boundaries.
    top = Math.min( top, mea.imageHeight - mea.cropperHeight - mea.cropperTop );
    top = Math.max( top, 0 - mea.cropperTop );

    value = 'translate3d(' + left + 'px, ' + top + 'px, 0)';

    this.$cropper.css({
        webkitTransform: value,
        mozTransform: value,
        transform: value
    });
};

PsCrop.prototype.onResize = function( e ) {
    var dir = this.direction,
        mea = this.measurements,
        css = {};

    // Stop on `panend` event.
    if ( this.released ) {
        return;
    }

    if ( dir.match( /n/ ) ) {
        css.top    = 'auto';
        css.bottom = mea.wrapperHeight - mea.cropperTop - mea.cropperHeight;
        css.height = mea.cropperHeight - e.deltaY;
    } else if ( dir.match( /s/ ) ) {
        css.bottom = 'auto';
        css.top    = mea.cropperTop;
        css.height = mea.cropperHeight + e.deltaY;
    }

    if ( dir.match( /e/ ) ) {
        css.right = 'auto';
        css.left  = mea.cropperLeft;
        css.width = mea.cropperWidth + e.deltaX;
    } else if ( dir.match( /w/ ) ) {
        css.left  = 'auto';
        css.right = mea.wrapperWidth - mea.cropperLeft - mea.cropperWidth;
        css.width = mea.cropperWidth - e.deltaX;
    }

    // Restrict cropper box to 1:1 ratio.
    css.width = css.height = Math.max( css.width || 0, css.height || 0, 64 );

    // Respect vertical boundaries.
    if ( dir.match( /n/ ) ) {
        css.height = Math.min( css.height, mea.wrapperHeight - css.bottom );
    } else if ( dir.match( /s/ ) ) {
        css.height = Math.min( css.height, mea.imageHeight - css.top );
    } else if ( this.$cropper[0].style.top !== 'auto' ) {
        css.height = Math.min( css.height, mea.imageHeight - parseInt( this.$cropper.css('top') ) );
    } else {
        css.height = Math.min( css.height, mea.wrapperHeight - parseInt( this.$cropper.css('bottom') ) );
    }

    // Respect horizontal boundaries.
    if ( dir.match( /e/ ) ) {
        css.width = Math.min( css.width, mea.imageWidth - css.left );
    } else if ( dir.match( /w/ ) ) {
        css.width = Math.min( css.width, mea.wrapperWidth - css.right );
    } else if ( this.$cropper[0].style.left !== 'auto' ) {
        css.width = Math.min( css.width, mea.imageWidth - parseInt( this.$cropper.css('left') ) );
    } else {
        css.width = Math.min( css.width, mea.wrapperWidth - parseInt( this.$cropper.css('right') ) );
    }

    // Restrict cropper box to 1:1 ratio.
    css.width = css.height = Math.min( css.width, css.height );

    this.$cropper.css( css );
};

PsCrop.prototype.onRelease = function( e ) {
    var pos = this.$cropper.position(),
        mea = this.measurements,
        css = {
            top: Math.max( pos.top + mea.wrapperTop, 0 ),
            left: Math.max( pos.left + mea.wrapperLeft, 0 ),
            right: '',
            bottom: '',
            webkitTransform: '',
            mozTransform: '',
            transform: ''
        };

    this.released = true;
    this.$cropper.css( css );
    this.measurements = this.measure();
    this.change( this.getSelection() );
};

PsCrop.prototype.destroy = function() {
    this.detach();
    this.$elem = null;
    this.$wrapper = null;
    this.$cropper = null;
    this.$wnd = null;
    this.hammertime = null;
};

PsCrop.prototype.enableDesktopEvents = function() {
    if ( IS_DESKTOP ) {
        this.$cropper.on('mousemove.ps-crop', $.proxy( this.onMouseMove, this ));
    }
};

PsCrop.prototype.disableDesktopEvents = function() {
    if ( IS_DESKTOP ) {
        this.$cropper.off('mousemove.ps-crop');
    }
};

PsCrop.prototype.onMouseMove = function( e ) {
    var parentOffset = $( e.target ).parent().offset(),
        relX = e.pageX - parentOffset.left,
        relY = e.pageY - parentOffset.top,
        treshhold = 15,
        cursor = '',
        m = this.measure();

    if ( relY < m.cropperTop - m.wrapperTop + treshhold ) {
        cursor += 'n';
    } else if ( relY > m.cropperTop - m.wrapperTop + m.cropperHeight - treshhold ) {
        cursor += 's';
    }

    if ( relX < m.cropperLeft - m.wrapperLeft + treshhold ) {
        cursor += 'w';
    } else if ( relX > m.cropperLeft - m.wrapperLeft + m.cropperWidth - treshhold ) {
        cursor += 'e';
    }

    this.$cropper.css({ cursor: cursor ? cursor + '-resize' : '' });
};

return PsCrop;

});
