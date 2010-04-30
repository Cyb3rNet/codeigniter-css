<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


/**
 *	@package CSS
 *	@version 1.0
 *	@license MIT License
 *
 *	@author Serafim Junior Dos Santos Fagundes <serafim@cyb3r.ca>
 *	@copyright Serafim Junior Dos Santos Fagundes Cyb3r Network
 *
 *	File containing classes for CSS style generation. 
 */


/**
 *	@name CYB3RNET_CI_CSS Constant for detection and interaction with other libraries
 */
define( 'CYB3RNET_CI_CSS', '20100429_1' );


/**
 *	@name CSS21 CSS version implementation; Version 2.1 is fully implemented 
 */
define( 'CSS21', 2.1 );


/**
 *	@name CSS3 CSS version implementation; Version 3 is partially implemented and shouldn't be seriously used
 */
define( 'CSS3', 3 );


/**
 *	@name CSSBLOCK Rendering constant defining a block statement for the Style class 
 */
define( 'CSSBLOCK',  'BLOCK' );


/**
 *	@name CSSINLINE Rendering constant defining an inline statement for the Style class
 */
define( 'CSSINLINE', 'INLINE' );


/**
 *	@name CSSVERSION Key for the constructor hash array parameter for style version 
 */
define( 'CSSVERSION', 'version' );


/**
 *	Class for stylesheet generation
 */
class Css
{
	/**
	 *	@param array $aParam Hash array for the instanciation of the class; [version] for the CSS version
	 *
	 *	@access public
	 */
	function Css( $aParams = array( CSSVERSION => CSS21 ) )
	{
		if ( $this->_validate_init_params( $aParams ) )
		{
			$this->_fVersion = $aParams[CSSVERSION];
		}
		else
		{
			show_error( 'Parameters of '.__CLASS__.' not valid', 500 );
		}
	
		$this->_bHasSelector = FALSE;
		$this->_bIsCached = FALSE;
		
		$this->_aDecls = array();
		$this->_iCurrentDecl = 0;
	
		$this->_oCI =& get_instance();
		
		$this->_oCI->load->helper( 'file' );

		$this->_sFilePath = "";
		
		$this->_sStyleStatements = "";
		
		$this->style = NULL;
	}
	
	
	/**
	 *	Validates passed parameters in class instanciation; return TRUE if success, FALSE otherwise
	 *
	 *	@param array $aParams Class instanciation parameters
	 *
	 *	@access private
	 *	@return bool
	 */
	function _validate_init_params( $aParams )
	{
		$bVersionOK = array_key_exists( CSSVERSION, $aParams );
		
		return ( $bVersionOK );
	}
	
	
	/**
	 *	Returns a Style versioned object based on the version set on class instanciation
	 *
	 *	@access private
	 *	@return object
	 */
	function &_get_style( $csRendering, $sSelector = '' )
	{
		switch ( $this->_fVersion )
		{
			case CSS21:
				$oStyle =& new CSSVersion21( $csRendering, $sSelector );
			break;
			case CSS3:
				$oStyle =& new CSSVersion3( $csRendering, $sSelector );
			break;
		}
	
		return $oStyle;
	}
	
	
	/**
	 *	Returns a Style object; by calling this method, it declares a style statement block to be inserted in a style entity or a CSS file
	 *
	 *	@param string $sSelector Selector identifying HTML entity(ies) to which the styles will apply
	 *
	 *	@access public
	 *	@return object
	 */
	function &select( $sSelector )
	{
		$this->_bHasSelector = TRUE;
		
		$oStyle =& $this->_get_style( CSSBLOCK, $sSelector );
		
		$this->_aDecls[] = $oStyle;
		$this->_iCurrentDecl++;
	
		$this->style =& $oStyle;
		
		return $oStyle;
	}
	
	
	/**
	 *	Return a Style object; by calling this method, it declares a style statement to be inserted in an HTML entity style attribute
	 *
	 *	@access public
	 *	@return object
	 */
	function &inline()
	{
		$oStyle =& $this->_get_style( CSSINLINE );
		
		return $oStyle;
	}


	/**
	 *	Generates the stylesheet block statements
	 *
	 *	@access public
	 *	@return string
	 */	
	function generate()
	{
		if ( $this->_bHasSelector )
		{
			for ( $i = 0; $i < count( $this->_aDecls ); $i++ )
			{
				$oStyle = $this->_aDecls[$i];
				
				$this->_sStyleStatements .= chr( 10 ).chr( 9 ).$oStyle->generate();
			}
		}
		
		return $this->_sStyleStatements;
	}
	
	
	/**
	 *	Saves the CSS block statements in the referenced path
	 *
	 *	@param string $sFilePath CSS file path
	 *
	 *	@access private
	 *	@return bool
	 */
	function _save( $sFilePath )
	{
		$bSuccess = FALSE;
		
		if ( write_file( $sFilePath, $this->_sStyleStatements ) )
		{
			
			$this->_bIsCached = TRUE;

			$bSuccess = TRUE;
		}

		return $bSuccess;
	}
	
	
	/**
	 *	Caches the declarations in a file
	 *
	 *	@param string $sFilePath File path to be used on save
	 *
	 *	@access public
	 */
	function cache( $sFilePath )
	{
		$this->_sFilePath = $sFilePath;
		
		$bSuccess = FALSE;
	
		if ( $this->_bHasSelector )
		{
			$this->generate();
			
			if ( file_exists( $sFilePath ) )
			{
				$sSha1StyleStatements = sha1( $this->_sStyleStatements );
				
				$sSha1Cache = sha1_file( $sFilePath );
				
				if ( $sSha1StyleStatements !== $sSha1Cache )
				{
					echo "Changement";
				
					$bSuccess = $this->_save( $sFilePath );
				}
				else
				{
					$this->_bIsCached = TRUE;
				}
			}
			else
			{
				$bSuccess = $this->_save( $sFilePath );
			}
			
			if ( ! ( $bSuccess OR $this->_bIsCached ) )
			{
				show_error( 'Error while saving CSS cache file', 500 );
			}
		}
		else
		{
			show_error( 'You must at least call once method <big>declare</big> and register some rules before using the <big>save</big> method.' );
		}
	}
	
	
	/**
	 *	Returns a link tag with the previously cached file as its hyper reference
	 *
	 *	@param string $sMedia Media of the stylesheet to which it applies
	 *
	 *	@access public
	 *	@return string
	 */
	function link( $sMedia = 'screen' )
	{
		if ( $this->_bIsCached )
		{
			$attrs = array();
		
			$attrs['type'] = 'text/css';
			$attrs['media'] = $sMedia;
			$attrs['rel'] = 'stylesheet';
			$attrs['href'] = $this->_sFilePath;
	
			if ( defined( 'CYB3RNET_CI_XHTML' ) )
			{
				return $this->_oCI->xhtml->link( $attrs );
			}
			else
			{
				return link_tag( $attrs );
			}
		}
		else
		{
			show_error( 'You must call method <big>cache</big> before using the <big>link</big> method.' );
		}
	}
}


