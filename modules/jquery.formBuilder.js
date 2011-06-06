/**
 * jQuery Form Builder
 * Written by Salvatore Ingala in 2011
 * Released under the MIT and GPL licenses.
 */

(function($, mw) {

	var idPrefix = "mw-gadgets-dialog-";

	function $s( str ) {
		if ( str[0] !== '@' ) {
			return str;
		} else {
			//TODO: better validation
			return mw.msg( str.substring( 1 ) );
		}
	}


	//validator for "required" fields (without trimming whitespaces)
	$.validator.addMethod( "requiredStrict", function( value, element ) {
		return value.length > 0;
	}, mw.msg( 'gadgets-formbuilder-required' ) );

	//validator for "minlength" fields (without trimming whitespaces)
	$.validator.addMethod( "minlengthStrict", function( value, element, param ) {
		return value.length >= param;
	} );

	//validator for "maxlength" fields (without trimming whitespaces)
	$.validator.addMethod( "maxlengthStrict", function( value, element, param ) {
		return value.length <= param;
	} );

	//validator for integer fields
	$.validator.addMethod( "integer", function( value, element ) {
		return this.optional( element ) || /^-?\d+$/.test(value);
	}, mw.msg( 'gadgets-formbuilder-integer' ) );


	//Helper function for inheritance, see http://javascript.crockford.com/prototypal.html
	function object(o) {
		function F() {}
		F.prototype = o;
		return new F();
	}

	//A field with no content
	function EmptyField( name, desc ) {
		//Check existence of compulsory fields
		if ( typeof name == 'undefined' || !desc.type || !desc.label ) {
			$.error( "Missing arguments" );
		}

		this.$p = $( '<p/>' );

		this.name = name;
		this.desc = desc;
	}

	EmptyField.prototype.getName = function() {
		return this.name;
	};

	EmptyField.prototype.getDesc = function() {
		return this.desc;
	};


	//Override expected
	EmptyField.prototype.getValue = function() {
		return null;
	};

	EmptyField.prototype.getElement = function() {
		return this.$p;
	};
	
	EmptyField.prototype.getValidationSettings = function() {
		return {
			rules: {},
			messages: {}
		};
	}

	//A field with just a label
	LabelField.prototype = object( EmptyField.prototype );
	LabelField.prototype.constructor = LabelField;
	function LabelField( name, desc ) {
		EmptyField.call( this, name, desc );

		var $label = $( '<label/>' )
			.text( $s( this.desc.label ) )
			.attr('for', idPrefix + this.name );

		this.$p.append( $label );
	}

	//A field with a label and a checkbox
	BooleanField.prototype = object( LabelField.prototype );
	BooleanField.prototype.constructor = BooleanField;
	function BooleanField( name, desc ){ 
		LabelField.call( this, name, desc );

		if ( typeof desc.value != 'boolean' ) {
			$.error( "desc.value is invalid" );
		}
		
		this.$c = $( '<input/>' )
			.attr( 'type', 'checkbox' )
			.attr( 'id', idPrefix + this.name )
			.attr( 'name', idPrefix + this.name )
			.attr( 'checked', this.desc.value );

		this.$p.append( this.$c );
	}
	
	BooleanField.prototype.getValue = function() {
		return this.$c.is( ':checked' );
	}

	//A field with a textbox

	StringField.prototype = object( LabelField.prototype );
	StringField.prototype.constructor = StringField;
	function StringField( name, desc ){ 
		LabelField.call( this, name, desc );

		if ( typeof desc.value != 'string' ) {
			$.error( "desc.value is invalid" );
		}

		this.$text = $( '<input/>' )
			.attr( 'type', 'text' )
			.attr( 'id', idPrefix + this.name )
			.attr( 'name', idPrefix + this.name )
			.val( desc.value );

		this.$p.append( this.$text );
	}
	
	StringField.prototype.getValue = function() {
		return this.$text.val();
	};

	StringField.prototype.getValidationSettings = function() {
		var	settings = LabelField.prototype.getValidationSettings.call( this ),
			fieldId = idPrefix + this.name;
		
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
	}

	
	NumberField.prototype = object( LabelField.prototype );
	NumberField.prototype.constructor = NumberField;
	function NumberField( name, desc ){ 
		LabelField.call( this, name, desc );

		if ( desc.value !== null && typeof desc.value != 'number' ) {
			$.error( "desc.value is invalid" );
		}

		this.$text = $( '<input/>' )
			.attr( 'type', 'text' )
			.attr( 'id', idPrefix + this.name )
			.attr( 'name', idPrefix + this.name )
			.val( desc.value );

		this.$p.append( this.$text );
	}
	
	NumberField.prototype.getValue = function() {
		var val = parseFloat( this.$text.val() );
		return isNaN( val ) ? null : val;
	};

	NumberField.prototype.getValidationSettings = function() {
		var	settings = LabelField.prototype.getValidationSettings.call( this ),
			fieldId = idPrefix + this.name;
		
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
	}
	

	var validFields = {
		"boolean": BooleanField,
		"string" : StringField,
		"number" : NumberField
	};

	function buildFormBody() {
		var description  = this.get(0);
		if ( typeof description != 'object' ) {
			mw.log( "description should be an object, instead of a " + typeof description );
			return null;
		}

		var $form = $( '<form/>' );
		var $fieldset = $( '<fieldset/>' ).appendTo( $form );

		if ( typeof description.label == 'string' ) {
			$( '<legend/>' )
				.text( $s( description.label ) )
				.appendTo( $fieldset );
		}

		//TODO: manage form params

		if ( typeof description.fields != 'object' ) {
			mw.log( "description.fields should be an object, instead of a " + typeof description.fields );
			return null;
		}

		var $form = $( '<form/>' );

		var fields = [];

		var settings = {} //validator settings

		for ( var fieldName in description.fields ) {
			if ( description.fields.hasOwnProperty( fieldName )) {
				//TODO: validate fieldName
				var field = description.fields[fieldName];

				var FieldConstructor = validFields[field.type];

				if ( typeof FieldConstructor != 'function' ) {
					mw.log( "field with invalid type: " + field.type );
					return null;
				}

				try {
					var f = new FieldConstructor( fieldName, field );
				} catch ( e ) {
					mw.log( e );
					return null; //constructor failed, wrong syntax in field description
				}
				
				$form.append( f.getElement() );
				
				//If this field has validation rules, add them to settings
				var	fieldSettings = f.getValidationSettings();
				
				if ( fieldSettings ) {
					$.extend( true, settings, fieldSettings );
				}
				
				fields.push( f );
			}
		}

		var validator = $form.validate( settings );

		$form.data( 'formBuilder', {
			fields: fields,
			validator: validator
		} );

		return $form;
	}

	var methods = {
		getValues: function() {
			var	data = this.data( 'formBuilder' ),
				result = {};
			
			for ( var i = 0; i < data.fields.length; i++ ) {
				var f = data.fields[i];
				result[f.getName()] = f.getValue();
			}
			 
			return result;
		},
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

