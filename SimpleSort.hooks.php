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
   // If two arguments are supplied, the 1st specifies options, whitespace-separated:
   //	desc	   Sort in descending order
   //	alpha	   Alphabetic sorting (regular, not natural)
   //	num		   Numeric sorting
   //	case	   Case-sensitive sorting
   //	insep="x"  Use x as a list separator in order to identify individual elements in the input.
   //			   Note that whitespace on either side of x is ignored, and discarded.
   //			   The quotes are required.
   //	outsep="x" Use x as a list separator in the output.
   //			   The quotes are required.
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

		   // Only 3 options actually possible, but excessively, parse up to 4 to
		   //  catch and isolate trailing debris.
		   $opts = preg_split( "/[\s]+/", trim( $options ), 4 );

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
		   if ( $insep === "" )
			   $ilist = str_split( preg_replace( "/[\s]+/", "", $input ) );
		   else
			   $ilist = preg_split( "/[\s]*" . preg_quote( $insep ) . "[\s]*/", $input );

		   $flags = ( ( $nat ) ? SORT_NATURAL : ( ( $alpha ) ? SORT_STRING : SORT_NUMERIC ) )
			   | ( ( $cs ) ? 0 : SORT_FLAG_CASE );

		   if ( $asc )
			   sort( $ilist, $flags );
		   else
			   rsort( $ilist, $flags );

		   $output = implode( $outsep, $ilist );
	   }

	   return [ $output, 'noparse' => false ];
   }
}