/**
 *	Class for CSS style rule statement generation
 */
class CSSRule
{
	/**
	 *	@param string $sProperty Property name of the rule
	 *	@param string $sValue Value to set the property to
	 *
	 *	@access public
	 */
	function CSSRule( $sProperty, $sValue )
	{
		$this->_sProperty = $sProperty;
		$this->_sValue = $sValue;
	}
	
	
	/**
	 *	Returns the rule property
	 *
	 *	@access public
	 *	@return string
	 */
	function get_property()
	{
		return $this->_sProperty;
	}
	
	
	/**
	 *	Returns the rule value
	 *
	 *	@access public
	 *	@return string
	 */
	function get_value()
	{
		return $this->_sValue;
	}
}


/**
 *	Class for CSS style block or inline statement generation
 */
class CSSStyle
{
	/**
	 *	@param string $csRendering Constant defining the type of rendering of the style; block or inline
	 *	@param string $sSelector CSS selector identifying the entities to which the style applies
	 *	@param array $aoRules Hash array of Rule objects
	 *
	 *	@access public
	 */
	function CSSStyle( $csRendering, $sSelector = '', $aoRules = array() )
	{
		$this->_sRendering = $csRendering;
	
		$this->_sSelector = $sSelector;
		
		$this->_aoRules = $aoRules;
	}
	
	
	/**
	 *	Adds a rule to the style statement
	 *
	 *	@param object $oRule Rule object
	 *
	 *	@access private
	 */
	function _add_rule( $oRule )
	{
		$this->_aoRules[] = $oRule;
	}
	
	
	/**
	 *	Assembles rules for stylesheet generation; returns a string with the rules ready to be inserted in style location
	 *
	 *	@param bool $bSpacedLine Defines if spaces are used to the rules generation; for ease of reading
	 *
	 *	@access private
	 *	@return string
	 */
	function _assemble_rules( $bSpacedLine )
	{
		$sRules = "";
		
		foreach ( $this->_aoRules as $oRule )
		{
			if ( $bSpacedLine )
			{
				$sRules .= $oRule->get_property().': '.$oRule->get_value().'; ';
			}
			else
			{
				$sRules .= $oRule->get_property().':'.$oRule->get_value().';';
			}
		}
	
		return $sRules;
	}
	
	
	/**
	 *	Generates a block of rules; dependent of type rendering; returns a string defining a style block statement
	 *
	 *	@access private
	 *	@return string
	 */
	function _generate_block()
	{
			$sRules = $this->_assemble_rules( TRUE );
	
		$sBlock = $this->_sSelector.' { '.$sRules.' }';
	
		return $sBlock;
	}
	
	
	/**
	 *	Generates an inline set of rules; dependent of type of rendering; returns a string defining an inline style statement
	 *
	 *	@access private
	 *	@return string
	 */
	function _generate_inline()
	{
		$sRules = $this->_assemble_rules( FALSE );

		return $sRules;
	}
	
	
	/**
	 *	Generates the style statement, returns a string defining an inline or block statement
	 *
	 *	@access public
	 *	@return string
	 */
	function generate()
	{
		switch ( $this->_sRendering )
		{
			case CSSBLOCK:
				$sCSS = $this->_generate_block();
			break;
			case CSSINLINE:
				$sCSS = $this->_generate_inline();
			break;
		}
	
		return $sCSS;
	}
}


/**
 *	Abstract class of the version classes
 */
class CSSBaseVersion extends CSSStyle
{
	function CSSVersionBase( $csRendering, $sSelector, $aoRules )
	{
		parent::CSSStyle(  $csRendering, $sSelector, $aoRules  );
	}
	
	
	/**
	 *	Sets a rule to the Style parent
	 *
	 *	@access private 
	 */
	function _set_rule( $sPropertyName, $sValue )
	{
		$oRule = new CSSRule( $sPropertyName, $sValue );
		
		$this->_add_rule( $oRule );
	}
}


/**
 *	Class containing the CSS properties of version 2.1
 */
class CSSVersion21 extends CSSBaseVersion
{
	/**
	 *	@param string $csRendering Constant value defining the type of style rendering in the HTML document
	 *	@param string $sSelector Selector declaration defining the entities to which the styles apply to
	 *	@param array $aoRules Hash array of Rule objects
	 *
	 *	@access public
	 */
	function CSSVersion21( $csRendering, $sSelector = '', $aoRules = array() )
	{
		parent::CSSBaseVersion( $csRendering, $sSelector, $aoRules );
		
		$this->_sVersion = 2.1;
	}
	
	
	/*
	 *	********************
	 *	CSS Property Methods
	 *	********************
	 */
	 
	 
	 /*
	  *	Box Model
	  */


