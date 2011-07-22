/**
 * jQuery Form Builder
 * Written by Salvatore Ingala in 2011
 * Released under the MIT and GPL licenses.
 */

(function($, mw) {

	var idPrefix = "mw-gadgets-dialog-";

	//Preprocesses strings end possibly replaces them with messages.
	//If str starts with "@" the rest of the string is assumed to be
	//a message, and the result of mw.msg is returned.
	//Two "@@" at the beginning escape for a single "@".
	function preproc( $form, str ) {
		if ( str.length <= 1 || str[0] !== '@' ) {
			return str;
		} else if ( str.substr( 0, 2 ) == '@@' ) {
			return str.substr( 1 );
		} else {
			return mw.message( $form.data( 'formBuilder' ).prefix + str.substring( 1 ) ).plain();
		}
	}

	//Commodity function to avoid id conflicts
	var getIncrementalCounter = ( function() {
		var cnt = 0;
		return function() {
			return cnt++;
		};
	} )();

	function pad( n, len ) {
		var res = '' + n;
		while ( res.length < len ) {
			res = '0' + res;
		}
		return res;
	}


	function testOptional( value, element ) {
		var rules = $( element ).rules();
		if ( typeof rules.required == 'undefined' || rules.required === false ) {
			if ( value.length == 0 ) {
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

	//Helper function for inheritance, see http://javascript.crockford.com/prototypal.html
	function object(o) {
		function F() {}
		F.prototype = o;
		return new F();
	}

	/* Basic interface for fields */
	function Field( $form, desc, values ) {
		this.$form = $form;
		this.desc = desc;
	}

	Field.prototype.getDesc = function() {
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


	/* A field with no content, generating an empty container */
	EmptyField.prototype = object( Field.prototype );
	EmptyField.prototype.constructor = EmptyField;
	function EmptyField( $form, desc, values ) {
		Field.call( this, $form, desc, values );
		
		//Check existence and type of the "type" field
		if ( !desc.type || typeof desc.type != 'string' ) {
			$.error( "Missing 'type' parameter" );
		}

		this.$p = $( '<p/>' );
	}

	EmptyField.prototype.getElement = function() {
		return this.$p;
	};

	/* A field with just a label */
	LabelField.prototype = object( EmptyField.prototype );
	LabelField.prototype.constructor = LabelField;
	function LabelField( $form, desc, values ) {
		EmptyField.call( this, $form, desc, values );

		//Check existence and type of the "label" field
		if ( !desc.label || typeof desc.label != 'string' ) {
			$.error( "Missing 'label' parameter" );
		}

		var $label = $( '<label/>' )
			.text( preproc( this.$form, this.desc.label ) )
			.attr('for', idPrefix + this.desc.name );

		this.$p.append( $label );
	}

	/* A field with a label and a checkbox */
	BooleanField.prototype = object( LabelField.prototype );
	BooleanField.prototype.constructor = BooleanField;
	function BooleanField( $form, desc, values ){ 
		LabelField.call( this, $form, desc, values );

		var value = values[this.desc.name];
		if ( typeof value != 'boolean' ) {
			$.error( "value is invalid" );
		}
		
		this.$c = $( '<input/>' )
			.attr( 'type', 'checkbox' )
			.attr( 'id', idPrefix + this.desc.name )
			.attr( 'name', idPrefix + this.desc.name )
			.attr( 'checked', value );

		this.$p.append( this.$c );
	}
	
	BooleanField.prototype.getValues = function() {
		var res = {};
		res[this.desc.name] = this.$c.is( ':checked' );
		return res;
	};

	/* A field with a textbox accepting string values */
	StringField.prototype = object( LabelField.prototype );
	StringField.prototype.constructor = StringField;
	function StringField( $form, desc, values ){ 
		LabelField.call( this, $form, desc, values );

		var value = values[this.desc.name];
		if ( typeof value != 'string' ) {
			$.error( "value is invalid" );
		}

		this.$text = $( '<input/>' )
			.attr( 'type', 'text' )
			.attr( 'id', idPrefix + this.desc.name )
			.attr( 'name', idPrefix + this.desc.name )
			.val( value );

		this.$p.append( this.$text );
	}
	
	StringField.prototype.getValues = function() {
		var res = {};
		res[this.desc.name] = this.$text.val();
		return res;
	};

	StringField.prototype.getValidationSettings = function() {
		var	settings = LabelField.prototype.getValidationSettings.call( this ),
			fieldId = idPrefix + this.desc.name;
		
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

	/* A field with a textbox accepting numeric values */
	NumberField.prototype = object( LabelField.prototype );
	NumberField.prototype.constructor = NumberField;
	function NumberField( $form, desc, values ){ 
		LabelField.call( this, $form, desc, values );

		var value = values[this.desc.name];
		if ( value !== null && typeof value != 'number' ) {
			$.error( "value is invalid" );
		}

		this.$text = $( '<input/>' )
			.attr( 'type', 'text' )
			.attr( 'id', idPrefix + this.desc.name )
			.attr( 'name', idPrefix + this.desc.name )
			.val( value );

		this.$p.append( this.$text );
	}
	
	NumberField.prototype.getValues = function() {
		var val = parseFloat( this.$text.val() ),
			res = {};
		res[this.desc.name] = isNaN( val ) ? null : val;
		return res;
	};

	NumberField.prototype.getValidationSettings = function() {
		var	settings = LabelField.prototype.getValidationSettings.call( this ),
			fieldId = idPrefix + this.desc.name;
		
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

	/* A field with a drop-down list */
	SelectField.prototype = object( LabelField.prototype );
	SelectField.prototype.constructor = SelectField;
	function SelectField( $form, desc, values ){ 
		LabelField.call( this, $form, desc, values );

		var $select = this.$select = $( '<select/>' )
			.attr( 'id', idPrefix + this.desc.name )
			.attr( 'name', idPrefix + this.desc.name );
		
		var validValues = [];
		var self = this;
		$.each( desc.options, function( optName, optVal ) {
			var i = validValues.length;
			$( '<option/>' )
				.text( preproc( self.$form, optName ) )
				.val( i )
				.appendTo( $select );
			validValues.push( optVal );
		} );

		this.validValues = validValues;

		var value = values[this.desc.name];
		if ( $.inArray( value, validValues ) == -1 ) {
			$.error( "value is not in the list of possible values" );
		}

		var i = $.inArray( value, validValues );
		$select.val( i ).attr( 'selected', 'selected' );

		this.$p.append( $select );
	}
	
	SelectField.prototype.getValues = function() {
		var i = parseInt( this.$select.val(), 10 ),
			res = {};
		res[this.desc.name] = this.validValues[i];
		return res;
	};


	/* A field with a slider, representing ranges of numbers */
	RangeField.prototype = object( LabelField.prototype );
	RangeField.prototype.constructor = RangeField;
	function RangeField( $form, desc, values ){ 
		LabelField.call( this, $form, desc, values );

		var value = values[this.desc.name];
		if ( typeof value != 'number' ) {
			$.error( "value is invalid" );
		}

		if ( typeof desc.min != 'number' ) {
			$.error( "desc.min is invalid" );
		}

		if ( typeof desc.max != 'number' ) {
			$.error( "desc.max is invalid" );
		}

		if ( typeof desc.step != 'undefined' && typeof desc.step != 'number' ) {
			$.error( "desc.step is invalid" );
		}

		if ( value < desc.min || value > desc.max ) {
			$.error( "value is out of range" );
		}

		var $slider = this.$slider = $( '<div/>' )
			.attr( 'id', idPrefix + this.desc.name );

		var options = {
			min: desc.min,
			max: desc.max,
			value: value
		};

		if ( typeof desc.step != 'undefined' ) {
			options['step'] = desc.step;
		}

		$slider.slider( options );

		this.$p.append( $slider );
	}
	
	RangeField.prototype.getValues = function() {
		var res = {};
		res[this.desc.name] = this.$slider.slider( 'value' );
		return res;
	};
	
	
	/* A field with a textbox with a datepicker */
	DateField.prototype = object( LabelField.prototype );
	DateField.prototype.constructor = DateField;
	function DateField( $form, desc, values ){ 
		LabelField.call( this, $form, desc, values );

		var value = values[this.desc.name];
		if ( typeof value == 'undefined' ) {
			$.error( "value is invalid" );
		}

		var date;
		if ( value !== null ) {
			date = new Date( value );
			
			if ( !isFinite( date ) ) {
				$.error( "value is invalid" );
			}
		}

		this.$text = $( '<input/>' )
			.attr( 'type', 'text' )
			.attr( 'id', idPrefix + this.desc.name )
			.attr( 'name', idPrefix + this.desc.name )
			.datepicker( {
				onSelect: function() {
					//Force validation, so that a previous 'invalid' state is removed
					$( this ).valid();
				}
			} );

		if ( value !== null ) {
			this.$text.datepicker( 'setDate', date );
		}


		this.$p.append( this.$text );
	}
	
	DateField.prototype.getValues = function() {
		var d = this.$text.datepicker( 'getDate' ),
			res = {};
		
		if ( d === null ) {
			return null;
		}

		//UTC date in ISO 8601 format [YYYY]-[MM]-[DD]T[hh]:[mm]:[ss]Z
		res[this.desc.name] = '' +
			pad( d.getUTCFullYear(), 4 ) + '-' +
			pad( d.getUTCMonth() + 1, 2 ) + '-' +
			pad( d.getUTCDate(), 2 ) + 'T' +
			pad( d.getUTCHours(), 2 ) + ':' +
			pad( d.getUTCMinutes(), 2 ) + ':' +
			pad( d.getUTCSeconds(), 2 ) + 'Z';
		return res;		
	};

	DateField.prototype.getValidationSettings = function() {
		var	settings = LabelField.prototype.getValidationSettings.call( this ),
			fieldId = idPrefix + this.desc.name;
		
		settings.rules[fieldId] = {
				"datePicker": true
			};
		return settings;
	};

	/* A field with color picker */
	
	function closeColorPicker() {
		$( '#colorpicker' ).fadeOut( 'fast', function() {
			$( this ).remove();
		} );
	}


	//If a click happens outside the colorpicker while it is showed, remove it
	$( document ).mousedown( function( event ) {
		var $target = $( event.target );
		if ( $target.parents( '#colorpicker' ).length == 0 ) {
			closeColorPicker();
		}
	} );
	
	ColorField.prototype = object( LabelField.prototype );
	ColorField.prototype.constructor = ColorField;
	function ColorField( $form, desc, values ){ 
		LabelField.call( this, $form, desc, values );

		var value = values[this.desc.name];
		if ( typeof value == 'undefined' ) {
			$.error( "value is invalid" );
		}

		this.$text = $( '<input/>' )
			.attr( 'type', 'text' )
			.attr( 'id', idPrefix + this.desc.name )
			.attr( 'name', idPrefix + this.desc.name )
			.addClass( 'colorpicker-input' )
			.val( value )
			.css( 'background-color', value )
			.focus( function() {
				$( '<div/>' )
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

		this.$p.append( this.$text );
	}
	
	ColorField.prototype.getValidationSettings = function() {
		var	settings = LabelField.prototype.getValidationSettings.call( this ),
			fieldId = idPrefix + this.desc.name;
		
		settings.rules[fieldId] = {
				"color": true
			};
		return settings;
	};
	
	ColorField.prototype.getValues = function() {
		var color = $.colorUtil.getRGB( this.$text.val() ),
			res = {};
		res[this.desc.name] = '#' + pad( color[0].toString( 16 ), 2 ) +
			pad( color[1].toString( 16 ), 2 ) + pad( color[2].toString( 16 ), 2 );
		return res;
	};
	
	/* A field that represent a section (group of fields) */
	SectionField.prototype = object( Field.prototype );
	SectionField.prototype.constructor = SectionField;
	function SectionField( $form, desc, values, id ) {
		Field.call( this, $form, desc, values );
		
		this.$p = $( '<p/>' );
		
		if ( id !== undefined ) {
			this.$p.attr( 'id', id );
		}
		
		var fields = [],
			settings = {}; //validator settings

		for ( var i = 0; i < desc.fields.length; i++ ) {
			//TODO: validate fieldName
			var field = desc.fields[i],
				FieldConstructor = validFieldTypes[field.type];

			if ( typeof FieldConstructor != 'function' ) {
				$.error( "field with invalid type: " + field.type );
			}

			var f = new FieldConstructor( $form, field, values );
			
			this.$p.append( f.getElement() );
			
			//If this field has validation rules, add them to settings
			var	fieldSettings = f.getValidationSettings();
			
			if ( fieldSettings ) {
				$.extend( true, settings, fieldSettings );
			}
			
			fields.push( f );
		}
		
		this.settings = settings;
		this.fields = fields;
	}
	
	SectionField.prototype.getElement = function() {
		return this.$p;
	};

	SectionField.prototype.getValues = function() {
		var values = {};
		for ( var i = 0; i < this.fields.length; i++ ) {
			$.extend( values, this.fields[i].getValues() );
		}
		return values;
	};

	SectionField.prototype.getValidationSettings = function() {
		return this.settings;
	};
	
	/* A field for 'bundle's */
	BundleField.prototype = object( EmptyField.prototype );
	BundleField.prototype.constructor = BundleField;
	function BundleField( $form, desc, values ) {
		EmptyField.call( this, $form, desc, values );

		//Create tabs
		var $tabs = this.$tabs = $( '<div><ul></ul></div>' )
			.attr( 'id', idPrefix + 'tab-' + getIncrementalCounter() )
			.tabs();

		this.sections = [];

		var self = this;
		$.each( desc.sections, function( sectionName, sectionDescription ) {
			var id = idPrefix + 'section-' + getIncrementalCounter(),
				sec = new SectionField( $form, sectionDescription, values, id );
			
			self.sections.push( sec );
			
			$tabs.append( sec.getElement() )
				.tabs( 'add', '#' + id, preproc( $form, sectionName ) ); 
		} );

		this.$p.append( $tabs );
	}
	
	BundleField.prototype.getValidationSettings = function() {
		var settings = {};
		$.each( this.sections, function( idx, section ) {
			$.extend( true, settings, section.getValidationSettings() );
		} );
		return settings;
	};

	BundleField.prototype.getValues = function() {
		var values = {};
		$.each( this.sections, function( idx, section ) {
			$.extend( values, section.getValues() );
		} );
		return values;
	};


	//Field types that can be referred to by preference descriptions
	var validFieldTypes = {
		"boolean": BooleanField,
		"string" : StringField,
		"number" : NumberField,
		"select" : SelectField,
		"range"  : RangeField,
		"date"   : DateField,
		"color"  : ColorField,
		"bundle" : BundleField
	};


	/* Public methods */
	
	/**
	 * Main method; takes the given preferences description object and builds
	 * the body of the form with the requested fields.
	 * 
	 * @param {Object} options
	 * @return {Element} the object with the requested form body.
	 */
	function buildFormBody( options ) {
		var description  = this.get( 0 );
		if ( typeof description != 'object' ) {
			mw.log( "description should be an object, instead of a " + typeof description );
			return null;
		}

		var $form = $( '<form/>' ).addClass( 'formbuilder' );
		var prefix = options.gadget === undefined ? '' : ( 'Gadget-' + options.gadget + '-' );
		$form.data( 'formBuilder', {
			prefix: prefix //prefix for messages
		} );

		//If there is an "intro", adds it to the form as a label
		if ( typeof description.intro == 'string' ) {
			$( '<p/>' )
				.text( preproc( $form, description.intro ) )
				.addClass( 'formBuilder-intro' )
				.appendTo( $form );
		}

		if ( typeof description.fields != 'object' ) {
			mw.log( "description.fields should be an object, instead of a " + typeof description.fields );
			return null;
		}

		var section = new SectionField( $form, description, options.values );
		
		section.getElement().appendTo( $form );

		var validator = $form.validate( section.getValidationSettings() );

		var data = $form.data( 'formBuilder' );
		data.mainSection = section;
		data.validator = validator;

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
})( jQuery, mediaWiki );

