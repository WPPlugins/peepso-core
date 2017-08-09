(function( $ ) {

    var $container = $('.ps-js-fields-container');

    // input handler
    $container.on('input', '.ps-js-field input[type=text]', function() {
        var $ct = $( this ).closest('.ps-js-fieldconf'),
            $btns = $ct.find('.ps-js-btn');
        $btns.show();
    })
    .on('keydown', '.ps-js-field input[type=text]', function( e ) {
        var $ct, $btn;
        if ( e.keyCode === 13 ) {
            e.preventDefault();
            e.stopPropagation();
            $ct = $( this ).closest('.ps-js-fieldconf');
            $btn = $ct.find('.ps-js-save');
            $btn.click();
        }
    })
    .find('.ps-js-field input[type=text]').each(function() {
        $(this).data('original-value', this.value );
    });

    $container.on('click', '.ps-js-field .ps-js-cancel', function() {
        var $ct = $( this ).closest('.ps-js-fieldconf'),
            $btns = $ct.find('.ps-js-btn'),
            $input = $ct.find('input[type=text]');

        $btns.hide();
        $input.val( $input.data('original-value') );
        $input.tooltip('destroy');
    });

    // text input handler
    $container.on('click', '.ps-js-field .ps-js-save', function() {
        var $ct = $( this ).closest('.ps-js-fieldconf'),
            $btns = $ct.find('.ps-js-btn'),
            $input = $ct.find('input[type=text]'),
            $progress = $ct.find('.ps-js-progress'),
            url, params;

        url = 'adminConfigFields.set_' + ( $input.data('prop-type') === 'meta' ? 'meta' : 'prop' );
        params = {
            id: $input.data('parent-id') || undefined,
            prop: $input.data('prop-name') || undefined,
            key: $input.data('prop-key') || undefined,
            value: $input.val()
        };

        // check min-max value
        if ( params.prop === 'validation' ) {
            var rMatch = /^[a-z]+(min|max)_value$/,
                oMatch = params.key.match( rMatch ),
                invalid = false,
                $tab, $pairvalue, $paircheck, pairkey, pairval;

            if ( oMatch ) {
                if (( $.trim( params.value ) === '' ) || isNaN( params.value ) || ( +params.value < 0 )) {
                    invalid = peepsoadminprofilesdata.number_invalid;
                } else {
                    pairkey = params.key.replace( oMatch[1], oMatch[1] === 'min' ? 'max' : 'min' );
                    $tab = $ct.closest('.ps-tab__content');
                    $pairvalue = $tab.find('[data-prop-key="' + pairkey + '"]');
                    $paircheck = $tab.find('[data-prop-key="' + pairkey.replace('_value', '') + '"]');
                    // validate with paired value
                    if ( $paircheck[0].checked ) {
                        pairval = $pairvalue.data('original-value') || $pairvalue.val();
                        if ( oMatch[1] === 'min' && ( +pairval < +params.value )) {
                            invalid = peepsoadminprofilesdata.min_invalid;
                            invalid = invalid.replace(/%d/g, +pairval );
                        } else if ( oMatch[1] === 'max' && ( +pairval > +params.value )) {
                            invalid = peepsoadminprofilesdata.max_invalid;
                            invalid = invalid.replace(/%d/g, +pairval );
                        }
                    }
                }
            }

            // show tooltip if value is invalid
            if ( invalid ) {
                $input.attr('title', invalid );
                $input.addClass('tooltip-error');
                $input.tooltip({ placement: 'left', trigger: 'manual' }).tooltip('show');
                return;
            }

            // destroy tooltip if entered value is valid
            $input.tooltip('destroy');
        }

        $progress.find('img').show();
        $input.attr('readonly', 'readonly');

        $PeepSo.postJson( url, params, function( json ) {
            $progress.find('img').hide();
            $progress.find('i').show().delay( 800 ).fadeOut();
            $input.removeAttr('readonly');

            if ( json.success ) {
                $btns.hide();
                $input.data('original-value', params.value );

                // change box title if saving the field title
                if('post_title' == $input.data('prop-name')) {
                    $('#field-' + params.id + '-box-title').html(params.value);
                }

            } else {
                // TODO
            }
        });
    });

    // checkbox handler
    $container.on('click', 'input[type=checkbox]', _.throttle(function() {
        var $cbx = $( this ),
            $progress = $cbx.closest('.ps-js-fieldconf').find('.ps-js-progress'),
            checked = $cbx[0].checked,
            updatevalue = false,
            url, params;

        url = 'adminConfigFields.set_' + ( $cbx.data('prop-type') === 'meta' ? 'meta' : 'prop' );
        params = {
            id: $cbx.data('parent-id') || undefined,
            prop: $cbx.data('prop-name') || undefined,
            key: $cbx.data('prop-key') || undefined,
            value: checked ? $cbx.val() : $cbx.data('disabled-value')
        };

        // check min-max value
        if ( checked && ( params.prop === 'validation')) {
            var rMatch = /^[a-z]+(min|max)$/,
                oMatch = params.key.match( rMatch ),
                $tab, $value, $valuebtn, $paircheck, $pairvalue, pairkey, pairval;

            if ( oMatch ) {
                pairkey = params.key.replace( oMatch[1], oMatch[1] === 'min' ? 'max' : 'min' );
                $tab = $cbx.closest('.ps-tab__content');
                $paircheck = $tab.find('[data-prop-key="' + pairkey + '"]');
                $pairvalue = $tab.find('[data-prop-key="' + pairkey + '_value"]');
                // validate with paired value
                if ( $paircheck[0].checked ) {
                    pairval = $pairvalue.data('original-value') || $pairvalue.val();
                    if ( oMatch[1] === 'min' && ( +pairval < +params.value )) {
                        updatevalue = true;
                    } else if ( oMatch[1] === 'max' && ( +pairval > +params.value )) {
                        updatevalue = true;
                    }
                }
                // update validation value if necessary
                if ( updatevalue ) {
                    $value = $tab.find('[data-prop-key="' + params.key + '_value"]');
                    $valuebtn = $value.nextAll('.ps-js-save');
                    $value.val( pairval );
                }
            }
        }

        $progress.find('i').stop().hide();
        $progress.find('img').show();
        $cbx.attr('readonly', 'readonly');

        $PeepSo.postJson( url, params, function( json ) {
            $progress.find('img').hide();
            $progress.find('i').show().delay( 800 ).fadeOut();
            $cbx.removeAttr('readonly');

            if ( json.success ) {
                // update validation value if necessary
                if ( updatevalue ) {
                    $valuebtn.trigger('click');
                }

                $value_container_id = '#' + $cbx[0].id + '-value-container';
                $container_id = '#' + $cbx[0].id + '-container';

                if(checked) {
                    $($value_container_id).fadeIn(500);
                } else {
                    $($value_container_id).fadeOut(500);
                }

                if ( params.prop === 'validation' && params.key === 'required' ) {
                    var $mark = $('#field-' + params.id + '-required-mark');
                    if ( checked ) {
                        $mark.removeClass('hidden');
                    } else {
                        $mark.addClass('hidden');
                    }
                } else if ( params.prop === 'post_status' ) {
                    if ( checked ) {
                        $cbx.closest('.postbox').removeClass('postbox-muted');
                    } else {
                        $cbx.closest('.postbox').addClass('postbox-muted');
                    }
                }
            } else {
                // TODO
            }
        });
    }, 1000 ));

    // select handler
    $container.on('change', 'select', _.throttle(function() {
        var $sel = $( this ),
            $progress = $sel.closest('.ps-js-fieldconf').find('.ps-js-progress'),
            url, params;

        url = 'adminConfigFields.set_' + ( $sel.data('prop-type') === 'meta' ? 'meta' : 'prop' );
        params = {
            id: $sel.data('parent-id') || undefined,
            prop: $sel.data('prop-name') || undefined,
            key: $sel.data('prop-key') || undefined,
            value: $sel.val()
        };

        $progress.find('i').stop().hide();
        $progress.find('img').show();
        $sel.attr('readonly', 'readonly');

        $PeepSo.postJson( url, params, function( json ) {
            $progress.find('img').hide();
            $progress.find('i').show().delay( 800 ).fadeOut();
            $sel.removeAttr('readonly');

            if ( json.success ) {
                // TODO
            } else {
                // TODO
            }
        });
    }, 1000 ));

    // drag n' drop functionality
    $container.sortable({
        handle: '.ps-js-handle',
        update: _.throttle(function() {
            var fields = [];
            $('.ps-js-fields-container .postbox').each(function() {
                fields.push( $(this).data('id') );
            });

            $PeepSo.postJson('adminConfigFields.set_order', { fields: JSON.stringify(fields) }, function( json ) {});
        }, 3000 )
    });

    // toggle a field
    $container.on('click', '.ps-js-field-toggle', function() {
        var $btn = $(this),
            $el = $btn.closest('.postbox'),
            $field = $el.find('.ps-js-field'),
            id = $el.data('id');

        if ( $field.is(':visible') ) {
            $field.slideUp('fast', function() {
                $btn.removeClass('fa-compress').addClass('fa-expand');
                updateToggleAllButton();
                updateFieldVisibility( id, 0 );
            });
        } else {
            $field.slideDown('fast', function() {
                $btn.removeClass('fa-expand').addClass('fa-compress');
                updateToggleAllButton();
                updateFieldVisibility( id, 1 );
            });
        }
    });

    // toggle a field
    $container.on('click', '.ps-js-field-title', function( e ) {
        if ( e.target === e.currentTarget ) {
            $(this).closest('.ps-postbox__title').find('.ps-js-field-toggle').click();
        }
    });

    // toggle drag-n-drop cursor
    var mousedownTimer;
    $container.on('mousedown', '.ps-postbox__title', function( e ) {
        var $this = $( e.currentTarget );
        mousedownTimer = setTimeout(function() {
            $this.addClass('ps-js-mousedown');
        }, 200 );
    }).on('mouseup mouseleave', '.ps-postbox__title', function( e ) {
        clearTimeout( mousedownTimer );
        $( e.currentTarget ).removeClass('ps-js-mousedown');
    });

    // toggle expand all fields
    $('.ps-js-field-expand-all').on('click', function() {
        $container.find('.ps-js-field').slideDown('fast', function() {
            toggleAllCallback(1);
        });
    });

    // toggle collapse all fields
    $('.ps-js-field-collapse-all').on('click', function() {
        $container.find('.ps-js-field').slideUp('fast', function() {
            toggleAllCallback(0);
        });
    });

    var toggleAllCallback = _.debounce(function( status ) {
        var $fields = $('.ps-js-fields-container').children('.postbox');

        if ( status === 0 ) {
            $fields.find('.ps-js-field-toggle').removeClass('fa-compress').addClass('fa-expand');
        } else {
            $fields.find('.ps-js-field-toggle').removeClass('fa-expand').addClass('fa-compress');
        }

        updateToggleAllButton( status );
        updateFieldVisibility('all', status );
    }, 200 );

    function updateToggleAllButton( status ) {
        var $btn = $('.ps-js-field-toggle-all'),
            $icon = $btn.find('span').first(),
            $label = $btn.find('span').last(),
            len, visible;

        if ( typeof status === 'undefined' ) {
            len = 0;
            visible = 0;
            status = 0;
            $('.ps-js-fields-container').find('.ps-js-field').each(function() {
                len++;
                if ( $(this).is(':visible') ) {
                    visible++;
                }
            });
            if ( visible >= len ) {
                status = 1;
            }
        }

        if ( +status === 0 ) {
            $btn.data('status', 0 );
            $label.html( $btn.data('expand-text') );
            $icon.removeClass('fa-compress').addClass('fa-expand');
        } else {
            $btn.data('status', 1 );
            $label.html( $btn.data('collapse-text') );
            $icon.removeClass('fa-expand').addClass('fa-compress');
        }
    }

    // check button on page-load
    updateToggleAllButton();

    var updateFieldXHR = {};
    var updateFieldVisibility = _.debounce(function( id, status ) {
        var ids = [];
        if ( id !== 'all' ) {
            ids = [ id ];
        } else {
            $('.ps-js-fields-container').children('.postbox').each(function() {
                ids.push( $(this).data('id') )
            });
        }

        updateFieldXHR[ id ] && updateFieldXHR[ id ].ret && updateFieldXHR[ id ].ret.abort();
        updateFieldXHR[ id ] = $PeepSo.postJson('adminConfigFields.set_admin_box_status', { id: JSON.stringify( ids ), status: status }, function( json ) {
            // Do nothing
        });
    }, 500 );

    // add new field button
    $('.ps-js-field-new').on('click', function() {
        var id = 'no-plugin-warning',
            $popup = $('#' + id);

        if ( !$popup.length ) {
            var data = window.peepsoadminprofilesdata || {};
            $popup = $( data.popup_template );
            $popup.prop('id', id );
            $popup.appendTo( document.body );
            $popup.on('click', '.button-link', function() {
                $popup.hide();
            })
            $popup.on('click', '.button-primary', function( e ) {
                e.preventDefault();
                e.stopPropagation();
                $popup.hide();
                window.open( data.plugin_url );
            });
        }

        $popup.show();
    });

    // tabs handler
    $container.on('click', '.ps-tabs a', function() {
        var $current_tab = $( this ),
            $active_tab = $current_tab.closest('.ps-tabs').find('.active');

        $active_tab.removeClass('active');
        $current_tab.addClass('active');
        $( $active_tab.attr('href') ).hide();
        $( $current_tab.attr('href') ).show();

        return false;
    });

    // edit title handler
    $container.on('click', '.ps-postbox__title .fa-edit', function() {
        var $ct = $( this ).closest('.ps-postbox__title'),
            $label = $ct.find('.ps-postbox__title-label'),
            $editor = $ct.find('.ps-postbox__title-editor'),
            $input = $editor.find('input[type=text]'),
            $btn = $input.nextAll('.ps-js-save'),
            isDefault = $input.data('prop-title-is-default'),
            value = $input.val();

        $label.hide();
        $editor.show();
        $input.data('original-value', value ).focus();
        $input.val( isDefault ? '' : value ).trigger('input');
    });

    // edit title handler
    $container.on('click', '.ps-js-field-title-text', function() {
        var $ct = $( this ).closest('.ps-postbox__title'),
            $btn = $ct.find('.fa-edit');
        $btn.click();
    });

    // cancel edit title handler
    $container.on('click', '.ps-postbox__title .ps-js-cancel', function() {
        var $ct = $( this ).closest('.ps-postbox__title'),
            $label = $ct.find('.ps-postbox__title-label'),
            $editor = $ct.find('.ps-postbox__title-editor'),
            $input = $editor.find('input[type=text]');

        $input.val( $input.data('original-value') );
        $editor.hide();
        $label.show();
    });

    // save edit title handler
    $container.on('click', '.ps-postbox__title .ps-js-save', function() {
        var $ct = $( this ).closest('.ps-postbox__title'),
            $label = $ct.find('.ps-postbox__title-label'),
            $editor = $ct.find('.ps-postbox__title-editor'),
            $input = $editor.find('input[type=text]'),
            $progress = $ct.find('.ps-js-progress'),
            url, params;

        url = 'adminConfigFields.set_' + ( $input.data('prop-type') === 'meta' ? 'meta' : 'prop' );
        params = {
            id: $input.data('parent-id') || undefined,
            prop: $input.data('prop-name') || undefined,
            key: $input.data('prop-key') || undefined,
            value: $input.val()
        };

        $progress.find('img').show();
        $input.attr('readonly', 'readonly');

        $PeepSo.postJson( url, params, function( json ) {
            $progress.find('img').hide();
            $progress.find('i').show().delay( 800 ).fadeOut();
            $input.removeAttr('readonly');

            if ( json.success ) {
                $editor.hide();
                $label.show();
                $input.data('original-value', params.value );
                $input.data('prop-title-is-default', false );
                $('#field-' + params.id + '-box-title').html(params.value);
            } else {
                // TODO
            }
        });
    });

    // save edit title handler on enter
    $container.on('keydown', '.ps-postbox__title input[type=text]', function( e ) {
        var $btn;
        if ( e.keyCode === 13 ) {
            e.preventDefault();
            e.stopPropagation();
            $btn = $( this ).nextAll('.ps-js-save');
            $btn.click();
        }
    }).on('input', '.ps-postbox__title input[type=text]', function( e ) {
        var $btn = $( this ).nextAll('.ps-js-save');
        if ( !$.trim( this.value ) ) {
            $btn.attr('disabled', 'disabled');
        } else {
            $btn.removeAttr('disabled');
        }
    });

    // cycle through option
    $container.on('focus', '.ps-js-focusguard', function() {
        var $guard = $( this ),
            $fields = $guard.closest('.ps-js-options').children('.ps-js-fieldconf');

        if ( $guard.data('tag') === 'last' ) {
            $fields.find('input').first().focus();
        } else {
            $fields.find('input').last().focus();
        }
    });

    // float-bar
    $(function() {
        var bar = $('.ps-settings__bar');
        bar.addClass('ps-settings__bar--static');

        $( window ).scroll(function() {
            var bar = $('.ps-settings__bar');
            var scrollVal = $( this ).scrollTop();
            if ( scrollVal > 50 ) {
                bar.removeClass('ps-settings__bar--static');
            } else {
                bar.addClass('ps-settings__bar--static');
            }
        });
    });

})( jQuery );