	/**
	 *	margin-top property
	 *
	 *	@param string $sValue
	 *
	 *	@access public
	 */
	function margin_top( $sValue )
	{
		/* W3C */
		$sPropertyName = 'margin-top';
		
		$this->_set_rule( $sPropertyName, $sValue );
	}


	/**
	 *	margin-right property
	 *
	 *	@param string $sValue
	 *
	 *	@access public
	 */
	function margin_right( $sValue )
	{
		/* W3C */
		$sPropertyName = 'margin-right';
		
		$this->_set_rule( $sPropertyName, $sValue );
	}


	/**
	 *	margin-bottom property
	 *
	 *	@param string $sValue
	 *
	 *	@access public
	 */
	function margin_bottom( $sValue )
	{
		/* W3C */
		$sPropertyName = 'margin-bottom';
		
		$this->_set_rule( $sPropertyName, $sValue );
	}


	/**
	 *	margin-left property
	 *
	 *	@param string $sValue
	 *
	 *	@access public
	 */
	function margin_left( $sValue )
	{
		/* W3C */
		$sPropertyName = 'margin-left';
		
		$this->_set_rule( $sPropertyName, $sValue );
	}


	/**
	 *	margin property
	 *
	 *	@param array $aValues Values indicating the size of the margins of an element
	 *
	 *	@access public
	 */
	function margin( $aValues )
	{
		/* W3C */
		$sPropertyName = 'margin';
		
		$sValues = "";
		
		foreach ( $aValues as $sValue )
		{
			$sValues .= ' '.$sValue;
		}
		
		$this->_set_rule( $sPropertyName, $sValues );
	}


	/**
	 *	padding-top property
	 *
	 *	@param string $sValue
	 *
	 *	@access public
	 */
	function padding_top( $sValue )
	{
		/* W3C */
		$sPropertyName = 'padding-top';
		
		$this->_set_rule( $sPropertyName, $sValue );
	}


	/**
	 *	padding-right property
	 *
	 *	@param string $sValue
	 *
	 *	@access public
	 */
	function padding_right( $sValue )
	{
		/* W3C */
		$sPropertyName = 'padding-right';
		
		$this->_set_rule( $sPropertyName, $sValue );
	}


	/**
	 *	padding-bottom property
	 *
	 *	@param string $sValue
	 *
	 *	@access public
	 */
	function padding_bottom( $sValue )
	{
		/* W3C */
		$sPropertyName = 'padding-bottom';
		
		$this->_set_rule( $sPropertyName, $sValue );
	}


	/**
	 *	padding-left property
	 *
	 *	@param string $sValue
	 *
	 *	@access public
	 */
	function padding_left( $sValue )
	{
		/* W3C */
		$sPropertyName = 'padding-left';
		
		$this->_set_rule( $sPropertyName, $sValue );
	}


	/**
	 *	padding property
	 *
	 *	@param array $aValues
	 *
	 *	@access public
	 */
	function padding( $aValues )
	{
		/* W3C */
		$sPropertyName = 'padding';
		
		$sValues = "";
		
		foreach ( $aValues as $sValue )
		{
			$sValues .= ' '.$sValue;
		}
		
		$this->_set_rule( $sPropertyName, $sValues );
	}


	/**
	 *	border-top-width property
	 *
	 *	@param string $sValue
	 *
	 *	@access public
	 */
	function border_top_width( $sValue )
	{
		/* W3C */
		$sPropertyName = 'border-top-width';
		
		$this->_set_rule( $sPropertyName, $sValue );
	}


	/**
	 *	border-right-width property
	 *
	 *	@param string $sValue
	 *
	 *	@access public
	 */
	function border_right_width( $sValue )
	{
		/* W3C */
		$sPropertyName = 'border-right-width';
		
		$this->_set_rule( $sPropertyName, $sValue );
	}


	/**
	 *	border-bottom-width property
	 *
	 *	@param string $sValue
	 *
	 *	@access public
	 */
	function border_bottom_width( $sValue )
	{
		/* W3C */
		$sPropertyName = 'border-bottom-width';
		
		$this->_set_rule( $sPropertyName, $sValue );
	}


	/**
	 *	border-left-width property
	 *
	 *	@param string $sValue
	 *
	 *	@access public
	 */
	function border_left_width( $sValue )
	{
		/* W3C */
		$sPropertyName = 'border-left-width';
		
		$this->_set_rule( $sPropertyName, $sValue );
	}


	/**
	 *	border-width property
	 *
	 *	@param array $aValues
	 *
	 *	@access public
	 */
	function border_width( $aValues )
	{
		/* W3C */
		$sPropertyName = 'border-width';
		
		$sValues = "";
		
		foreach ( $aValues as $sValue )
		{
			$sValues .= ' '.$sValue;
		}
		
		$this->_set_rule( $sPropertyName, $sValues );
	}


	/**
	 *	border-top-color property
	 *
	 *	@param string $sValue
	 *
	 *	@access public
	 */
	function border_top_color( $sValue )
	{
		/* W3C */
		$sPropertyName = 'border-top-color';
		
		$this->_set_rule( $sPropertyName, $sValue );
	}


	/**
	 *	border-right-color property
	 *
	 *	@param string $sValue
	 *
	 *	@access public
	 */
	function border_right_color( $sValue )
	{
		/* W3C */
		$sPropertyName = 'border-right-color';
		
		$this->_set_rule( $sPropertyName, $sValue );
	}


	/**
	 *	border-bottom-color property
	 *
	 *	@param string $sValue
	 *
	 *	@access public
	 */
	function border_bottom_color( $sValue )
	{
		/* W3C */
		$sPropertyName = 'border-bottom-color';
		
		$this->_set_rule( $sPropertyName, $sValue );
	}


	/**
	 *	border-left-color property
	 *
	 *	@param string $sValue
	 *
	 *	@access public
	 */
	function border_left_color( $sValue )
	{
		/* W3C */
		$sPropertyName = 'border-left-color';
		
		$this->_set_rule( $sPropertyName, $sValue );
	}


