/**
 * jQuery Form Builder
 * Written by Salvatore Ingala in 2011
 * Released under the MIT and GPL licenses.
 */

// TODO: Review this whole file

( function( $, mw ) {

	//Field types that can be referred to by preference descriptions
	var	validFieldTypes = {}, //filled when constructors are initialized
		prefsSpecifications;  //defined later, declaring here to avoid references to undeclared variable

	/* Utility functions */
	
	/**
	 * Preprocesses strings end possibly replaces them with messages.
	 * If str starts with "@" the rest of the string is assumed to be
	 * a message, and the result of mw.msg is returned.
	 * Two "@@" at the beginning escape for a single "@".
	 */
	function preproc( msgPrefix, str ) {
		if ( str.length <= 1 || str.charAt( 0 ) !== '@' ) {
			return str;
		} else if ( str.substr( 0, 2 ) == '@@' ) {
			return str.substr( 1 );
		} else {
			if ( !msgPrefix ) {
				msgPrefix = "";
			}
			return mw.message( msgPrefix + str.substr( 1 ) ).plain();
		}
	}

	/**
	 * Commodity function to avoid id conflicts
	 */
	var getIncrementalCounter = ( function() {
		var cnt = 0;
		return function() {
			return cnt++;
		};
	} )();

	/**
	 * Pads a number with leading zeroes until length is n characters
	 */
	function pad( n, len ) {
		// TODO optimize
		var res = '' + n;
		while ( res.length < len ) {
			res = '0' + res;
		}
		return res;
	}

	/**
	 * Returns an object with only one key and the corresponding value given in arguments
	 */
	function pair( key, val ) {
		var res = {};
		res[key] = val;
		return res;
	}

	function isInteger( val ) {
		return typeof val == 'number' && val === Math.floor( val );
	}

	/**
	 * Returns true if val is either true, false, null, a number or a string
	 */
	function isScalar( val ) {
		return val === true || val === false || val === null
			|| typeof val == 'number' || typeof val == 'string';
	}

	/**
	 * Returns true if name is a valid preference name
	 */
	function isValidPreferenceName( name ) {
		return typeof name == 'string'
			&& /^[a-zA-Z_][a-zA-Z0-9_]*$/.test( name )
			&& name.length <= 40;
	}

	/**
	 * Make a deep copy of an object
	 */
	function clone( obj ) {
		return $.extend( true, {}, obj );
	}

	/**
	 * Helper function for inheritance, see http://javascript.crockford.com/prototypal.html
	 */
	function object( o ) {
		function F() {}
		F.prototype = o;
		return new F();
	}

	/**
	 * Helper function for inheritance
	 */
	function inherit( Derived, Base ) {
		Derived.prototype = object( Base.prototype );
		Derived.prototype.constructor = Derived;
	}

	/**
	 * Add a "smart" listener to watch for changes to an <input /> element
	 * This binds to several events, but calls the callback only if the value actually changed
	 */
	function addSmartChangeListener( $input, callback ) {
		var oldValue = $input.val();
		//bind all events that may change the value of the field (some are brower-specific)
		$input.bind( 'keyup change propertychange input paste', function() {
			var newValue = $input.val();
			if ( oldValue !== newValue ) {
				oldValue = newValue;
				callback();
			}
		} );
	}

	/* Validator plugin utility functions and methods */

	/**
	 * Removes the field rules of "field" to the formbuilder form.
	 * NOTE: this method must be called before physically removing the element from the form.
	 */
	function deleteFieldRules( field ) {
		//Remove all its validation rules
		var validationSettings = field.getValidationSettings();
		if ( validationSettings.rules ) {
			$.each( validationSettings.rules, function( name, value ) {
				$( '#' + name ).rules( 'remove' );
			} );
		}
	}

	/**
	 * Adds the field rules of "field" to the formbuilder form.
	 * NOTE: the field's element must have been appended to the form, yet.
	 */
	function addFieldRules( field ) {
		var validationSettings = field.getValidationSettings();
		if ( validationSettings.rules ) {
			$.each( validationSettings.rules, function( name, rules ) {
				//Find messages associated to this rule, if any
				if ( typeof validationSettings.messages != 'undefined' &&
					typeof validationSettings.messages[name] != 'undefined')
				{
					rules.messages = validationSettings.messages[name];
				}
				
				$( field.getElement() ).find( '#' + name ).rules( 'add', rules );
			} );
		}
	}


	function testOptional( value, element ) {
		var rules = $( element ).rules();
		if ( typeof rules.required == 'undefined' || rules.required === false ) {
			if ( value.length === 0 ) {
				return true;
			}
		}
		return false;
	}

	//validator for "required" fields (without trimming whitespaces)
	$.validator.addMethod( "requiredStrict", function( value, element ) {
		return value.length > 0;
	}, mw.msg( 'gadgets-formbuilder-required' ) );

	//validator for "minlength" fields (without trimming whitespaces)
	$.validator.addMethod( "minlengthStrict", function( value, element, param ) {
		return testOptional( value, element ) || value.length >= param;
	} );

	//validator for "maxlength" fields (without trimming whitespaces)
	$.validator.addMethod( "maxlengthStrict", function( value, element, param ) {
		return testOptional( value, element ) || value.length <= param;
	} );

	//validator for integer fields
	$.validator.addMethod( "integer", function( value, element ) {
		return testOptional( value, element ) || /^-?\d+$/.test(value);
	}, mw.msg( 'gadgets-formbuilder-integer' ) );

	//validator for datepicker fields
	$.validator.addMethod( "datePicker", function( value, element ) {
		var format = $( element ).datepicker( 'option', 'dateFormat' );
		try {
			var date = $.datepicker.parseDate( format, value );
			return true;
		} catch ( e ) {
			return false;
		}
	}, mw.msg( 'gadgets-formbuilder-date' ) );

	//validator for colorpicker fields
	$.validator.addMethod( "color", function( value, element ) {
		return $.colorUtil.getRGB( value ) !== undefined;
	}, mw.msg( 'gadgets-formbuilder-color' ) );

	//validator for scalar fields
	$.validator.addMethod( "scalar", function( value, element ) {
		//parseJSON decodes interprets the empty string as 'null', and we don't want that
		if ( value === '' ) {
			return false;
		}
		
		try {
			if ( isScalar( $.parseJSON( value ) ) ) {
				return true;
			}
		} catch( e ) { /* nothing */ }
		
		return false;
	}, mw.msg( 'gadgets-formbuilder-scalar' ) );

	/* Functions used by the preferences editor */
	function createFieldDialog( params, options ) {
		var self = this;
		
		if ( !$.isFunction( params.callback ) ) {
			$.error( 'createFieldDialog: missing or wrong "callback" parameter' );
		}
		
		if ( typeof options == 'undefined' ) {
			options = {};
		}
		
		var type, description, values;
		if ( typeof params.description == 'undefined' && typeof params.type == 'undefined' ) {
			//Create a dialog to choose the type of field to create
			var selectOptions = [];
			$.each( validFieldTypes, function( fieldType ) {
				selectOptions.push( {
					name: fieldType,
					value: fieldType
				} );
			} );
			
			$( {
				fields: [ {
					'name': "type",
					'type': "select",
					'label': mw.msg( 'gadgets-formbuilder-editor-choose-field' ),
					'options': selectOptions,
					'default': selectOptions[0].value
				} ]
			} ).formBuilder( { idPrefix: 'choose-field-' } )
				.submit( function() {
					return false; //prevent form submission
				} )
				.dialog( {
					width: 450,
					modal: true,
					resizable: false,
					title: mw.msg( 'gadgets-formbuilder-editor-choose-field-title' ),
					close: function() {
						$( this ).remove();
					},
					buttons: [
						{
							text: mw.msg( 'gadgets-formbuilder-editor-ok' ),
							click: function() {
								var values = $( this ).formBuilder( 'getValues' );
								$( this ).dialog( "close" );
								createFieldDialog( {
									type: values.type,
									oldDescription: params.oldDescription,
									callback: params.callback
								}, options );
							}
						},
						{
							text: mw.msg( 'gadgets-formbuilder-editor-cancel' ),
							click: function() {
								$( this ).dialog( "close" );
							}
						}
					]
				} );
			
			return;
		} else {
			type = params.type;
			if ( typeof prefsSpecifications[type] == 'undefined' ) {
				$.error( 'createFieldDialog: invalid type: ' + type );
			} else if ( $.isFunction( prefsSpecifications[type].builder ) ) {
				prefsSpecifications[type].builder( options, function( field ) {
					if ( field !== null ) {
						params.callback( field );
					}
				} );
				return;
			}
			
			//typeof prefsSpecifications[type].builder == 'object'
			
			description = {
				fields: prefsSpecifications[type].builder
			};
		}
		
		if ( typeof params.values != 'undefined' ) {
			values = params.values;
		} else {
			values = {};
		}
		
		//Create the dialog to set field properties
		var dlg = $( '<div/>' );
		var form = $( description ).formBuilder( {
			values: values,
			idPrefix: 'create-field-'
		} ).submit( function() {
			return false; //prevent form submission
		} ).appendTo( dlg );
		
		dlg.dialog( {
			modal: true,
			width: 550,
			resizable: false,
			title: mw.msg( 'gadgets-formbuilder-editor-create-field-title', type ),
			close: function() {
				$( this ).remove();
			},
			buttons: [
				{
					text: mw.msg( 'gadgets-formbuilder-editor-ok' ),
					click: function() {
						var isValid = $( form ).formBuilder( 'validate' );
							
						if ( isValid ) {
							var fieldDescription = $( form ).formBuilder( 'getValues' );
							
							if ( typeof type != 'undefined' ) {
								//Remove properties that equal their default
								$.each( description.fields, function( index, fieldSpec ) {
									var property = fieldSpec.name;
									if ( fieldDescription[property] === fieldSpec['default'] ) {
										delete fieldDescription[property];
									}
								} );
							}
							
							//Try to create the field. In case of error, warn the user.
							fieldDescription.type = type;
							
							if ( typeof params.oldDescription != 'undefined' ) {
								//If there are values in the old description that cannot be set by
								//the dialog, don't lose them (e.g.: 'fields' member in composite fields).
								$.each( params.oldDescription, function( key, value ) {
									if ( typeof fieldDescription[key] == 'undefined' ) {
										fieldDescription[key] = value;
									}
								} );
							}
							
							var	FieldConstructor = validFieldTypes[type],
								field;
							
							try {
								field = new FieldConstructor( fieldDescription, options );
							} catch ( err ) {
								alert( "Invalid field options: " + err ); //TODO: i18n
								return;
							}

							if ( params.callback( field ) === true ) {
								$( this ).dialog( "close" );
							}
						}
					}
				},
				{
					text: mw.msg( 'gadgets-formbuilder-editor-cancel' ),
					click: function() {
						$( this ).dialog( "close" );
						params.callback( null );
					}
				}
			]
		} );
	}

	function showEditFieldDialog( fieldDesc, listParams, options, callback ) {
		$( { "fields": [ fieldDesc ] } )
			.formBuilder( {
				editable: true,
				staticFields: true,
				idPrefix: 'list-edit-field-'
			} )
			.submit( function() {
				return false;
			} )
			.dialog( {
				modal: true,
				width: 550,
				resizable: false,
				title: mw.msg( 'gadgets-formbuilder-editor-edit-field-title' ),
				close: function() {
					$( this ).remove();
				},
				buttons: [
					{
						text: mw.msg( 'gadgets-formbuilder-editor-ok' ),
						click: function() {
							
							if ( !$( this ).formBuilder( 'validate' ) ) {
								return;
							}
							
							var	fieldDesc = $( this ).formBuilder( 'getDescription' ).fields[0],
								name = fieldDesc.name;
							
							delete fieldDesc.name;
							
							$( this ).dialog( "close" );

							var ListField = validFieldTypes.list;
							
							$.extend( listParams, {
								type: 'list',
								name: name,
								field: fieldDesc
							} );
							
							callback( new ListField( listParams, options ) );
						}
					},
					{
						text: mw.msg( 'gadgets-formbuilder-editor-cancel' ),
						click: function() {
							$( this ).dialog( "close" );
							callback( null );
						}
					}
				]
			} );
	}

	/* Basic interface for fields */
	function Field( desc, options ) {
		if ( typeof options.idPrefix == 'undefined' ) {
			options.idPrefix = 'formbuilder-';
		}
		
		this.desc = desc;
		this.options = options;
	}

	Field.prototype.getDesc = function( useValuesAsDefaults ) {
		return this.desc;
	};

	//Override expected
	Field.prototype.getValues = function() {
		return {};
	};

	//Override expected
	Field.prototype.getElement = function() {
		return null;
	};
	
	//Override expected
	Field.prototype.getValidationSettings = function() {
		return {
			rules: {},
			messages: {}
		};
	};

	/*
	 * A field with no content, generating an empty container
	 * and checking existence and type of the 'type' member of description.
	 * 
	 **/
	function EmptyField( desc, options ) {
		Field.call( this, desc, options );
		
		//Check existence and type of the "type" field
		if ( ( !this.desc.type || typeof this.desc.type != 'string' )
			&& !$.isFunction( this.desc.type ) )
		{
			$.error( "Missing 'type' parameter" );
		}

		this.$div = $( '<div/>' )
			.addClass( 'formbuilder-field' )
			.data( 'field', this );
		
		if ( !$.isFunction( this.desc.type ) ) {
			this.$div.addClass( 'formbuilder-field-' + this.desc.type );
		}
	}
	inherit( EmptyField, Field );

	EmptyField.prototype.getElement = function() {
		return this.$div;
	};

	/* A field with just a label */
	function LabelField( desc, options ) {
		EmptyField.call( this, desc, options );

		//Check existence and type of the "label" field
		if ( typeof this.desc.label != 'string' ) {
			$.error( "Missing or wrong 'label' parameter" );
		}

		this.$label = $( '<label/>' )
			.text( preproc( this.options.msgPrefix, this.desc.label ) );

		this.$div.append( this.$label );
	}
	inherit( LabelField, EmptyField );

	validFieldTypes.label = LabelField;

	/* Abstract base class for all "simple" fields. Should not be instantiated. */
	function SimpleField( desc, options ) {
		LabelField.call( this, desc, options );
		
		//Validate the 'name' member
		if ( !isValidPreferenceName( desc.name ) ) {
			$.error( 'invalid name' );
		}

		this.$label.attr('for', this.options.idPrefix + this.desc.name );

		//Use default if it is given and no value has been set
		if ( ( typeof options.values == 'undefined' || typeof options.values[desc.name] == 'undefined' )
			&& typeof desc['default'] != 'undefined' )
		{
			if ( typeof options.values == 'undefined' ) {
				options.values = {};
			}
			
			options.values[desc.name] = desc['default'];
			
			this.options = options;
		}
	}
	inherit( SimpleField, LabelField );
	
	SimpleField.prototype.getDesc = function( useValuesAsDefaults ) {
		var desc = clone( LabelField.prototype.getDesc.call( this, useValuesAsDefaults ) );
		if ( useValuesAsDefaults === true ) {
			//set 'default' to current value.
			var values = this.getValues();
			desc['default'] = values[this.desc.name];
		}
		
		return desc;
	};


	/* A field with a label and a checkbox */
	function BooleanField( desc, options ) {
		SimpleField.call( this, desc, options );

		this.$c = $( '<input/>' ).attr( {
			type: 'checkbox',
			id: this.options.idPrefix + this.desc.name,
			name: this.options.idPrefix + this.desc.name
		} );

		if ( options.change ) {
			this.$c.change( function() {
				options.change();
			} );
		}

		var value = options.values && options.values[this.desc.name];
		if ( typeof value != 'undefined' ) {
			if ( typeof value != 'boolean' ) {
				$.error( "value is invalid" );
			}
			
			this.$c.attr( 'checked', value );
		}

		this.$div.append( this.$c );
	}
	inherit( BooleanField, SimpleField );
	
	BooleanField.prototype.getValues = function() {
		return pair( this.desc.name, this.$c.is( ':checked' ) );
	};

	validFieldTypes.boolean = BooleanField;

	/* A field with a textbox accepting string values */
	function StringField( desc, options ) {
		SimpleField.call( this, desc, options );

		//Validate minlength and maxlength
		var	minlength = typeof desc.minlength != 'undefined' ? desc.minlength : 0,
			maxlength = typeof desc.maxlength != 'undefined' ? desc.maxlength : 1024;
		
		if ( !isInteger( minlength ) || minlength < 0 ) {
			$.error( "minlength must be a non-negative integer" );
		}
		if ( !isInteger( maxlength ) || maxlength <= 0 ) {
			$.error( "maxlength must be a positive integer" );
		}
		if ( maxlength < minlength ) {
			$.error( "maxlength must be no less than minlength" );
		}

		this.$text = $( '<input/>' ).attr( {
			type: 'text',
			id: this.options.idPrefix + this.desc.name,
			name: this.options.idPrefix + this.desc.name
		} );

		var value = options.values && options.values[this.desc.name];
		if ( typeof value != 'undefined' ) {
			if ( typeof value != 'string' ) {
				$.error( "value is invalid" );
			}
			
			this.$text.val( value );
		}

		//Add the change event listener
		if ( options.change ) {
			addSmartChangeListener( this.$text, options.change );
		}

		this.$div.append( this.$text );
	}
	inherit( StringField, SimpleField );
	
	StringField.prototype.getValues = function() {
		return pair( this.desc.name, this.$text.val() );
	};

	StringField.prototype.getValidationSettings = function() {
		var	settings = SimpleField.prototype.getValidationSettings.call( this ),
			fieldId = this.options.idPrefix + this.desc.name;
		
		settings.rules[fieldId] = {};
		var	fieldRules = settings.rules[fieldId],
			desc = this.desc;

		if ( desc.required === true ) {
			fieldRules.requiredStrict = true;
		}
		
		if ( typeof desc.minlength != 'undefined' ) {
			fieldRules.minlengthStrict = desc.minlength;
		}
		if ( typeof desc.maxlength != 'undefined' ) {
			fieldRules.maxlengthStrict = desc.maxlength;
		}
		
		settings.messages = {};
		
		settings.messages[fieldId] = {
			"minlengthStrict": mw.msg( 'gadgets-formbuilder-minlength', desc.minlength ),
			"maxlengthStrict": mw.msg( 'gadgets-formbuilder-maxlength', desc.maxlength )
		};
				
		return settings;
	};

	validFieldTypes.string = StringField;


	/* A field with a textbox accepting numeric values */
	function NumberField( desc, options ) {
		SimpleField.call( this, desc, options );

		//Validation of description
		if ( desc.integer === true ) {
			if ( typeof desc.min != 'undefined' && !isInteger( desc.min ) ) {
				$.error( "min is not an integer" );
			}
			if ( typeof desc.max != 'undefined' && !isInteger( desc.max ) ) {
				$.error( "max is not an integer" );
			}
		}
		
		if ( typeof desc.min != 'undefined' && typeof desc.max != 'undefined' && desc.min > desc.max ) {
			$.error( 'max must be no less than min' );
		}


		this.$text = $( '<input/>' ).attr( {
			type: 'text',
			id: this.options.idPrefix + this.desc.name,
			name: this.options.idPrefix + this.desc.name
		} );

		var value = options.values && options.values[this.desc.name];
		if ( typeof value != 'undefined' ) {
			if ( value !== null && typeof value != 'number' ) {
				$.error( "value is invalid" );
			}
			
			this.$text.val( value );
		}

		//Add the change event listener
		if ( options.change ) {
			addSmartChangeListener( this.$text, options.change );
		}

		this.$div.append( this.$text );
	}
	inherit( NumberField, SimpleField );
	
	NumberField.prototype.getValues = function() {
		var val = parseFloat( this.$text.val() );
		return pair( this.desc.name, isNaN( val ) ? null : val );
	};

	NumberField.prototype.getValidationSettings = function() {
		var	settings = SimpleField.prototype.getValidationSettings.call( this ),
			fieldId = this.options.idPrefix + this.desc.name;
		
		settings.rules[fieldId] = {};
		var	fieldRules = settings.rules[fieldId],
			desc = this.desc;

		if ( desc.required !== false ) {
			fieldRules.requiredStrict = true;
		}

		if ( desc.integer === true ) {
			fieldRules.integer = true;
		}

		
		if ( typeof desc.min != 'undefined' ) {
			fieldRules.min = desc.min;
		}
		if ( typeof desc.max != 'undefined' ) {
			fieldRules.max = desc.max;
		}
		
		settings.messages = {};
		
		settings.messages[fieldId] = {
			"required": mw.msg( 'gadgets-formbuilder-required' ),
			"min": mw.msg( 'gadgets-formbuilder-min', desc.min ),
			"max": mw.msg( 'gadgets-formbuilder-max', desc.max )
		};
				
		return settings;
	};

	validFieldTypes.number = NumberField;

	/* A field with a drop-down list */
	function SelectField( desc, options ) {
		SimpleField.call( this, desc, options );

		var $select = this.$select = $( '<select/>' ).attr( {
			id: this.options.idPrefix + this.desc.name,
			name: this.options.idPrefix + this.desc.name
		} );
		
		var	validValues = [],
			self = this;
		$.each( this.desc.options, function( idx, option ) {
			var i = validValues.length;
			$( '<option/>' )
				.text( preproc( self.options.msgPrefix, option.name ) )
				.val( i )
				.appendTo( $select );
			validValues.push( option.value );
		} );

		this.validValues = validValues;

		var value = options.values && options.values[this.desc.name];
		if ( typeof value != 'undefined' ) {
			if ( $.inArray( value, validValues ) == -1 ) {
				$.error( "value is not in the list of possible values" );
			}

			var i = $.inArray( value, validValues );
			$select.val( i ).prop( 'selected', 'selected' );
		}

		//Add the change event listener
		if ( options.change ) {
			$select.change( function() {
				options.change();
			} );
		}

		this.$div.append( $select );
	}
	inherit( SelectField, SimpleField );
	
	SelectField.prototype.getValues = function() {
		var i = parseInt( this.$select.val(), 10 );
		return pair( this.desc.name, this.validValues[i] );
	};

	validFieldTypes.select = SelectField;

	/* A field with a slider, representing ranges of numbers */
	function RangeField( desc, options ) {
		SimpleField.call( this, desc, options );

		//Validation
		if ( desc.min > desc.max ) {
			$.error( "max must be no less than min" );
		}
		if ( desc.step <= 0 ) {
			$.error( "step must be a positive number" );
		}

		//Check that max differs from min by an integer multiple of step
		//(that is: (max - min) / step is integer, with good approximation)
		var eps = 1.0e-6; //tolerance
		var tmp = ( desc.max - desc.min ) / desc.step;
		if ( Math.abs( tmp - Math.floor( tmp ) ) > eps ) {
			$.error( "The list {min, min + step, min + 2*step, ...} must contain max" );
		}
		
		var value = options.values && options.values[this.desc.name];
		if ( typeof value != 'undefined' ) {
			if ( typeof value != 'number' ) {
				$.error( "value is invalid" );
			}
			if ( value < this.desc.min || value > this.desc.max ) {
				$.error( "value is out of range" );
			}
		}

		var $slider = this.$slider = $( '<div/>' )
			.attr( 'id', this.options.idPrefix + this.desc.name );

		var sliderOptions = {
			min: this.desc.min,
			max: this.desc.max
		};

		if ( typeof value != 'undefined' ) {
			sliderOptions.value = value;
		}

		if ( typeof this.desc.step != 'undefined' ) {
			sliderOptions.step = this.desc.step;
		}

		//A tooltip to show current value.
		//TODO: use jQuery UI tooltips when they are released (in 1.9)
		var $tooltip = $( '<div/>' )
			.addClass( 'formBuilder-slider-tooltip ui-widget ui-corner-all ui-widget-content' )
			.css( {
				position: 'absolute',
				display: 'none'
			} )
			.appendTo( this.$div );

		var tooltipShown = false, sliding = false, mouseOver = false;
		
		function refreshTooltip( visible, handle, value ) {
			if ( !tooltipShown && visible ) {
				$tooltip.fadeIn( 'fast' );
				tooltipShown = true;
			} else if ( tooltipShown && !visible ) {
				$tooltip.fadeOut( 'fast' );
				tooltipShown = false;
			}
			
			$tooltip
				.zIndex( $( handle ).parent().zIndex() + 1 )
				.text( value )
				.position( {
					my: "bottom",
					at: "top",
					of: handle
				} );
		}

		$.extend( sliderOptions, {
			start: function( event, ui ) {
				sliding = true;
			},
			slide: function( event, ui ) {
				//Deferring to allow the widget to refresh his position
				setTimeout( function() {
					refreshTooltip( true, $slider.find( '.ui-slider-handle' ), ui.value );
				}, 1 );
			},
			stop: function( event, ui ) {
				//After a delay, hide tooltip if the handle doesn't have focus and pointer isn't over the handle.
				setTimeout( function() {
					if ( !$slider.find( '.ui-slider-handle' ).is( ':focus' ) && !mouseOver ) {
						refreshTooltip( false, $slider.find( '.ui-slider-handle' ), ui.value );
					}
				}, 300 );
				
				sliding = false;
			},
			change: function( event, ui ) {
				if ( options.change ) {
					options.change();
				}
			}
		} );

		$slider.slider( sliderOptions );
		
		var $handle = $slider.find( '.ui-slider-handle' )
			.focus( function( event ) {
				refreshTooltip( true, $handle, $slider.slider( 'value' ) );
			} )
			.blur( function( event ) {
				refreshTooltip( false, $handle, $slider.slider( 'value' ) );
			} )
			.mouseenter( function( event ) {
				mouseOver = true;
				refreshTooltip( true, $handle, $slider.slider( 'value' ) );
			} )
			.mouseleave( function( event ) {
				setTimeout( function() {
					if ( !$handle.is( ':focus' ) && !sliding ) {
						refreshTooltip( false, $handle, $slider.slider( 'value' ) );
					}
				}, 1 );
				mouseOver = false;
			} );
			
		this.$div.append( $slider );
	}
	inherit( RangeField, SimpleField );
	
	RangeField.prototype.getValues = function() {
		return pair( this.desc.name, this.$slider.slider( 'value' ) );
	};

	validFieldTypes.range = RangeField;	
	
	/* A field with a textbox with a datepicker */
	function DateField( desc, options ) {
		SimpleField.call( this, desc, options );

		var $text = this.$text = $( '<input/>' )
			.attr( {
				type: 'text',
				id: this.options.idPrefix + this.desc.name,
				name: this.options.idPrefix + this.desc.name
			} ).datepicker( {
				onSelect: function() {
					//Force validation, so that a previous 'invalid' state is removed
					$( this ).valid();
					//trigger change event on the textbox
					$text.trigger( 'change' );
				}
			} );

		var	value = options.values && options.values[this.desc.name],
			date;
		if ( typeof value != 'undefined' && value !== null ) {
			date = this._parseDate( value );
			
			if ( !isFinite( date ) ) {
				$.error( "value is invalid" );
			}

			this.$text.datepicker( 'setDate', date );
		}

		//Add the change event listener
		if ( options.change ) {
			addSmartChangeListener( this.$text, options.change );
		}

		this.$div.append( this.$text );
	}
	inherit( DateField, SimpleField );
	
	//Parses a date in the [YYYY]-[MM]-[DD]T[hh]:[mm]:[ss]Z format, returns a date object
	//Used to avoid the "new Date( dateString )" constructor, which is implementation-specific.
	DateField.prototype._parseDate = function( str ) {
		var	date,
			parts = /^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2}):(\d{2})Z$/.exec( str );
		
		if ( parts === null ) {
			return new Date( NaN );
		}
		
		var	year = parseInt( parts[1], 10 ),
			month = parseInt( parts[2], 10 ) - 1,
			day = parseInt( parts[3], 10 ),
			h = parseInt( parts[4], 10 ),
			m = parseInt( parts[5], 10 ),
			s = parseInt( parts[6], 10 );
		
		date = new Date();
		date.setUTCFullYear( year, month, day );
		date.setUTCHours( h );
		date.setUTCMinutes( m );
		date.setUTCSeconds( s );
		
		//Check if the date was actually correct, since the date handling functions may wrap around invalid dates
		if ( date.getUTCFullYear() !== year || date.getUTCMonth() !== month || date.getUTCDate() !== day ||
			date.getUTCHours() !== h || date.getUTCMinutes() !== m || date.getUTCSeconds() !== s )
		{
			return new Date( NaN );
		}
		
		return date;
	};
	
	DateField.prototype.getValues = function() {
		var	d = this.$text.datepicker( 'getDate' ),
			res = {};
		
		if ( d === null ) {
			return pair( this.desc.name, null );
		}

		//UTC date in ISO 8601 format [YYYY]-[MM]-[DD]T[hh]:[mm]:[ss]Z
		return pair( this.desc.name, '' +
			pad( d.getUTCFullYear(), 4 ) + '-' +
			pad( d.getUTCMonth() + 1, 2 ) + '-' +
			pad( d.getUTCDate(), 2 ) + 'T' +
			pad( d.getUTCHours(), 2 ) + ':' +
			pad( d.getUTCMinutes(), 2 ) + ':' +
			pad( d.getUTCSeconds(), 2 ) + 'Z' );
	};

	DateField.prototype.getValidationSettings = function() {
		var	settings = SimpleField.prototype.getValidationSettings.call( this ),
			fieldId = this.options.idPrefix + this.desc.name;
		
		settings.rules[fieldId] = {
				"datePicker": true
			};
		return settings;
	};

	validFieldTypes.date = DateField;

	/* A field with color picker */
	
	function closeColorPicker() {
		$( '#colorpicker' ).fadeOut( 'fast', function() {
			$( this ).remove();
		} );
	}

	//If a click happens outside the colorpicker while it is showed, remove it
	$( document ).mousedown( function( event ) {
		var $target = $( event.target );
		if ( $target.parents( '#colorpicker' ).length === 0 ) {
			closeColorPicker();
		}
	} );
	
	function ColorField( desc, options ) {
		SimpleField.call( this, desc, options );

		var value;
		if ( typeof options.values != 'undefined' && typeof options.values[this.desc.name] != 'undefined' ) {
			value = options.values[this.desc.name];
		} else {
			value = '';
		}

		this.$text = $( '<input/>' ).attr( {
				type: 'text',
				id: this.options.idPrefix + this.desc.name,
				name: this.options.idPrefix + this.desc.name
			} )
			.addClass( 'colorpicker-input' )
			.val( value )
			.css( 'background-color', value )
			.focus( function() {
				$( '<div/>' )
					.addClass( 'ui-widget ui-widget-content' )
					.attr( 'id', 'colorpicker' )
					.css( 'position', 'absolute' )
					.hide()
					.appendTo( document.body )
					.zIndex( $( this ).zIndex() + 1 )
					.farbtastic( this )
					.position( {
						my: 'left bottom',
						at: 'left top',
						of: this,
						collision: 'none'
					} )
					.fadeIn( 'fast' );
			} )
			.keydown( function( event ) {
				if ( event.keyCode == 13 || event.keyCode == 27 ) {
					closeColorPicker();
					event.preventDefault();
					event.stopPropagation();
				}
			} )
			.change( function() {
				//Force validation
				$( this ).valid();
			} )
			.blur( closeColorPicker );

		//Add the change event listener
		if ( options.change ) {
			addSmartChangeListener( this.$text, options.change );
		}

		this.$div.append( this.$text );
	}
	inherit( ColorField, SimpleField );
	
	ColorField.prototype.getValidationSettings = function() {
		var	settings = SimpleField.prototype.getValidationSettings.call( this ),
			fieldId = this.options.idPrefix + this.desc.name;
		
		settings.rules[fieldId] = {
				"color": true
			};
		return settings;
	};
	
	ColorField.prototype.getValues = function() {
		var color = $.colorUtil.getRGB( this.$text.val() );
		if ( color ) {
			return pair( this.desc.name, '#' + pad( color[0].toString( 16 ), 2 ) +
				pad( color[1].toString( 16 ), 2 ) + pad( color[2].toString( 16 ), 2 ) );
		} else {
			return pair( this.desc.name, null );
		}
	};

	validFieldTypes.color = ColorField;
	
	
	/* A field that represent a section (group of fields) */

	function SectionField( desc, options, id ) {
		Field.call( this, desc, options );
		
		this.$div = $( '<div/>' ).data( 'field', this );
		
		if ( id !== undefined ) {
			this.$div.attr( 'id', id );
		}

		for ( var i = 0; i < this.desc.fields.length; i++ ) {
			if ( options.editable === true && !options.staticFields ) {
				//add an empty slot
				this._createSlot( 'yes' ).appendTo( this.$div );
			}

			var	field = this.desc.fields[i],
				FieldConstructor;

			if ( $.isFunction( field.type ) ) {
				FieldConstructor = field.type;
			} else {
				FieldConstructor = validFieldTypes[field.type];
			}

			if ( !$.isFunction( FieldConstructor ) ) {
				$.error( "field with invalid type: " + field.type );
			}

			var editable;
			if ( options.editable === true ) {
				editable = options.staticFields ? 'partial' : 'yes';
			} else {
				editable = 'no';
			}

			var	f = new FieldConstructor( field, options ),
				$slot = this._createSlot( editable, f );
			
			$slot.appendTo( this.$div );
		}
		
		if ( options.editable === true && !options.staticFields ) {
			//add an empty slot
			this._createSlot( 'yes' ).appendTo( this.$div );
		}
	}
	inherit( SectionField, Field );
	
	SectionField.prototype.getElement = function() {
		return this.$div;
	};

	SectionField.prototype.getDesc = function( useValuesAsDefaults ) {
		var desc = clone( this.desc );
		desc.fields = [];
		this.$div.children().each( function( idx, slot ) {
			var field = $( slot ).data( 'field' );
			if ( field !== undefined ) {
				desc.fields.push( field.getDesc( useValuesAsDefaults ) );
			}
		} );
		return desc;
	};

	SectionField.prototype.setTitle = function( newTitle ) {
		this.desc.title = newTitle;
	};

	SectionField.prototype.getValues = function() {
		var values = {};
		this.$div.children().each( function( idx, slot ) {
			var field = $( slot ).data( 'field' );
			if ( field !== undefined ) {
				$.extend( values, field.getValues() );
			}
		} );
		return values;
	};

	SectionField.prototype.getValidationSettings = function() {
		var settings = {};
		this.$div.children().each( function( idx, slot ) {
			var field = $( slot ).data( 'field' );
			if ( field !== undefined ) {
				var fieldSettings = $( slot ).data( 'field' ).getValidationSettings();
				if ( fieldSettings ) {
					$.extend( true, settings, fieldSettings );
				}
			}
		} );

		return settings;
	};

	SectionField.prototype._deleteSlot = function( $slot ) {
		var field = $slot.data( 'field' );
		if ( field !== undefined ) {
			//Slot with a field
			deleteFieldRules( field );
		}
		
		//Delete it
		$slot.remove();
	};
	
	SectionField.prototype._createSlot = function( editable, field ) {
		var	self = this,
			$slot = $( '<div/>' ).addClass( 'formbuilder-slot ui-widget' ),
			$divButtons;
		
		if ( editable == 'partial' || editable == 'yes' ) {
			$slot.addClass( 'formbuilder-slot-editable' );

			$divButtons = $( '<div/>' )
				.addClass( 'formbuilder-editor-slot-buttons' )
				.appendTo( $slot );
		}
		
		if ( typeof field != 'undefined' ) {
			//Nonempty slot
			$slot.prepend( field.getElement() )
				.data( 'field', field );
			
			if ( editable == 'partial' || editable == 'yes' ) {
				$slot.addClass( 'formbuilder-slot-nonempty' );

				if ( editable == 'yes' ) {
					//Add the handle for moving slots
					$( '<span />' )
						.addClass( 'formbuilder-button formbuilder-editor-button-move ui-icon ui-icon-arrow-4' )
						.attr( 'title', mw.msg( 'gadgets-formbuilder-editor-move' ) )
						.mousedown( function() {
							$( this ).focus();
						} )
						.appendTo( $divButtons );
				}
				
				//Add the button for changing existing slots
				var type = field.getDesc().type;
				//TODO: using the 'builder' info is not optimal
				if ( !$.isFunction( prefsSpecifications[type].builder ) ) {
					$( '<a href="javascript:;" />' )
						.addClass( 'formbuilder-button formbuilder-editor-button-edit ui-icon ui-icon-gear' )
						.attr( 'title', mw.msg( 'gadgets-formbuilder-editor-edit-field' ) )
						.click( function() {
							createFieldDialog( {
								type: field.getDesc().type,
								values: field.getDesc(),
								oldDescription: field.getDesc(),
								callback: function( newField ) {
									if ( newField !== null ) {
										//check that there are no duplicate preference names
										var	existingValues = self.$div.closest( '.formbuilder' ).formBuilder( 'getValues' ),
											removedValues = field.getValues(),
											duplicateName = null;
										$.each( field.getValues(), function( name, val ) {
											//Only complain for preference names that are not in names for the field being replaced
											if ( typeof existingValues[name] != 'undefined' && removedValues[name] == 'undefined'  ) {
												duplicateName = name;
												return false;
											}
										} );
										
										if ( duplicateName !== null ) {
											alert( mw.msg( 'gadgets-formbuilder-editor-duplicate-name', duplicateName ) );
											return false;
										}
										
										var $newSlot = self._createSlot( 'yes', newField );
										
										deleteFieldRules( field );
										
										$slot.replaceWith( $newSlot );
										
										//Add field's validation rules
										addFieldRules( newField );
									}
									return true;
								}
							}, this.options );
						} )
						.appendTo( $divButtons );
					}
				
				if ( editable == 'yes' ) {
					//Add the button to delete slots
					$( '<a href="javascript:;" />' )
						.addClass( 'formbuilder-button formbuilder-editor-button-delete ui-icon ui-icon-trash' )
						.attr( 'title', mw.msg( 'gadgets-formbuilder-editor-delete-field' ) )
						.click( function( event, ui ) {
							//Make both slots disappear, then delete them
							$.each( [$slot, $slot.prev()], function( idx, $s ) {
								$s.slideUp( function() {
									self._deleteSlot( $s );
								} );
							} );
						} )
						.appendTo( $divButtons );

					//Make this slot draggable to allow moving it
					$slot.draggable( {
						revert: true,
						handle: ".formbuilder-editor-button-move",
						helper: "original",
						zIndex: $slot.closest( '.formbuilder' ).zIndex() + 1000, //TODO: ugly, find a better way
						scroll: false,
						opacity: 0.8,
						cursor: "move",
						cursorAt: {
							top: -5,
							left: -5
						}
					} );
				}
			}
		} else {
			//Create empty slot
			$slot.addClass( 'formbuilder-slot-empty' )
				.droppable( {
					hoverClass: 'formbuilder-slot-can-drop',
					tolerance: 'pointer',
					drop: function( event, ui ) {
						var srcSlot = ui.draggable,
							dstSlot = this;
						
						//Remove one empty slot surrounding source
						$( srcSlot ).prev().remove();
						
						//Replace dstSlot with srcSlot:
						$( dstSlot ).replaceWith( srcSlot );
						
						//Add one empty slot before and one after the new position
						self._createSlot( 'yes' ).insertBefore( srcSlot );
						self._createSlot( 'yes' ).insertAfter( srcSlot );
					},
					accept: function( draggable ) {
						//All non empty slots accepted, except for closest siblings
						return $( draggable ).hasClass( 'formbuilder-slot-nonempty' ) &&
							$( draggable ).prev().get( 0 ) !== $slot.get( 0 ) &&
							$( draggable ).next().get( 0 ) !== $slot.get( 0 );
					}
				} );
			
			//The button to create a new field
			$( '<a href="javascript:;" />' )
				.addClass( 'formbuilder-button formbuilder-editor-button-new ui-icon ui-icon-plus' )
				.attr( 'title', mw.msg( 'gadgets-formbuilder-editor-insert-field' ) )
				.click( function() {
					createFieldDialog( {
						callback: function( field ) {
							if ( field !== null ) {
								//check that there are no duplicate preference names
								var	existingValues = $slot.closest( '.formbuilder' ).formBuilder( 'getValues' ),
									duplicateName = null;
								$.each( field.getValues(), function( name, val ) {
									if ( typeof existingValues[name] != 'undefined' ) {
										duplicateName = name;
										return false;
									}
								} );
								
								if ( duplicateName !== null ) {
									alert( mw.msg( 'gadgets-formbuilder-editor-duplicate-name' , duplicateName ) );
									return false;
								}

								var	$newSlot = self._createSlot( 'yes', field ).hide(),
									$newEmptySlot = self._createSlot( 'yes' ).hide();
								
								$slot.after( $newSlot, $newEmptySlot );
								
								$newSlot.slideDown();
								$newEmptySlot.slideDown();
								
								//Add field's validation rules
								addFieldRules( field );
								
								//Ensure immediate visual feedback if the current value is invalid
								self.$div.closest( '.formbuilder' ).formBuilder( 'validate' );
							}
							return true;
						}
					}, self.options );
				} )
				.appendTo( $divButtons );
		}
		
		return $slot;
	};

	
	/* A field for 'bundle' type fields */
	function BundleField( desc, options ) {
		EmptyField.call( this, desc, options );

		//Create tabs
		var $tabs = this.$tabs = $( '<div><ul></ul></div>' )
			.attr( 'id', this.options.idPrefix + 'tab-' + getIncrementalCounter() )
			.tabs( {
				add: function( event, ui ) {
					//Links the anchor to the panel
					$( ui.tab ).data( 'panel', ui.panel );
					
					//Allow to drop over tabs to move slots around
					var section = ui.panel;
					$( ui.tab ).droppable( {
						tolerance: 'pointer',
						accept:  '.formbuilder-slot-nonempty',
						drop: function( event, ui ) {
							var	$slot = $( ui.draggable ),
								$srcSection = $slot.parent(),
								$dstSection = $( section );
							
							if ( $dstSection.get( 0 ) !== $srcSection.get( 0 ) ) {
								//move the slot (and the next empty slot) to dstSection with a nice animation
								var $slots = $slot.add( $slot.next() );
								$slots.slideUp( 'fast' )
									.promise().done( function() {
										$tabs.tabs( 'select', '#' + $dstSection.attr( 'id' ) );
										$slots.detach()
											.appendTo( $dstSection )
											.slideDown( 'fast' );
									} );
							}
						}
					} );
					
					if ( options.editable === true ) {
						//Add "delete section" button
						$( '<span />' )
							.addClass( 'formbuilder-button formbuilder-editor-button-delete-section ui-icon ui-icon-trash' )
							.attr( 'title', mw.msg( 'gadgets-formbuilder-editor-delete-section' ) )
							.click( function() {
								var sectionField = $( ui.panel ).data( 'field' );
								deleteFieldRules( sectionField );

								var index = $( "li", $tabs ).index( $( this ).closest( "li" ) );
								index -= 1; //Don't count the "add section" button
								
								$tabs.tabs( 'remove', index );
							} )
							.appendTo( ui.tab );

						//Add "edit section" button
						$( '<span />' )
							.addClass( 'formbuilder-button formbuilder-editor-button-edit-section ui-icon ui-icon-gear' )
							.attr( 'title', mw.msg( 'gadgets-formbuilder-editor-edit-section' ) )
							.click( function() {
								var	button = this,
									sectionField = $( ui.panel ).data( 'field' );
									
								$( {
									fields: [ {
										'name': "title",
										'type': "string",
										'label': mw.msg( 'gadgets-formbuilder-editor-choose-title' )
									} ]
								} ).formBuilder( {
									values: {
										title: sectionField.getDesc().title
									},
									idPrefix: 'section-edit-title-'
								} ).dialog( {
									modal: true,
									resizable: false,
									title: mw.msg( 'gadgets-formbuilder-editor-choose-title-title' ),
									close: function() {
										$( this ).remove(); //completely destroy on close
									},
									buttons: [
										{
											text: mw.msg( 'gadgets-formbuilder-editor-ok' ),
											click: function() {
												var title = $( this ).formBuilder( 'getValues' ).title;
												
												//Update field description
												sectionField.setTitle( title );
												
												//Update tab's title
												$( button ).parent().find( ':first-child' ).text( title );
												
												$( this ).dialog( "close" );
											}
										},
										{
											text: mw.msg( 'gadgets-formbuilder-editor-cancel' ),
											click: function() {
												$( this ).dialog( "close" );
											}
										}
									]
								} );
							} )
							.appendTo( ui.tab );
					}
				}
			} );

		//Save for future reference
		this.$ui_tabs_nav = $tabs.find( '.ui-tabs-nav' );

		var self = this;
		$.each( this.desc.sections, function( index, sectionDescription ) {
			var	id = self.options.idPrefix + 'section-' + getIncrementalCounter(),
				sec = new SectionField( sectionDescription, options, id );
			
			$tabs.append( sec.getElement() )
				.tabs( 'add', '#' + id, preproc( options.msgPrefix, sectionDescription.title ) );
		} );

		if ( options.editable === true ) {
			//Add the button to create a new section
			$( '<span>' )
				.addClass( 'formbuilder-button formbuilder-editor-button-new-section ui-icon ui-icon-plus' )
				.attr( 'title', mw.msg( 'gadgets-formbuilder-editor-new-section' ) )
				.click( function() {
					$( {
						fields: [ {
							'name': "title",
							'type': "string",
							'label': mw.msg( 'gadgets-formbuilder-editor-choose-title' )
						} ]
					} ).formBuilder( { idPrefix: 'section-create-' } ).dialog( {
						modal: true,
						resizable: false,
						title: mw.msg( 'gadgets-formbuilder-editor-choose-title-title' ),
						close: function() {
							$( this ).remove();
						},
						buttons: [
							{
								text: mw.msg( 'gadgets-formbuilder-editor-ok' ),
								click: function() {
									var	title = $( this ).formBuilder( 'getValues' ).title,
										id = self.options.idPrefix + 'section-' + getIncrementalCounter(),
										newSectionDescription = {
											title: title,
											fields: []
										},
										newSection = new SectionField( newSectionDescription, options, id );
									
									$tabs.append( newSection.getElement() )
										.tabs( 'add', '#' + id, preproc( options.msgPrefix, title ) );
									
									$( this ).dialog( "close" );
								}
							},
							{
								text: mw.msg( 'gadgets-formbuilder-editor-cancel' ),
								click: function() {
									$( this ).dialog( "close" );
								}
							}
						]
					} );
				} )
				.wrap( '<li />' ).parent()
				.prependTo( this.$ui_tabs_nav );
	
			//Make the tabs sortable
			this.$ui_tabs_nav.sortable( {
				axis: 'x',
				items: 'li:not(:has(.formbuilder-editor-button-new-section))'
			} );
		}

		this.$div.append( $tabs );
	}
	inherit( BundleField, EmptyField );
	
	BundleField.prototype.getValidationSettings = function() {
		var settings = {};
		this.$ui_tabs_nav.find( 'a' ).each( function( idx, anchor ) {
			var	panel = $( anchor ).data( 'panel' ),
				field = $( panel ).data( 'field' );
				
			$.extend( true, settings, field.getValidationSettings() );
		} );
		return settings;
	};

	BundleField.prototype.getDesc = function( useValuesAsDefaults ) {
		var desc = clone( this.desc );
		desc.sections = [];
		this.$ui_tabs_nav.find( 'a' ).each( function( idx, anchor ) {
			var	panel = $( anchor ).data( 'panel' ),
				field = $( panel ).data( 'field' );
				
			desc.sections.push( field.getDesc( useValuesAsDefaults ) );
		} );
		return desc;
	};

	BundleField.prototype.getValues = function() {
		var values = {};
		this.$ui_tabs_nav.find( 'a' ).each( function( idx, anchor ) {
			var	panel = $( anchor ).data( 'panel' ),
				field = $( panel ).data( 'field' );
				
			$.extend( values, field.getValues() );
		} );
		return values;
	};

	validFieldTypes.bundle = BundleField;


	/* A field for 'composite' fields */

	function CompositeField( desc, options ) {
		EmptyField.call( this, desc, options );
		
		//Validate the 'name' member
		if ( !isValidPreferenceName( desc.name ) ) {
			$.error( 'invalid name' );
		}
		
		if ( !$.isArray( desc.fields ) ) {
			//Don't throw an error, to allow creating empty sections in the editor
			desc.fields = [];
		}
		
		//TODO: add something to easily visually identify 'composite' fields during editing
		
		var sectionOptions = clone( options );
		
		//Add another chunk to the prefix, to ensure uniqueness
		sectionOptions.idPrefix += desc.name + '-';
		if ( typeof options.values != 'undefined' ) {
			//Tell the section the actual values it should show
			sectionOptions.values = options.values[desc.name];
		}
		
		this._section = new SectionField( desc, sectionOptions );
		this.$div.append( this._section.getElement() );
	}
	inherit( CompositeField, EmptyField );

	CompositeField.prototype.getDesc = function( useValuesAsDefaults ) {
		var desc = clone( this.desc );
		desc.fields = this._section.getDesc( useValuesAsDefaults ).fields;
		return desc;
	};

	CompositeField.prototype.getValues = function() {
		return pair( this.desc.name, this._section.getValues() );
	};
	
	CompositeField.prototype.getValidationSettings = function() {
		return this._section.getValidationSettings();
	};	

	validFieldTypes.composite = CompositeField;

	/* A field for 'list' fields */

	function ListField( desc, options ) {
		EmptyField.call( this, desc, options );
		
		if ( typeof desc.field != 'object' ) {
			$.error( "The 'field' parameter is missing or wrong" );
		}

		if ( typeof desc.field.name != 'undefined' ) {
			$.error( "The 'field' parameter must not specify the field 'name'" );
		}

		if ( ( typeof desc.field.type != 'string' )
			|| prefsSpecifications[desc.field.type].simple !== true )
		{
			$.error( "Missing or invalid field type specified in 'field' parameter." );
		}
		
		this._$divItems = $( '<div/>' ).addClass( 'formbuilder-list-items' );
		
		if ( typeof options.values == 'undefined' ) {
			options.values = {};
		}
		
		var	value = ( typeof options.values[desc.name] != 'undefined' ) ? options.values[desc.name] : desc['default'],
			self = this;
		if ( typeof value != 'undefined' ) {
			$.each( value, function( index, itemValue ) {
				self._createItem( false, itemValue );
			} );
		}
		
		this._$divItems.sortable( {
				axis: 'y',
				items: '.formbuilder-list-item',
				handle: '.formbuilder-list-button-move',
				placeholder: 'ui-state-highlight',
				forcePlaceholderSize: true
			} )
			.appendTo( this.$div );
		
		$( '<a href="javascript:;" />' )
			.addClass( 'formbuilder-button formbuilder-list-button-new ui-icon ui-icon-plus' )
			.attr( 'title', mw.msg( 'gadgets-formbuilder-editor-insert-field' ) )
			.click( function() {
				self._createItem( true );
			} )
			.appendTo( this.$div );
		
		//Add a hidden input to attach validation rules on the number of items
		//We set its value to "" if there are no elements, or to the number of items otherwise
		this._$hiddenInput = $( '<input/>').attr( {
				type: 'hidden',
				name: this.options.idPrefix + this.desc.name,
				id: this.options.idPrefix + this.desc.name
			} )
			.hide()
			.appendTo( this.$div );
		
		this._refreshHiddenField();
	}
	inherit( ListField, EmptyField );

	ListField.prototype._refreshHiddenField = function() {
		var nItems = this._$divItems.children().length;
		this._$hiddenInput.val( nItems ? nItems : "" );
	};

	ListField.prototype._createItem = function( afterInit, itemValue ) {
		var	itemDesc = $.extend( {}, this.desc.field, {
				"name": this.desc.name
			} ),
			itemOptions = $.extend( {}, this.options, {
				editable: false,
				idPrefix: this.options.idPrefix + getIncrementalCounter() + "-"
			} );

		if ( typeof itemValue != 'undefined' ) {
			itemOptions.values = pair( this.desc.name, itemValue );
		} else {
			itemOptions.values = pair( this.desc.name, this.desc.field['default'] );
		}

		var FieldConstructor;
		if ( $.isFunction( this.desc.field.type ) ) {
			FieldConstructor = this.desc.field.type;
		} else {
			FieldConstructor = validFieldTypes[this.desc.field.type];
		}
		
		var itemField = new FieldConstructor( itemDesc, itemOptions ),
			$itemDiv = $( '<div/>' )
				.addClass( 'formbuilder-list-item' )
				.data( 'field', itemField ),
			$itemContent = $( '<div/>' )
				.addClass( 'formbuilder-list-item-content' )
				.append( itemField.getElement() );
		
		$( '<div/>' )
			.addClass( 'formbuilder-list-item-container' )
			.append( $itemContent )
			.appendTo( $itemDiv );
		
		var $itemButtons = $( '<div/>' )
			.addClass( 'formbuilder-list-item-buttons' );
		
		var self = this;
		$( '<span/>' )
			.addClass( 'formbuilder-button formbuilder-list-button-delete ui-icon ui-icon-trash' )
			.attr( 'title', mw.msg( 'gadgets-formbuilder-editor-delete' ) )
			.click( function() {
				$itemDiv.slideUp( function() {
					deleteFieldRules( itemField );
					$itemDiv.remove();
					self._refreshHiddenField();
					self._$hiddenInput.valid(); //force revalidation of the number of items
				} );
			} )
			.appendTo( $itemButtons );
		
		$( '<span/>' )
			.addClass( 'formbuilder-button formbuilder-list-button-move ui-icon ui-icon-arrow-4' )
			.attr( 'title', mw.msg( 'gadgets-formbuilder-editor-move' ) )
			.appendTo( $itemButtons );

		$itemButtons.appendTo( $itemDiv );

		//Add an empty div with clear:both style
		$itemDiv.append( $('<div style="clear:both"></div>' ) );
		
		if ( afterInit ) {
			$itemDiv.hide()
				.appendTo( this._$divItems )
				.slideDown();

			addFieldRules( itemField );

			this._refreshHiddenField();
			this._$hiddenInput.valid(); //force revalidation of the number of items
		} else {
			$itemDiv.appendTo( this._$divItems );
		}
	};

	ListField.prototype.getDesc = function( useValuesAsDefaults ) {
		var desc = clone( this.desc );
		if ( useValuesAsDefaults ) {
			desc['default'] = this.getValues()[this.desc.name];
		}
		return desc;
	};

	ListField.prototype.getValues = function() {
		var value = [];
		this._$divItems.children().each( function( index, divItem ) {
			var field = $( divItem ).data( 'field' );
			$.each( field.getValues(), function( name, v ) {
				value.push( v );
			} );
		} );
		
		return pair( this.desc.name, value );
	};
	
	ListField.prototype.getValidationSettings = function() {
		var validationSettings = EmptyField.prototype.getValidationSettings.call( this );
			hiddenFieldRules = {}, hiddenFieldMessages = {};

		if ( typeof this.desc.required != 'undefined' ) {
			hiddenFieldRules.required = this.desc.required;
			hiddenFieldMessages.required = mw.msg( 'gadgets-formbuilder-list-required' );
		}
		
		if ( typeof this.desc.minlength != 'undefined' ) {
			hiddenFieldRules.min = this.desc.minlength;
			hiddenFieldMessages.min = mw.msg( 'gadgets-formbuilder-list-minlength', this.desc.minlength );
		}

		if ( typeof this.desc.maxlength == 'undefined' ) {
			this.desc.maxlength = 1024;
		}
		hiddenFieldRules.max = this.desc.maxlength;
		hiddenFieldMessages.max = mw.msg( 'gadgets-formbuilder-list-maxlength', this.desc.maxlength );

		validationSettings.rules[this.options.idPrefix + this.desc.name] = hiddenFieldRules;
		validationSettings.messages[this.options.idPrefix + this.desc.name] = hiddenFieldMessages;
		
		this._$divItems.children().each( function( index, divItem ) {
			var field = $( divItem ).data( 'field' );
			$.extend( true, validationSettings, field.getValidationSettings() );
		} );
		return validationSettings;
	};

	validFieldTypes.list = ListField;

	/* Fields for internal use only */
	
	/*
	 * A text field that allow an arbitrary javascript scalar value, that is:
	 * true, false, a number, a (double quoted) string or null.
	 * 
	 * Used to create editor's select options.
	 * 
	 **/
	function ScalarField( desc, options ) {
		LabelField.call( this, desc, options );
		
		this.$div.addClass( 'formbuilder-field-scalar' );
		
		this.$text = $( '<input/>' )
			.attr( {
				type: 'text',
				id: this.options.idPrefix + this.desc.name,
				name: this.options.idPrefix + this.desc.name
			} )
			.appendTo( this.$div );

		var value = options.values && options.values[this.desc.name];
		if ( typeof value != 'undefined' ) {
			if ( !isScalar( value ) ) {
				$.error( "value is invalid" );
			}
			
			this.$text.val( $.toJSON( value ) );
		}
	}
	inherit( ScalarField, LabelField );

	ScalarField.prototype.getDesc = function( useValuesAsDefault ) {
		var desc = clone( LabelField.prototype.getDesc.call( this, useValuesAsDefaults ) );
		if ( useValuesAsDefaults === true ) {
			//set 'default' to current value.
			var values = this.getValues();
			desc['default'] = values[this.desc.name];
		}
	};

	ScalarField.prototype.getValues = function() {
		var text = this.$text.val();
		
		try {
			var value = $.parseJSON( text );
			return pair( this.desc.name, value );
		} catch( e ) {
			return pair( this.desc.name, undefined );
		}
	};

	ScalarField.prototype.getValidationSettings = function() {
		var	settings = Field.prototype.getValidationSettings.call( this ),
			fieldId = this.options.idPrefix + this.desc.name;
		
		settings.rules[fieldId] = pair( "scalar", true );		
		return settings;
	};

	/* Specifications of preferences descriptions syntax and field types */
	
	//Describes 'name' and 'label' field members, common to all "simple" fields
	var simpleFields = [
		{
			"name": "name",
			"type": "string",
			"label": "name",
			"required": true,
			"maxlength": 40,
			"default": ""
		},
		{
			"name": "label",
			"type": "string",
			"label": "label",
			"required": false,
			"default": ""
		}
	];

	//Used by preference editor to build field properties dialogs
	//TODO: document
	prefsSpecifications = {
		"label": {
			"simple": false,
			"builder": [ {
				"name": "label",
				"type": "string",
				"label": "label",
				"required": false,
				"default": ""
			} ]
		},
		"boolean": {
			"simple": true,
			"builder": simpleFields
		},
		"string": {
			"simple": true,
			"builder": simpleFields.concat( [
				{
					"name": "required",
					"type": "select",
					"label": "required",
					"default": null,
					"options": [
						{
							"name": "not specified",
							"value": null
						},
						{
							"name": "true",
							"value": true
						},
						{
							"name": "false",
							"value": false
						}
					]
				},
				{
					"name": "minlength",
					"type": "number",
					"label": "minlength",
					"integer": true,
					"min": 0,
					"required": false,
					"default": null
				},
				{
					"name": "maxlength",
					"type": "number",
					"label": "maxlength",
					"integer": true,
					"min": 1,
					"required": false,
					"default": null
				}
			] )
		},
		"number": {
			"simple": true,
			"builder": simpleFields.concat( [
				{
					"name": "required",
					"type": "boolean",
					"label": "required",
					"default": true
				},
				{
					"name": "integer",
					"type": "boolean",
					"label": "integer",
					"default": false
				},
				{
					"name": "min",
					"type": "number",
					"label": "min",
					"required": false,
					"default": null
				},
				{
					"name": "max",
					"type": "number",
					"label": "max",
					"required": false,
					"default": null
				}
			] )
		},
		"select": {
			"simple": true,
			"builder": simpleFields.concat( [
				{
					"name": "options",
					"type": "list",
					"default": [],
					"field": {
						"type": "composite",
						"fields": [
							{
								"name": "name",
								"type": "string",
								"label": "Option name", //TODO: i18n
								"default": ""
							},
							{
								"name": "value",
								"type": ScalarField,
								"label": "Option value", //TODO: i18n
								"default": ""
							}
						]
					}
				}
			] )
		},
		"range": {
			"simple": true,
			"builder": simpleFields.concat( [
				{
					"name": "min",
					"type": "number",
					"label": "min",
					"required": true
				},
				{
					"name": "step",
					"type": "number",
					"label": "step",
					"required": true,
					"default": 1
				},
				{
					"name": "max",
					"type": "number",
					"label": "max",
					"required": true
				}
			] )
		},
		"date": {
			"simple": true,
			"builder": simpleFields
		},
		"color": {
			"simple": true,
			"builder": simpleFields
		},
		"bundle": {
			"simple": false,
			"builder": function( options, callback ) {
				callback(
					new BundleField( {
						"type": "bundle",
						"sections": [
							{
								"title": "Section 1",
								"fields": []
							},
							{
								"title": "Section 2",
								"fields": []
							}
						]
					}, options )
				);
			}
		},
		"composite": {
			"simple": true,
			"builder": [ {
				"name": "name",
				"type": "string",
				"label": "name",
				"required": true,
				"maxlength": 40,
				"default": ""
			} ]
		},
		"list": {
			"simple": true,
			"builder": function( options, callback ) {

				//Create list of "simple" types
				var selectOptions = [];
				$.each( prefsSpecifications, function( type, typeInfo ) {
					if ( typeInfo.simple === true ) {
						selectOptions.push( { "name": type, "value": type } );
					}
				} );
				
				//Create the dialog to chose the field type and set list properties
				var description = {
					"fields": [
						{
							"name": "name",
							"type": "string",
							"label": "name",
							"required": true,
							"maxlength": 40,
							"default": ""
						},
						{
							"name": "required",
							"type": "select",
							"label": "required",
							"default": null,
							"options": [
								{
									"name": "not specified",
									"value": null
								},
								{
									"name": "true",
									"value": true
								},
								{
									"name": "false",
									"value": false
								}
							]
						},
						{
							"name": "minlength",
							"type": "number",
							"label": "minlength",
							"integer": true,
							"min": 0,
							"required": false,
							"default": null
						},
						{
							"name": "maxlength",
							"type": "number",
							"label": "maxlength",
							"integer": true,
							"min": 1,
							"required": false,
							"default": null
						},
						{
							"name": "type",
							"type": "select",
							"label": "type",
							"options": selectOptions
						}
					]
				};
				var $form = $( description ).formBuilder( { idPrefix: 'list-choose-type-' } )
					.submit( function() {
						return false; //prevent form submission
					} );
				
				$form.dialog( {
						width: 450,
						modal: true,
						resizable: false,
						title: mw.msg( 'gadgets-formbuilder-editor-create-field-title', 'list' ),
						close: function() {
							$( this ).remove();
						},
						buttons: [
							{
								text: mw.msg( 'gadgets-formbuilder-editor-ok' ),
								click: function() {
									var values = $( this ).formBuilder( 'getValues' );
									$( this ).dialog( "close" );

									//Remove properties that equal their default
									$.each( description.fields, function( index, fieldSpec ) {
										var property = fieldSpec.name;
										if ( values[property] === fieldSpec['default'] ) {
											delete values[property];
										}
									} );
									
									var $dialog = $( this );
									createFieldDialog( {
										type: values.type,
										values: {
											"name": values.name
										},
										callback: function( field ) {
											$dialog.dialog( 'close' );
											showEditFieldDialog( field.getDesc(), values, options, callback );
											return true;
										}
									}, { editable: true } );
								}
							},
							{
								text: mw.msg( 'gadgets-formbuilder-editor-cancel' ),
								click: function() {
									$( this ).dialog( "close" );
								}
							}
						]
					} );
			}
		}
	};

	/* Public methods */
	
	/**
	 * Main method; takes the given preferences description object and builds
	 * the body of the form with the requested fields.
	 * 
	 * @param {Object} options options to set properties of the form and to change its behaviour.
	 *     Valid options:
	 *         idPrefix: compulsory, a unique prefix for all ids of elements created by formBuilder, to avoid conflicts.
	 *         msgPrefix: compulsory, a prefix to be added to all messages referred to by the description.
	 *         values: optional, a map of values of preferences; if omitted, default values will be used.
	 *         editable: optional, defaults to false; true if the form must be editable via the UI; used by the preferences editor.
	 *         staticFields: optional, defaults to false; ignored if editable is not true. If both editable and staticFields are true,
	 *                       insertion or removal of fields is not allowed, but changing field properties is. Used by the preferences
	 *                       editor.
	 *         change: optional, a function that will be called back if the value of a field changes (it's not strictly warranted that
	 *                 a field value has actually changed).
	 *
	 * @return {Element} the object with the requested form body.
	 */
	function buildFormBody( options ) {		
		var description = this.get( 0 );
		if ( typeof description != 'object' ) {
			mw.log( "description should be an object, instead of a " + typeof description );
			return null;
		}

		var $form = $( '<form/>' ).addClass( 'formbuilder' );

		if ( typeof description.fields != 'object' ) {
			mw.log( "description.fields should be an object, instead of a " + typeof description.fields );
			return null;
		}

		var section = new SectionField( description, options );
		section.getElement().appendTo( $form );

		//Initialize validator
		var validator = $form.validate( section.getValidationSettings() );

		$form.data( 'formBuilder', {
			mainSection: section,
			validator: validator
		} );

		return $form;
	}

	var methods = {
		
		/**
		 * Returns a dictionary of field names and field values.
		 * Returned values are not warranted to pass field validation.
		 * 
		 * @return {Object}
		 */
		getValues: function() {
			var data = this.data( 'formBuilder' );
			return data.mainSection.getValues();
		},

		/**
		 * Returns the current description, where current values as set for 'default' values.
		 * Used by the preference editor.
		 * 
		 * NOTE: it is responsibility of the caller to call 'validate' and ensure that
		 * current values pass validation before calling this method.
		 * 
		 * @return {Object}
		 */
		getDescription: function() {
			var data = this.data( 'formBuilder' );
			return data.mainSection.getDesc( true );
		},

		/**
		 * Do validation of form fields and warn the user about wrong values, if any.
		 * 
		 * @return {Boolean} true if all fields pass validation, false otherwise.
		 */
		validate: function() {
			var data = this.data( 'formBuilder' );
			return data.validator.form();
		}
	};

	$.fn.formBuilder = function( method ) {
		if ( methods[method] ) {
			return methods[method].apply( this, Array.prototype.slice.call( arguments, 1 ));
		} else if ( typeof method === 'object' || !method ) {
			return buildFormBody.apply( this, arguments );
		} else {
			$.error( 'Method ' +  method + ' does not exist on jQuery.formBuilder' );
		}
	};
} )( jQuery, mediaWiki );

