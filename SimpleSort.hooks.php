<?php
/**
 * Hooks for SimpleSort extension
 *
 * @file
 * @ingroup Extensions
 */

class SimpleSortHooks {

   // Register any render callbacks with the parser
   public static function onParserFirstCallInit( Parser &$parser ) {

	  // Create a function hook associating the "simplesort" magic word
	  $parser->setFunctionHook( 'simplesort', [ __CLASS__, 'renderSort' ] );

	  return true;
   }

   // Render the output of {{#simplesort:}}.
   // If two or more arguments are supplied, the 1st specifies options, whitespace-separated:
   //	desc	     Sort in descending order
   //	alpha	     Alphabetic sorting (regular, not natural)
   //	num		     Numeric sorting
   //	case	     C ase-sensitive sorting
   //	insep="x"    Use x as a list separator in order to identify individual elements in the input.
   //			     Note that whitespace on either side of x is ignored, and discarded.
   //			     The quotes are required.
   //	outsep="x"   Use x as a list separator in the output.
   //			     The quotes are required.
   //	keyed        Sorting is applied to two lists simultaneously, returning only the second list.
   //	stoponblank  A blank element means to truncate the list at that point.  If keyed, this applies to the first list.
   //  Default sorting options are to use php's "natural", case-insensitive sort order,
   //	and a comma-separator for both input and output.
   // The remaining argument is the sortable list.
   public static function renderSort( $parser ) {
	   // defaults
	   $asc = true;	 // Ascending or descending.
	   $nat = true;	 // Natural sorting.
	   $alpha = true; // Alphabetic or numeric, overridden by nat.
	   $cs = false; // Case-sensitive
	   $insep = ",";  // Input separator.
	   $outsep = ",";  // Output separator.
	   $keyed = false;  // Keyed output sorting.
	   $stoponblank = false;  // Truncate list at first blank element.

	   $numargs = func_num_args();
	   $arglist = func_get_args();
	   $input = "";

	   if ( $numargs >= 3 ) {
		   // Options specified, extract them.
		   $options = $arglist[1];

		   // Outsep and insep options are complicated by the potential for using
		   //  whitespace as a separator.  So first look for them.
		   preg_match( '/insep="([^"]*)"/', $options, $msep );
		   if ( $msep ) {
			   $insep = $msep[1];
			   // Remove the option from the string of options.
			   $options = str_replace( $msep[0], "", $options );
		   }

		   preg_match( '/outsep="([^"]*)"/', $options, $osep );
		   if ( $osep ) {
			   $outsep = $osep[1];
			   // Remove the option from the string of options.
			   $options = str_replace( $osep[0], "", $options );
		   } else {
			   $outsep = $insep;
		   }

		   // Only 5 options actually possible, but excessively, parse up to 6 to
		   //  catch and isolate trailing debris.
		   $opts = preg_split( "/[\s]+/", trim( $options ), 6 );

		   // Check for each option.  Ignore blank options if they somehow got in there.
		   for ( $i=0; $i < count( $opts ); $i++ ) {
			   if ( $opts[$i] === "desc" ) {
				   $asc = false;
			   } else if ( $opts[$i] === "alpha" ) {
				   $alpha = true;
				   $nat = false;
			   } else if ( $opts[$i] === "num" ) {
				   $alpha = false;
				   $nat = false;
			   } else if ( $opts[$i] === "case" ) {
				   $cs = true;
			   } else if ( $opts[$i] === "keyed" ) {
				   $keyed = true;
			   } else if ( $opts[$i] === "stoponblank" ) {
				   $stoponblank = true;
			   } else if ( $opts[$i] !== "" ) {
				   return wfMessage( 'simplesort-err', $opts[$i] )->text();
			   }
		   }
		   $input = $arglist[2];
	   } else if ( $numargs == 2 ) {
		   $input = $arglist[1];
	   }

	   if ( $input === "" ) {
		   $output = "";
	   } else {
		   $flags = ( ( $nat ) ? SORT_NATURAL : ( ( $alpha ) ? SORT_STRING : SORT_NUMERIC ) )
			   | ( ( $cs ) ? 0 : SORT_FLAG_CASE );

		   $ilist = SimpleSortHooks::stringToArray( $input, $insep );

		   if ( $stoponblank ) {
			   $max = count( $ilist );
			   for ( $i = 0; $i < $max; ++$i ) {
				   if ( $ilist[$i] === "" ) {
					   array_splice( $ilist, $i );
					   break;
				   }
			   }
		   }
		   
		   if ( $keyed ) {
			   $ilist2 = SimpleSortHooks::stringToArray( $arglist[3], $insep );

			   if ($asc) 
				   asort( $ilist, $flags );
			   else
				   arsort( $ilist, $flags );

			   $olist = array();
			   foreach ( $ilist as $k => $v) {
				   $olist[$k] = $ilist2[$k];
			   }

			   $ilist = $olist;
		   } else {
			   if ($asc) 
				   sort( $ilist, $flags );
			   else
				   rsort( $ilist, $flags );
		   }

		   $output = implode( $outsep, $ilist );
	   }

	   return [ $output, 'noparse' => false ];
   }

   // Split an input into an array based on the given separator string, discarding
   //  surrounding whitespace.
   //  Also handles the special case when the separator is an empty string.
   static function stringToArray( $inarray, $sep ) {
	   if ( $sep === "" )
		   $outarray = str_split( preg_replace( "/[\s]+/", "", $inarray ) );
	   else
		   $outarray = preg_split( "/[\s]*" . preg_quote( $sep ) . "[\s]*/", $inarray );
	   return $outarray;
   }

}