	/**
	 *	border-color property
	 *
	 *	@param array $aValues
	 *
	 *	@access public
	 */
	function border_color( $aValues )
	{
		/* W3C */
		$sPropertyName = 'border-color';
		
		$sValues = "";
		
		foreach ( $aValues as $sValue )
		{
			$sValues .= ' '.$sValue;
		}
		
		$this->_set_rule( $sPropertyName, $sValues );
	}


	/**
	 *	border-top-style property
	 *
	 *	@param string $sValue
	 *
	 *	@access public
	 */
	function border_top_style( $sValue )
	{
		/* W3C */
		$sPropertyName = 'border-top-style';
		
		$this->_set_rule( $sPropertyName, $sValue );
	}


	/**
	 *	border-right-style property
	 *
	 *	@param string $sValue
	 *
	 *	@access public
	 */
	function border_right_style( $sValue )
	{
		/* W3C */
		$sPropertyName = 'border-right-style';
		
		$this->_set_rule( $sPropertyName, $sValue );
	}


	/**
	 *	border-bottom-style property
	 *
	 *	@param string $sValue
	 *
	 *	@access public
	 */
	function border_bottom_style( $sValue )
	{
		/* W3C */
		$sPropertyName = 'border-bottom-style';
		
		$this->_set_rule( $sPropertyName, $sValue );
	}


	/**
	 *	border-left-style property
	 *
	 *	@param string $sValue
	 *
	 *	@access public
	 */
	function border_left_style( $sValue )
	{
		/* W3C */
		$sPropertyName = 'border-left-style';
		
		$this->_set_rule( $sPropertyName, $sValue );
	}


	/**
	 *	border-style property
	 *
	 *	@param array $aValues
	 *
	 *	@access public
	 */
	function border_style( $aValues )
	{
		/* W3C */
		$sPropertyName = 'border-style';
		
		$sValues = "";
		
		foreach ( $aValues as $sValue )
		{
			$sValues .= ' '.$sValues;
		} 
		
		$this->_set_rule( $sPropertyName, $sValues );
	}


	/**
	 *	border-top property
	 *
	 *	@param array $aValues
	 *
	 *	@access public
	 */
	function border_top( $aValues )
	{
		/* W3C */
		$sPropertyName = 'border-top';
		
		$sValues = "";
		
		foreach ( $aValues as $sValue )
		{
			$sValues .= ' '.$sValue;
		}

		$this->_set_rule( $sPropertyName, $sValues );
	}


	/**
	 *	border-right property
	 *
	 *	@param array $aValues
	 *
	 *	@access public
	 */
	function border_right( $aValues )
	{
		/* W3C */
		$sPropertyName = 'border-right';
		
		$sValues = "";
		
		foreach ( $aValues as $sValue )
		{
			$sValues .= ' '.$sValue;
		}

		$this->_set_rule( $sPropertyName, $sValues );
	}


	/**
	 *	border-bottom property
	 *
	 *	@param array $aValues
	 *
	 *	@access public
	 */
	function border_bottom( $aValues )
	{
		/* W3C */
		$sPropertyName = 'border-bottom';
		
		$sValues = "";
		
		foreach ( $aValues as $sValue )
		{
			$sValues .= ' '.$sValue;
		}

		$this->_set_rule( $sPropertyName, $sValues );
	}


	/**
	 *	border-left property
	 *
	 *	@param array $aValues
	 *
	 *	@access public
	 */
	function border_left( $aValues )
	{
		/* W3C */
		$sPropertyName = 'border-left';
		
		$sValues = "";
		
		foreach ( $aValues as $sValue )
		{
			$sValues .= ' '.$sValue;
		}

		$this->_set_rule( $sPropertyName, $sValues );
	}


	/**
	 *	border property
	 *
	 *	@param string $sValue
	 *
	 *	@access public
	 */
	function border( $aValues )
	{
		/* W3C */
		$sPropertyName = 'border';
		
		$sValues = "";
		
		foreach ( $aValues as $sValue )
		{
			$sValues .= ' '.$sValue;
		}
		
		$this->_set_rule( $sPropertyName, $sValues );
	}
	
	
	/*
	 *	Visual Formatting Model
	 */


	/**
	 *	display property
	 *
	 *	@param string $sValue
	 *
	 *	@access public
	 */
	function display( $sValue )
	{
		/* W3C */
		$sPropertyName = 'display';
		
		$this->_set_rule( $sPropertyName, $sValue );
	}


	/**
	 *	position property
	 *
	 *	@param string $sValue
	 *
	 *	@access public
	 */
	function position( $sValue )
	{
		/* W3C */
		$sPropertyName = 'position';
		
		$this->_set_rule( $sPropertyName, $sValue );
	}


	/**
	 *	top property
	 *
	 *	@param string $sValue
	 *
	 *	@access public
	 */
	function top( $sValue )
	{
		/* W3C */
		$sPropertyName = 'top';
		
		$this->_set_rule( $sPropertyName, $sValue );
	}


	/**
	 *	right property
	 *
	 *	@param string $sValue
	 *
	 *	@access public
	 */
	function right( $sValue )
	{
		/* W3C */
		$sPropertyName = 'right';
		
		$this->_set_rule( $sPropertyName, $sValue );
	}


	/**
	 *	bottom property
	 *
	 *	@param string $sValue
	 *
	 *	@access public
	 */
	function bottom( $sValue )
	{
		/* W3C */
		$sPropertyName = 'bottom';
		
		$this->_set_rule( $sPropertyName, $sValue );
	}


	/**
	 *	left property
	 *
	 *	@param string $sValue
	 *
	 *	@access public
	 */
	function left( $sValue )
	{
		/* W3C */
		$sPropertyName = 'left';
		
		$this->_set_rule( $sPropertyName, $sValue );
	}


	/**
	 *	float property
	 *
	 *	@param string $sValue
	 *
	 *	@access public
	 */
	function float( $sValue )
	{
		/* W3C */
		$sPropertyName = 'float';
		
		$this->_set_rule( $sPropertyName, $sValue );
	}


	/**
	 *	clear property
	 *
	 *	@param string $sValue
	 *
	 *	@access public
	 */
	function clear( $sValue )
	{
		/* W3C */
		$sPropertyName = 'clear';
		
		$this->_set_rule( $sPropertyName, $sValue );
	}


	/**
	 *	z-index property
	 *
	 *	@param string $sValue
	 *
	 *	@access public
	 */
	function z_index( $sValue )
	{
		/* W3C */
		$sPropertyName = 'z-index';
		
		$this->_set_rule( $sPropertyName, $sValue );
	}


	/**
	 *	direction property
	 *
	 *	@param string $sValue
	 *
	 *	@access public
	 */
	function direction( $sValue )
	{
		/* W3C */
		$sPropertyName = 'direction';
		
		$this->_set_rule( $sPropertyName, $sValue );
	}


	/**
	 *	unicode-bidi property
	 *
	 *	@param string $sValue
	 *
	 *	@access public
	 */
	function unicode_bidi( $sValue )
	{
		/* W3C */
		$sPropertyName = 'unicode-bidi';
		
		$this->_set_rule( $sPropertyName, $sValue );
	}


	/**
	 *	width property
	 *
	 *	@param string $sValue
	 *
	 *	@access public
	 */
	function width( $sValue )
	{
		/* W3C */
		$sPropertyName = 'width';
		
		$this->_set_rule( $sPropertyName, $sValue );
	}


	/**
	 *	min-width property
	 *
	 *	@param string $sValue
	 *
	 *	@access public
	 */
	function min_width( $sValue )
	{
		/* W3C */
		$sPropertyName = 'min-width';
		
		$this->_set_rule( $sPropertyName, $sValue );
	}


	/**
	 *	max-width property
	 *
	 *	@param string $sValue
	 *
	 *	@access public
	 */
	function max_width( $sValue )
	{
		/* W3C */
		$sPropertyName = 'max-width';
		
		$this->_set_rule( $sPropertyName, $sValue );
	}


	/**
	 *	height property
	 *
	 *	@param string $sValue
	 *
	 *	@access public
	 */
	function height( $sValue )
	{
		/* W3C */
		$sPropertyName = 'height';
		
		$this->_set_rule( $sPropertyName, $sValue );
	}


	/**
	 *	min-height property
	 *
	 *	@param string $sValue
	 *
	 *	@access public
	 */
	function min_height( $sValue )
	{
		/* W3C */
		$sPropertyName = 'min-height';
		
		$this->_set_rule( $sPropertyName, $sValue );
	}


	/**
	 *	max-height property
	 *
	 *	@param string $sValue
	 *
	 *	@access public
	 */
	function max_height( $sValue )
	{
		/* W3C */
		$sPropertyName = 'max-height';
		
		$this->_set_rule( $sPropertyName, $sValue );
	}


	/**
	 *	line-height property
	 *
	 *	@param string $sValue
	 *
	 *	@access public
	 */
	function line_height( $sValue )
	{
		/* W3C */
		$sPropertyName = 'line-height';
		
		$this->_set_rule( $sPropertyName, $sValue );
	}


	/**
	 *	vertical-align property
	 *
	 *	@param string $sValue
	 *
	 *	@access public
	 */
	function vertical_align( $sValue )
	{
		/* W3C */
		$sPropertyName = 'vertical-align';
		
		$this->_set_rule( $sPropertyName, $sValue );
	}


	/*
	 *	Visual Effects
	 */


	/**
	 *	overflow property
	 *
	 *	@param string $sValue
	 *
	 *	@access public
	 */
	function overflow( $sValue )
	{
		/* W3C */
		$sPropertyName = 'overflow';
		
		$this->_set_rule( $sPropertyName, $sValue );
	}


	/**
	 *	clip property
	 *
	 *	@param string $sValue
	 *
	 *	@access public
	 */
	function clip( $sValue )
	{
		/* W3C */
		$sPropertyName = 'clip';
		
		$this->_set_rule( $sPropertyName, $sValue );
	}


	/**
	 *	visibility property
	 *
	 *	@param string $sValue
	 *
	 *	@access public
	 */
	function visibility( $sValue )
	{
		/* W3C */
		$sPropertyName = 'visibility';
		
		$this->_set_rule( $sPropertyName, $sValue );
	}
	
	
	/*
	 *	Generated Content 
	 */


	/**
	 *	content property
	 *
	 *	@param string $sValue
	 *
	 *	@access public
	 */
	function content( $sValue )
	{
		/* W3C */
		$sPropertyName = 'content';
		
		$this->_set_rule( $sPropertyName, $sValue );
	}


	/**
	 *	quotes property
	 *
	 *	@param string $sValue
	 *
	 *	@access public
	 */
	function quotes( $sValue )
	{
		/* W3C */
		$sPropertyName = 'quotes';
		
		$this->_set_rule( $sPropertyName, $sValue );
	}


	/**
	 *	counter-reset property
	 *
	 *	@param string $sValue
	 *
	 *	@access public
	 */
	function counter_reset( $sValue )
	{
		/* W3C */
		$sPropertyName = 'counter-reset';
		
		$this->_set_rule( $sPropertyName, $sValue );
	}


	/**
	 *	counter-increment property
	 *
	 *	@param string $sValue
	 *
	 *	@access public
	 */
	function counter_increment( $sValue )
	{
		/* W3C */
		$sPropertyName = 'counter-increment';
		
		$this->_set_rule( $sPropertyName, $sValue );
	}


	/**
	 *	list-style-type property
	 *
	 *	@param string $sValue
	 *
	 *	@access public
	 */
	function list_style_type( $sValue )
	{
		/* W3C */
		$sPropertyName = 'list-style-type';
		
		$this->_set_rule( $sPropertyName, $sValue );
	}


	/**
	 *	list-style-image property
	 *
	 *	@param string $sValue
	 *
	 *	@access public
	 */
	function list_style_image( $sValue )
	{
		/* W3C */
		$sPropertyName = 'list-style-image';
		
		$this->_set_rule( $sPropertyName, $sValue );
	}


	/**
	 *	list-style-position property
	 *
	 *	@param string $sValue
	 *
	 *	@access public
	 */
	function list_style_position( $sValue )
	{
		/* W3C */
		$sPropertyName = 'list-style-position';
		
		$this->_set_rule( $sPropertyName, $sValue );
	}


	/**
	 *	list-style property
	 *
	 *	@param array $aValues
	 *
	 *	@access public
	 */
	function list_style( $aValues )
	{
		/* W3C */
		$sPropertyName = 'list-style';
		
		$sValues = "";
		
		foreach ($aValues as $sValue)
		{
			$sValues .= ' '.$sValue;
		}
		
		$this->_set_rule( $sPropertyName, $sValues );
	}


	/*
	 *	Paged Media
	 */

	/**
	 *	page-break-before property
	 *
	 *	@param string $sValue
	 *
	 *	@access public
	 */
	function page_break_before( $sValue )
	{
		/* W3C */
		$sPropertyName = 'page-break-before';
		
		$this->_set_rule( $sPropertyName, $sValue );
	}


	/**
	 *	page-break-after property
	 *
	 *	@param string $sValue
	 *
	 *	@access public
	 */
	function page_break_after( $sValue )
	{
		/* W3C */
		$sPropertyName = 'page-break-after';
		
		$this->_set_rule( $sPropertyName, $sValue );
	}


	/**
	 *	page-break-inside property
	 *
	 *	@param string $sValue
	 *
	 *	@access public
	 */
	function page_break_inside( $sValue )
	{
		/* W3C */
		$sPropertyName = 'page-break-inside';
		
		$this->_set_rule( $sPropertyName, $sValue );
	}


	/**
	 *	orphans property
	 *
	 *	@param string $sValue
	 *
	 *	@access public
	 */
	function orphans( $sValue )
	{
		/* W3C */
		$sPropertyName = 'orphans';
		
		$this->_set_rule( $sPropertyName, $sValue );
	}


	/**
	 *	widows property
	 *
	 *	@param string $sValue
	 *
	 *	@access public
	 */
	function widows( $sValue )
	{
		/* W3C */
		$sPropertyName = 'widows';
		
		$this->_set_rule( $sPropertyName, $sValue );
	}
	
	
	/*
	 *	Colors & Backgrounds
	 */


	/**
	 *	color property
	 *
	 *	@param string $sValue
	 *
	 *	@access public
	 */
	function color( $sValue )
	{
		/* W3C */
		$sPropertyName = 'color';
		
		$this->_set_rule( $sPropertyName, $sValue );
	}


	/**
	 *	background-color property
	 *
	 *	@param string $sValue
	 *
	 *	@access public
	 */
	function background_color( $sValue )
	{
		/* W3C */
		$sPropertyName = 'background-color';
		
		$this->_set_rule( $sPropertyName, $sValue );
	}


	/**
	 *	background-image property
	 *
	 *	@param string $sValue
	 *
	 *	@access public
	 */
	function background_image( $sValue )
	{
		/* W3C */
		$sPropertyName = 'background-image';
		
		$this->_set_rule( $sPropertyName, $sValue );
	}


	/**
	 *	background-repeat property
	 *
	 *	@param string $sValue
	 *
	 *	@access public
	 */
	function background_repeat( $sValue )
	{
		/* W3C */
		$sPropertyName = 'background-repeat';
		
		$this->_set_rule( $sPropertyName, $sValue );
	}


	/**
	 *	background-attachment property
	 *
	 *	@param string $sValue
	 *
	 *	@access public
	 */
	function background_attachment( $sValue )
	{
		/* W3C */
		$sPropertyName = 'background-attachment';
		
		$this->_set_rule( $sPropertyName, $sValue );
	}


	/**
	 *	background-position property
	 *
	 *	@param string $sValue
	 *
	 *	@access public
	 */
	function background_position( $sValue )
	{
		/* W3C */
		$sPropertyName = 'background-position';
		
		$this->_set_rule( $sPropertyName, $sValue );
	}


	/**
	 *	background property
	 *
	 *	@param array $aValues
	 *
	 *	@access public
	 */
	function background( $aValues )
	{
		/* W3C */
		$sPropertyName = 'background';
		
		$sValues = "";
		
		foreach ( $aValues as $sValue )
		{
			$sValues .= ' '.$sValue;
		}
		
		$this->_set_rule( $sPropertyName, $sValues );
	}
	
	
	/*
	 *	Fonts
	 */


	/**
	 *	font-family property
	 *
	 *	@param string $sValue
	 *
	 *	@access public
	 */
	function font_family( $sValue )
	{
		/* W3C */
		$sPropertyName = 'font-family';
		
		$this->_set_rule( $sPropertyName, $sValue );
	}


	/**
	 *	font-style property
	 *
	 *	@param string $sValue
	 *
	 *	@access public
	 */
	function font_style( $sValue )
	{
		/* W3C */
		$sPropertyName = 'font-style';
		
		$this->_set_rule( $sPropertyName, $sValue );
	}


	/**
	 *	font-variant property
	 *
	 *	@param string $sValue
	 *
	 *	@access public
	 */
	function font_variant( $sValue )
	{
		/* W3C */
		$sPropertyName = 'font-variant';
		
		$this->_set_rule( $sPropertyName, $sValue );
	}


	/**
	 *	font-weight property
	 *
	 *	@param string $sValue
	 *
	 *	@access public
	 */
	function font_weight( $sValue )
	{
		/* W3C */
		$sPropertyName = 'font-weight';
		
		$this->_set_rule( $sPropertyName, $sValue );
	}


	/**
	 *	font-size property
	 *
	 *	@param string $sValue
	 *
	 *	@access public
	 */
	function font_size( $sValue )
	{
		/* W3C */
		$sPropertyName = 'font-size';
		
		$this->_set_rule( $sPropertyName, $sValue );
	}


	/**
	 *	font property
	 *
	 *	@param array $aValues
	 *
	 *	@access public
	 */
	function font( $aValues )
	{
		/* W3C */
		$sPropertyName = 'font';
		
		$sValues = "";
		
		foreach ($aValues as $sValue)
		{
			$sValues .= ' '.$sValue;
		}
		
		$this->_set_rule( $sPropertyName, $sValues );
	}
	
	
	/*
	 *	Text
	 */


	/**
	 *	text-indent property
	 *
	 *	@param string $sValue
	 *
	 *	@access public
	 */
	function text_indent( $sValue )
	{
		/* W3C */
		$sPropertyName = 'text-indent';
		
		$this->_set_rule( $sPropertyName, $sValue );
	}


	/**
	 *	text-align property
	 *
	 *	@param string $sValue
	 *
	 *	@access public
	 */
	function text_align( $sValue )
	{
		/* W3C */
		$sPropertyName = 'text-align';
		
		$this->_set_rule( $sPropertyName, $sValue );
	}


	/**
	 *	text-decoration property
	 *
	 *	@param string $sValue
	 *
	 *	@access public
	 */
	function text_decoration( $sValue )
	{
		/* W3C */
		$sPropertyName = 'text-decoration';
		
		$this->_set_rule( $sPropertyName, $sValue );
	}


	/**
	 *	letter-spacing property
	 *
	 *	@param string $sValue
	 *
	 *	@access public
	 */
	function letter_spacing( $sValue )
	{
		/* W3C */
		$sPropertyName = 'letter-spacing';
		
		$this->_set_rule( $sPropertyName, $sValue );
	}


	/**
	 *	word-spacing property
	 *
	 *	@param string $sValue
	 *
	 *	@access public
	 */
	function word_spacing( $sValue )
	{
		/* W3C */
		$sPropertyName = 'word-spacing';
		
		$this->_set_rule( $sPropertyName, $sValue );
	}


	/**
	 *	text-transform property
	 *
	 *	@param string $sValue
	 *
	 *	@access public
	 */
	function text_transform( $sValue )
	{
		/* W3C */
		$sPropertyName = 'text-transform';
		
		$this->_set_rule( $sPropertyName, $sValue );
	}


	/**
	 *	white-space property
	 *
	 *	@param string $sValue
	 *
	 *	@access public
	 */
	function white_space( $sValue )
	{
		/* W3C */
		$sPropertyName = 'white-space';
		
		$this->_set_rule( $sPropertyName, $sValue );
	}
	
	
	/*
	 *	Tables
	 */


	/**
	 *	caption-side property
	 *
	 *	@param string $sValue
	 *
	 *	@access public
	 */
	function caption_side( $sValue )
	{
		/* W3C */
		$sPropertyName = 'caption-side';
		
		$this->_set_rule( $sPropertyName, $sValue );
	}


	/**
	 *	table-layout property
	 *
	 *	@param string $sValue
	 *
	 *	@access public
	 */
	function table_layout( $sValue )
	{
		/* W3C */
		$sPropertyName = 'table-layout';
		
		$this->_set_rule( $sPropertyName, $sValue );
	}


	/**
	 *	border-collapse property
	 *
	 *	@param string $sValue
	 *
	 *	@access public
	 */
	function border_collapse( $sValue )
	{
		/* W3C */
		$sPropertyName = 'border-collapse';
		
		$this->_set_rule( $sPropertyName, $sValue );
	}


	/**
	 *	border-spacing property
	 *
	 *	@param string $sValue
	 *
	 *	@access public
	 */
	function border_spacing( $sValue )
	{
		/* W3C */
		$sPropertyName = 'border-spacing';
		
		$this->_set_rule( $sPropertyName, $sValue );
	}


	/**
	 *	empty-cells property
	 *
	 *	@param string $sValue
	 *
	 *	@access public
	 */
	function empty_cells( $sValue )
	{
		/* W3C */
		$sPropertyName = 'empty-cells';
		
		$this->_set_rule( $sPropertyName, $sValue );
	}
	
	
	/*
	 *	User Interface
	 */


	/**
	 *	cursor property
	 *
	 *	@param string $sValue
	 *
	 *	@access public
	 */
	function cursor( $sValue )
	{
		/* W3C */
		$sPropertyName = 'cursor';
		
		$this->_set_rule( $sPropertyName, $sValue );
	}


	/**
	 *	outline property
	 *
	 *	@param array $aValues
	 *
	 *	@access public
	 */
	function outline( $aValues )
	{
		/* W3C */
		$sPropertyName = 'outline';
		
		$sValues = "";
		
		foreach ( $aValues as $sValue )
		{
			$sValues .= ' '.$sValue;
		}
		
		$this->_set_rule( $sPropertyName, $sValues );
	}


	/**
	 *	outline-width property
	 *
	 *	@param string $sValue
	 *
	 *	@access public
	 */
	function outline_width( $sValue )
	{
		/* W3C */
		$sPropertyName = 'outline-width';
		
		$this->_set_rule( $sPropertyName, $sValue );
	}


	/**
	 *	outline-style property
	 *
	 *	@param string $sValue
	 *
	 *	@access public
	 */
	function outline_style( $sValue )
	{
		/* W3C */
		$sPropertyName = 'outline-style';
		
		$this->_set_rule( $sPropertyName, $sValue );
	}


	/**
	 *	outline-color property
	 *
	 *	@param string $sValue
	 *
	 *	@access public
	 */
	function outline_color( $sValue )
	{
		/* W3C */
		$sPropertyName = 'outline-color';
		
		$this->_set_rule( $sPropertyName, $sValue );
	}
}


/**
 *	Class containing the CSS properties of version 3
 *
 *	Experimental - Shouldn't be really used
 */
class CSSVersion3 extends CSSVersion21
{
	/**
	 *	@param string $csRendering Constant value defining the type of style rendering in the HTML document
	 *	@param string $sSelector Selector declaration defining the entities to which the styles apply to
	 *	@param array $aoRules Hash array of Rule objects
	 *
	 *	@access public
	 */
	function CSSVersion3( $csRendering, $sSelector = '', $aoRules = array() )
	{
		parent::CSSVersion21( $csRendering, $sSelector, $aoRules );
		
		$this->_sVersion = 3;
	}
	
	
	/*
	 *	********************
	 *	CSS Property Methods
	 *	********************
	 */


	/**
	 *	background-attachment property
	 *
	 *	@param string $sValue Pass the value as recommended by the W3C
	 *
	 *	@access public
	 */
	function background_attachment( $sValue )
	{
		/* W3C */
		$sPropertyName = 'background-attachment';
		
		$this->_set_rule( $sPropertyName, $sValue );
	}


	/**
	 *	background-image property
	 *
	 *	@param string $sValue Parameter can be multiple comma separated CSS URL functions within the string
	 *
	 *	@access public
	 */
	function background_image( $sValue )
	{
		/* W3C */
		$sPropertyName = 'background-image';
		
		$this->_set_rule( $sPropertyName, $sValue );
	}


	/**
	 *	box-sizing property
	 *
	 *	@param string $sValue Parameter value varies depending of the user-agent
	 *
	 *	@access public
	 */
	function box_sizing( $sValue )
	{
		/* W3C */
		$sPropertyName = 'box-sizing';
		
		$this->_set_rule( $sPropertyName, $sValue );

		/* Mozilla */
		$sPropertyName = '-moz-box-sizing';
		
		$this->_set_rule( $sPropertyName, $sValue );

		/* WebKit */
		$sPropertyName = '-webkit-box-sizing';
		
		$this->_set_rule( $sPropertyName, $sValue );
		
		/* IE */
		$sPropertyName = '-ms-box-sizing';
		
		$this->_set_rule( $sPropertyName, $sValue );
	}


	/**
	 *	content property
	 *
	 *	@param string $sValue Parameter can be a string or a URL within a CSS URL function
	 *
	 *	@access public
	 */
	function content( $sValue )
	{
		/* W3C */
		$sPropertyName = 'content';
		
		$this->_set_rule( $sPropertyName, $sValue );
	}


	/**
	 *	opacity property
	 *
	 *	@param float $fValue Pass value as recommended by W3C; automatically adjusted for IE
	 *
	 *	@access public
	 */
	function opacity( $fValue )
	{
		/* W3C */
		$sPropertyName = 'opacity';
		
		$this->_set_rule( $sPropertyName, $fValue );
		
		/* For IE */
		$iValue = $fValue * 100;

		/* IE8 */
		$sPropertyName = '-ms-filter';
		
		$this->_set_rule( $sPropertyName, '"progid:DXImageTransform.Microsoft.Alpha(Opacity='.$iValue.')"' );

		/* IE5-7 */
		$sPropertyName = 'filter';
		
		$this->_set_rule( $sPropertyName, 'alpha(opacity='.$iValue.')' );
	}


	/**
	 *	resize property
	 *
	 *	@param string $sValue Pass value as recommended by W3C
	 *
	 *	@access public
	 */
	function resize( $sValue )
	{
		/* W3C */
		$sPropertyName = 'resize';
		
		$this->_set_rule( $sPropertyName, $sValue );
	}


	/**
	 *	text-overflow property
	 *
	 *	@param string $sValue Value indicating the type of reaction to text overflow
	 *
	 *	@access public
	 */
	function text_overflow( $sValue )
	{
		/* W3C */
		$sPropertyName = 'text-overflow';
		
		$this->_set_rule( $sPropertyName, $sValue );
	}


	/**
	 *	text-shadow property
	 *
	 *	@param string $sColor Color of the shadow
	 *	@param string $sXCoord Absolute length of the x coordinate
	 *	@param string $sYCoord Absolute length of the y coordinate
	 *	@param string $sBlurRadius Absolute length of the blur radius
	 *
	 *	@access public
	 */
	function text_shadow( $sColor, $sXCoord, $sYCoord, $sBlurRadius )
	{
		/* W3C */
		$sPropertyName = 'text-shadow';
		
		$sValue = $sColor.' '.$sXCoord.' '.$sYCoord.' '.$sBlurRadius;
		
		$this->_set_rule( $sPropertyName, $sValue );
	}


	/**
	 *	border-radius property
	 *
	 *	@param string $sValue Absolute length defining the border radius of cornered shapes
	 *
	 *	@access public
	 */
	function border_radius( $sValue )
	{
		/* W3C */
		$sPropertyName = 'border-radius';
		
		$this->_set_rule( $sPropertyName, $sValue );

		/* WebKit */
		$sPropertyName = '-webkit-border-radius';
		
		$this->_set_rule( $sPropertyName, $sValue );

		/* Mozilla */
		$sPropertyName = '-moz-border-radius';
		
		$this->_set_rule( $sPropertyName, $sValue );
	}


	/**
	 *	box-shadow property
	 *
	 *	@param string $sValue
	 *
	 *	@access public
	 */
	function box_shadow( $sValue )
	{
		/* W3C */
		$sPropertyName = 'box-shadow';
		
		$this->_set_rule( $sPropertyName, $sValue );

		/* WebKit */
		$sPropertyName = '-webkit-box-shadow';
		
		$this->_set_rule( $sPropertyName, $sValue );

		/* Mozilla */
		$sPropertyName = '-moz-box-shadow';
		
		$this->_set_rule( $sPropertyName, $sValue );
	}
}


?>
